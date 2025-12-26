<?php
/**
 * Admin Category Delete
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
admin_check_auth();

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$category_id) {
    header('Location: categories.php');
    exit;
}

// Get category
$category = blog_get_category_by_id($mysqli, $category_id);
if (!$category) {
    header('Location: categories.php');
    exit;
}

// Get post count
$stmt = $mysqli->prepare(
    "SELECT COUNT(*) as count FROM blog_post_categories WHERE category_id = ?"
);
$stmt->bind_param('i', $category_id);
$stmt->execute();
$post_count = $stmt->get_result()->fetch_assoc()['count'];

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (admin_validate_csrf($_POST['csrf_token'] ?? '')) {
        if (blog_delete_category($mysqli, $category_id)) {
            header('Location: categories.php?success=deleted');
            exit;
        }
    }
    header('Location: categories.php');
    exit;
}

$admin_page = 'categories';
$page_title = 'Delete Category';
require_once __DIR__ . '/header.php';
?>

<h2>Delete Category</h2>

<div style="background-color: #fff3cd; border: 2px solid #ffc107; padding: 20px; margin-bottom: 20px;">
    <p><strong>Are you sure you want to delete this category?</strong></p>
    <p>
        Name: <strong><?php echo htmlspecialchars($category['name']); ?></strong><br>
        Slug: <?php echo htmlspecialchars($category['slug']); ?>
    </p>
    <?php if ($post_count > 0): ?>
        <p style="color: #cc6600;">
            <strong>Warning:</strong> This category is assigned to <?php echo $post_count; ?> post(s).
            The posts will NOT be deleted, but they will be unlinked from this category.
        </p>
    <?php endif; ?>
    <p><em>This action cannot be undone.</em></p>
</div>

<form method="post" action="">
    <?php admin_csrf_field(); ?>
    <button type="submit" class="btn btn-danger">Yes, Delete Category</button>
    <a href="categories.php" class="btn btn-secondary">Cancel</a>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
