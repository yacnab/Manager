
<?php
session_start();
if (!empty($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] ?? '') === 'مدیر' ? 'dashboard_admin.php' : 'dashboard.php';
    header("Location: $redirect");
    exit;
}
header("Location: login.php");
exit;
