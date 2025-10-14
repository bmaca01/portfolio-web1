<?php
// Database configuration for Web 1.0 Guestbook
// Secure credential management - NO HARDCODED PASSWORDS

// Detect environment
$environment = getenv('ENVIRONMENT') ?: 'production';

// Configure based on environment
if ($environment === 'development') {
    // Development: Read from environment variables set by docker-compose
    $db_host = getenv('DB_HOST') ?: 'mysql';
    $db_name = getenv('DB_NAME') ?: 'guestbook';
    $db_user = getenv('DB_USER') ?: 'guestbook_user';
    $db_pass = getenv('DB_PASSWORD') ?: 'guestbook_dev_pass';
    $db_port = getenv('DB_PORT') ?: 3306;
} else {
    // Production: Include external credentials file (NOT in git)
    $credentials_file = '/home/abc/web1-site1-config/db-credentials.php';

    if (!file_exists($credentials_file)) {
        error_log("Production database credentials file not found: $credentials_file");
        die("<p style='color: red;'>Database configuration error. Please contact the administrator.</p>");
    }

    // Include the external credentials file
    // This file defines: $db_host, $db_name, $db_user, $db_pass, $db_port
    require_once $credentials_file;
}

// Create MySQL connection
try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

    // Check connection
    if ($mysqli->connect_error) {
        error_log("Database connection failed: " . $mysqli->connect_error);
        throw new Exception("Database connection failed");
    }

    // Set charset to UTF-8
    if (!$mysqli->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $mysqli->error);
    }

} catch (Exception $e) {
    // Log error but don't expose details to user
    error_log("Database error: " . $e->getMessage());
    die("<p style='color: red;'>Sorry, the guestbook is temporarily unavailable. Please try again later.</p>");
}
?>
