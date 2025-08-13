<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';

enforceAccess('admin_dashboard'); 

$pageTitle = "مدیریت پروژه‌ها";


function getProjectFilterClauses(string $filter): array {
    $orderClause = 'p.created_at DESC';
    $havingClause = '';

    switch ($filter) {
        case 'oldest': 
            $orderClause = 'p.created_at ASC'; 
            break;
        case 'name': 
            $orderClause = 'p.name ASC'; 
            break;
        case 'done': 
            $havingClause = 'HAVING done_count > 0'; 
            $orderClause = 'done_count DESC';
            break;
        case 'not_done': 
            $havingClause = 'HAVING not_done_count > 0'; 
            $orderClause = 'not_done_count DESC';
            break;
        case 'in_progress': 
            $havingClause = 'HAVING in_progress_count > 0'; 
            $orderClause = 'in_progress_count DESC';
            break;
    }
    return [$orderClause, $havingClause];
}


function fetchProjects(PDO $pdo, string $orderClause, string $havingClause): array {
    $sql = "
        SELECT
            p.id,
            p.name AS project_name,
            COUNT(DISTINCT b.id) AS board_count,
            COUNT(DISTINCT t.id) AS task_count,
            GROUP_CONCAT(DISTINCT CONCAT(u.first_name, ' ', u.last_name) SEPARATOR ', ') AS users_involved,
            SUM(t.status = 'انجام شده') AS done_count,
            SUM(t.status = 'برای انجام') AS not_done_count,
            SUM(t.status = 'در حال انجام') AS in_progress_count
        FROM projects p
        LEFT JOIN boards b ON b.project_id = p.id
        LEFT JOIN tasks t ON t.project_id = p.id
        LEFT JOIN task_users ta ON ta.task_id = t.id
        LEFT JOIN users u ON u.id = ta.user_id
        GROUP BY p.id, p.name
        $havingClause
        ORDER BY $orderClause
    ";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}


function renderProjectsTable(array $projects): void {
    if (empty($projects)) {
        echo '<div class="no-projects">هیچ پروژه‌ای یافت نشد.</div>';
        return;
    }
    ?>
    <table>
        <thead>
            <tr>
                <th>نام پروژه</th>
                <th>تعداد بردها</th>
                <th>تعداد تسک‌ها</th>
                <th>افراد درگیر</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($projects as $project): ?>
            <tr>
                <td>
                    <a href="see_projects.php?id=<?= $project['id'] ?>">
                        <?= htmlspecialchars($project['project_name']) ?>
                    </a>
                </td>
                <td><?= $project['board_count'] ?></td>
                <td><?= $project['task_count'] ?></td>
                <td><?= htmlspecialchars($project['users_involved'] ?: '-') ?></td>
                <td>
                    <form method="post" action="delete_project.php" 
                          onsubmit="return confirm('آیا از حذف پروژه مطمئن هستید؟');">
                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                        <button type="submit" class="btn-delete">حذف</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}


$filter = $_GET['filter'] ?? 'newest';
[$orderClause, $havingClause] = getProjectFilterClauses($filter);
$projects = fetchProjects($pdo, $orderClause, $havingClause);

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../includes/layout.css" />
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
        }
        th {
            background: linear-gradient(90deg, #ffd27f, #ffb84d);
        }
        tr:hover {
            background: #fff2cc;
        }
        .no-projects {
            text-align: center;
            padding: 20px;
            font-weight: bold;
            color: #555;
        }
        .btn-delete {
            background: #d9534f;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-delete:hover {
            filter: brightness(0.9);
        }
        .filter-bar {
            margin-bottom: 12px;
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .filter-bar select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="page">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <h2>لیست پروژه‌ها</h2>

        <div class="filter-bar">
            <label>نمایش بر اساس:</label>
            <form method="get" id="filterForm">
                <select name="filter" onchange="document.getElementById('filterForm').submit()">
                    <option value="newest" <?= $filter=='newest' ? 'selected' : '' ?>>جدیدترین پروژه ثبت شده</option>
                    <option value="oldest" <?= $filter=='oldest' ? 'selected' : '' ?>>قدیمی‌ترین پروژه ثبت شده</option>
                    <option value="name" <?= $filter=='name' ? 'selected' : '' ?>>بر اساس اسم</option>
                    <option value="done" <?= $filter=='done' ? 'selected' : '' ?>>پروژه‌های انجام شده</option>
                    <option value="not_done" <?= $filter=='not_done' ? 'selected' : '' ?>>پروژه‌های انجام نشده</option>
                    <option value="in_progress" <?= $filter=='in_progress' ? 'selected' : '' ?>>پروژه‌های در حال انجام</option>
                </select>
            </form>
        </div>

        <?php renderProjectsTable($projects); ?>
    </div>
</div>
</body>
</html>
