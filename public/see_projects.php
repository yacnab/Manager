<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth_check.php';
enforceAccess('admin_dashboard');

$pageTitle = "جزئیات و ویرایش پروژه ";
$userId = getCurrentUserId();
$role = getCurrentUserRole();




$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    echo "<h2 style='text-align:center;margin-top:20%;font-family:Tahoma;'>شناسه پروژه نامعتبر است.</h2>";
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        
        $projName = trim($_POST['project_name'] ?? '');
        $projDesc = trim($_POST['project_desc'] ?? '');

        if ($projName === '') throw new Exception('نام پروژه نمی‌تواند خالی باشد.');

        $check = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE name = ? AND id <> ?");
        $check->execute([$projName, $project_id]);
        if ($check->fetchColumn() > 0) throw new Exception('نام پروژه تکراری است.');

        $stmt = $pdo->prepare("UPDATE projects SET name = ?, description = ? WHERE id = ?");
        $stmt->execute([$projName, $projDesc, $project_id]);

        
        if (!empty($_POST['boards']) && is_array($_POST['boards'])) {
            foreach ($_POST['boards'] as $board_id => $bData) {
                $board_id = (int)$board_id;

                if (!empty($bData['delete']) && (int)$bData['delete'] === 1) {
                    $pdo->prepare("DELETE FROM boards WHERE id = ? AND project_id = ?")->execute([$board_id, $project_id]);
                    continue;
                }

                $bName = trim($bData['name'] ?? '');
                $bDesc = trim($bData['desc'] ?? '');
                if ($bName === '') throw new Exception('نام برد نمی‌تواند خالی باشد.');

                $chkB = $pdo->prepare("SELECT COUNT(*) FROM boards WHERE project_id = ? AND name = ? AND id <> ?");
                $chkB->execute([$project_id, $bName, $board_id]);
                if ($chkB->fetchColumn() > 0) throw new Exception('در این پروژه برد دیگری با همین نام وجود دارد.');

                $pdo->prepare("UPDATE boards SET name = ?, description = ? WHERE id = ? AND project_id = ?")
                    ->execute([$bName, $bDesc, $board_id, $project_id]);

              
                if (!empty($_POST['tasks'][$board_id]) && is_array($_POST['tasks'][$board_id])) {
                    foreach ($_POST['tasks'][$board_id] as $task_id => $tData) {
                        $task_id = (int)$task_id;

                        if (!empty($tData['delete']) && (int)$tData['delete'] === 1) {
                            $pdo->prepare("DELETE FROM tasks WHERE id = ? AND board_id = ? AND project_id = ?")
                                ->execute([$task_id, $board_id, $project_id]);
                            continue;
                        }

                        $tName = trim($tData['name'] ?? '');
                        $tDesc = trim($tData['desc'] ?? '');
                        $tStatus = $tData['status'] ?? 'برای انجام';

                        if ($tName === '') throw new Exception('نام تسک نمی‌تواند خالی باشد.');

                        $chkT = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE board_id = ? AND name = ? AND id <> ?");
                        $chkT->execute([$board_id, $tName, $task_id]);
                        if ($chkT->fetchColumn() > 0) throw new Exception('در این برد تسک دیگری با همین نام وجود دارد.');

                        $pdo->prepare("UPDATE tasks SET name = ?, description = ?, status = ? WHERE id = ? AND board_id = ? AND project_id = ?")
                            ->execute([$tName, $tDesc, $tStatus, $task_id, $board_id, $project_id]);

                     
                        $pdo->prepare("DELETE FROM task_users WHERE task_id = ?")->execute([$task_id]);
                        if (!empty($tData['assignees']) && is_array($tData['assignees'])) {
                            $ins = $pdo->prepare("INSERT INTO task_users (task_id, user_id) VALUES (?, ?)");
                            foreach ($tData['assignees'] as $uid) {
                                $uid = (int)$uid;
                                if ($uid > 0) $ins->execute([$task_id, $uid]);
                            }
                        }
                    }
                }
            }
        }

        $pdo->commit();
        header("Location: see_projects.php?id={$project_id}&updated=1");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $errorMsg = $e->getMessage();
    }
}


