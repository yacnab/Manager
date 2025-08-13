<?php
$menuItems = [
    'dashboard_admin.php' => 'داشبورد',
    'manage_users.php' => 'مدیریت اعضا',
    'manage_projects.php' => 'مدیریت پروژه‌ها',
    'create_project.php' => 'ساخت پروژه',
    'create_board.php' => 'ساخت برد',
    'create_task.php' => 'ساخت تسک',
    'logout.php' => 'خروج از اکانت'
];
?>
<div class="sidebar">
    <?php foreach($menuItems as $href => $title): ?>
        <a href="<?= $href ?>" <?= basename($_SERVER['PHP_SELF']) === $href ? 'class="active"' : '' ?>>
            <?= $title ?>
        </a>
    <?php endforeach; ?>
</div>
