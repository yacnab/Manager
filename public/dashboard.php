<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';


enforceAccess('user_dashboard');

$userId = getCurrentUserId();
$role = getCurrentUserRole();
$userName = $_SESSION['first_name'] ?? 'کاربر';
$pageTitle = "داشبورد کاربر - " . $userName;

$error = '';
$success = '';


function getUserById(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT first_name, last_name, phone, national_code FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

function updateUser(PDO $pdo, int $userId, string $firstName, string $lastName, string $phone, string $password = ''): bool {
    if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, password_hash=? WHERE id=?");
        return $stmt->execute([$firstName, $lastName, $phone, $passwordHash, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, phone=? WHERE id=?");
        return $stmt->execute([$firstName, $lastName, $phone, $userId]);
    }
}

function handlePost(PDO $pdo, int $userId, string &$error, string &$success): ?array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return getUserById($pdo, $userId);
    }

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$firstName || !$lastName || !$phone) {
        $error = "همه فیلدها بجز رمز عبور اجباری است.";
        return getUserById($pdo, $userId);
    }

    $res = updateUser($pdo, $userId, $firstName, $lastName, $phone, $password);
    if ($res) {
        $success = "اطلاعات با موفقیت به‌روزرسانی شد.";
        return getUserById($pdo, $userId);
    } else {
        $error = "خطا در به‌روزرسانی اطلاعات.";
        return getUserById($pdo, $userId);
    }
}


$updatedUser = handlePost($pdo, $userId, $error, $success);
if (!$updatedUser) {
    echo "<h2 style='text-align:center;margin-top:20%;font-family:Tahoma;'>کاربر یافت نشد.</h2>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../includes/layout.css">
    <style>
        body { font-family: 'Vazir', sans-serif !important; }
        form { max-width: 480px; background: #fff9e6; padding: 20px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); margin-top: 20px;}
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #333; }
        input[type="text"], input[type="password"] { width: 100%; padding: 8px 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; font-family: 'Vazir', sans-serif;}
        input[readonly] { background: #eee; cursor: not-allowed; }
        button { background-color: #ffb84d; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; color: #333; font-size: 16px; transition: background-color 0.3s ease; }
        button:hover { background-color: #ff9f00; }
        .message { margin-bottom: 15px; font-weight: bold; text-align: center; }
        .error { color:red; }
        .success { color:green; }
    </style>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        }
    </script>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar_user.php'; ?>

    <div class="page">
        <div class="content">
            <h2> مشخصات شما</h2>

            <?php if ($error): ?>
                <div class="message error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="message success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <label for="first_name">نام</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($updatedUser['first_name']) ?>" required>

                <label for="last_name">نام خانوادگی</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($updatedUser['last_name']) ?>" required>

                <label for="phone">شماره تلفن</label>
                <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($updatedUser['phone']) ?>" required>

                <label for="national_code">کد ملی</label>
                <input type="text" id="national_code" name="national_code" value="<?= htmlspecialchars($updatedUser['national_code']) ?>" readonly>

                <label for="password">رمز عبور (در صورت تمایل به تغییر وارد کنید)</label>
                <input type="password" id="password" name="password" placeholder="رمز عبور جدید">

                <button type="submit">ثبت تغییرات</button>
            </form>
        </div>
    </div>
</body>
</html>
