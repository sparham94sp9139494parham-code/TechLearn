<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    http_response_code(404);
    exit('رام موردنظر پیدا نشد.');
}

$stmt = $pdo->prepare("SELECT * FROM roms WHERE slug = :slug LIMIT 1");
$stmt->execute(['slug' => $slug]);
$rom = $stmt->fetch();

if (!$rom) {
    http_response_code(404);
    exit('رام موردنظر پیدا نشد.');
}

$stmt = $pdo->prepare("
    SELECT * FROM rom_sources
    WHERE rom_id = :rom_id OR rom_id IS NULL
    ORDER BY id DESC
");
$stmt->execute(['rom_id' => $rom['id']]);
$sources = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=e($rom['name'])?> | ROM Hub</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.rom-page{width:92%;max-width:1050px;margin:auto}
.rom-nav{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap;padding:18px 0}
.rom-nav a{color:#aabaff;text-decoration:none}
.rom-hero{
    margin:35px 0;padding:38px;
    border:1px solid var(--border);border-radius:22px;
    background:linear-gradient(135deg,#172b55,#29204d,#101a30);
}
.rom-hero h1{font-size:clamp(27px,5vw,38px);margin:0 0 12px}
.rom-hero p{color:var(--muted);white-space:normal}
.rom-btn{
    display:inline-block;margin-top:15px;padding:10px 18px;
    border-radius:10px;background:linear-gradient(135deg,#5878f5,#8059e8);
    color:white!important;text-decoration:none
}
.rom-section{margin:38px 0}
.rom-section h2{font-size:22px;margin-bottom:18px}
.rom-info{
    display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px
}
.rom-item,.rom-source{
    padding:20px;background:var(--surface);
    border:1px solid var(--border);border-radius:15px
}
.rom-item small{display:block;color:var(--muted);margin-bottom:8px}
.rom-item div{overflow-wrap:anywhere}
.rom-source{margin-bottom:13px}
.rom-source h3{margin-top:0}
.rom-source p{color:var(--muted);font-size:14px}
.rom-source a{color:#aabaff}
.rom-back{display:inline-block;margin:20px 0;color:#aabaff;text-decoration:none}
.rom-footer{text-align:center;padding:35px 0;color:var(--muted)}
@media(max-width:600px){.rom-hero{padding:25px}.rom-nav{align-items:flex-start}}
</style>
</head>
<body>

<header>
<div class="rom-page">
<nav class="rom-nav">
    <strong style="font-size:21px;color:#aabaff">⚡ ROM HUB</strong>
    <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px">
        <a href="../index.php">خانه تک‌لرن</a>
        <a href="index.php">فهرست رام‌ها</a>
        <a href="downloads.php">آرشیو دانلود</a>
        <?php if(isset($_SESSION['admin_id'])): ?>
            <a href="../admin/index.php">پنل مدیریت</a>
        <?php endif; ?>
    </div>
</nav>
</div>
</header>

<main class="rom-page">

<section class="rom-hero">
    <h1><?=e($rom['name'])?></h1>
    <p><?=nl2br(e($rom['description']))?></p>

    <?php if (!empty($rom['official_url'])): ?>
        <a class="rom-btn" href="<?=e($rom['official_url'])?>"
           target="_blank" rel="noopener noreferrer">
            وب‌سایت رسمی رام ↗
        </a>
    <?php endif; ?>
</section>

<section class="rom-section">
    <h2>📋 اطلاعات رام</h2>

    <div class="rom-info">
        <div class="rom-item">
            <small>نسخه اندروید</small>
            <div><?=e($rom['android_version'] ?: 'نامشخص')?></div>
        </div>

        <div class="rom-item">
            <small>دستگاه‌های پشتیبانی‌شده</small>
            <div><?=nl2br(e($rom['supported_devices'] ?: 'اطلاعاتی ثبت نشده'))?></div>
        </div>
    </div>
</section>

<section class="rom-section">
    <h2>🌐 منابع و لینک‌های مرتبط</h2>

    <?php if ($sources): ?>
        <?php foreach ($sources as $source): ?>
            <article class="rom-source">
                <h3><?=e($source['title'])?></h3>
                <p><?=e($source['description'])?></p>
                <a href="<?=e($source['url'])?>" target="_blank" rel="noopener noreferrer">
                    مشاهده منبع ↗
                </a>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <p>هنوز منبعی برای این رام ثبت نشده است.</p>
    <?php endif; ?>
</section>

<a class="rom-back" href="index.php">← بازگشت به فهرست رام‌ها</a>

</main>

<footer class="rom-footer">ROM Hub | بخشی از تک‌لرن</footer>
</body>
</html>
