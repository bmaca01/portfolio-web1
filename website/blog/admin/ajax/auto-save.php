<?php
/**
 * Auto-Save AJAX Endpoint
 * Web 1.0 Portfolio Site - Blog Admin
 *
 * Accepts POST requests with JSON body:
 * {
 *   "post_id": int|null,
 *   "title": string,
 *   "content_markdown": string,
 *   "excerpt": string
 * }
 *
 * Returns JSON:
 * {
 *   "success": bool,
 *   "post_id": int,
 *   "saved_at": string (Y-m-d H:i:s),
 *   "error": string (only if success=false)
 * }
 */

require_once __DIR__ . '/../../includes/autosave-functions.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check authentication
if (!admin_is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Validate session fingerprint
if (!admin_validate_fingerprint()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Session invalid']);
    exit;
}

// Check session timeout
if (admin_check_session_timeout()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Session expired']);
    exit;
}

// Validate CSRF token from header
$csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!admin_validate_csrf($csrf_token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

// Update session activity
admin_update_activity();

// Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
if ($input === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Extract and validate fields
$post_id = isset($input['post_id']) && $input['post_id'] !== null ? (int)$input['post_id'] : null;
$title = trim($input['title'] ?? '');
$content_markdown = $input['content_markdown'] ?? '';
$excerpt = trim($input['excerpt'] ?? '');

// Minimal validation (auto-save should be lenient)
if (empty($title) && empty($content_markdown)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nothing to save']);
    exit;
}

// Perform auto-save
$result = blog_auto_save($mysqli, [
    'title' => $title,
    'content_markdown' => $content_markdown,
    'excerpt' => $excerpt,
], $post_id);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'post_id' => $result['post_id'],
        'saved_at' => date('Y-m-d H:i:s'),
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $result['error'] ?? 'Save failed',
    ]);
}
