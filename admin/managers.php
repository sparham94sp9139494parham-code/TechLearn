<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';

$error = '';
$success = '';

if (empty($_SESSION['manager_csrf'])) {
    $_SESSION['manager_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['manager_csrf'], $token)) {
        $error = 'درخواست نامعتبر است.';
    } elseif (!preg_match('/^[a-zA-Z0-9_-]{3,50}$/', $username)) {
        $error = 'نام کاربری باید ۳ تا ۵۰ کاراکتر انگلیسی باشد.';
    } elseif (strlen($password) < 10) {
        $error = 'رمز عبور باید حداقل ۱۰ کاراکتر باشد.';
    } elseif ($password !== $confirm) {
        $error = 'تکرار رمز عبور مطابقت ندارد.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, password, role)
                 VALUES (?, ?, 'admin')"
            );

            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $success = 'حساب مدیر جدید با موفقیت ساخته شد.';
            $_SESSION['manager_csrf'] = bin2hex(random_bytes(32));
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $error = 'این نام کاربری قبلاً ثبت شده است.';
            } else {
                error_log($e->getMessage());
                $error = 'خطا در ساخت حساب مدیر.';
            }
        }
    }
}

$managers = $pdo->query(
    "SELECT id, username, created_at
     FROM users
     WHERE role = 'admin'
     ORDER BY id ASC"
)->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>مدیریت مدیران | تک‌لرن</title>
<link rel="stylesheet" href="../assets/css/admin.css">
<style>
.manager-box {
    background: #101a2e;
    border: 1px solid #263550;
    border-radius: 16px;
    padding: 24px;
    margin-bottom: 24px;
}
.manager-box input {
    display: block;
    width: 100%;
    max-width: 420px;
    padding: 13px;
    margin: 12px 0;
    border-radius: 9px;
    border: 1px solid #334155;
    background: #080d1b;
    color: white;
    box-sizing: border-box;
}
.manager-box button {
    padding: 12px 22px;
    border: 0;
    border-radius: 9px;
    color: white;
    background: #2563eb;
    cursor: pointer;
}
.manager-list {
    width: 100%;
    border-collapse: collapse;
}
.manager-list th, .manager-list td {
    padding: 14px;
    text-align: right;
    border-bottom: 1px solid #263550;
}
.error { color: #f87171; }
.success { color: #4ade80; }
</style>
</head>
<body>

<aside class="sidebar">
    <h2>تک‌لرن<span>.</span></h2>
    <p class="side-label">مدیریت سایت</p>
    <a href="index.php">◈ داشبورد</a>
    <a href="write.php">✎ نوشتن آموزش</a>
    <a href="articles.php">▤ مدیریت آموزش‌ها</a>
    <a class="active" href="managers.php">♙ مدیریت مدیران</a>
    <a href="../rom-hub/upload.php">⇧ آپلود رام</a>
    <a href="../index.php">⌂ مشاهده سایت</a>
    <a href="logout.php">↪ خروج</a>
</aside>

<main class="main">
    <header>
        <div>
            <h1>مدیریت مدیران</h1>
            <p>ساخت و مشاهده حساب‌های مدیریتی</p>
        </div>
    </header>

    <section class="manager-box">
        <h2>ساخت حساب مدیر جدید</h2>

        <?php if ($error): ?>
            <p class="error"><?=e($error)?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><?=e($success)?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token"
                   value="<?=e($_SESSION['manager_csrf'])?>">

            <input name="username" placeholder="نام کاربری مدیر جدید"
                   maxlength="50" required>

            <input type="password" name="password"
                   placeholder="رمز عبور (حداقل ۱۰ کاراکتر)" required>

            <input type="password" name="confirm_password"
                   placeholder="تکرار رمز عبور" required>

            <button type="submit">ساخت حساب مدیر</button>
        </form>
    </section>

    <section class="manager-box">
        <h2>مدیران ثبت‌شده</h2>

        <table class="manager-list">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>نام کاربری</th>
                    <th>تاریخ ایجاد</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($managers as $manager): ?>
                    <tr>
                        <td><?=e($manager['id'])?></td>
                        <td><?=e($manager['username'])?></td>
                        <td><?=e($manager['created_at'])?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
