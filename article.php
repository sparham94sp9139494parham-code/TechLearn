<?php
require_once __DIR__ . '/includes/database.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '' || strlen($slug) > 220) {
    http_response_code(404);
    exit('آموزش پیدا نشد.');
}

$stmt = $pdo->prepare(
    "SELECT title, content, created_at
     FROM articles
     WHERE slug = ? AND status = 'published'
     LIMIT 1"
);

$stmt->execute([$slug]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    exit('این آموزش وجود ندارد یا هنوز منتشر نشده است.');
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?> | تک‌لرن</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .article-page {
            max-width: 900px;
            margin: 50px auto;
            padding: 25px;
        }

        .article-card {
            background: #151f35;
            border: 1px solid #26344e;
            padding: 35px;
            border-radius: 18px;
        }

        .article-card h1 {
            font-size: 30px;
            margin-bottom: 18px;
        }

        .article-date {
            color: #94a3b8;
            margin-bottom: 30px;
        }

        .article-content {
            color: #e2e8f0;
            line-height: 2.2;
            font-size: 17px;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #38bdf8;
            text-decoration: none;
        }
    </style>
</head>

<body>

<main class="article-page">

    <a class="back-link" href="index.php">
        ← بازگشت به صفحه اصلی
    </a>

    <article class="article-card">

        <h1>
            <?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <p class="article-date">
            تاریخ انتشار:
            <?= htmlspecialchars($article['created_at'], ENT_QUOTES, 'UTF-8') ?>
        </p>

        <div class="article-content"><?=
            htmlspecialchars($article['content'], ENT_QUOTES, 'UTF-8')
        ?></div>

    </article>

</main>

</body>
</html>

