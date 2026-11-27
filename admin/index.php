<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';

$total = $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn();
$published = $pdo->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
$drafts = $pdo->query("SELECT COUNT(*) FROM articles WHERE status='draft'")->fetchColumn();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>داشبورد مدیریت | تک‌لرن</title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<aside class="sidebar">
    <h2>تک‌لرن<span>.</span></h2>
    <p class="side-label">مدیریت سایت</p>

    <a class="active" href="index.php">◈ داشبورد</a>
    <a href="write.php">✎ نوشتن آموزش</a>
    <a href="articles.php">▤ مدیریت آموزش‌ها</a>
    <a href="managers.php">♙ مدیریت مدیران</a>
    <a href="../rom-hub/upload.php">⇧ آپلود رام</a>
    <a href="recoveries.php">🛠 مدیریت ریکاوری‌ها</a>
    <a href="recovery-projects.php">🖼 مدیریت پروژه‌ها و تصاویر</a>
    <a href="../index.php">⌂ مشاهده سایت</a>
    <a href="logout.php">↪ خروج</a>
</aside>

<main class="main">

    <header>
        <div>
            <h1>داشبورد مدیریت</h1>
            <p>خوش آمدی، <?=e($_SESSION['admin_username'] ?? 'مدیر')?> 👋</p>
        </div>
        <div class="avatar">مدیر سایت</div>
    </header>

    <section class="welcome">
        <span>پنل اختصاصی مدیریت تک‌لرن</span>
        <h2>به تک‌لرن خوش آمدی 👋</h2>
        <p>از اینجا می‌تونی آموزش‌ها و فایل‌های رام سایت رو مدیریت کنی.</p>
        <a href="write.php">+ نوشتن آموزش جدید</a>
    </section>

    <section class="stats">
        <div class="stat">
            <span>کل آموزش‌ها</span>
            <strong><?=e($total)?></strong>
        </div>

        <div class="stat">
            <span>آموزش‌های منتشرشده</span>
            <strong><?=e($published)?></strong>
        </div>

        <div class="stat">
            <span>پیش‌نویس‌ها</span>
            <strong><?=e($drafts)?></strong>
        </div>
    </section>

</main>
</body>
</html>
