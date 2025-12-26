<?php
/**
 * Single Blog Post Page
 * Web 1.0 Portfolio Site
 */

require_once __DIR__ . '/../includes/db-config.php';
require_once __DIR__ . '/includes/functions.php';

// Get post by slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$post = blog_get_post_by_slug($mysqli, $slug, false);

if (!$post) {
    // Post not found - show 404
    http_response_code(404);
    $currentPage = 'blog';
    $htmlTitle = 'benjmacaro.dev - Post Not Found';
    $metaDescription = 'The requested blog post was not found.';
    $metaKeywords = 'blog, 404, not found';
    require_once __DIR__ . '/../includes/page-header.php';
    ?>
    <h2 class="section-heading">Post Not Found</h2>
    <p class="body-text">
        Sorry, the blog post you're looking for doesn't exist or has been removed.
    </p>
    <p class="body-text">
        <a href="index.php">&laquo; Back to Blog</a>
    </p>
    <?php
    require_once __DIR__ . '/../includes/page-footer.php';
    exit;
}

// Set page variables
$currentPage = 'blog';
$htmlTitle = 'benjmacaro.dev - ' . $post['title'];
$metaDescription = $post['excerpt'];
$metaKeywords = 'blog, ' . implode(', ', array_map(function($c) {
    return $c['name'];
}, $post['categories']));

require_once __DIR__ . '/../includes/page-header.php';
?>

<p class="body-text" style="margin-bottom: 5px;">
    <a href="index.php">&laquo; Back to Blog</a>
</p>

<h2 class="section-heading" style="margin-bottom: 10px;">
    <?php echo htmlspecialchars($post['title']); ?>
</h2>

<p class="text-secondary" style="margin-bottom: 20px; font-size: 14px;">
    Published <?php echo blog_format_date($post['published_at'] ?? $post['created_at'], 'F j, Y'); ?>
    <?php if (!empty($post['categories'])): ?>
        in
        <?php
        $cat_links = [];
        foreach ($post['categories'] as $cat) {
            $cat_links[] = '<a href="index.php?category=' . urlencode($cat['slug']) . '">'
                . htmlspecialchars($cat['name']) . '</a>';
        }
        echo implode(', ', $cat_links);
        ?>
    <?php endif; ?>
</p>

<div class="blog-post-content body-text">
    <?php echo $post['content_html']; ?>
</div>

<hr>

<p class="body-text" style="margin-top: 20px;">
    <a href="index.php">&laquo; Back to Blog</a>
</p>

<?php if (!empty($post['categories'])): ?>
    <p class="text-secondary" style="font-size: 14px;">
        Categories:
        <?php
        $cat_links = [];
        foreach ($post['categories'] as $cat) {
            $cat_links[] = '<a href="index.php?category=' . urlencode($cat['slug']) . '">'
                . htmlspecialchars($cat['name']) . '</a>';
        }
        echo implode(', ', $cat_links);
        ?>
    </p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/page-footer.php'; ?>
