<?php
/**
 * Admin Image Upload Handler
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/image-functions.php';
require_once __DIR__ . '/auth.php';
admin_check_auth();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: images.php');
    exit;
}

// Validate CSRF
if (!admin_validate_csrf($_POST['csrf_token'] ?? '')) {
    header('Location: images.php?error=csrf');
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
    header('Location: images.php?error=nofile');
    exit;
}

// Upload the image
$result = image_upload($mysqli, $_FILES['image']);

if ($result['success']) {
    // Check if this is an AJAX request
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    // Regular form submission - redirect
    header('Location: images.php?success=uploaded');
    exit;
} else {
    // Error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode($result);
        exit;
    }

    // Store error in session and redirect
    session_start();
    $_SESSION['upload_error'] = $result['message'];
    header('Location: images.php?error=upload');
    exit;
}