$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    echo "<h2 style='text-align:center;margin-top:20%;font-family:Tahoma;'>پروژه یافت نشد.</h2>";
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM boards WHERE project_id = ? ORDER BY created_at ASC");
$stmt->execute([$project_id]);
$boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

$allUsers = $pdo->query("SELECT id, first_name, last_name, national_code FROM users ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);

$tasksByBoard = [];
$assigneesByTask = [];
if ($boards) {
    $boardIds = array_column($boards, 'id');
    $in = implode(',', array_fill(0, count($boardIds), '?'));
    $q  = $pdo->prepare("SELECT * FROM tasks WHERE board_id IN ($in) AND project_id = ? ORDER BY created_at ASC");
    $q->execute(array_merge($boardIds, [$project_id]));
    $allTasks = $q->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allTasks as $t) $tasksByBoard[$t['board_id']][] = $t;

    if (!empty($allTasks)) {
        $taskIds = array_column($allTasks, 'id');
        $inT = implode(',', array_fill(0, count($taskIds), '?'));
        $qa = $pdo->prepare(
            "SELECT tu.task_id, u.id AS user_id, CONCAT(u.first_name, ' ', u.last_name) AS fullname
             FROM task_users tu
             JOIN users u ON u.id = tu.user_id
             WHERE tu.task_id IN ($inT)"
        );
        $qa->execute($taskIds);
        $rows = $qa->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) $assigneesByTask[$r['task_id']][] = $r;
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
.wrap{display:flex;flex-direction:column;gap:16px;}
.panel{background:#fff;border:1px solid #f3d28c;border-radius:12px;padding:16px;box-shadow:0 4px 10px rgba(0,0,0,0.04);}
.panel h2,h3,h4{margin:0 0 10px 0;color:#7a5600;}
.field{margin-bottom:12px;}
.field label{display:block;font-size:14px;color:#6a6a6a;margin-bottom:6px;}
.field input[type="text"],.field textarea{width:100%;border:1px solid #e0c073;border-radius:10px;padding:10px 12px;font-family:Vazir,sans-serif;background:#fffef7;}
.board-box{border:1px dashed #ffcc7a;border-radius:12px;padding:12px;margin-bottom:14px;background:#fffdf3;position:relative;}
.task-card{border:1px solid #ffe1a8;border-radius:10px;padding:12px;margin:10px 0;background:#fff;position:relative;}
.delete-icon{position:absolute;top:8px;left:8px;cursor:pointer;color:#d9534f;font-size:20px;font-weight:bold;user-select:none;}
.actions-bar{position:sticky;bottom:20px;background:linear-gradient(0deg,#fff9e6 0%,rgba(255,249,230,0.8) 60%,rgba(255,249,230,0) 100%);padding:12px;margin-top:8px;}
.btn-primary{background:#ffb84d;border:1px solid #f1aa3a;color:#4b3000;padding:10px 16px;border-radius:12px;cursor:pointer;font-weight:600;}
.btn-primary:hover{filter:brightness(0.97);}
.hint{font-size:12px;color:#8a6d3b;}
.chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px;}
.chip{background:#fff2cc;border:1px solid #ffd27f;border-radius:999px;padding:2px 8px;font-size:12px;color:#6a4c00;}
</style>
<script>
function markDelete(selectorName){
    const el=document.querySelector('[name="'+selectorName+'"]');
    if(el){el.value=1;const box=el.closest('.board-box')||el.closest('.task-card');if(box)box.style.opacity=0.45;}
}
function limitCheckbox(groupName,max=5){
    const checkboxes=document.querySelectorAll('input[name^="'+groupName+'"]');
    checkboxes.forEach(cb=>cb.addEventListener('change',()=>{
        const checked=document.querySelectorAll('input[name^="'+groupName+'"]:checked');
        if(checked.length>max) cb.checked=false;
    }));
}
document.addEventListener('DOMContentLoaded',()=>{limitCheckbox('tasks');});
</script>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page">
<?php include __DIR__ . '/../includes/sidebar.php'; ?>
<div class="content">
<div class="wrap">
<?php if(!empty($_GET['updated'])): ?>
<div class="panel" style="border-color:#b7df8a;background:#f6ffec;">تغییرات با موفقیت ذخیره شد.</div>
<?php endif; ?>
<?php if(!empty($errorMsg)): ?>
<div class="panel" style="border-color:#ff9e9e;background:#fff2f2;color:#8b0000;">خطا: <?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<form method="post">
<div class="panel">
<h2>پروژه</h2>
<div class="field">
<label>نام پروژه (تکراری نباشد):</label>
<input type="text" name="project_name" value="<?= htmlspecialchars($project['name']) ?>" required>
</div>
<div class="field">
<label>توضیحات پروژه:</label>
<textarea name="project_desc" rows="4"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
</div>
<div class="hint">تاریخ ایجاد: <?= htmlspecialchars($project['created_at']) ?> (غیرقابل تغییر)</div>
</div>

<div class="panel">
<h3>بردها</h3>
<?php if(empty($boards)): ?>
<div class="hint">هنوز بردی برای این پروژه ساخته نشده است.</div>
<?php else: foreach($boards as $b): ?>
<div class="board-box">
<span class="delete-icon" title="حذف برد" onclick="markDelete('boards[<?= $b['id'] ?>][delete]')">×</span>
<input type="hidden" name="boards[<?= $b['id'] ?>][delete]" value="0">
<div class="field"><label>نام برد:</label>
<input type="text" name="boards[<?= $b['id'] ?>][name]" value="<?= htmlspecialchars($b['name']) ?>" required>
</div>
<div class="field"><label>توضیحات برد:</label>
<textarea name="boards[<?= $b['id'] ?>][desc]" rows="3"><?= htmlspecialchars($b['description'] ?? '') ?></textarea>
</div>

<h4 style="margin-top:14px;">تسک‌های این برد</h4>
<?php
$boardTasks=$tasksByBoard[$b['id']]??[];
if(empty($boardTasks)): ?>
<div class="hint">تسکی برای این برد ثبت نشده است.</div>
<?php else: foreach($boardTasks as $t): ?>
<div class="task-card">
<span class="delete-icon" title="حذف تسک" onclick="markDelete('tasks[<?= $b['id'] ?>][<?= $t['id'] ?>][delete]')">×</span>
<input type="hidden" name="tasks[<?= $b['id'] ?>][<?= $t['id'] ?>][delete]" value="0">
<div class="field"><label>نام تسک:</label>
<input type="text" name="tasks[<?= $b['id'] ?>][<?= $t['id'] ?>][name]" value="<?= htmlspecialchars($t['name']) ?>" required>
</div>
<div class="field"><label>توضیحات تسک:</label>
<textarea name="tasks[<?= $b['id'] ?>][<?= $t['id'] ?>][desc]" rows="2"><?= htmlspecialchars($t['description'] ?? '') ?></textarea>
</div>
<div class="field"><label>وضعیت:</label>
<select name="tasks[<?= $b['id'] ?>][<?= $t['id'] ?>][status]" required>
<?php foreach(['برای انجام','در حال انجام','انجام شده'] as $st): ?>
<option value="<?= $st ?>" <?= ($t['status']===$st?'selected':'') ?>><?= $st ?></option>
<?php endforeach; ?>
</select>
</div>


<div class="field"><label>مسئولین تسک (حداکثر 5 نفر):</label>
<?php
$selected=array_column($assigneesByTask[$t['id']]??[],'user_id');
foreach($allUsers as $u):
$label=trim(($u['first_name']??'').' '.($u['last_name']??'')); 
$label=$label!==''?$label:'کاربر #'.$u['id']; 
$label.=' - '.($u['national_code']??''); 
?>
<label style="display:block;margin-bottom:3px;">
<input type="checkbox" name="tasks[<?= $b['id'] ?>][<?= $t['id'] ?>][assignees][]" value="<?= (int)$u['id'] ?>" <?= in_array($u['id'],$selected??[],true)?'checked':'' ?>>
<?= htmlspecialchars($label) ?>
</label>
<?php endforeach; ?>
<?php if(!empty($selected)): ?>
<div class="chips">
<?php foreach($assigneesByTask[$t['id']] as $a): ?>
<span class="chip"><?= htmlspecialchars($a['fullname']) ?></span>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

</div>
<?php endforeach; endif; ?>
</div>
<?php endforeach; endif; ?>
</div>

<div class="actions-bar">
<button type="submit" class="btn-primary">ثبت تغییرات</button>
</div>
</form>
</div>
</div>
</div>
</body>
</html>
