<?php
session_start();
require_once __DIR__ . '/includes/database.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$categories = $pdo->query("
    SELECT id, name, slug
    FROM categories
    ORDER BY id ASC
")->fetchAll();

$selectedSlug = trim($_GET['category'] ?? '');
$searchTerm = trim($_GET['q'] ?? '');
$selectedCategory = null;

foreach ($categories as $category) {
    if ($category['slug'] === $selectedSlug) {
        $selectedCategory = $category;
        break;
    }
}

if ($selectedCategory) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.title, a.slug, a.content, a.created_at,
               c.name AS category_name
        FROM articles a
        LEFT JOIN categories c ON c.id = a.category_id
        WHERE a.status = 'published' AND c.id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$selectedCategory['id']]);
} else {
    $selectedSlug = '';
    $stmt = $pdo->query("
        SELECT a.id, a.title, a.slug, a.content, a.created_at,
               c.name AS category_name
        FROM articles a
        LEFT JOIN categories c ON c.id = a.category_id
        WHERE a.status = 'published'
        ORDER BY a.created_at DESC
        LIMIT 6
    ");
}

$articles = $stmt->fetchAll();

$icons = [
    'android' => '🤖',
    'phone' => '📱',
    'computer' => '💻',
    'windows' => '🪟',
    'linux' => '🐧'
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تک‌لرن | آموزش تکنولوژی</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .category-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }
        .category-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 24px 12px;
            min-height: 125px;
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 16px;
            background: rgba(255,255,255,.04);
            color: inherit;
            text-decoration: none;
            transition: .2s;
        }
        .category-card:hover,
        .category-card.active {
            border-color: #7185ff;
            background: rgba(100,120,255,.15);
            transform: translateY(-3px);
        }
        .category-icon { font-size: 30px; }
        .category-name { font-weight: bold; }
        .article-category {
            display: inline-block;
            margin: 8px 0;
            padding: 5px 10px;
            border-radius: 20px;
            background: rgba(100,120,255,.15);
            font-size: 12px;
        }
    </style>
</head>
<body>

<header class="topbar">
    <a href="index.php" class="logo">تک‌لرن<span>.</span></a>
    <nav>
        <a href="index.php">خانه</a>
        <a href="#articles">آموزش‌ها</a>
        <a href="rom-hub/index.php" class="rom-link">ROM Hub</a>
        <a href="recovery-hub/index.php" class="rom-link">Recovery Hub</a>
        <?php if (isset($_SESSION['admin_id'])): ?>
            <a href="admin/index.php" class="admin-link">پنل مدیریت</a>
        <?php else: ?>
            <a href="admin/login.php" class="admin-link">ورود مدیر</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="hero">
        <div>
            <h1>به دنیای <span>تک‌لرن</span> خوش آمدید</h1>
            <p>اینجا دنیای آموزش تکنولوژی، اندروید، کامپیوتر و لینوکس است.</p>
            <a href="#categories" class="read-more">مشاهده دسته‌بندی‌ها ←</a>
        </div>
    </section>

    <section class="section" id="categories">
        <div class="section-heading">
            <div>
                <h2>دسته‌بندی آموزش‌ها</h2>
                <p>موضوع موردنظرت رو انتخاب کن.</p>
            </div>
        </div>

        <div class="category-grid">
            <a href="index.php#articles"
               class="category-card <?= $selectedSlug === '' ? 'active' : '' ?>">
                <span class="category-icon">📚</span>
                <span class="category-name">همه آموزش‌ها</span>
            </a>

            <?php foreach ($categories as $category): ?>
                <a
                    href="index.php?category=<?= urlencode($category['slug']) ?>#articles"
                    class="category-card <?= $selectedSlug === $category['slug'] ? 'active' : '' ?>"
                >
                    <span class="category-icon">
                        <?= $icons[$category['slug']] ?? '📘' ?>
                    </span>
                    <span class="category-name"><?= e($category['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    </section>

    <section class="section" id="articles">
        <div class="section-heading">
            <div>
                <h2>
                    <?= $selectedCategory
                        ? 'آموزش‌های ' . e($selectedCategory['name'])
                        : 'آخرین آموزش‌ها' ?>
                </h2>
                <p>آموزش‌های منتشرشده در تک‌لرن</p>
            </div>
        </div>

        <?php if (count($articles) > 0): ?>
            <div class="article-grid">
                <?php foreach ($articles as $article): ?>
                    <article class="article-card">
                        <h3><?= e($article['title']) ?></h3>

                        <?php if ($article['category_name']): ?>
                            <span class="article-category">
                                <?= e($article['category_name']) ?>
                            </span>
                        <?php endif; ?>

                        <p>
                            <?= e(mb_substr(strip_tags($article['content']), 0, 150)) ?>...
                        </p>

                        <a class="read-more"
                           href="article.php?slug=<?= urlencode($article['slug']) ?>">
                            مطالعه آموزش ←
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty">هنوز آموزشی در این دسته‌بندی منتشر نشده است.</p>
        <?php endif; ?>
    </section>
</main>

<footer>
    <p>© تمامی حقوق برای تک‌لرن محفوظ است.</p>
</footer>

</body>
</html>
