<?php
session_start();
require_once __DIR__ . '/../includes/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'درخواست نامعتبر است. صفحه را تازه‌سازی کن.';
    } else {
        $action = $_POST['action'] ?? '';

        try {
            if ($action === 'add_image') {
                $projectId = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
                $url = trim($_POST['image_url'] ?? '');
                $caption = trim($_POST['image_caption'] ?? '');
                $sort = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);

                $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));

                if (!$projectId || !filter_var($url, FILTER_VALIDATE_URL)
                    || $scheme !== 'https') {
                    $error = 'شناسه پروژه یا لینک تصویر معتبر نیست. لینک باید HTTPS باشد.';
                } else {
                    $check = $pdo->prepare('SELECT id FROM recovery_projects WHERE id = ?');
                    $check->execute([$projectId]);

                    if (!$check->fetch()) {
                        $error = 'پروژه انتخاب‌شده وجود ندارد.';
                    } else {
                        $stmt = $pdo->prepare(
                            'INSERT INTO recovery_project_images
                             (project_id, image_url, image_caption, sort_order)
                             VALUES (?, ?, ?, ?)'
                        );
                        $stmt->execute([
                            $projectId,
                            $url,
                            $caption ?: null,
                            $sort === false || $sort === null ? 0 : $sort
                        ]);

                        $message = 'تصویر با موفقیت ثبت شد.';
                    }
                }
            } elseif ($action === 'delete_image') {
                $imageId = filter_input(INPUT_POST, 'image_id', FILTER_VALIDATE_INT);

                if (!$imageId) {
                    $error = 'شناسه تصویر معتبر نیست.';
                } else {
                    $stmt = $pdo->prepare(
                        'DELETE FROM recovery_project_images WHERE id = ?'
                    );
                    $stmt->execute([$imageId]);

                    $message = $stmt->rowCount()
                        ? 'تصویر حذف شد.'
                        : 'تصویری با این شناسه پیدا نشد.';
                }
            } else {
                $error = 'عملیات نامعتبر است.';
            }

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $error = 'عملیات انجام نشد. خطای ثبت‌شده را بررسی می‌کنیم.';
        }
    }
}

$projects = $pdo->query(
    'SELECT id, project_name FROM recovery_projects ORDER BY project_name'
)->fetchAll(PDO::FETCH_ASSOC);

$images = $pdo->query(
    'SELECT i.id, i.project_id, i.image_url, i.image_caption,
            i.sort_order, p.project_name
     FROM recovery_project_images i
     JOIN recovery_projects p ON p.id = i.project_id
     ORDER BY p.project_name, i.sort_order, i.id'
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>مدیریت پروژه‌های ریکاوری | تک‌لرن</title>
<style>
*{box-sizing:border-box}
body{margin:0;padding:18px;background:#080d1b;color:#f1f4ff;font-family:Tahoma,sans-serif;line-height:1.9}
.container{max-width:1000px;margin:20px auto}
.panel{background:#131d33;border:1px solid #293957;border-radius:15px;padding:20px;margin:18px 0}
h1,h2{color:#aabaff}
label{display:block;margin:12px 0 5px}
input,select{width:100%;padding:12px;border-radius:9px;border:1px solid #39496b;background:#0b1427;color:white;font-family:Tahoma}
button,.btn{display:inline-block;border:0;border-radius:8px;padding:10px 15px;background:#647cf4;color:white;font-family:Tahoma;text-decoration:none;cursor:pointer;margin-top:12px}
.delete{background:#a83d58}
.notice{padding:12px;border-radius:9px;margin:12px 0}
.success{background:#102c27;color:#65e6a0}
.error{background:#351c28;color:#ff8585}
.image-row{display:flex;gap:15px;align-items:center;border-bottom:1px solid #293957;padding:14px 0;flex-wrap:wrap}
.image-row img{width:110px;height:85px;object-fit:cover;border-radius:9px;background:#0b1427}
.image-info{flex:1;min-width:180px;overflow-wrap:anywhere}
small{color:#aab6d5}
a{color:#aabaff}
</style>
</head>
<body>
<div class="container">
    <h1>🖼️ مدیریت تصاویر پروژه‌های ریکاوری</h1>
    <p>برای هر پروژه می‌توانی چند تصویر ثبت کنی.</p>

    <?php if ($message): ?>
        <div class="notice success"><?=e($message)?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?=e($error)?></div>
    <?php endif; ?>

    <section class="panel">
        <h2>افزودن تصویر جدید</h2>

        <?php if (!$projects): ?>
            <p>هنوز پروژه‌ای ثبت نشده است.</p>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
            <input type="hidden" name="action" value="add_image">

            <label>انتخاب پروژه</label>
            <select name="project_id" required>
                <option value="">انتخاب کن</option>
                <?php foreach ($projects as $project): ?>
                    <option value="<?=e($project['id'])?>"><?=e($project['project_name'])?></option>
                <?php endforeach; ?>
            </select>

            <label>لینک مستقیم تصویر (HTTPS)</label>
            <input type="url" name="image_url" placeholder="https://example.com/recovery-image.jpg" required>

            <label>عنوان یا توضیح تصویر</label>
            <input type="text" name="image_caption" maxlength="255" placeholder="مثلاً صفحه اصلی محیط ریکاوری">

            <label>ترتیب نمایش</label>
            <input type="number" name="sort_order" value="0">

            <button type="submit">ثبت تصویر</button>
        </form>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>تصاویر ثبت‌شده (<?=count($images)?>)</h2>

        <?php if (!$images): ?>
            <p>هنوز تصویری ثبت نشده است.</p>
        <?php else: ?>
            <?php foreach ($images as $image): ?>
                <div class="image-row">
                    <img src="<?=e($image['image_url'])?>" alt="<?=e($image['image_caption'])?>" loading="lazy">
                    <div class="image-info">
                        <strong><?=e($image['project_name'])?></strong><br>
                        <?=e($image['image_caption'] ?: 'بدون عنوان')?><br>
                        <small>ترتیب: <?=e($image['sort_order'])?></small><br>
                        <a href="<?=e($image['image_url'])?>" target="_blank" rel="noopener noreferrer">مشاهده تصویر اصلی</a>
                    </div>
                    <form method="POST" onsubmit="return confirm('این تصویر حذف شود؟')">
                        <input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
                        <input type="hidden" name="action" value="delete_image">
                        <input type="hidden" name="image_id" value="<?=e($image['id'])?>">
                        <button class="delete" type="submit">حذف تصویر</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <p><a href="recoveries.php">مدیریت فایل‌های ریکاوری</a></p>
    <p><a href="index.php">← بازگشت به پنل مدیریت</a></p>
</div>
</body>
</html>
