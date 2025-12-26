<?php
/**
 * Admin Post Edit/Create
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/auth.php';
admin_check_auth();

// Determine if editing or creating
$post_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$is_edit = $post_id !== null;

// Initialize variables
$post = null;
$post_categories = [];
$error_message = '';

// Load existing post for editing
if ($is_edit) {
    $post = blog_get_post_by_id($mysqli, $post_id);
    if ($post) {
        $post_categories = array_map(function($c) { return $c['id']; }, $post['categories']);
    }
}

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!admin_validate_csrf($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        // Collect form data
        $data = [
            'title' => trim($_POST['title'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'content_markdown' => $_POST['content_markdown'] ?? '',
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'status' => $_POST['status'] ?? 'draft',
            'published_at' => !empty($_POST['published_at']) ? $_POST['published_at'] : null,
            'categories' => isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : []
        ];

        // Validation
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        } elseif (strlen($data['title']) > 255) {
            $errors[] = 'Title must be 255 characters or less.';
        }

        if (empty($data['slug'])) {
            $data['slug'] = blog_generate_slug($data['title']);
        }
        $data['slug'] = blog_generate_slug($data['slug']); // Sanitize

        if (!blog_is_slug_unique($mysqli, $data['slug'], $post_id)) {
            $errors[] = 'Slug is already in use. Please choose a different one.';
        }

        if (empty($data['content_markdown'])) {
            $errors[] = 'Content is required.';
        }

        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
            // Preserve form data
            $post = $data;
            $post['id'] = $post_id;
            $post_categories = $data['categories'];
        } else {
            // Save post
            $result = blog_save_post($mysqli, $data, $post_id);
            if ($result) {
                $redirect = $is_edit ? 'posts.php?success=updated' : 'posts.php?success=created';
                header('Location: ' . $redirect);
                exit;
            } else {
                $error_message = 'Failed to save post. Please try again.';
            }
        }
    }
}

// Now include header (after all redirects are handled)
$admin_page = $is_edit ? 'posts' : 'post-new';
$page_title = $is_edit ? 'Edit Post' : 'New Post';
require_once __DIR__ . '/header.php';

// Check if post exists (for edit mode)
if ($is_edit && !$post) {
    echo '<div class="error-message">Post not found.</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

// Get all categories for checkboxes
$all_categories = blog_get_categories($mysqli);
?>

<h2><?php echo $is_edit ? 'Edit Post' : 'New Post'; ?></h2>

<?php if (!empty($error_message)): ?>
    <div class="error-message"><?php echo $error_message; ?></div>
<?php endif; ?>

<form method="post" action="">
    <?php admin_csrf_field(); ?>

    <div class="form-group">
        <label for="title">Title *</label>
        <input type="text" id="title" name="title"
               value="<?php echo htmlspecialchars($post['title'] ?? ''); ?>"
               required maxlength="255">
    </div>

    <div class="form-group">
        <label for="slug">Slug</label>
        <input type="text" id="slug" name="slug"
               value="<?php echo htmlspecialchars($post['slug'] ?? ''); ?>"
               maxlength="255">
        <small>URL-friendly name. Leave blank to auto-generate from title.</small>
    </div>

    <div class="form-group">
        <label for="content_markdown">Content (Markdown) *</label>
        <textarea id="content_markdown" name="content_markdown" required><?php
            echo htmlspecialchars($post['content_markdown'] ?? '');
        ?></textarea>
        <small>
            Write in <a href="https://www.markdownguide.org/basic-syntax/" target="_blank">Markdown</a>.
            Use <code>![alt text](url)</code> to insert images.
            <a href="images.php" target="_blank">Open Image Library</a>
        </small>
    </div>

    <div class="form-group">
        <label for="excerpt">Excerpt</label>
        <input type="text" id="excerpt" name="excerpt"
               value="<?php echo htmlspecialchars($post['excerpt'] ?? ''); ?>"
               maxlength="500">
        <small>Brief summary. Leave blank to auto-generate from content.</small>
    </div>

    <div class="form-group">
        <label>Categories</label>
        <div class="checkbox-group">
            <?php foreach ($all_categories as $cat): ?>
                <label>
                    <input type="checkbox" name="categories[]"
                           value="<?php echo $cat['id']; ?>"
                           <?php echo in_array($cat['id'], $post_categories) ? 'checked' : ''; ?>>
                    <?php echo htmlspecialchars($cat['name']); ?>
                </label>
            <?php endforeach; ?>
            <?php if (empty($all_categories)): ?>
                <em>No categories yet. <a href="categories.php">Create some!</a></em>
            <?php endif; ?>
        </div>
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="draft" <?php echo ($post['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>
                Draft
            </option>
            <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>
                Published
            </option>
        </select>
    </div>

    <div class="form-group">
        <label for="published_at">Publish Date</label>
        <input type="datetime-local" id="published_at" name="published_at"
               value="<?php
                   $pub_date = $post['published_at'] ?? '';
                   if ($pub_date) {
                       echo date('Y-m-d\TH:i', strtotime($pub_date));
                   }
               ?>">
        <small>Leave blank to use current time when publishing.</small>
    </div>

    <div style="margin-top: 20px;">
        <button type="submit" class="btn"><?php echo $is_edit ? 'Update Post' : 'Create Post'; ?></button>
        <a href="posts.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<script>
// Auto-generate slug from title if slug is empty
document.getElementById('title').addEventListener('blur', function() {
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
