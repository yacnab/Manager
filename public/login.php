<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] ?? '') === 'مدیر' ? 'dashboard_admin.php' : 'dashboard.php';
    header("Location: $redirect");
    exit;
}
$error = "";


function validateLoginInput(array $data): ?string {
    if (empty(trim($data['identifier'] ?? '')) || empty(trim($data['password'] ?? ''))) {
        return "لطفاً همه فیلدها را پر کنید.";
    }
    return null;
}


function attemptLogin(PDO $pdo, string $identifier, string $password): array|null {
    $stmt = $pdo->prepare("
        SELECT users.*, rules.name AS role_name
        FROM users
        JOIN rules ON users.rule_id = rules.id
        WHERE users.phone = :identifier_phone OR users.national_code = :identifier_national
        LIMIT 1
    ");
    $stmt->execute([
        'identifier_phone' => $identifier,
        'identifier_national' => $identifier,
    ]);
    $user = $stmt->fetch();
    if (!$user) return ['error' => "کاربری با این مشخصات یافت نشد."];
    if (!password_verify($password, $user['password_hash'])) return ['error' => "رمز عبور اشتباه است."];
    return $user;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $error = validateLoginInput($_POST) ?? "";

    if (!$error) {
        $loginResult = attemptLogin($pdo, trim($_POST['identifier']), trim($_POST['password']));
        if (isset($loginResult['error'])) {
            $error = $loginResult['error'];
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $loginResult['id'];
            $_SESSION['role'] = $loginResult['role_name'];
            $_SESSION['first_name'] = $loginResult['first_name'];

            $redirect = $loginResult['role_name'] === 'مدیر' ? 'dashboard_admin.php' : 'dashboard.php';
            header("Location: $redirect");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ورود</title>
    <link rel="stylesheet" href="assets/styles.css" />
</head>
<body>

<div class="page-bg">
  <div class="glass-card">
    <div class="left-visual">
      <img src="assets/login-bg.jpg" alt="Login background" />
    </div>
    <div class="right-form">
      <h1>ورود به حساب</h1>
      <?php if ($error): ?>
        <div class="alert error"><?=htmlspecialchars($error)?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="field">
          <span>شماره تلفن یا کد ملی</span>
          <input type="text" name="identifier" placeholder="مثال: 09123456789" value="<?=htmlspecialchars($_POST['identifier'] ?? '')?>" />
        </div>
        <div class="field">
          <span>رمز عبور</span>
          <input type="password" name="password" placeholder="********" />
        </div>
        <div class="actions">
          <button class="btn" type="submit">ورود</button>
        </div>
      </form>
      <p class="register-link">
        اکانت ندارید؟ <a href="register.php">ثبت‌نام کنید</a>
      </p>
    </div>
  </div>
</div>

</body>
</html>
