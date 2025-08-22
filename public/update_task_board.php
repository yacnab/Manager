<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth_check.php';
enforceAccess('user_dashboard');

header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'] ?? 0;
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['status' => 'ERROR', 'message' => 'کاربر نامعتبر است.']);
    exit;
}

$taskId     = isset($_POST['task_id'])   ? (int)$_POST['task_id']   : 0;
$newBoardId = isset($_POST['board_id'])  ? (int)$_POST['board_id']  : 0;
$projectId  = isset($_POST['project_id'])? (int)$_POST['project_id']: 0;

if ($taskId <= 0 || $newBoardId <= 0 || $projectId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'ERROR', 'message' => 'داده‌های ارسالی نامعتبر است.']);
    exit;
}

try {
    
    $stmt = $pdo->prepare("SELECT id, board_id, project_id FROM tasks WHERE id = ? AND project_id = ?");
    $stmt->execute([$taskId, $projectId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$task) {
        http_response_code(404);
        echo json_encode(['status' => 'ERROR', 'message' => 'تسک پیدا نشد یا متعلق به این پروژه نیست.']);
        exit;
    }

   
    $stmt = $pdo->prepare("SELECT id, project_id FROM boards WHERE id = ? AND project_id = ?");
    $stmt->execute([$newBoardId, $projectId]);
    $board = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$board) {
        http_response_code(404);
        echo json_encode(['status' => 'ERROR', 'message' => 'برد مقصد یافت نشد یا متعلق به این پروژه نیست.']);
        exit;
    }

    
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM users u
        JOIN projects p ON p.id = ?
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->execute([$projectId, $userId]);
    $isProjectMember = (bool)$stmt->fetchColumn();
    if (!$isProjectMember) {
        http_response_code(403);
        echo json_encode(['status' => 'ERROR', 'message' => 'شما اجازه جابه‌جایی این تسک را ندارید.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT 1 FROM tasks WHERE board_id = ? AND name = (SELECT name FROM tasks WHERE id = ?) LIMIT 1");
    $stmt->execute([$newBoardId, $taskId]);
    if ($stmt->fetchColumn()) {
        http_response_code(409); 
        echo json_encode(['status' => 'ERROR', 'message' => 'در این برد نمی‌توانید دو تسک با نام یکسان داشته باشید.']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE tasks SET board_id = ? WHERE id = ? AND project_id = ?");
    $stmt->execute([$newBoardId, $taskId, $projectId]);

    echo json_encode(['status' => 'OK']);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'ERROR', 'message' => 'خطا در سرور: '.$e->getMessage()]);
    exit;
}
