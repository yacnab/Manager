<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

enforceAccess('admin_dashboard');

$pageTitle = "ساخت برد";
$userId = getCurrentUserId();
$role = getCurrentUserRole();





function getProjects(PDO $pdo) {
    $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY created_at DESC");
    return $stmt->fetchAll();
}


function validateBoardForm($projectId, $boardName, PDO $pdo) {
    if (!$projectId) {
        return 'لطفا یک پروژه انتخاب کنید.';
    }
    if ($boardName === '') {
        return 'نام برد نمی‌تواند خالی باشد.';
    }
    if (isBoardNameDuplicate($projectId, $boardName, $pdo)) {
        return 'نام برد در این پروژه قبلا استفاده شده است.';
    }
    return '';
}


function isBoardNameDuplicate($projectId, $boardName, PDO $pdo) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM boards WHERE project_id = ? AND name = ?");
    $stmt->execute([$projectId, $boardName]);
    return $stmt->fetchColumn() > 0;
}

function createBoard($projectId, $boardName, $boardDescription, PDO $pdo) {
    $stmt = $pdo->prepare("INSERT INTO boards (project_id, name, description) VALUES (?, ?, ?)");
    $stmt->execute([$projectId, $boardName, $boardDescription]);
    return $pdo->lastInsertId();
}





$error = '';
$selected_project_id = $_GET['project_id'] ?? null;
$board_name = '';
$board_description = '';
$projects = getProjects($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_project_id = (int)($_POST['project_id'] ?? 0);
    $board_name = trim($_POST['board_name'] ?? '');
    $board_description = trim($_POST['board_description'] ?? '');

    $error = validateBoardForm($selected_project_id, $board_name, $pdo);

    if ($error === '') {
        $boardId = createBoard($selected_project_id, $board_name, $board_description, $pdo);
        header("Location: create_task.php?project_id=$selected_project_id&board_id=$boardId");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../includes/layout.css" />
    <style>
        form {
            max-width: 500px;
            margin: 30px auto;
            background: #fff9e6;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
            color: #333;
        }
        select, input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-family: 'Vazir', sans-serif;
            font-size: 14px;
            resize: vertical;
        }
        button {
            background-color: #ffb84d;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            color: #333;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #ff9f00;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <h2>ایجاد برد جدید</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <label for="project_id">انتخاب پروژه</label>
            <select id="project_id" name="project_id" required>
                <option value="">انتخاب کنید</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?= $project['id'] ?>" <?= ($selected_project_id == $project['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($project['name'] . ' (ID: ' . $project['id'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="board_name">نام برد</label>
            <input type="text" id="board_name" name="board_name" required value="<?= htmlspecialchars($board_name) ?>" placeholder="مثال: برد توسعه فرانت اند">

            <label for="board_description">توضیحات برد</label>
            <textarea id="board_description" name="board_description" rows="4" placeholder="توضیحات بیشتر درباره برد (اختیاری)"><?= htmlspecialchars($board_description) ?></textarea>

            <button type="submit">ثبت برد</button>
        </form>
    </div>
</div>
</body>
</html>
