<?php
/**
 * Admin Categories List
 * Web 1.0 Portfolio Site - Blog Admin
 */

$admin_page = 'categories';
$page_title = 'Categories';

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/header.php';

// Get all categories with post counts
$categories = blog_get_categories($mysqli, true);

// Check for success message
$success = isset($_GET['success']) ? $_GET['success'] : '';
?>

<?php if ($success === 'created'): ?>
    <div class="success-message">Category created successfully!</div>
<?php elseif ($success === 'updated'): ?>
    <div class="success-message">Category updated successfully!</div>
<?php elseif ($success === 'deleted'): ?>
    <div class="success-message">Category deleted successfully!</div>
<?php endif; ?>

<h2>Categories</h2>

<?php if (empty($categories)): ?>
    <p>No categories yet. <a href="category-edit.php">Create your first category!</a></p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Posts</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                    <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                    <td><?php echo htmlspecialchars($cat['description'] ?? ''); ?></td>
                    <td><?php echo $cat['post_count']; ?></td>
                    <td class="actions">
                        <a href="category-edit.php?id=<?php echo $cat['id']; ?>" class="btn btn-small">Edit</a>
                        <a href="../category.php?slug=<?php echo urlencode($cat['slug']); ?>"
                           class="btn btn-small btn-secondary" target="_blank">View</a>
                        <a href="category-delete.php?id=<?php echo $cat['id']; ?>"
                           class="btn btn-small btn-danger">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div style="margin-top: 20px;">
    <a href="category-edit.php" class="btn">+ New Category</a>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
