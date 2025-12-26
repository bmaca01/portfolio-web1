<?php
/**
 * Blog Helper Functions
 * Web 1.0 Portfolio Site
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../lib/Parsedown.php';

/**
 * Get paginated list of blog posts
 *
 * @param mysqli $mysqli Database connection
 * @param int $page Page number (1-indexed)
 * @param int $per_page Posts per page
 * @param string|null $category_slug Filter by category slug
 * @param string $status Filter by status ('published', 'draft', or 'all')
 * @return array ['posts' => array, 'total' => int, 'pages' => int]
 */
function blog_get_posts($mysqli, $page = 1, $per_page = BLOG_POSTS_PER_PAGE, $category_slug = null, $status = 'published') {
    $offset = ($page - 1) * $per_page;
    $params = [];
    $types = '';

    // Build WHERE clause
    $where = [];
    if ($status === 'published') {
        $where[] = "p.status = 'published'";
    } elseif ($status === 'draft') {
        $where[] = "p.status = 'draft'";
    }

    // Category filter
    $join = '';
    if ($category_slug !== null) {
        $join = "INNER JOIN blog_post_categories pc ON p.id = pc.post_id
                 INNER JOIN blog_categories c ON pc.category_id = c.id";
        $where[] = "c.slug = ?";
        $params[] = $category_slug;
        $types .= 's';
    }

    $where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    // Get total count
    $count_sql = "SELECT COUNT(DISTINCT p.id) as total
                  FROM blog_posts p
                  $join
                  $where_sql";

    if (!empty($params)) {
        $stmt = $mysqli->prepare($count_sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $mysqli->query($count_sql);
    }
    $total = $result->fetch_assoc()['total'];
    $pages = ceil($total / $per_page);

    // Get posts
    $sql = "SELECT DISTINCT p.id, p.slug, p.title, p.excerpt, p.status,
                   p.created_at, p.updated_at, p.published_at
            FROM blog_posts p
            $join
            $where_sql
            ORDER BY COALESCE(p.published_at, p.created_at) DESC
            LIMIT ? OFFSET ?";

    $params[] = $per_page;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $posts = [];
    while ($row = $result->fetch_assoc()) {
        $row['categories'] = blog_get_post_categories($mysqli, $row['id']);
        $posts[] = $row;
    }

    return [
        'posts' => $posts,
        'total' => $total,
        'pages' => $pages,
        'current_page' => $page
    ];
}

/**
 * Get a single post by slug
 *
 * @param mysqli $mysqli Database connection
 * @param string $slug Post slug
 * @param bool $include_drafts Include draft posts
 * @return array|null Post data or null if not found
 */
function blog_get_post_by_slug($mysqli, $slug, $include_drafts = false) {
    $sql = "SELECT * FROM blog_posts WHERE slug = ?";
    if (!$include_drafts) {
        $sql .= " AND status = 'published'";
    }

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if ($post) {
        $post['categories'] = blog_get_post_categories($mysqli, $post['id']);
    }

    return $post;
}

/**
 * Get a single post by ID
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Post ID
 * @return array|null Post data or null if not found
 */
function blog_get_post_by_id($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();

    if ($post) {
        $post['categories'] = blog_get_post_categories($mysqli, $post['id']);
    }

    return $post;
}

/**
 * Get all categories
 *
 * @param mysqli $mysqli Database connection
 * @param bool $with_counts Include post counts
 * @return array List of categories
 */
function blog_get_categories($mysqli, $with_counts = false) {
    if ($with_counts) {
        $sql = "SELECT c.*, COUNT(pc.post_id) as post_count
                FROM blog_categories c
                LEFT JOIN blog_post_categories pc ON c.id = pc.category_id
                LEFT JOIN blog_posts p ON pc.post_id = p.id AND p.status = 'published'
                GROUP BY c.id
                ORDER BY c.name ASC";
    } else {
        $sql = "SELECT * FROM blog_categories ORDER BY name ASC";
    }

    $result = $mysqli->query($sql);
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    return $categories;
}

/**
 * Get category by slug
 *
 * @param mysqli $mysqli Database connection
 * @param string $slug Category slug
 * @return array|null Category data or null
 */
function blog_get_category_by_slug($mysqli, $slug) {
    $stmt = $mysqli->prepare("SELECT * FROM blog_categories WHERE slug = ?");
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get category by ID
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Category ID
 * @return array|null Category data or null
 */
function blog_get_category_by_id($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT * FROM blog_categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get categories for a specific post
 *
 * @param mysqli $mysqli Database connection
 * @param int $post_id Post ID
 * @return array List of categories
 */
function blog_get_post_categories($mysqli, $post_id) {
    $stmt = $mysqli->prepare(
        "SELECT c.* FROM blog_categories c
         INNER JOIN blog_post_categories pc ON c.id = pc.category_id
         WHERE pc.post_id = ?
         ORDER BY c.name ASC"
    );
    $stmt->bind_param('i', $post_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }

    return $categories;
}

/**
 * Generate URL-friendly slug from title
 *
 * @param string $title Post title
 * @return string URL-friendly slug
 */
function blog_generate_slug($title) {
    // Convert to lowercase
    $slug = strtolower($title);
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    // Limit length
    $slug = substr($slug, 0, 100);

    return $slug;
}

/**
 * Check if slug is unique
 *
 * @param mysqli $mysqli Database connection
 * @param string $slug Slug to check
 * @param int|null $exclude_id Post ID to exclude (for updates)
 * @return bool True if unique
 */
function blog_is_slug_unique($mysqli, $slug, $exclude_id = null) {
    $sql = "SELECT id FROM blog_posts WHERE slug = ?";
    $params = [$slug];
    $types = 's';

    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
        $types .= 'i';
    }

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows === 0;
}

/**
 * Parse Markdown to HTML
 *
 * @param string $markdown Markdown content
 * @return string HTML output
 */
function blog_parse_markdown($markdown) {
    $parsedown = new Parsedown();
    $parsedown->setSafeMode(true); // Escape HTML in markdown
    return $parsedown->text($markdown);
}

/**
 * Generate excerpt from content
 *
 * @param string $content Full content (markdown or HTML)
 * @param int $length Maximum length
 * @return string Excerpt
 */
function blog_get_excerpt($content, $length = BLOG_EXCERPT_LENGTH) {
    // Strip markdown/HTML formatting
    $text = strip_tags($content);
    // Remove extra whitespace
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);

    if (strlen($text) <= $length) {
        return $text;
    }

    // Cut at word boundary
    $text = substr($text, 0, $length);
    $last_space = strrpos($text, ' ');
    if ($last_space !== false) {
        $text = substr($text, 0, $last_space);
    }

    return $text . '...';
}

/**
 * Save a blog post (insert or update)
 *
 * @param mysqli $mysqli Database connection
 * @param array $data Post data
 * @param int|null $id Post ID for update, null for insert
 * @return int|false Post ID on success, false on failure
 */
function blog_save_post($mysqli, $data, $id = null) {
    // Parse markdown to HTML
    $content_html = blog_parse_markdown($data['content_markdown']);

    // Generate excerpt if not provided
    $excerpt = !empty($data['excerpt']) ? $data['excerpt'] : blog_get_excerpt($data['content_markdown']);

    // Set published_at if publishing
    $published_at = null;
    if ($data['status'] === 'published') {
        if (!empty($data['published_at'])) {
            $published_at = $data['published_at'];
        } else {
            $published_at = date('Y-m-d H:i:s');
        }
    }

    if ($id === null) {
        // Insert
        $stmt = $mysqli->prepare(
            "INSERT INTO blog_posts (slug, title, content_markdown, content_html, excerpt, status, published_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssssss',
            $data['slug'],
            $data['title'],
            $data['content_markdown'],
            $content_html,
            $excerpt,
            $data['status'],
            $published_at
        );

        if ($stmt->execute()) {
            $id = $mysqli->insert_id;
        } else {
            return false;
        }
    } else {
        // Update
        $stmt = $mysqli->prepare(
            "UPDATE blog_posts
             SET slug = ?, title = ?, content_markdown = ?, content_html = ?,
                 excerpt = ?, status = ?, published_at = ?
             WHERE id = ?"
        );
        $stmt->bind_param('sssssssi',
            $data['slug'],
            $data['title'],
            $data['content_markdown'],
            $content_html,
            $excerpt,
            $data['status'],
            $published_at,
            $id
        );

        if (!$stmt->execute()) {
            return false;
        }
    }

    // Update categories
    if (isset($data['categories'])) {
        blog_update_post_categories($mysqli, $id, $data['categories']);
    }

    return $id;
}

/**
 * Update post categories
 *
 * @param mysqli $mysqli Database connection
 * @param int $post_id Post ID
 * @param array $category_ids Array of category IDs
 */
function blog_update_post_categories($mysqli, $post_id, $category_ids) {
    // Remove existing categories
    $stmt = $mysqli->prepare("DELETE FROM blog_post_categories WHERE post_id = ?");
    $stmt->bind_param('i', $post_id);
    $stmt->execute();

    // Add new categories
    if (!empty($category_ids)) {
        $stmt = $mysqli->prepare("INSERT INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
        foreach ($category_ids as $category_id) {
            $stmt->bind_param('ii', $post_id, $category_id);
            $stmt->execute();
        }
    }
}

/**
 * Delete a blog post
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Post ID
 * @return bool Success
 */
function blog_delete_post($mysqli, $id) {
    $stmt = $mysqli->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

/**
 * Save a category
 *
 * @param mysqli $mysqli Database connection
 * @param array $data Category data
 * @param int|null $id Category ID for update
 * @return int|false Category ID on success
 */
function blog_save_category($mysqli, $data, $id = null) {
    if ($id === null) {
        $stmt = $mysqli->prepare(
            "INSERT INTO blog_categories (slug, name, description) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('sss', $data['slug'], $data['name'], $data['description']);

        if ($stmt->execute()) {
            return $mysqli->insert_id;
        }
    } else {
        $stmt = $mysqli->prepare(
            "UPDATE blog_categories SET slug = ?, name = ?, description = ? WHERE id = ?"
        );
        $stmt->bind_param('sssi', $data['slug'], $data['name'], $data['description'], $id);

        if ($stmt->execute()) {
            return $id;
        }
    }

    return false;
}

/**
 * Delete a category
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Category ID
 * @return bool Success
 */
function blog_delete_category($mysqli, $id) {
    $stmt = $mysqli->prepare("DELETE FROM blog_categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

/**
 * Check if category slug is unique
 *
 * @param mysqli $mysqli Database connection
 * @param string $slug Slug to check
 * @param int|null $exclude_id Exclude this category ID (for updates)
 * @return bool True if unique
 */
function blog_is_category_slug_unique($mysqli, $slug, $exclude_id = null) {
    $sql = "SELECT id FROM blog_categories WHERE slug = ?";
    $params = [$slug];
    $types = 's';

    if ($exclude_id !== null) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
        $types .= 'i';
    }

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows === 0;
}

/**
 * Format date for display
 *
 * @param string $date Date string
 * @param string $format PHP date format
 * @return string Formatted date
 */
function blog_format_date($date, $format = 'M j, Y') {
    if (empty($date)) {
        return '';
    }
    return date($format, strtotime($date));
}
