<?php
/**
 * Admin Header
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/auth.php';
admin_check_auth();

$current_user = admin_get_current_user();
$admin_page = isset($admin_page) ? $admin_page : '';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>Blog Admin - <?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard'; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="/styles.css">
    <style type="text/css">
        /* Admin-specific styles */
        .admin-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-header {
            background-color: #292f32;
            color: #fff;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        .admin-header h1 {
            margin: 0;
            font-size: 24px;
            display: inline;
        }
        .admin-header .user-info {
            float: right;
            font-size: 14px;
        }
        .admin-header .user-info a {
            color: #fff;
        }
        .admin-nav {
            background-color: #f0e6ff;
            border: 2px solid #292f32;
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        .admin-nav a {
            color: #292f32;
            text-decoration: none;
            margin-right: 20px;
            font-weight: normal;
        }
        .admin-nav a:hover {
            text-decoration: underline;
        }
        .admin-nav a.active {
            font-weight: bold;
            text-decoration: underline;
        }
        .admin-content {
            background-color: #fff;
            border: 2px solid #292f32;
            padding: 20px;
        }
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        .admin-table th,
        .admin-table td {
            border: 1px solid #292f32;
            padding: 10px;
            text-align: left;
        }
        .admin-table th {
            background-color: #f0e6ff;
        }
        .admin-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #292f32;
            color: #fff;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #444;
        }
        .btn-small {
            padding: 4px 10px;
            font-size: 12px;
        }
        .btn-danger {
            background-color: #cc0000;
        }
        .btn-danger:hover {
            background-color: #990000;
        }
        .btn-secondary {
            background-color: #666;
        }
        .success-message {
            background-color: #ccffcc;
            border: 2px solid #00cc00;
            color: #006600;
            padding: 10px;
            margin-bottom: 15px;
        }
        .error-message {
            background-color: #ffcccc;
            border: 2px solid #ff0000;
            color: #cc0000;
            padding: 10px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 2px solid #292f32;
            font-size: 14px;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-group textarea {
            min-height: 300px;
            font-family: monospace;
        }
        .form-group small {
            color: #666;
            font-size: 12px;
        }
        .checkbox-group label {
            display: inline;
            font-weight: normal;
            margin-right: 15px;
        }
        .status-draft {
            color: #996600;
        }
        .status-published {
            color: #006600;
        }
        .actions {
            white-space: nowrap;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        /* Auto-save status indicator */
        #autosave-status {
            font-size: 12px;
            padding: 5px 10px;
            background-color: #ffffcc;
            border: 1px solid #cccc00;
            margin-bottom: 15px;
        }
        .autosave-saving {
            color: #996600;
        }
        .autosave-saved {
            color: #006600;
        }
        .autosave-error {
            color: #cc0000;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header clearfix">
            <h1>Blog Admin</h1>
            <div class="user-info">
                Logged in as <strong><?php echo htmlspecialchars($current_user['username']); ?></strong>
                | <a href="logout.php">Log Out</a>
                | <a href="../">View Blog</a>
            </div>
        </div>

        <div class="admin-nav">
            <a href="posts.php" class="<?php echo $admin_page === 'posts' ? 'active' : ''; ?>">Posts</a>
            <a href="post-edit.php" class="<?php echo $admin_page === 'post-new' ? 'active' : ''; ?>">New Post</a>
            <a href="categories.php" class="<?php echo $admin_page === 'categories' ? 'active' : ''; ?>">Categories</a>
            <a href="images.php" class="<?php echo $admin_page === 'images' ? 'active' : ''; ?>">Images</a>
            <a href="messages.php" class="<?php echo $admin_page === 'messages' ? 'active' : ''; ?>">Messages</a>
            <a href="guestbook.php" class="<?php echo $admin_page === 'guestbook' ? 'active' : ''; ?>">Guestbook</a>
        </div>

        <div class="admin-content">
