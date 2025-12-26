<?php
/**
 * Admin Image Library
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/image-functions.php';
require_once __DIR__ . '/auth.php';
admin_check_auth();

// Handle image deletion BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    if (admin_validate_csrf($_POST['csrf_token'] ?? '')) {
        $image_id = (int)$_POST['delete_image'];
        image_delete($mysqli, $image_id);
    }
    header('Location: images.php?deleted=1');
    exit;
}

// Now include header (after all redirects are handled)
$admin_page = 'images';
$page_title = 'Image Library';
require_once __DIR__ . '/header.php';

// Get images
$images = image_get_all($mysqli, 100, 0);
$total_images = image_get_count($mysqli);

$success = isset($_GET['success']) ? $_GET['success'] : '';
$deleted = isset($_GET['deleted']);
?>

<?php if ($success === 'uploaded'): ?>
    <div class="success-message">Image uploaded successfully!</div>
<?php endif; ?>

<?php if ($deleted): ?>
    <div class="success-message">Image deleted successfully!</div>
<?php endif; ?>

<h2>Image Library</h2>

<div style="margin-bottom: 20px;">
    <form method="post" action="image-upload.php" enctype="multipart/form-data"
          style="display: inline-block; border: 2px dashed #292f32; padding: 15px; background: #f9f9f9;">
        <?php admin_csrf_field(); ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
        <button type="submit" class="btn">Upload Image</button>
        <br><small>Max 5MB. JPG, PNG, GIF, WebP only.</small>
    </form>
</div>

<p>Total images: <?php echo $total_images; ?></p>

<?php if (empty($images)): ?>
    <p><em>No images uploaded yet.</em></p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th style="width: 100px;">Preview</th>
                <th>Details</th>
                <th>Markdown</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($images as $image): ?>
                <tr>
                    <td style="text-align: center;">
                        <a href="<?php echo htmlspecialchars($image['url']); ?>" target="_blank">
                            <img src="<?php echo htmlspecialchars($image['url']); ?>"
                                 alt="<?php echo htmlspecialchars($image['original_name']); ?>"
                                 style="max-width: 80px; max-height: 80px; border: 1px solid #ccc;">
                        </a>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($image['original_name']); ?></strong><br>
                        <small>
                            <?php echo $image['width']; ?> x <?php echo $image['height']; ?> px<br>
                            <?php echo image_format_size($image['file_size']); ?><br>
                            <?php echo blog_format_date($image['uploaded_at'], 'M j, Y g:i A'); ?>
                        </small>
                    </td>
                    <td>
                        <input type="text" value="<?php echo htmlspecialchars($image['markdown']); ?>"
                               readonly style="width: 100%; font-family: monospace; font-size: 11px;"
                               onclick="this.select();">
                        <small>Click to select, then copy.</small>
                    </td>
                    <td class="actions">
                        <a href="<?php echo htmlspecialchars($image['url']); ?>"
                           class="btn btn-small btn-secondary" target="_blank">View</a>
                        <form method="post" action="" style="display: inline;">
                            <?php admin_csrf_field(); ?>
                            <input type="hidden" name="delete_image" value="<?php echo $image['id']; ?>">
                            <button type="submit" class="btn btn-small btn-danger"
                                    onclick="return confirm('Delete this image?');">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border: 1px solid #ccc;">
    <h3 style="margin-top: 0;">How to use images in posts</h3>
    <p>Copy the Markdown code from the table above and paste it into your post content.</p>
    <p>Example: <code>![Image description](<?php echo BLOG_IMAGE_URL; ?>example.jpg)</code></p>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
