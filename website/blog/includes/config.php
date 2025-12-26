<?php
/**
 * Blog Configuration
 * Web 1.0 Portfolio Site
 */

// Pagination
define('BLOG_POSTS_PER_PAGE', 10);

// Paths (relative to website root)
define('BLOG_IMAGE_DIR', __DIR__ . '/../images/');
define('BLOG_IMAGE_URL', '/blog/images/');
define('BLOG_IMAGE_PATH', BLOG_IMAGE_DIR); // Alias for tests

// Image upload settings
define('BLOG_IMAGE_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_IMAGE_SIZE', BLOG_IMAGE_MAX_SIZE); // Alias for tests
define('BLOG_IMAGE_ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('BLOG_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Admin session settings
define('ADMIN_SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('ADMIN_MAX_LOGIN_ATTEMPTS', 5);
define('ADMIN_LOCKOUT_TIME', 900); // 15 minutes

// Excerpt settings
define('BLOG_EXCERPT_LENGTH', 300);
