<?php
require_once 'config/config.php';

if (!isset($_GET['slug'])) {
    redirect('articles.php');
}

$db = getDBConnection();
$slug = sanitize($_GET['slug']);

// Get article by slug
try {
    $stmt = $db->prepare("SELECT * FROM articles WHERE slug = :slug AND status = 'published'");
    $stmt->execute([':slug' => $slug]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    $article = null;
}

if (!$article) {
    redirect('articles.php');
}

// Increment view count
try {
    $db->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = :id")->execute([':id' => $article['id']]);
} catch (PDOException $e) {
    // Ignore if view_count column doesn't exist
}

// Get related articles (same category or recent)
try {
    $relatedStmt = $db->prepare("SELECT * FROM articles 
                                 WHERE id != :id AND status = 'published' 
                                 ORDER BY created_at DESC 
                                 LIMIT 3");
    $relatedStmt->execute([':id' => $article['id']]);
    $relatedArticles = $relatedStmt->fetchAll();
} catch (PDOException $e) {
    $relatedArticles = [];
}

$pageTitle = $article['title'];
$pageDescription = $article['excerpt'] ?: $article['title'];

include 'includes/header.php';
?>

<section class="article-page-section">
    <div class="container">
        <nav class="breadcrumbs">
            <a href="<?php echo BASE_URL; ?>">Главная</a>
            <span>/</span>
            <a href="articles.php">Статьи</a>
            <span>/</span>
            <span><?php echo htmlspecialchars($article['title']); ?></span>
        </nav>
        
        <article class="article-single">
            <?php if (!empty($article['image'])): ?>
                <div class="article-single-image">
                    <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                </div>
            <?php endif; ?>
            
            <div class="article-single-header">
                <h1 class="article-single-title"><?php echo htmlspecialchars($article['title']); ?></h1>
                
                <div class="article-single-meta">
                    <span class="article-date">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                        <?php echo date('d.m.Y', strtotime($article['created_at'])); ?>
                    </span>
                    <?php if (isset($article['view_count']) && $article['view_count'] > 0): ?>
                        <span class="article-views">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <?php echo $article['view_count']; ?> просмотров
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($article['short_description'] ?? $article['excerpt']): ?>
                <div class="article-single-excerpt">
                    <p><?php echo htmlspecialchars($article['short_description'] ?? $article['excerpt']); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="article-single-content">
                <?php
                // Get article blocks
                try {
                    $blocksStmt = $db->prepare("SELECT * FROM article_blocks WHERE article_id = :id ORDER BY sort_order ASC");
                    $blocksStmt->execute([':id' => $article['id']]);
                    $blocks = $blocksStmt->fetchAll();
                    
                    if (!empty($blocks)) {
                        // Render blocks
                        foreach ($blocks as $block) {
                            $content = json_decode($block['content'], true) ?? [];
                            $templateType = $block['template_type'];
                            
                            // Render block based on template type
                            include 'includes/article-block-render.php';
                        }
                    } else {
                        // Fallback to old content field
                        if (!empty($article['content'])) {
                            echo $article['content'];
                        }
                    }
                } catch (PDOException $e) {
                    // Fallback to old content field
                    if (!empty($article['content'])) {
                        echo $article['content'];
                    }
                }
                ?>
            </div>
        </article>
        
        <?php if (!empty($relatedArticles)): ?>
            <section class="related-articles">
                <h2 class="related-articles-title">Похожие статьи</h2>
                <div class="articles-grid">
                    <?php foreach ($relatedArticles as $relatedArticle): ?>
                        <article class="article-card">
                            <?php if ($relatedArticle['image']): ?>
                                <a href="article.php?slug=<?php echo htmlspecialchars($relatedArticle['slug']); ?>" class="article-image">
                                    <img src="<?php echo BASE_URL; ?>uploads/articles/<?php echo htmlspecialchars($relatedArticle['image']); ?>" alt="<?php echo htmlspecialchars($relatedArticle['title']); ?>">
                                </a>
                            <?php endif; ?>
                            <div class="article-content">
                                <h3 class="article-title">
                                    <a href="article.php?slug=<?php echo htmlspecialchars($relatedArticle['slug']); ?>"><?php echo htmlspecialchars($relatedArticle['title']); ?></a>
                                </h3>
                                <?php if ($relatedArticle['excerpt']): ?>
                                    <p class="article-excerpt"><?php echo htmlspecialchars($relatedArticle['excerpt']); ?></p>
                                <?php endif; ?>
                                <div class="article-meta">
                                    <span class="article-date"><?php echo date('d.m.Y', strtotime($relatedArticle['created_at'])); ?></span>
                                    <?php if (isset($relatedArticle['view_count']) && $relatedArticle['view_count'] > 0): ?>
                                        <span class="article-views"><?php echo $relatedArticle['view_count']; ?> просмотров</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>
</section>

<?php
include 'includes/footer.php';
?>


