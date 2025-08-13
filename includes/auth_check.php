<?php
declare(strict_types=1);

session_start();


function isLoggedIn(): bool {
    return isset($_SESSION['user_id'], $_SESSION['role']);
}


function hasAccess(string $page): bool {
    
    $accessMap = [
        'admin_dashboard' => ['مدیر'],
        'user_dashboard' => ['برنامه نویس بک اند', 'برنامه نویس فرانت اند', 'امنیت', 'گرافیست'],
       
    ];

    if (!isLoggedIn()) {
        return false;
    }

    $role = $_SESSION['role'];

    return isset($accessMap[$page]) && in_array($role, $accessMap[$page], true);
}


function enforceAccess(string $page): void {
    if (!hasAccess($page)) {
        http_response_code(403);
        echo "<h2 style='text-align:center;margin-top:20%;font-family:Tahoma;'>شما به این صفحه دسترسی ندارید.</h2>";
        exit;
    }
}


function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}


function getCurrentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}
