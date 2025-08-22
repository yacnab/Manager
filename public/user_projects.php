<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth_check.php';
enforceAccess('user_dashboard');

$pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

$userId = $_SESSION['user_id'] ?? 0;
if ($userId <= 0) {
    echo "<h2 style='text-align:center;margin-top:20%;font-family:Tahoma;'>کاربر نامعتبر است.</h2>";
    exit;
}

$projectId = $_GET['id'] ?? 0;
if ($projectId <= 0) {
    echo "<h2 style='text-align:center;margin-top:20%;font-family:Tahoma;'>پروژه نامعتبر است.</h2>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$projectId]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    echo "<h2 style='text-align:center;margin-top:20%;font-family:Tahoma;'>پروژه پیدا نشد.</h2>";
    exit;
}

$pageTitle = $project['name'];

$stmt = $pdo->prepare("SELECT * FROM boards WHERE project_id = ? ORDER BY id ASC");
$stmt->execute([$projectId]);
$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$tasksByBoard = [];
foreach ($boards as $board) {
    $stmt = $pdo->prepare("
        SELECT t.id, t.name, t.description, t.status,
               GROUP_CONCAT(CONCAT(u.first_name,' ',u.last_name) SEPARATOR ', ') as assigned_users,
               tu.user_id
        FROM tasks t
        LEFT JOIN task_users tu ON t.id = tu.task_id AND tu.user_id = ?
        LEFT JOIN task_users alltu ON t.id = alltu.task_id
        LEFT JOIN users u ON alltu.user_id = u.id
        WHERE t.board_id = ?
        GROUP BY t.id
        ORDER BY t.id ASC
    ");
    $stmt->execute([$userId, $board['id']]);
    $tasksByBoard[$board['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['status'])) {
    $taskId = (int)$_POST['task_id'];
    $newStatus = $_POST['status'];
    $allowedStatuses = ['انجام شده', 'در حال انجام', 'برای انجام'];
    if (!in_array($newStatus, $allowedStatuses)) {
        die('وضعیت نامعتبر است.');
    }
  
    $stmt = $pdo->prepare("SELECT * FROM task_users WHERE task_id = ? AND user_id = ?");
    $stmt->execute([$taskId, $userId]);
    if ($stmt->fetch()) {
        $stmtUpdate = $pdo->prepare("UPDATE tasks SET status = ? WHERE id = ?");
        $stmtUpdate->execute([$newStatus, $taskId]);
        header("Location: user_projects.php?id=".$projectId);
        exit;
    } else {
        die('شما اجازه تغییر این تسک را ندارید.');
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../includes/layout.css">
    <style>
        .boards-container { display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px; }
        .board { background: #fff7c7; padding: 16px; border-radius: 12px; min-width: 280px; max-width: 280px; box-shadow: 0 4px 8px rgba(0,0,0,0.05); }
        .board h3 { margin-top: 0; font-size: 18px; color: #7a5600; }
        .board p.desc { font-size: 13px; color: #5c3d00; margin-bottom:10px; }
        .task { background: #fff; padding: 10px; margin-bottom: 10px; border-radius: 10px; cursor: grab; transition: box-shadow 0.2s; position: relative; }
        .task.done { text-decoration: line-through; opacity: 0.6; }
        .task:hover { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .task select { position: absolute; bottom: 10px; left: 10px; }
        .assigned-users { font-size: 11px; color:#555; margin-top:5px; }
        .task-desc { font-size:12px; color:#444; margin-bottom:6px; }
        .board.drag-over { background: #ffeaa7; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page">
    <?php include __DIR__ . '/../includes/sidebar_user.php'; ?>
    <div class="content">
        <h2 style="margin-top:0;"><?= htmlspecialchars($pageTitle) ?></h2>
        <?php if($project['description']): ?>
            <p style="color:#555;"><?= htmlspecialchars($project['description']) ?></p>
        <?php endif; ?>
        <div class="boards-container" id="boardsRoot" data-project-id="<?= (int)$projectId ?>">
            <?php foreach($boards as $board): ?>
                <div class="board" data-board-id="<?= $board['id'] ?>">
                    <h3><?= htmlspecialchars($board['name']) ?></h3>
                    <?php if($board['description']): ?>
                        <p class="desc"><?= htmlspecialchars($board['description']) ?></p>
                    <?php endif; ?>
                    <?php foreach($tasksByBoard[$board['id']] as $task): ?>
                        <?php
                        $isOwner = ($task['user_id'] == $userId);
                        $doneClass = ($task['status'] === 'انجام شده') ? 'done' : '';
                        ?>
                        <div class="task <?= $doneClass ?>" draggable="true" data-task-id="<?= $task['id'] ?>">
                            <strong><?= htmlspecialchars($task['name']) ?></strong>
                            <?php if($task['description']): ?>
                                <div class="task-desc"><?= htmlspecialchars($task['description']) ?></div>
                            <?php endif; ?>
                            <div class="assigned-users">مسئولین: <?= $task['assigned_users'] ?: '-' ?></div>
                            <?php if($isOwner): ?>
                                <form method="post" style="margin:0;">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="برای انجام" <?= $task['status']==='برای انجام'?'selected':'' ?>>برای انجام</option>
                                        <option value="در حال انجام" <?= $task['status']==='در حال انجام'?'selected':'' ?>>در حال انجام</option>
                                        <option value="انجام شده" <?= $task['status']==='انجام شده'?'selected':'' ?>>انجام شده</option>
                                    </select>
                                </form>
                            <?php else: ?>
                                <div style="margin-top:6px; color:#6a6a6a;">وضعیت: <?= $task['status'] ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>

const tasks = document.querySelectorAll(".task");
const boards = document.querySelectorAll(".board");
const boardsRoot = document.getElementById("boardsRoot");
const projectId = boardsRoot ? boardsRoot.dataset.projectId : "";

tasks.forEach(task => {
    task.addEventListener("dragstart", e => {
        e.dataTransfer.setData("taskId", task.dataset.taskId);
        
        e.dataTransfer.effectAllowed = "move";
    });
});

boards.forEach(board => {
    board.addEventListener("dragover", e => {
        e.preventDefault();
        board.classList.add("drag-over");
        e.dataTransfer.dropEffect = "move";
    });

    board.addEventListener("dragleave", () => {
        board.classList.remove("drag-over");
    });

    board.addEventListener("drop", e => {
        e.preventDefault();
        board.classList.remove("drag-over");

        const taskId = e.dataTransfer.getData("taskId");
        if (!taskId) return;

        const taskElement = document.querySelector(`[data-task-id='${taskId}']`);
        if (!taskElement) return;

        
        const oldParent = taskElement.parentElement;
        
        board.appendChild(taskElement);

       
        const params = new URLSearchParams();
        params.append("task_id", taskId);
        params.append("board_id", board.dataset.boardId);
        params.append("project_id", projectId);

        fetch("update_task_board.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: params.toString()
        })
        .then(res => res.json().catch(() => ({})).then(data => ({ ok: res.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data || data.status !== "OK") {
                
                if (oldParent) oldParent.appendChild(taskElement);
                alert(data && data.message ? data.message : "بروزرسانی جابه‌جایی تسک در سرور ناموفق بود.");
            }
        })
        .catch(() => {
            if (oldParent) oldParent.appendChild(taskElement);
            alert("عدم دسترسی به سرور برای ذخیره جابه‌جایی.");
        });
    });
});
</script>
</body>
</html>
