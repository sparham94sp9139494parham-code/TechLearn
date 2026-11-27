<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
}

if (!$id || $id < 1) {
    http_response_code(400);
    exit('شناسه آموزش معتبر نیست.');
}

$stmt = $pdo->prepare(
    "SELECT id, title, content, status
     FROM articles WHERE id = ? LIMIT 1"
);
$stmt->execute([$id]);
$article = $stmt->fetch();

if (!$article) {
    http_response_code(404);
    exit('آموزش پیدا نشد.');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !isset($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'درخواست معتبر نیست. صفحه را دوباره بارگذاری کن.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = $_POST['status'] ?? '';

        if ($title === '' || $content === '') {
            $error = 'عنوان و متن آموزش را وارد کن.';
        } elseif (mb_strlen($title) > 200) {
            $error = 'عنوان نباید بیشتر از ۲۰۰ کاراکتر باشد.';
        } elseif (!in_array($status, ['draft', 'published'], true)) {
            $error = 'وضعیت آموزش معتبر نیست.';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE articles
                 SET title = ?, content = ?, status = ?
                 WHERE id = ?"
            );

            $stmt->execute([$title, $content, $status, $id]);

            $_SESSION['success_message'] = 'آموزش با موفقیت ویرایش شد.';
            header('Location: articles.php');
            exit;
        }
    }

    $article['title'] = $title;
    $article['content'] = $content;
    $article['status'] = $status;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ویرایش آموزش | تک‌لرن</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .editor {
            background: #151f35;
            border: 1px solid #26344e;
            padding: 25px;
            border-radius: 16px;
            max-width: 850px;
        }

        .editor label {
            display: block;
            margin: 18px 0 8px;
        }

        .editor input,
        .editor textarea,
        .editor select {
            width: 100%;
            padding: 13px;
            border-radius: 9px;
            border: 1px solid #334155;
            background: #0b1020;
            color: white;
            font: inherit;
        }

        .editor textarea {
            min-height: 260px;
        }

        .editor button {
            padding: 12px 22px;
            border: 0;
            border-radius: 9px;
            margin-top: 20px;
            color: white;
            background: #0284c7;
            font: inherit;
        }

        .error {
            color: #f87171;
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
            <h1>ویرایش آموزش</h1>
            <p>تغییر عنوان، متن و وضعیت آموزش</p>
        </div>
    </header>

    <section class="editor">
        <?php if ($error): ?>
            <p class="error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="id" value="<?= (int)$article['id'] ?>">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <label>عنوان آموزش</label>
            <input
                type="text"
                name="title"
                maxlength="200"
                required
                value="<?= htmlspecialchars($article['title'], ENT_QUOTES, 'UTF-8') ?>"
            >

            <label>متن آموزش</label>
            <textarea name="content" required><?= htmlspecialchars($article['content'], ENT_QUOTES, 'UTF-8') ?></textarea>

            <label>وضعیت آموزش</label>
            <select name="status">
                <option value="draft" <?= $article['status'] === 'draft' ? 'selected' : '' ?>>
                    پیش‌نویس
                </option>
                <option value="published" <?= $article['status'] === 'published' ? 'selected' : '' ?>>
                    منتشر شده
                </option>
            </select>

            <button type="submit">ذخیره تغییرات</button>
        </form>
    </section>
</main>

</body>
</html>

