<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
enforceAccess('admin_dashboard');

$userId = getCurrentUserId();
$role = getCurrentUserRole();

$pageTitle = "داشبورد ادمین";







function validateNewUser(PDO $pdo, array $data): array {
    $errors = [];

    $first_name = trim($data['first_name'] ?? '');
    $last_name = trim($data['last_name'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $national_code = trim($data['national_code'] ?? '');
    $role_id = (int)($data['role_id'] ?? 0);
    $password = $data['password'] ?? '';

   
    if (!$first_name || !$last_name || !$phone || !$national_code || !$role_id || !$password) {
        $errors[] = 'لطفا همه فیلدها را پر کنید.';
    }

  
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE national_code = ? OR phone = ?");
    $stmt->execute([$national_code, $phone]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'کد ملی یا شماره تلفن وارد شده قبلاً ثبت شده است.';
    }

    return $errors;
}


function insertNewUser(PDO $pdo, array $data): bool {
    $stmt = $pdo->prepare("
        INSERT INTO users 
        (first_name, last_name, phone, national_code, rule_id, password_hash) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
    return $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['phone'],
        $data['national_code'],
        (int)$data['role_id'],
        $password_hash
    ]);
}


$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateNewUser($pdo, $_POST);

    if (empty($errors)) {
        if (insertNewUser($pdo, $_POST)) {
            $success = true;
            header("Location: manage_users.php");
            exit;
        } else {
            $error = 'مشکلی در ثبت کاربر جدید رخ داده است.';
        }
    } else {
        $error = implode('<br>', $errors);
    }
}


$roles_stmt = $pdo->query("SELECT id, name FROM rules ORDER BY id ASC");
$roles = $roles_stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>اضافه کردن کاربر جدید</title>
    <link rel="stylesheet" href="../includes/layout.css" />
    <style>
        form {
            max-width: 450px;
            margin: 20px auto;
            background: #fff9e6;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        input[type="text"], input[type="password"], select {
            width: 100%; padding: 8px 10px; margin-bottom: 15px;
            border: 1px solid #ccc; border-radius: 4px;
            font-size: 14px; font-family: 'Vazir', sans-serif;
        }
        button {
            background-color: #ffb84d; border: none;
            padding: 10px 15px; border-radius: 5px;
            cursor: pointer; font-weight: bold; color: #333; font-size: 16px;
            transition: background-color 0.3s ease;
        }
        button:hover { background-color: #ff9f00; }
        .error { color: red; margin-bottom: 15px; font-weight: bold; max-width: 450px; margin-left:auto; margin-right:auto; }
        .top-link { max-width: 450px; margin: 10px auto; text-align: left; }
        .top-link a { color: #333; text-decoration: none; font-weight: bold; }
        .top-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="page">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <h2>اضافه کردن کاربر جدید</h2>

        <div class="top-link">
            <a href="manage_users.php">&larr; بازگشت به لیست کاربران</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <label for="first_name">نام</label>
            <input type="text" name="first_name" id="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">

            <label for="last_name">نام خانوادگی</label>
            <input type="text" name="last_name" id="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">

            <label for="phone">شماره تلفن</label>
            <input type="text" name="phone" id="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

            <label for="national_code">کد ملی</label>
            <input type="text" name="national_code" id="national_code" required value="<?= htmlspecialchars($_POST['national_code'] ?? '') ?>">

            <label for="role_id">نقش کاربر</label>
            <select name="role_id" id="role_id" required>
                <option value="">انتخاب نقش</option>
                <?php foreach ($roles as $role): ?>
                    <option value="<?= $role['id'] ?>" <?= (isset($_POST['role_id']) && $_POST['role_id'] == $role['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($role['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="password">رمز عبور</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">ثبت کاربر جدید</button>
        </form>
    </div>
</div>

</body>
</html>
