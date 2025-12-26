<?php
/**
 * Blog Listing Page
 * Web 1.0 Portfolio Site
 */

$currentPage = 'blog';
$htmlTitle = 'benjmacaro.dev - blog';
$metaDescription = 'Read my blog posts about technology, projects, and life.';
$metaKeywords = 'blog, tech, programming, personal, web development';

require_once __DIR__ . '/../includes/db-config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../includes/page-header.php';

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get category filter
$category_slug = isset($_GET['category']) ? $_GET['category'] : null;
$category = null;
if ($category_slug) {
    $category = blog_get_category_by_slug($mysqli, $category_slug);
}

// Get posts
$result = blog_get_posts($mysqli, $page, BLOG_POSTS_PER_PAGE, $category_slug, 'published');
$posts = $result['posts'];
$total_pages = $result['pages'];

// Get all categories for sidebar
$all_categories = blog_get_categories($mysqli, true);
?>

<h2 class="section-heading">Blog</h2>

<?php if ($category): ?>
    <p class="body-text">
        Showing posts in category: <strong><?php echo htmlspecialchars($category['name']); ?></strong>
        | <a href="index.php">Show all posts</a>
    </p>
<?php endif; ?>

<?php if (empty($posts)): ?>
    <p class="body-text">
        <em>No posts yet. Check back soon!</em>
    </p>
<?php else: ?>
    <?php foreach ($posts as $post): ?>
        <table class="blog-post-entry" width="100%" cellpadding="15" cellspacing="0" style="margin-bottom: 20px; border: 2px solid #292f32;">
            <tr>
                <td class="text-primary">
                    <h3 style="margin: 0 0 10px 0;">
                        <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>">
                            <?php echo htmlspecialchars($post['title']); ?>
                        </a>
                    </h3>
                    <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
                        <?php echo blog_format_date($post['published_at'] ?? $post['created_at']); ?>
                        <?php if (!empty($post['categories'])): ?>
                            |
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
                    <p class="body-text" style="margin: 0;">
                        <?php echo htmlspecialchars($post['excerpt']); ?>
                    </p>
                    <p style="margin: 10px 0 0 0;">
                        <a href="post.php?slug=<?php echo urlencode($post['slug']); ?>">Read more &raquo;</a>
                    </p>
                </td>
            </tr>
        </table>
    <?php endforeach; ?>

    <?php if ($total_pages > 1): ?>
        <div class="blog-pagination" align="center" style="margin-top: 20px;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?><?php echo $category_slug ? '&category=' . urlencode($category_slug) : ''; ?>">&laquo; Newer</a>
            <?php endif; ?>

            <span style="margin: 0 15px;">
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
            </span>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?><?php echo $category_slug ? '&category=' . urlencode($category_slug) : ''; ?>">Older &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (!empty($all_categories)): ?>
    <br>
    <h3 class="section-heading" style="font-size: 20px;">Categories</h3>
    <ul class="body-text">
        <?php foreach ($all_categories as $cat): ?>
            <li>
                <a href="index.php?category=<?php echo urlencode($cat['slug']); ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
                (<?php echo $cat['post_count']; ?>)
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/page-footer.php'; ?>
