<?php
/**
 * Auto-Save Helper Functions
 * Web 1.0 Portfolio Site
 */

require_once __DIR__ . '/functions.php';

/**
 * Auto-save a blog post (create or update)
 *
 * @param mysqli $mysqli Database connection
 * @param array $data Post data (title, content_markdown, excerpt)
 * @param int|null $post_id Post ID for update, null for new draft
 * @return array ['success' => bool, 'post_id' => int, 'error' => string]
 */
function blog_auto_save($mysqli, $data, $post_id = null) {
    // Parse markdown to HTML for preview purposes
    $content_html = blog_parse_markdown($data['content_markdown']);

    // Generate excerpt if not provided
    $excerpt = !empty($data['excerpt'])
        ? $data['excerpt']
        : blog_get_excerpt($data['content_markdown']);

    if ($post_id === null) {
        // Create new draft
        return blog_auto_save_create($mysqli, $data, $content_html, $excerpt);
    } else {
        // Update existing post
        return blog_auto_save_update($mysqli, $data, $content_html, $excerpt, $post_id);
    }
}

/**
 * Create a new draft via auto-save
 *
 * @param mysqli $mysqli Database connection
 * @param array $data Post data
 * @param string $content_html Parsed HTML content
 * @param string $excerpt Generated or provided excerpt
 * @return array Result with success status and post_id
 */
function blog_auto_save_create($mysqli, $data, $content_html, $excerpt) {
    // Generate a temporary slug (will be finalized on manual save)
    $temp_slug = 'draft-' . date('YmdHis') . '-' . bin2hex(random_bytes(4));

    $stmt = $mysqli->prepare(
        "INSERT INTO blog_posts
         (slug, title, content_markdown, content_html, excerpt, status)
         VALUES (?, ?, ?, ?, ?, 'draft')"
    );

    if (!$stmt) {
        return ['success' => false, 'error' => 'Database error'];
    }

    $title = $data['title'];
    $content_markdown = $data['content_markdown'];

    $stmt->bind_param('sssss',
        $temp_slug,
        $title,
        $content_markdown,
        $content_html,
        $excerpt
    );

    if ($stmt->execute()) {
        return [
            'success' => true,
            'post_id' => $mysqli->insert_id,
        ];
    }

    return ['success' => false, 'error' => 'Insert failed'];
}

/**
 * Update existing post via auto-save
 * Note: Never changes status or published_at
 *
 * @param mysqli $mysqli Database connection
 * @param array $data Post data
 * @param string $content_html Parsed HTML content
 * @param string $excerpt Generated or provided excerpt
 * @param int $post_id Post ID to update
 * @return array Result with success status
 */
function blog_auto_save_update($mysqli, $data, $content_html, $excerpt, $post_id) {
    // Verify post exists
    $check = $mysqli->prepare("SELECT id, status FROM blog_posts WHERE id = ?");
    $check->bind_param('i', $post_id);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if (!$existing) {
        return ['success' => false, 'error' => 'Post not found'];
    }

    // Update content fields only (status preserved)
    $stmt = $mysqli->prepare(
        "UPDATE blog_posts
         SET title = ?, content_markdown = ?, content_html = ?, excerpt = ?
         WHERE id = ?"
    );

    if (!$stmt) {
        return ['success' => false, 'error' => 'Database error'];
    }

    $title = $data['title'];
    $content_markdown = $data['content_markdown'];

    $stmt->bind_param('ssssi',
        $title,
        $content_markdown,
        $content_html,
        $excerpt,
        $post_id
    );

    if ($stmt->execute()) {
        return [
            'success' => true,
            'post_id' => $post_id,
        ];
    }

    return ['success' => false, 'error' => 'Update failed'];
}

/**
 * Get post data needed for auto-save conflict detection
 *
 * @param mysqli $mysqli Database connection
 * @param int $post_id Post ID
 * @return array|null Post data or null if not found
 */
function blog_get_post_for_autosave($mysqli, $post_id) {
    $stmt = $mysqli->prepare(
        "SELECT id, title, content_markdown, excerpt, updated_at
         FROM blog_posts WHERE id = ?"
    );
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
