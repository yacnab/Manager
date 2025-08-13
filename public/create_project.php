<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
enforceAccess('admin_dashboard');

$pageTitle = "ساخت پروژه";
$userId = getCurrentUserId();
$role = getCurrentUserRole();




function validateProjectForm(string $name): ?string {
    if (empty($name)) {
        return 'نام پروژه نمی‌تواند خالی باشد.';
    }
    return null;
}


function projectExists(PDO $pdo, string $name): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE name = ?");
    $stmt->execute([$name]);
    return $stmt->fetchColumn() > 0;
}


function createProject(PDO $pdo, string $name, string $description): int {
    $stmt = $pdo->prepare("INSERT INTO projects (name, description) VALUES (?, ?)");
    $stmt->execute([$name, $description]);
    return (int)$pdo->lastInsertId();
}




$error = '';
$name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

 
    $error = validateProjectForm($name);

    if (!$error) {
       
        if (projectExists($pdo, $name)) {
            $error = 'نام پروژه قبلاً استفاده شده است. لطفاً نام دیگری انتخاب کنید.';
        } else {
           
            $projectId = createProject($pdo, $name, $description);
            header("Location: create_board.php?project_id=" . $projectId);
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../includes/layout.css" />
    <style>
        form {
            max-width: 500px;
            margin: 30px auto;
            background: #fff9e6;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            font-weight: bold;
            margin-bottom: 6px;
            display: block;
            color: #333;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-family: 'Vazir', sans-serif;
            font-size: 14px;
            resize: vertical;
        }
        button {
            background-color: #ffb84d;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            color: #333;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #ff9f00;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="page">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="content">
            <h2>ایجاد پروژه جدید</h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <label for="name">نام پروژه</label>
                <input type="text" id="name" name="name" required value="<?= htmlspecialchars($name) ?>" placeholder="مثال: پروژه مدیریت وظایف">

                <label for="description">توضیحات پروژه</label>
                <textarea id="description" name="description" rows="4" placeholder="توضیحات بیشتر درباره پروژه (اختیاری)"><?= htmlspecialchars($description) ?></textarea>

                <button type="submit">ثبت پروژه</button>
            </form>
        </div>
    </div>
</body>
</html>
