<?php
require __DIR__ . '/../config/db.php';
require __DIR__ . '/../includes/auth_check.php';


enforceAccess('user_dashboard');


function getUserProjects(PDO $pdo, int $userId, string $filter): array {
    $filterSQL = "";
    $orderSQL = " ORDER BY p.created_at DESC ";

    switch ($filter) {
        case 'completed':
            $filterSQL = " AND t.status = 'انجام شده' ";
            break;
        case 'in_progress':
            $filterSQL = " AND t.status = 'در حال انجام' ";
            break;
        case 'pending':
            $filterSQL = " AND t.status = 'برای انجام' ";
            break;
        case 'oldest':
            $orderSQL = " ORDER BY p.created_at ASC ";
            break;
        case 'name':
            $orderSQL = " ORDER BY p.name COLLATE utf8mb4_unicode_ci ASC ";
            break;
        case 'newest':
        default:
            $orderSQL = " ORDER BY p.created_at DESC ";
            break;
    }

    $sql = "
        SELECT DISTINCT p.id, p.name, p.description, p.created_at
        FROM projects p
        JOIN boards b ON b.project_id = p.id
        JOIN tasks t ON t.board_id = b.id
        JOIN task_users tu ON tu.task_id = t.id
        WHERE tu.user_id = ? $filterSQL
        $orderSQL
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function renderProjectsList(array $projects): void {
    if (empty($projects)) {
        echo '<div class="hint">هیچ پروژه‌ای پیدا نشد.</div>';
        return;
    }

    foreach ($projects as $p) {
        echo '<div class="project-item">';
        echo '<a class="project-link" href="user_projects.php?id=' . $p['id'] . '">'
            . htmlspecialchars($p['name']) . '</a>';
        echo '<div class="hint">تاریخ ایجاد: ' . htmlspecialchars($p['created_at']) . '</div>';
        if (!empty($p['description'])) {
            echo '<div style="margin-top:6px; color:#6a6a6a;">'
                . htmlspecialchars($p['description']) . '</div>';
        }
        echo '</div>';
    }
}

$userId = getCurrentUserId();
$pageTitle = "پروژه‌های من";

if ($userId <= 0) {
    echo "<h2 style='text-align:center;margin-top:20%;font-family:Tahoma;'>کاربر نامعتبر است.</h2>";
    exit;
}

$filter = $_GET['filter'] ?? 'newest';
$projects = getUserProjects($pdo, $userId, $filter);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../includes/layout.css">
    <style>
        .wrap { display: flex; flex-direction: column; gap: 16px; }
        .panel { background: #fff; border: 1px solid #f3d28c; border-radius: 12px; padding: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.04); }
        .panel h2 { margin: 0 0 10px 0; color: #7a5600; }
        .project-item { border: 1px solid #ffe1a8; border-radius: 10px; padding: 12px; margin-bottom: 10px; background: #fffdf3; transition: box-shadow 0.2s; }
        .project-item:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .project-link { font-weight: 600; color: #4b3000; text-decoration: none; }
        .project-link:hover { text-decoration: underline; }
        .filter-bar { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
        .filter-bar select { padding: 6px 10px; border-radius: 8px; border: 1px solid #e0c073; background: #fffef7; font-family: Vazir, sans-serif; }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page">
    <?php include __DIR__ . '/../includes/sidebar_user.php'; ?>
    <div class="content">
        <div class="wrap">
            <div class="panel">
                <h2>نمایش بر اساس:</h2>
                <form method="get" class="filter-bar">
                    <select name="filter" onchange="this.form.submit()">
                        <option value="newest" <?= $filter==='newest' ? 'selected' : '' ?>>جدیدترین پروژه‌ها</option>
                        <option value="oldest" <?= $filter==='oldest' ? 'selected' : '' ?>>قدیمی‌ترین پروژه‌ها</option>
                        <option value="completed" <?= $filter==='completed' ? 'selected' : '' ?>>پروژه‌های انجام شده</option>
                        <option value="in_progress" <?= $filter==='in_progress' ? 'selected' : '' ?>>پروژه‌های در حال انجام</option>
                        <option value="pending" <?= $filter==='pending' ? 'selected' : '' ?>>پروژه‌های برای انجام </option>
                        <option value="name" <?= $filter==='name' ? 'selected' : '' ?>>مرتب‌سازی بر اساس اسم</option>
                    </select>
                </form>

                <?php renderProjectsList($projects); ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
