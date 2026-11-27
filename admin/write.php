<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$categories = $pdo->query(
    "SELECT id, name FROM categories ORDER BY id ASC"
)->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'درخواست معتبر نیست. صفحه را دوباره بارگذاری کن.';
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $status = $_POST['status'] ?? 'draft';
        $categoryId = filter_var(
            $_POST['category_id'] ?? '',
            FILTER_VALIDATE_INT
        );

        if ($title === '' || $content === '') {
            $error = 'عنوان و متن آموزش را وارد کن.';
        } elseif (!in_array($status, ['draft', 'published'], true)) {
            $error = 'وضعیت انتخاب‌شده معتبر نیست.';
        } elseif (mb_strlen($title) > 200) {
            $error = 'عنوان نباید بیشتر از ۲۰۰ کاراکتر باشد.';
        } elseif (!$categoryId || !in_array(
            $categoryId,
            array_column($categories, 'id')
        )) {
            $error = 'لطفاً یک دسته‌بندی معتبر انتخاب کن.';
        } else {
            $slug = 'article-' . bin2hex(random_bytes(8));

            $stmt = $pdo->prepare(
                "INSERT INTO articles
                (user_id, category_id, title, slug, content, status)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmt->execute([
                $_SESSION['admin_id'],
                $categoryId,
                $title,
                $slug,
                $content,
                $status
            ]);

            $_SESSION['success_message'] =
                $status === 'draft'
                ? 'پیش‌نویس با موفقیت ذخیره شد.'
                : 'آموزش با موفقیت منتشر شد.';

            header('Location: articles.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نوشتن آموزش | تک‌لرن</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .editor {
            background: #101a2e;
            border: 1px solid #263550;
            padding: 25px;
            border-radius: 16px;
            max-width: 850px;
        }
        .editor label {
            display: block;
            margin: 18px 0 8px;
        }
        .editor input, .editor textarea, .editor select {
            width: 100%;
            padding: 13px;
            border-radius: 9px;
            border: 1px solid #334155;
            background: #080d1b;
            color: white;
            font: inherit;
            box-sizing: border-box;
        }
        .editor textarea {
            min-height: 260px;
            resize: vertical;
        }
        .editor button {
            padding: 12px 22px;
            border: 0;
            border-radius: 9px;
            margin-top: 20px;
            color: white;
            background: #0284c7;
            font: inherit;
            cursor: pointer;
        }
        .error { color: #f87171; }
    </style>
</head>
<body>

<aside class="sidebar">
    <h2>تک‌لرن<span>.</span></h2>
    <p class="side-label">مدیریت سایت</p>
    <a href="index.php">داشبورد</a>
    <a class="active" href="write.php">نوشتن آموزش</a>
    <a href="articles.php">مدیریت آموزش‌ها</a>
    <a href="managers.php">مدیریت مدیران</a>
    <a href="../index.php">مشاهده سایت</a>
    <a href="logout.php">خروج</a>
</aside>

<main class="main">
    <header>
        <div>
            <h1>نوشتن آموزش جدید</h1>
            <p>دانشت را با دیگران به اشتراک بگذار.</p>
        </div>
    </header>

    <section class="editor">
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <label>عنوان آموزش</label>
            <input type="text" name="title" maxlength="200"
                placeholder="مثلاً آموزش نصب PixelOS" required>

            <label>دسته‌بندی آموزش</label>
            <select name="category_id" required>
                <option value="">انتخاب دسته‌بندی</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>"
                        <?= isset($_POST['category_id']) &&
                            (int)$_POST['category_id'] === (int)$category['id']
                            ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>متن آموزش</label>
            <textarea name="content"
                placeholder="آموزش خودت را اینجا بنویس..."
                required><?= htmlspecialchars($_POST['content'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>

            <label>وضعیت آموزش</label>
            <select name="status">
                <option value="draft">ذخیره به‌عنوان پیش‌نویس</option>
                <option value="published">انتشار آموزش</option>
            </select>

            <button type="submit">ثبت آموزش</button>
        </form>
    </section>
</main>

</body>
</html>
