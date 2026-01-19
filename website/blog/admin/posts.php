<?php
/**
 * Admin Posts List
 * Web 1.0 Portfolio Site - Blog Admin
 */

$admin_page = 'posts';
$page_title = 'Posts';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/header.php';

// Get page number
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get posts (including drafts for admin)
$result = blog_get_posts($mysqli, $page, BLOG_POSTS_PER_PAGE, null, 'all');
$posts = $result['posts'];
$total_pages = $result['pages'];

// Check for success message
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<?php if ($success === 'created'): ?>
    <div class="success-message">Post created successfully!</div>
<?php elseif ($success === 'updated'): ?>
    <div class="success-message">Post updated successfully!</div>
<?php elseif ($success === 'deleted'): ?>
    <div class="success-message">Post deleted successfully!</div>
<?php endif; ?>

<h2>All Posts</h2>

<?php if (empty($posts)): ?>
    <p>No posts yet. <a href="post-edit.php">Create your first post!</a></p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Status</th>
                <th>Categories</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($posts as $post): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($post['title']); ?></strong><br>
                        <small style="color: #666;">/blog/post.php?slug=<?php echo htmlspecialchars($post['slug']); ?></small>
                    </td>
                    <td>
                        <?php if ($post['status'] === 'published'): ?>
                            <span class="status-published">Published</span>
                        <?php else: ?>
                            <span class="status-draft">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $cat_names = array_map(function($c) {
                            return htmlspecialchars($c['name']);
                        }, $post['categories']);
                        echo implode(', ', $cat_names) ?: '<em>None</em>';
                        ?>
                    </td>
                    <td>
                        <?php
                        $date = $post['published_at'] ?? $post['created_at'];
                        echo blog_format_date($date, 'M j, Y');
                        ?>
                    </td>
                    <td class="actions">
                        <a href="post-edit.php?id=<?php echo $post['id']; ?>" class="btn btn-small">Edit</a>
                        <?php if ($post['status'] === 'published'): ?>
                            <a href="../post.php?slug=<?php echo urlencode($post['slug']); ?>"
                               class="btn btn-small btn-secondary" target="_blank">View</a>
                        <?php else: ?>
                            <a href="preview.php?id=<?php echo $post['id']; ?>"
                               class="btn btn-small btn-secondary" target="_blank">Preview</a>
                        <?php endif; ?>
                        <a href="post-delete.php?id=<?php echo $post['id']; ?>"
                           class="btn btn-small btn-danger">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="btn btn-small">&laquo; Previous</a>
            <?php endif; ?>

            <span style="margin: 0 10px;">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="btn btn-small">Next &raquo;</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div style="margin-top: 20px;">
    <a href="post-edit.php" class="btn">+ New Post</a>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
