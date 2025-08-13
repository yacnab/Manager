<?php
declare(strict_types=1);


$pageTitle = $pageTitle ?? 'صفحه';
?>
<div class="topbar">
    <div class="menu-toggle" onclick="toggleSidebar()" title="باز و بسته کردن منو">
        <img src="assets/menu.svg" alt="منو">
    </div>
    <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <div class="logout-btn" onclick="window.location.href='logout.php'" title="خروج">
        <img src="assets/logout.svg" alt="خروج">
    </div>
</div>

<script>
   
    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) {
            sidebar.classList.toggle('collapsed');
        }
    }
</script>
