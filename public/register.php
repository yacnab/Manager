<?php

session_start();
require_once __DIR__ . '/../config/db.php';

if (!empty($_SESSION['user_id'])) {
    $redirect = ($_SESSION['role'] ?? '') === 'مدیر' ? 'dashboard_admin.php' : 'dashboard.php';
    header("Location: $redirect");
    exit;
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;


$roles = $pdo->query("SELECT id, name FROM rules ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);


function validateUserData(PDO $pdo, array $data): array {
    $errors = [];

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $data['csrf_token'] ?? '')) {
        $errors[] = 'توکن CSRF نامعتبر است.';
    }

    $first = trim($data['first_name'] ?? '');
    $last = trim($data['last_name'] ?? '');
    $national = trim($data['national_code'] ?? '');
    $phone = trim($data['phone'] ?? '');
    $rule_id = (int)($data['rule_id'] ?? 0);
    $password = $data['password'] ?? '';
    $password_confirm = $data['password_confirm'] ?? '';

  
    if ($first === '' || $last === '') $errors[] = 'اسم و فامیل باید وارد شوند.';
    if (!preg_match('/^[0-9]{10,20}$/u', $national)) $errors[] = 'کد ملی نامعتبر است.';
    if (!preg_match('/^[0-9\+\- ]{7,20}$/u', $phone)) $errors[] = 'شماره تلفن نامعتبر است.';
    if ($password === '' || strlen($password) < 8) $errors[] = 'رمز عبور باید حداقل 8 کاراکتر باشد.';
    if ($password !== $password_confirm) $errors[] = 'تأیید رمز عبور مطابقت ندارد.';

   
    $stmt = $pdo->prepare("SELECT name FROM rules WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $rule_id]);
    $role_name = $stmt->fetchColumn();

    if (!$role_name) {
        $errors[] = 'نقش انتخابی نامعتبر است.';
    } elseif ($role_name === 'مدیر') {
      
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE rule_id = :rule_id");
        $stmt->execute([':rule_id' => $rule_id]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'ثبت‌نام به عنوان مدیر امکان‌پذیر نیست — یک مدیر در حال حاضر ثبت شده است.';
        }
    }

  
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE national_code = :national");
    $stmt->execute([':national' => $national]);
    if ((int)$stmt->fetchColumn() > 0) {
        $errors[] = 'این کد ملی قبلاً ثبت شده است.';
    }

    return $errors;
}


function insertUser(PDO $pdo, array $data): bool {
    $hash = password_hash($data['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users 
        (first_name, last_name, national_code, phone, rule_id, password_hash)
        VALUES (:first, :last, :national, :phone, :rule_id, :hash)
    ");
    return $stmt->execute([
        ':first' => trim($data['first_name']),
        ':last' => trim($data['last_name']),
        ':national' => trim($data['national_code']),
        ':phone' => trim($data['phone']),
        ':rule_id' => (int)$data['rule_id'],
        ':hash' => $hash,
    ]);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = validateUserData($pdo, $_POST);
    if (empty($errors)) {
        $success = insertUser($pdo, $_POST);
        
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

?>
<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ثبت‌نام</title>
<link rel="stylesheet" href="assets/styles.css">
</head>
<body>
<div class="page-bg">
  <div class="glass-card">
    <div class="left-visual">
      <img src="assets/bg-left.jpg" alt="illustration" />
    </div>
    <div class="right-form">
      <h1>ثبت‌نام</h1>

      <?php if ($success): ?>
        <div class="alert success">ثبت‌نام با موفقیت انجام شد.</div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert error">
          <ul>
            <?php foreach ($errors as $e): ?>
              <li><?=htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form method="post" novalidate autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>">

        <label class="field">
          <span>اسم</span>
          <input name="first_name" type="text" required value="<?=htmlspecialchars($_POST['first_name'] ?? '')?>">
        </label>

        <label class="field">
          <span>فامیل</span>
          <input name="last_name" type="text" required value="<?=htmlspecialchars($_POST['last_name'] ?? '')?>">
        </label>

        <label class="field">
          <span>کد ملی</span>
          <input name="national_code" type="text" required pattern="\d{10,20}" value="<?=htmlspecialchars($_POST['national_code'] ?? '')?>">
        </label>

        <label class="field">
          <span>شماره تلفن</span>
          <input name="phone" type="text" required value="<?=htmlspecialchars($_POST['phone'] ?? '')?>">
        </label>

        <label class="field">
          <span>نقش</span>
          <select name="rule_id" required>
            <option value="">انتخاب کنید</option>
            <?php foreach ($roles as $r): ?>
              <option value="<?=$r['id']?>" <?=isset($_POST['rule_id']) && (int)$_POST['rule_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                <?=htmlspecialchars($r['name'])?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="field">
          <span>رمز عبور</span>
          <input name="password" type="password" required>
        </label>

        <label class="field">
          <span>تأیید رمز</span>
          <input name="password_confirm" type="password" required>
        </label>

        <div class="actions">
          <button type="submit" class="btn">ثبت‌نام</button>
          <p class="register-link">اکانت دارید؟ <a href="login.php">وارد شوید</a></p>
        </div>
      </form>

    </div>
  </div>
</div>
</body>
</html>
