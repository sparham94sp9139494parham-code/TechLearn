<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';

$stmt = $pdo->query(
    "SELECT id, title, status, created_at
     FROM articles
     ORDER BY created_at DESC"
);

$articles = $stmt->fetchAll();

$message = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مدیریت آموزش‌ها | تک‌لرن</title>
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        .articles-box {
            background: #151f35;
            border: 1px solid #26344e;
            padding: 25px;
            border-radius: 16px;
        }

        .article-item {
            background: #0b1020;
            border: 1px solid #26344e;
            padding: 18px;
            border-radius: 12px;
            margin-top: 15px;
        }

        .article-item h3 {
            margin-bottom: 12px;
        }

        .status {
            color: #7dd3fc;
            margin-bottom: 12px;
        }

        .edit-link,
        .delete-button {
            display: inline-block;
            padding: 9px 16px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font: inherit;
            border: 0;
            cursor: pointer;
        }

        .edit-link {
            background: #0284c7;
        }

        .delete-button {
            background: #b91c1c;
        }

        .success {
            color: #4ade80;
            margin-bottom: 15px;
        }

        .empty {
            color: #94a3b8;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<aside class="sidebar">
    <h2>تک‌لرن<span>.</span></h2>
    <p class="side-label">مدیریت سایت</p>

    <a href="index.php">داشبورد</a>
    <a href="write.php">نوشتن آموزش</a>
    <a class="active" href="articles.php">مدیریت آموزش‌ها</a>
    <a href="../index.php">مشاهده سایت</a>
    <a href="logout.php">خروج</a>
</aside>

<main class="main">

    <header>
        <div>
            <h1>مدیریت آموزش‌ها</h1>
            <p>تمام آموزش‌های ثبت‌شده در تک‌لرن</p>
        </div>
    </header>

    <section class="articles-box">

        <?php if ($message): ?>
            <p class="success">
                <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <h2>فهرست آموزش‌ها</h2>

        <?php if (count($articles) > 0): ?>

            <?php foreach ($articles as $article): ?>

                <div class="article-item">

                    <h3>
                        <?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>
                    </h3>

                    <p class="status">
                        وضعیت:
                        <?= $article['status'] === 'published'
                            ? 'منتشر شده'
                            : 'پیش‌نویس' ?>
                    </p>

                    <p>
                        تاریخ ثبت:
                        <?= htmlspecialchars($article['created_at'], ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <br>

                    <a
                        class="edit-link"
                        href="edit.php?id=<?= (int)$article['id'] ?>"
                    >
                        ویرایش آموزش
                    </a>

                    <form
                        method="post"
                        action="delete.php"
                        style="display:inline-block; margin-top:10px;"
                        onsubmit="return confirm('مطمئنی می‌خوای این آموزش رو برای همیشه حذف کنی؟');"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int)$article['id'] ?>"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                        >

                        <button class="delete-button" type="submit">
                            حذف آموزش
                        </button>
                    </form>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p class="empty">هنوز هیچ آموزشی ثبت نشده است.</p>

        <?php endif; ?>

    </section>

</main>

</body>
</html>

