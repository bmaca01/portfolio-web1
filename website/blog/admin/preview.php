<?php
/**
 * Admin Post Preview
 * Web 1.0 Portfolio Site - Blog Admin
 *
 * Allows authenticated admins to preview posts (including drafts) before publishing.
 * Uses the main site layout to match how published posts look.
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
admin_check_auth();

// Get post ID from query string
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirect to posts list if no ID provided
if (!$post_id) {
    header('Location: posts.php');
    exit;
}

// Load post by ID (includes drafts)
$post = blog_get_post_by_id($mysqli, $post_id);

if (!$post) {
    // Post not found - show 404 with main site layout
    http_response_code(404);
    $currentPage = 'blog';
    $htmlTitle = 'benjmacaro.dev - Post Not Found';
    $metaDescription = 'The requested blog post was not found.';
    $metaKeywords = 'blog, 404, not found';
    require_once __DIR__ . '/../../includes/page-header.php';
    ?>
    <h2 class="section-heading">Post Not Found</h2>
    <p class="body-text">
        Sorry, the blog post you're looking for doesn't exist or has been removed.
    </p>
    <p class="body-text">
        <a href="posts.php">&laquo; Back to Admin</a>
    </p>
    <?php
    require_once __DIR__ . '/../../includes/page-footer.php';
    exit;
}

// Set page variables (matching post.php structure)
$currentPage = 'blog';
$htmlTitle = 'benjmacaro.dev - ' . $post['title'];
$metaDescription = $post['excerpt'];
$metaKeywords = 'blog, ' . implode(', ', array_map(function($c) {
    return $c['name'];
}, $post['categories']));

require_once __DIR__ . '/../../includes/page-header.php';
?>

<style type="text/css">
    .preview-banner {
        padding: 15px 20px;
        margin-bottom: 20px;
        border: 2px solid;
    }
    .preview-banner-draft {
        background-color: #ffffcc;
        border-color: #cccc00;
        color: #666600;
    }
    .preview-banner-published {
        background-color: #ccffcc;
        border-color: #00cc00;
        color: #006600;
    }
    .preview-banner h3 {
        margin: 0 0 10px 0;
        font-size: 16px;
    }
    .preview-banner p {
        margin: 0;
        font-size: 14px;
    }
    .preview-actions {
        margin-top: 10px;
    }
    .preview-actions a {
        display: inline-block;
        padding: 4px 10px;
        background-color: #292f32;
        color: #fff;
        text-decoration: none;
        font-size: 12px;
        margin-right: 10px;
    }
    .preview-actions a:hover {
        background-color: #444;
    }
    .preview-actions a.btn-secondary {
        background-color: #666;
    }
</style>

<?php if ($post['status'] === 'draft'): ?>
    <div class="preview-banner preview-banner-draft">
        <h3>DRAFT PREVIEW</h3>
        <p>This post is not published and is only visible to administrators.</p>
        <div class="preview-actions">
            <a href="post-edit.php?id=<?php echo $post['id']; ?>">Edit Post</a>
            <a href="posts.php" class="btn-secondary">&laquo; Back to Posts</a>
        </div>
    </div>
<?php else: ?>
    <div class="preview-banner preview-banner-published">
        <h3>PUBLISHED POST PREVIEW</h3>
        <p>This post is live and visible to the public.</p>
        <div class="preview-actions">
            <a href="post-edit.php?id=<?php echo $post['id']; ?>">Edit Post</a>
            <a href="../post.php?slug=<?php echo urlencode($post['slug']); ?>" class="btn-secondary">View Live Post</a>
            <a href="posts.php" class="btn-secondary">&laquo; Back to Posts</a>
        </div>
    </div>
<?php endif; ?>

<p class="body-text" style="margin-bottom: 5px;">
    <a href="/blog/">&laquo; Back to Blog</a>
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
            $cat_links[] = '<a href="/blog/index.php?category=' . urlencode($cat['slug']) . '">'
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
    <a href="/blog/">&laquo; Back to Blog</a>
</p>

<?php if (!empty($post['categories'])): ?>
    <p class="text-secondary" style="font-size: 14px;">
        Categories:
        <?php
        $cat_links = [];
        foreach ($post['categories'] as $cat) {
            $cat_links[] = '<a href="/blog/index.php?category=' . urlencode($cat['slug']) . '">'
                . htmlspecialchars($cat['name']) . '</a>';
        }
        echo implode(', ', $cat_links);
        ?>
    </p>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/page-footer.php'; ?>
