<?php
/**
 * Blog Image Helper Functions
 * Web 1.0 Portfolio Site
 */

require_once __DIR__ . '/config.php';

/**
 * Upload and save an image
 *
 * @param mysqli $mysqli Database connection
 * @param array $file $_FILES array element
 * @return array ['success' => bool, 'message' => string, 'data' => array|null]
 */
function image_upload($mysqli, $file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        ];
        return [
            'success' => false,
            'message' => $errors[$file['error']] ?? 'Unknown upload error',
            'data' => null
        ];
    }

    // Check file size
    if ($file['size'] > BLOG_IMAGE_MAX_SIZE) {
        return [
            'success' => false,
            'message' => 'File size exceeds ' . (BLOG_IMAGE_MAX_SIZE / 1024 / 1024) . 'MB limit',
            'data' => null
        ];
    }

    // Validate MIME type using finfo (more secure than trusting $_FILES['type'])
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, BLOG_IMAGE_ALLOWED_TYPES)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WebP',
            'data' => null
        ];
    }

    // Get image dimensions
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        return [
            'success' => false,
            'message' => 'Could not read image dimensions',
            'data' => null
        ];
    }
    $width = $image_info[0];
    $height = $image_info[1];

    // Generate unique filename
    $filename = image_generate_filename($file['name'], $mime_type);
    $filepath = BLOG_IMAGE_DIR . $filename;

    // Ensure directory exists
    if (!is_dir(BLOG_IMAGE_DIR)) {
        mkdir(BLOG_IMAGE_DIR, 0755, true);
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => false,
            'message' => 'Failed to save uploaded file',
            'data' => null
        ];
    }

    // Save to database
    $stmt = $mysqli->prepare(
        "INSERT INTO blog_images (filename, original_name, mime_type, file_size, width, height)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssiii',
        $filename,
        $file['name'],
        $mime_type,
        $file['size'],
        $width,
        $height
    );

    if (!$stmt->execute()) {
        // Clean up file if database insert fails
        unlink($filepath);
        return [
            'success' => false,
            'message' => 'Failed to save image to database',
            'data' => null
        ];
    }

    $image_id = $mysqli->insert_id;

    return [
        'success' => true,
        'message' => 'Image uploaded successfully',
        'data' => [
            'id' => $image_id,
            'filename' => $filename,
            'url' => BLOG_IMAGE_URL . $filename,
            'width' => $width,
            'height' => $height,
            'markdown' => '![' . htmlspecialchars($file['name']) . '](' . BLOG_IMAGE_URL . $filename . ')'
        ]
    ];
}

/**
 * Generate unique filename for uploaded image
 *
 * @param string $original_name Original filename
 * @param string $mime_type MIME type
 * @return string Unique filename
 */
function image_generate_filename($original_name, $mime_type) {
    // Map MIME type to extension
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    $ext = $extensions[$mime_type] ?? 'jpg';

    // Generate unique name: timestamp + random string
    $unique = date('Ymd_His') . '_' . bin2hex(random_bytes(4));

    return $unique . '.' . $ext;
}

/**
 * Get all uploaded images
 *
 * @param mysqli $mysqli Database connection
 * @param int $limit Max images to return
 * @param int $offset Offset for pagination
 * @return array List of images
 */
function image_get_all($mysqli, $limit = 50, $offset = 0) {
    $stmt = $mysqli->prepare(
        "SELECT * FROM blog_images ORDER BY uploaded_at DESC LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $images = [];
    while ($row = $result->fetch_assoc()) {
        $row['url'] = BLOG_IMAGE_URL . $row['filename'];
        $row['markdown'] = '![' . htmlspecialchars($row['original_name']) . '](' . $row['url'] . ')';
        $images[] = $row;
    }

    return $images;
}

/**
 * Get image by ID
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Image ID
 * @return array|null Image data or null
 */
function image_get_by_id($mysqli, $id) {
    $stmt = $mysqli->prepare("SELECT * FROM blog_images WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $image = $result->fetch_assoc();

    if ($image) {
        $image['url'] = BLOG_IMAGE_URL . $image['filename'];
    }

    return $image;
}

/**
 * Delete an image
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Image ID
 * @return bool Success
 */
function image_delete($mysqli, $id) {
    // Get image info first
    $image = image_get_by_id($mysqli, $id);
    if (!$image) {
        return false;
    }

    // Delete from filesystem
    $filepath = BLOG_IMAGE_DIR . $image['filename'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    // Delete from database
    $stmt = $mysqli->prepare("DELETE FROM blog_images WHERE id = ?");
    $stmt->bind_param('i', $id);
    return $stmt->execute();
}

/**
 * Get total image count
 *
 * @param mysqli $mysqli Database connection
 * @return int Total count
 */
function image_get_count($mysqli) {
    $result = $mysqli->query("SELECT COUNT(*) as total FROM blog_images");
    return $result->fetch_assoc()['total'];
}

/**
 * Resize image (optional, for thumbnails)
 *
 * @param string $source_path Source image path
 * @param string $dest_path Destination path
 * @param int $max_width Maximum width
 * @param int $max_height Maximum height
 * @return bool Success
 */
function image_resize($source_path, $dest_path, $max_width, $max_height) {
    $image_info = getimagesize($source_path);
    if ($image_info === false) {
        return false;
    }

    $width = $image_info[0];
    $height = $image_info[1];
    $mime = $image_info['mime'];

    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    if ($ratio >= 1) {
        // Image is already smaller than max dimensions
        return copy($source_path, $dest_path);
    }

    $new_width = (int)($width * $ratio);
    $new_height = (int)($height * $ratio);

    // Create image resource from source
    switch ($mime) {
        case 'image/jpeg':
            $source = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source = imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }

    if (!$source) {
        return false;
    }

    // Create new image
    $dest = imagecreatetruecolor($new_width, $new_height);

    // Preserve transparency for PNG and GIF
    if ($mime === 'image/png' || $mime === 'image/gif') {
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);
    }

    // Resize
    imagecopyresampled($dest, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

    // Save
    $result = false;
    switch ($mime) {
        case 'image/jpeg':
            $result = imagejpeg($dest, $dest_path, 85);
            break;
        case 'image/png':
            $result = imagepng($dest, $dest_path, 8);
            break;
        case 'image/gif':
            $result = imagegif($dest, $dest_path);
            break;
        case 'image/webp':
            $result = imagewebp($dest, $dest_path, 85);
            break;
    }

    // Clean up
    imagedestroy($source);
    imagedestroy($dest);

    return $result;
}

/**
 * Format file size for display
 *
 * @param int $bytes Size in bytes
 * @return string Formatted size
 */
function image_format_size($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 1) . ' KB';
    }
    return $bytes . ' bytes';
}

/**
 * Check if MIME type is allowed for upload
 *
 * @param string $mime_type MIME type to check
 * @return bool
 */
function image_is_allowed_type($mime_type) {
    return in_array($mime_type, BLOG_IMAGE_ALLOWED_TYPES, true);
}
