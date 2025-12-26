<?php
/**
 * Admin Post Delete
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
admin_check_auth();

$post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$post_id) {
    header('Location: posts.php');
    exit;
}

// Get post
$post = blog_get_post_by_id($mysqli, $post_id);
if (!$post) {
    header('Location: posts.php');
    exit;
}

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (admin_validate_csrf($_POST['csrf_token'] ?? '')) {
        if (blog_delete_post($mysqli, $post_id)) {
            header('Location: posts.php?success=deleted');
            exit;
        }
    }
    header('Location: posts.php');
    exit;
}

$admin_page = 'posts';
$page_title = 'Delete Post';
require_once __DIR__ . '/header.php';
?>

<h2>Delete Post</h2>

<div style="background-color: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin-bottom: 20px;">
    <p><strong>Are you sure you want to delete this post?</strong></p>
    <p>
        Title: <strong><?php echo htmlspecialchars($post['title']); ?></strong><br>
        Slug: <?php echo htmlspecialchars($post['slug']); ?><br>
        Status: <?php echo htmlspecialchars($post['status']); ?>
    </p>
    <p><em>This action cannot be undone.</em></p>
</div>

<form method="post" action="">
    <?php admin_csrf_field(); ?>
    <button type="submit" class="btn btn-danger">Yes, Delete Post</button>
    <a href="posts.php" class="btn btn-secondary">Cancel</a>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
