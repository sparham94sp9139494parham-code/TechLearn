<?php
require_once __DIR__ . '/../includes/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function safeUrl($url) {
    if (!$url) return '';
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return in_array(strtolower((string)$scheme), ['http', 'https'], true) ? $url : '';
}

$search = trim($_GET['q'] ?? '');

$projects = [];
$projectImages = [];
$recoveries = [];
$loadError = false;

try {
    if ($search !== '') {
        $term = '%' . $search . '%';
        $stmt = $pdo->prepare("
            SELECT * FROM recovery_projects
            WHERE project_name LIKE ?
               OR short_description LIKE ?
               OR description LIKE ?
            ORDER BY is_featured DESC, project_name ASC
        ");
        $stmt->execute([$term, $term, $term]);
    } else {
        $stmt = $pdo->query("
            SELECT * FROM recovery_projects
            ORDER BY is_featured DESC, project_name ASC
        ");
    }

    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($projects) {
        $ids = array_column($projects, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $imageStmt = $pdo->prepare("
            SELECT * FROM recovery_project_images
            WHERE project_id IN ($placeholders)
            ORDER BY sort_order ASC, id ASC
        ");
        $imageStmt->execute($ids);

        foreach ($imageStmt->fetchAll(PDO::FETCH_ASSOC) as $image) {
            $projectImages[$image['project_id']][] = $image;
        }
    }
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $loadError = true;
}

try {
    if ($search !== '') {
        $term = '%' . $search . '%';
        $stmt = $pdo->prepare("
            SELECT id, project_name, recovery_type, device_model,
                   recovery_version, android_version, description,
                   source_url, file_size
            FROM uploaded_recoveries
            WHERE status = 'published'
              AND (project_name LIKE ? OR recovery_type LIKE ? OR device_model LIKE ?)
            ORDER BY id DESC
        ");
        $stmt->execute([$term, $term, $term]);
    } else {
        $stmt = $pdo->query("
            SELECT id, project_name, recovery_type, device_model,
                   recovery_version, android_version, description,
                   source_url, file_size
            FROM uploaded_recoveries
            WHERE status = 'published'
            ORDER BY id DESC
        ");
    }

    $recoveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    $loadError = true;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recovery Hub | تک‌لرن</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#080d1b;color:#f1f4ff;font-family:Tahoma,sans-serif;line-height:1.9}
a{color:#aabaff;text-decoration:none}
.container{width:92%;max-width:1150px;margin:35px auto}
.hero,.search,.project-card,.recovery-card,.notice{background:#131d33;border:1px solid #293957;border-radius:17px;padding:22px;margin-bottom:18px}
.hero h1{color:#aabaff;margin-top:0}
.section-title{color:#c2ccff;margin:32px 0 16px}
.search form{display:flex;gap:10px;flex-wrap:wrap}
.search input{flex:1;min-width:190px;padding:12px;border-radius:9px;border:1px solid #39496b;background:#0b1427;color:white;font-family:Tahoma}
button,.download{display:inline-block;padding:10px 17px;border:0;border-radius:9px;background:#647cf4;color:white;font-family:Tahoma;font-size:14px;cursor:pointer}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(270px,1fr));gap:16px}
.project-card h2,.recovery-card h2{margin:0 0 8px;color:#c2ccff;font-size:21px}
.meta{color:#aab6d5;font-size:13px}
.description{white-space:pre-wrap;overflow-wrap:anywhere}
.notice{color:#ffd27a}
.empty{text-align:center;padding:30px;color:#aab6d5;background:#131d33;border-radius:15px}
.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:15px}
.gallery{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:9px;margin:14px 0}
.gallery img{width:100%;height:125px;object-fit:cover;border-radius:10px;border:1px solid #39496b;background:#0b1427}
.status{display:inline-block;padding:3px 10px;border-radius:20px;background:#202e4c;color:#b9c7ff;font-size:12px}
.featured{border-color:#647cf4}
.features{color:#c4cce2;font-size:14px}
@media(max-width:500px){.container{width:94%;margin:18px auto}.hero,.search,.project-card,.recovery-card{padding:16px}}
</style>
</head>
<body>
<div class="container">

    <section class="hero">
        <h1>🛠️ Recovery Hub</h1>
        <p>مرجع پروژه‌های ریکاوری اندروید، معرفی امکانات، تصاویر و فایل‌های منتشرشده</p>
        <a href="../index.php">← بازگشت به تک‌لرن</a>
    </section>

    <section class="search">
        <form method="GET">
            <input type="search" name="q" value="<?=e($search)?>" placeholder="جست‌وجوی نام ریکاوری یا مدل دستگاه...">
            <button type="submit">جست‌وجو</button>
            <?php if ($search !== ''): ?>
                <a class="download" href="index.php">پاک‌کردن</a>
            <?php endif; ?>
        </form>
    </section>

    <h2 class="section-title">📚 پروژه‌های ریکاوری</h2>

    <?php if ($loadError): ?>
        <div class="notice">در بارگذاری بخشی از اطلاعات مشکلی پیش آمد. جزئیات در لاگ سرور ثبت شده است.</div>
    <?php endif; ?>

    <?php if (!$projects): ?>
        <div class="empty">هنوز پروژه‌ای ثبت نشده است.</div>
    <?php else: ?>
        <div class="grid">
        <?php foreach ($projects as $project): ?>
            <article class="project-card <?=!empty($project['is_featured']) ? 'featured' : ''?>">
                <h2><?=e($project['project_name'])?></h2>

                <span class="status">
                    <?php
                    $statuses = [
                        'active' => 'فعال',
                        'legacy' => 'قدیمی',
                        'discontinued' => 'متوقف‌شده',
                        'unknown' => 'وضعیت نامشخص'
                    ];
                    echo e($statuses[$project['project_status']] ?? 'نامشخص');
                    ?>
                </span>

                <p><?=e($project['short_description'])?></p>

                <?php if (!empty($projectImages[$project['id']])): ?>
                    <div class="gallery">
                        <?php foreach ($projectImages[$project['id']] as $image): ?>
                            <?php $imageUrl = safeUrl($image['image_url']); ?>
                            <?php if ($imageUrl !== ''): ?>
                                <a href="<?=e($imageUrl)?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?=e($imageUrl)?>" alt="<?=e($image['image_caption'] ?: $project['project_name'])?>" loading="lazy">
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <p class="description"><?=e($project['description'])?></p>

                <?php if (!empty($project['features'])): ?>
                    <p class="features"><strong>امکانات:</strong><br><?=e($project['features'])?></p>
                <?php endif; ?>

                <div class="actions">
                    <a class="download" href="project.php?slug=<?=rawurlencode($project['slug'])?>">مشاهده جزئیات پروژه</a>
                    <?php $official = safeUrl($project['official_url']); ?>
                    <?php $docs = safeUrl($project['docs_url']); ?>

                    <?php if ($official !== ''): ?>
                        <a class="download" href="<?=e($official)?>" target="_blank" rel="noopener noreferrer">وب‌سایت رسمی</a>
                    <?php endif; ?>

                    <?php if ($docs !== ''): ?>
                        <a class="download" href="<?=e($docs)?>" target="_blank" rel="noopener noreferrer">مستندات</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h2 class="section-title">📦 فایل‌های ریکاوری قابل دانلود</h2>

    <?php if (!$recoveries): ?>
        <div class="empty">
            <?= $search !== '' ? 'فایل منتشرشده‌ای برای این جست‌وجو پیدا نشد.' : 'هنوز فایل ریکاوری منتشر نشده است.' ?>
        </div>
    <?php else: ?>
        <div class="grid">
        <?php foreach ($recoveries as $recovery): ?>
            <article class="recovery-card">
                <h2><?=e($recovery['project_name'])?></h2>
                <div class="meta">
                    <?=e($recovery['recovery_type'])?> · <?=e($recovery['device_model'])?>
                </div>
                <p><strong>نسخه ریکاوری:</strong> <?=e($recovery['recovery_version'] ?: 'نامشخص')?></p>
                <p><strong>اندروید سازگار:</strong> <?=e($recovery['android_version'] ?: 'نامشخص')?></p>
                <p class="description"><?=e($recovery['description'])?></p>
                <div class="meta">
                    حجم فایل: <?=number_format($recovery['file_size'] / 1048576, 2)?> مگابایت
                </div>
                <div class="actions">
                    <?php $source = safeUrl($recovery['source_url']); ?>
                    <?php if ($source !== ''): ?>
                        <a class="download" href="<?=e($source)?>" target="_blank" rel="noopener noreferrer">منبع پروژه</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
