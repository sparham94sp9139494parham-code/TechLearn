<?php
session_start();

require_once __DIR__ . '/../includes/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare(
        "SELECT id, username, password
         FROM users
         WHERE username = ? AND role = 'admin'
         LIMIT 1"
    );

    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);

        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];

        header('Location: index.php');
        exit;
    }

    $error = 'نام کاربری یا رمز عبور اشتباه است.';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود مدیر | تک‌لرن</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 20px;
            background: #080d1b;
            color: white;
            font-family: sans-serif;
        }
        .login {
            background: #101a2e;
            border: 1px solid #263550;
            padding: 30px;
            border-radius: 18px;
            width: min(100%, 370px);
            box-shadow: 0 20px 60px #0005;
        }
        input, button {
            width: 100%;
            padding: 14px;
            margin-top: 13px;
            box-sizing: border-box;
            border-radius: 10px;
            border: 1px solid #334155;
        }
        input {
            background: #080d1b;
            color: white;
        }
        button {
            background: linear-gradient(120deg, #0284c7, #6366f1);
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        .error { color: #f87171; }
        a { color: #38bdf8; text-decoration: none; }
        .links { margin-top: 22px; line-height: 2; }
    </style>
</head>
<body>
    <main class="login">
        <h2>ورود به تک‌لرن</h2>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post">
            <input name="username" placeholder="نام کاربری" required>
            <input type="password" name="password" placeholder="رمز عبور" required>
            <button type="submit">ورود به پنل</button>
        </form>

        <div class="links">
            <a href="register.php">ساخت حساب مدیر جدید</a>
            <br>
            <a href="../index.php">بازگشت به سایت</a>
        </div>
    </main>
</body>
</html>
