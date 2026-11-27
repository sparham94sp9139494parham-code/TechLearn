<?php
require_once __DIR__ . '/../includes/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function safeUrl($url) {
    $url = trim((string)$url);
    return preg_match('~^https?://~i', $url) ? $url : '';
}

$slug = trim($_GET['slug'] ?? '');

$stmt = $pdo->prepare("
    SELECT *
    FROM recovery_projects
    WHERE slug = ?
    LIMIT 1
");
$stmt->execute([$slug]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$project) {
    http_response_code(404);
    exit('پروژه موردنظر پیدا نشد.');
}

$stmt = $pdo->prepare("
    SELECT image_url, image_caption
    FROM recovery_project_images
    WHERE project_id = ?
    ORDER BY sort_order ASC, id ASC
");
$stmt->execute([$project['id']]);
$images = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'active' => 'فعال',
    'legacy' => 'قدیمی',
    'discontinued' => 'متوقف‌شده',
    'unknown' => 'نامشخص'
];

$features = array_filter(
    array_map('trim', explode(';', (string)$project['features']))
);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($project['project_name']) ?> | TechLearn</title>

<style>
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    background: #080d1b;
    color: #eaf0ff;
    font-family: Tahoma, Arial, sans-serif;
    line-height: 1.9;
}
.container {
    width: min(100% - 32px, 1000px);
    margin: 35px auto;
}
.back {
    display: inline-block;
    color: #9eafff;
    text-decoration: none;
    margin-bottom: 20px;
}
.hero, .section {
    background: #101a2e;
    border: 1px solid #263653;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 20px;
}
h1 {
    font-size: 26px;
    margin: 0 0 10px;
}
h2 {
    font-size: 19px;
    margin-top: 0;
    color: #aabaff;
}
.short {
    color: #b9c6df;
}
.status {
    display: inline-block;
    background: #26365c;
    color: #cbd5ff;
    padding: 3px 12px;
    border-radius: 20px;
    font-size: 13px;
}
.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}
.feature {
    background: #172541;
    padding: 12px;
    border-radius: 12px;
}
.gallery {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 15px;
}
.gallery img {
    width: 100%;
    max-height: 380px;
    object-fit: contain;
    background: #080d1b;
    border-radius: 12px;
}
.caption {
    color: #aebbd4;
    font-size: 13px;
    margin-top: 7px;
}
.links {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.links a {
    display: inline-block;
    background: linear-gradient(120deg, #647cf4, #8059e8);
    color: white;
    padding: 9px 17px;
    border-radius: 10px;
    text-decoration: none;
}
footer {
    color: #7888a7;
    text-align: center;
    padding: 20px;
    font-size: 13px;
}
</style>
</head>

<body>
<main class="container">

<a class="back" href="index.php">← بازگشت به Recovery Hub</a>

<section class="hero">
    <span class="status">
        وضعیت: <?= e($statusLabels[$project['project_status']] ?? 'نامشخص') ?>
    </span>

    <h1><?= e($project['project_name']) ?></h1>
    <div class="short"><?= e($project['short_description']) ?></div>
</section>

<section class="section">
    <h2>درباره پروژه</h2>
    <p><?= nl2br(e($project['description'])) ?></p>
</section>

<?php if ($features): ?>
<section class="section">
    <h2>امکانات و ویژگی‌ها</h2>
    <div class="features">
        <?php foreach ($features as $feature): ?>
            <div class="feature">✓ <?= e($feature) ?></div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($images): ?>
<section class="section">
    <h2>گالری تصاویر</h2>
    <div class="gallery">
        <?php foreach ($images as $image):
            $imageUrl = safeUrl($image['image_url']);
            if (!$imageUrl) continue;
        ?>
            <div>
                <img src="<?= e($imageUrl) ?>"
                     alt="<?= e($project['project_name']) ?>"
                     loading="lazy">
                <?php if ($image['image_caption']): ?>
                    <div class="caption">
                        <?= e($image['image_caption']) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <h2>منابع و لینک‌ها</h2>
    <div class="links">
        <?php if ($url = safeUrl($project['official_url'])): ?>
            <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">
                وب‌سایت رسمی
            </a>
        <?php endif; ?>

        <?php if ($url = safeUrl($project['docs_url'])): ?>
            <a href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer">
                مستندات و منبع
            </a>
        <?php endif; ?>
    </div>
</section>

<footer>TechLearn — Recovery Hub</footer>

</main>
</body>
</html>
