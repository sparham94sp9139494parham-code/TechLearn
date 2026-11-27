<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

$roms = $pdo->query("SELECT * FROM roms ORDER BY id DESC")->fetchAll();
$sources = $pdo->query("SELECT * FROM rom_sources ORDER BY id DESC")->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ROM Hub | تک‌لرن</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.rom-page{max-width:1150px;width:92%;margin:auto}
.rom-hero{
    margin:35px 0;padding:55px 25px;text-align:center;
    border:1px solid var(--border);
    border-radius:24px;
    background:linear-gradient(135deg,#172b55,#29204d,#101a30);
}
.rom-hero h1{font-size:clamp(27px,5vw,40px);margin:0 0 12px}
.rom-hero p{color:var(--muted);margin:0}
.rom-section{margin:45px 0}
.rom-section h2{font-size:23px;margin-bottom:22px}
.rom-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
    gap:20px;
}
.rom-card{
    overflow:hidden;border-radius:18px;
    background:var(--surface);
    border:1px solid var(--border);
    transition:.25s;
}
.rom-card:hover{transform:translateY(-5px);border-color:var(--blue)}
.rom-cover{
    height:175px;display:flex;align-items:center;justify-content:center;
    padding:20px;
    background:linear-gradient(135deg,#263d78,#563a85);
}
.rom-cover img{max-width:85%;max-height:130px;object-fit:contain}
.rom-fallback{font-size:22px;font-weight:bold}
.rom-content{padding:20px}
.rom-content h3{margin:0 0 10px;color:#aebeff}
.rom-content p{font-size:13px;color:var(--muted);min-height:65px}
.rom-version{font-size:12px;color:#aabaff}
.rom-btn{
    display:inline-block;margin-top:15px;padding:9px 15px;
    border-radius:9px;background:linear-gradient(135deg,#5878f5,#8059e8);
    color:white!important;font-size:13px;
}
.rom-archive{
    padding:30px;border:1px solid var(--border);
    border-radius:20px;
    background:linear-gradient(120deg,#182c4d,#252044);
}
.rom-archive h2{margin-top:0}
.rom-archive p{color:var(--muted)}
.rom-source{
    padding:20px;margin-bottom:13px;
    border:1px solid var(--border);
    border-radius:15px;background:var(--surface);
}
.rom-source h3{margin-top:0}
.rom-source p{font-size:13px;color:var(--muted)}
.rom-source a{color:#aabaff}
.rom-empty{
    padding:25px;text-align:center;
    border:1px dashed var(--border);
    border-radius:15px;color:var(--muted);
}
.rom-footer{text-align:center;padding:35px 0;color:var(--muted)}
.rom-nav{
    display:flex;align-items:center;justify-content:space-between;
    flex-wrap:wrap;gap:15px;padding:18px 0;
}
.rom-nav-links{display:flex;flex-wrap:wrap;gap:18px;font-size:14px}
.rom-nav-links a:hover{color:#aabaff}
@media(max-width:600px){
    .rom-hero{padding:38px 18px}
    .rom-nav{align-items:flex-start}
    .rom-nav-links{gap:12px;font-size:12px}
}
</style>
</head>
<body>

<header>
<div class="rom-page">
<nav class="rom-nav">
    <strong style="font-size:22px;color:#aabaff">⚡ ROM HUB</strong>
    <div class="rom-nav-links">
        <a href="../index.php">خانه تک‌لرن</a>
        <a href="index.php">رام‌ها</a>
        <a href="#sources">منابع</a>
        <a href="downloads.php">آرشیو دانلود</a>
        <?php if(isset($_SESSION['admin_id'])): ?>
            <a href="../admin/index.php">پنل مدیریت</a>
        <?php else: ?>
            <a href="../admin/login.php">ورود مدیر</a>
        <?php endif; ?>
    </div>
</nav>
</div>
</header>

<main class="rom-page">

<section class="rom-hero">
    <h1>دنیای کاستوم رام‌ها</h1>
    <p>کشف رام‌ها، بررسی مشخصات و دسترسی به منابع رسمی اندروید</p>
</section>

<section class="rom-section">
    <h2>🚀 کشف کاستوم رام‌ها</h2>

    <?php if (!$roms): ?>
        <div class="rom-empty">هنوز رامی ثبت نشده است.</div>
    <?php else: ?>
        <div class="rom-grid">
        <?php foreach($roms as $rom): ?>
            <article class="rom-card">
                <div class="rom-cover">
                    <?php if(!empty($rom['logo_url'])): ?>
                        <img src="<?=e($rom['logo_url'])?>"
                             alt="لوگوی <?=e($rom['name'])?>" loading="lazy">
                    <?php else: ?>
                        <strong class="rom-fallback"><?=e($rom['name'])?></strong>
                    <?php endif; ?>
                </div>

                <div class="rom-content">
                    <h3><?=e($rom['name'])?></h3>
                    <p><?=e($rom['description'])?></p>
                    <div class="rom-version">
                        اندروید: <?=e($rom['android_version'] ?: 'نامشخص')?>
                    </div>
                    <a class="rom-btn" href="rom.php?slug=<?=urlencode($rom['slug'])?>">
                        مشاهده مشخصات رام
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="rom-section rom-archive">
    <h2>📦 آرشیو اختصاصی رام‌های تک‌لرن</h2>
    <p>رام‌های منتشرشده توسط مدیران تک‌لرن را همراه با مشخصات و نسخه اندروید مشاهده کن.</p>
    <a class="rom-btn" href="downloads.php">ورود به آرشیو دانلود</a>
</section>

<section class="rom-section" id="sources">
    <h2>🌐 منابع و سایت‌های مفید</h2>

    <?php if (!$sources): ?>
        <div class="rom-empty">هنوز منبعی ثبت نشده است.</div>
    <?php else: ?>
        <?php foreach($sources as $source): ?>
            <article class="rom-source">
                <h3><?=e($source['title'])?></h3>
                <p><?=e($source['description'])?></p>
                <a href="<?=e($source['url'])?>" target="_blank" rel="noopener noreferrer">
                    مشاهده منبع ↗
                </a>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

</main>

<footer class="rom-footer">TechLearn | ROM Hub</footer>
</body>
</html>
