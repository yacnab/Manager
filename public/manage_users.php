<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
enforceAccess('admin_dashboard');

$pageTitle = "مدیریت اعضا";


$userId = getCurrentUserId();
$role = getCurrentUserRole();





function getAllUsers(PDO $pdo): array {
    $stmt = $pdo->query("SELECT users.id, first_name, last_name, phone, national_code, rules.name AS role_name
                         FROM users
                         JOIN rules ON users.rule_id = rules.id
                         ORDER BY users.id ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function deleteUser(PDO $pdo, int $deleteUserId, int $currentUserId): bool {
    if ($deleteUserId === $currentUserId) {
        return false; 
    }
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    return $stmt->execute([$deleteUserId]);
}


if (isset($_GET['delete_user_id'])) {
    $delete_user_id = (int)$_GET['delete_user_id'];
    if (!deleteUser($pdo, $delete_user_id, $_SESSION['user_id'])) {
        echo "<script>alert('شما نمی‌توانید خودتان را حذف کنید.');</script>";
    } else {
        header("Location: manage_users.php");
        exit;
    }
}


$users = getAllUsers($pdo);

?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <title>مدیریت اعضا</title>
    <link rel="stylesheet" href="../includes/layout.css" />
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: linear-gradient(90deg, #ffd27f, #ffb84d);
        }
        tr:hover {
            background: #fff2cc;
        }
        .delete-btn {
            background-color: #ff5c5c;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .delete-btn:hover {
            background-color: #ff1a1a;
        }
    </style>
    <script>
        function confirmDelete(userId, userName) {
            if(confirm(`آیا از حذف کاربر ${userName} مطمئن هستید؟`)) {
                window.location.href = 'manage_users.php?delete_user_id=' + userId;
            }
        }
    </script>
</head>
<body>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="page">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="content">
        <h2>مدیریت اعضا</h2>
        <div style="margin-bottom: 15px; text-align: left;">
            <a href="add_member.php" style="
                background-color: #ffb84d;
                color: #333;
                padding: 10px 15px;
                border-radius: 5px;
                text-decoration: none;
                font-weight: bold;
                transition: background-color 0.3s ease;
            " onmouseover="this.style.backgroundColor='#ff9f00'" onmouseout="this.style.backgroundColor='#ffb84d'">
                اضافه کردن کاربر جدید +
            </a>
        </div>

        <table>
            <thead>
                <tr>
                    <th>نام</th>
                    <th>نام خانوادگی</th>
                    <th>شماره تلفن</th>
                    <th>کد ملی</th>
                    <th>نقش کاربر</th>
                    <th>حذف</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['first_name']) ?></td>
                            <td><?= htmlspecialchars($user['last_name']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td><?= htmlspecialchars($user['national_code']) ?></td>
                            <td><?= htmlspecialchars($user['role_name']) ?></td>
                            <td>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                    <button class="delete-btn" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>')">حذف کاربر</button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">هیچ کاربری یافت نشد.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
