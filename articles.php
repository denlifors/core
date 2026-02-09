<?php
require_once 'config/config.php';

$db = getDBConnection();

// Get all published articles
try {
    $stmt = $db->query("SELECT * FROM articles WHERE status = 'published' ORDER BY created_at DESC");
    $articles = $stmt->fetchAll();
} catch (PDOException $e) {
    $articles = [];
}

$pageTitle = 'Статьи';
$pageDescription = 'Полезные статьи о здоровье и БАДах';
include 'includes/header.php';
?>

<main class="main-content">
    <section class="articles-page-section">
        <div class="container">
            <h1 class="page-title">Полезные статьи</h1>
            
            <?php if (empty($articles)): ?>
                <div style="text-align: center; padding: 4rem 0;">
                    <p style="color: var(--text-light); font-size: 1.1rem;">Статьи будут добавлены в ближайшее время</p>
                </div>
            <?php else: ?>
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                        <article class="article-card">
                            <?php if ($article['image']): ?>
                                <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>" class="article-image">
                                    <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                                </a>
                            <?php endif; ?>
                            <div class="article-content">
                                <h3 class="article-title">
                                    <a href="article.php?slug=<?php echo htmlspecialchars($article['slug']); ?>"><?php echo htmlspecialchars($article['title']); ?></a>
                                </h3>
                                <?php if ($article['excerpt']): ?>
                                    <p class="article-excerpt"><?php echo htmlspecialchars($article['excerpt']); ?></p>
                                <?php endif; ?>
                                <div class="article-meta">
                                    <span class="article-date"><?php echo date('d.m.Y', strtotime($article['created_at'])); ?></span>
                                    <?php if ($article['view_count'] > 0): ?>
                                        <span class="article-views"><?php echo $article['view_count']; ?> просмотров</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php
include 'includes/footer.php';
?>





