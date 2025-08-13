<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';


enforceAccess('admin_dashboard');


$userId = getCurrentUserId();
$role = getCurrentUserRole();


function getProjects(PDO $pdo): array {
    return $pdo->query("SELECT id, name FROM projects ORDER BY created_at DESC")->fetchAll();
}

function getBoards(PDO $pdo, ?int $projectId): array {
    if (!$projectId) return [];
    $stmt = $pdo->prepare("SELECT id, name FROM boards WHERE project_id = ? ORDER BY created_at DESC");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

function getUsers(PDO $pdo): array {
    return $pdo->query("SELECT id, first_name, last_name, national_code FROM users ORDER BY first_name ASC")->fetchAll();
}

function isTaskNameDuplicate(PDO $pdo, int $boardId, string $taskName): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE board_id = ? AND name = ?");
    $stmt->execute([$boardId, $taskName]);
    return $stmt->fetchColumn() > 0;
}

function validateTaskForm(int $projectId, int $boardId, string $taskName, array $selectedUsers, string $status): ?string {
    if (!$projectId) return 'لطفا پروژه را انتخاب کنید.';
    if (!$boardId) return 'لطفا برد را انتخاب کنید.';
    if ($taskName === '') return 'نام تسک نمی‌تواند خالی باشد.';
    if (empty($selectedUsers)) return 'حداقل یک کاربر مسئول انتخاب کنید.';
    if (count($selectedUsers) > 5) return 'تعداد کاربران انتخاب شده نمی‌تواند بیشتر از ۵ نفر باشد.';
    if (!in_array($status, ['انجام شده', 'در حال انجام', 'برای انجام'])) return 'وضعیت انتخاب شده نامعتبر است.';
    return null;
}

function createTask(PDO $pdo, int $projectId, int $boardId, string $taskName, string $taskDescription, string $status, array $selectedUsers): int {
    $stmt = $pdo->prepare("INSERT INTO tasks (project_id, board_id, name, description, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$projectId, $boardId, $taskName, $taskDescription, $status]);
    $taskId = (int)$pdo->lastInsertId();

    $stmtUsers = $pdo->prepare("INSERT INTO task_users (task_id, user_id) VALUES (?, ?)");
    foreach ($selectedUsers as $userId) {
        $stmtUsers->execute([$taskId, (int)$userId]);
    }
    return $taskId;
}



enforceAccess('admin_dashboard');


$pageTitle = "ساخت تسک";
$userId = getCurrentUserId();
$role = getCurrentUserRole();

$error = '';
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
$board_id = isset($_GET['board_id']) ? (int)$_GET['board_id'] : null;
$task_name = '';
$task_description = '';
$selected_users = [];
$status = 'برای انجام';

$projects = getProjects($pdo);
$users = getUsers($pdo);
$boards = getBoards($pdo, $project_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project_id = (int)($_POST['project_id'] ?? 0);
    $board_id = (int)($_POST['board_id'] ?? 0);
    $task_name = trim($_POST['task_name'] ?? '');
    $task_description = trim($_POST['task_description'] ?? '');
    $selected_users = $_POST['assigned_users'] ?? [];
    $status = $_POST['status'] ?? 'برای انجام';

   
    $error = validateTaskForm($project_id, $board_id, $task_name, $selected_users, $status);

   
    if (!$error && isTaskNameDuplicate($pdo, $board_id, $task_name)) {
        $error = "این نام تسک قبلاً در این برد وجود دارد. لطفاً نام دیگری انتخاب کنید.";
    }

    if (!$error) {
        createTask($pdo, $project_id, $board_id, $task_name, $task_description, $status, $selected_users);
        header("Location: manage_projects.php");
        exit;
    }

 
    $boards = getBoards($pdo, $project_id);
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../includes/layout.css" />
    <style>
        form { max-width: 600px; margin:30px auto; background:#fff9e6; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
        label { font-weight:bold; margin-bottom:6px; display:block; color:#333;}
        input[type=text], textarea, select { width:100%; padding:10px; margin-bottom:15px; border-radius:5px; border:1px solid #ccc; font-family:'Vazir',sans-serif; font-size:14px; resize:vertical;}
        button { background-color:#ffb84d; border:none; padding:12px 20px; border-radius:5px; cursor:pointer; font-weight:bold; color:#333; font-size:16px; transition:0.3s;}
        button:hover { background-color:#ff9f00;}
        .error { color:red; font-weight:bold; margin-bottom:15px; text-align:center;}
        .checkbox-container { border:1px solid #ccc; padding:10px; border-radius:5px; max-height:150px; overflow-y:auto;}
        .checkbox-container div { margin-bottom:5px; }
    </style>
    <script>
        function onProjectChange() {
            const projectSelect = document.getElementById('project_id');
            const selectedProjectId = projectSelect.value;
            if (selectedProjectId) {
                window.location.href = 'create_task.php?project_id=' + selectedProjectId;
            }
        }

        function limitSelection(el) {
            const checkboxes = document.querySelectorAll('input[name="assigned_users[]"]');
            const checked = Array.from(checkboxes).filter(c => c.checked);
            if (checked.length > 5) {
                alert('حداکثر ۵ نفر می‌توانید انتخاب کنید.');
                el.checked = false;
            }
        }
    </script>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>

<div class="content">
    <h2>ایجاد تسک جدید</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label for="project_id">انتخاب پروژه</label>
        <select id="project_id" name="project_id" required onchange="onProjectChange()">
            <option value="">انتخاب کنید</option>
            <?php foreach ($projects as $project): ?>
                <option value="<?= $project['id'] ?>" <?= ($project_id == $project['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($project['name'] . ' (ID: ' . $project['id'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="board_id">انتخاب برد</label>
        <select id="board_id" name="board_id" required>
            <option value="">ابتدا پروژه را انتخاب کنید</option>
            <?php foreach ($boards as $board): ?>
                <option value="<?= $board['id'] ?>" <?= ($board_id == $board['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($board['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="task_name">نام تسک</label>
        <input type="text" id="task_name" name="task_name" required value="<?= htmlspecialchars($task_name) ?>">

        <label for="task_description">توضیحات تسک</label>
        <textarea id="task_description" name="task_description" rows="4"><?= htmlspecialchars($task_description) ?></textarea>

        <label>انتخاب مسئول(ها) (حداکثر ۵ نفر)</label>
        <div class="checkbox-container">
            <?php foreach ($users as $user):
                $checked = in_array($user['id'], $selected_users) ? 'checked' : '';
            ?>
                <div>
                    <input type="checkbox" name="assigned_users[]" value="<?= $user['id'] ?>" <?= $checked ?> onclick="limitSelection(this)">
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['national_code'] . ')') ?>
                </div>
            <?php endforeach; ?>
        </div>

        <label for="status">وضعیت انجام تسک</label>
        <select id="status" name="status" required>
            <option value="برای انجام" <?= $status == 'برای انجام' ? 'selected' : '' ?>>برای انجام</option>
            <option value="در حال انجام" <?= $status == 'در حال انجام' ? 'selected' : '' ?>>در حال انجام</option>
            <option value="انجام شده" <?= $status == 'انجام شده' ? 'selected' : '' ?>>انجام شده</option>
        </select>

        <button type="submit">ثبت تسک</button>
    </form>
</div>
</div>
</body>
</html>
