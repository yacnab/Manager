<?php
$menuItems = [
    'dashboard.php' => 'داشبورد',
    'my_projects.php' => 'پروژه‌های من',
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
