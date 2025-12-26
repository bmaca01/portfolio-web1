<?php
/**
 * Blog Category Archive Page
 * Web 1.0 Portfolio Site
 */

require_once __DIR__ . '/../includes/db-config.php';
require_once __DIR__ . '/includes/functions.php';

// Get category by slug
$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$category = blog_get_category_by_slug($mysqli, $slug);

if (!$category) {
    // Category not found - redirect to blog
    header('Location: index.php');
    exit;
}

// Redirect to main blog with category filter
header('Location: index.php?category=' . urlencode($category['slug']));
exit;
