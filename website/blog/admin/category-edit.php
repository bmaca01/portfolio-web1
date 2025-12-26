<?php
/**
 * Admin Category Edit/Create
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
admin_check_auth();

// Determine if editing or creating
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$is_edit = $category_id !== null;

// Initialize variables
$category = null;
$error_message = '';

// Load existing category for editing
if ($is_edit) {
    $category = blog_get_category_by_id($mysqli, $category_id);
}

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!admin_validate_csrf($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        // Collect form data
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? '')
        ];

        // Validation
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Name is required.';
        } elseif (strlen($data['name']) > 100) {
            $errors[] = 'Name must be 100 characters or less.';
        }

        if (empty($data['slug'])) {
            $data['slug'] = blog_generate_slug($data['name']);
        }
        $data['slug'] = blog_generate_slug($data['slug']); // Sanitize

        // Check slug uniqueness
        $sql = "SELECT id FROM blog_categories WHERE slug = ?";
        $params = [$data['slug']];
        $types = 's';
        if ($is_edit) {
            $sql .= " AND id != ?";
            $params[] = $category_id;
            $types .= 'i';
        }
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = 'Slug is already in use.';
        }

        if (strlen($data['description']) > 255) {
            $errors[] = 'Description must be 255 characters or less.';
        }

        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
            // Preserve form data
            $category = $data;
            $category['id'] = $category_id;
        } else {
            // Save category
            $result = blog_save_category($mysqli, $data, $category_id);
            if ($result) {
                $redirect = $is_edit ? 'categories.php?success=updated' : 'categories.php?success=created';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error_message = 'Failed to save category. Please try again.';
            }
        }
    }
}

// Now include header (after all redirects are handled)
$admin_page = 'categories';
$page_title = $is_edit ? 'Edit Category' : 'New Category';
require_once __DIR__ . '/header.php';

// Check if category exists (for edit mode)
if ($is_edit && !$category) {
    echo '<div class="error-message">Category not found.</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}
?>

<h2><?php echo $is_edit ? 'Edit Category' : 'New Category'; ?></h2>

<?php if (!empty($error_message)): ?>
    <div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<form method="post" action="">
    <?php admin_csrf_field(); ?>

    <div class="form-group">
        <label for="name">Name *</label>
        <input type="text" id="name" name="name"
               value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>"
               required maxlength="100">
    </div>

    <div class="form-group">
        <label for="slug">Slug</label>
        <input type="text" id="slug" name="slug"
               value="<?php echo htmlspecialchars($category['slug'] ?? ''); ?>"
               maxlength="100">
        <small>URL-friendly name. Leave blank to auto-generate from name.</small>
    </div>

    <div class="form-group">
        <label for="description">Description</label>
        <input type="text" id="description" name="description"
               value="<?php echo htmlspecialchars($category['description'] ?? ''); ?>"
               maxlength="255">
        <small>Brief description of this category.</small>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" class="btn"><?php echo $is_edit ? 'Update Category' : 'Create Category'; ?></button>
        <a href="categories.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
// Auto-generate slug from name if slug is empty
document.getElementById('name').addEventListener('blur', function() {
    var slugField = document.getElementById('slug');
    if (slugField.value === '') {
        var slug = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .substring(0, 100);
        slugField.value = slug;
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
