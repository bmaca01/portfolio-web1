<?php
/**
 * Admin Login Page
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (admin_is_logged_in()) {
    header('Location: posts.php');
    exit;
}

$error_message = '';
$username = '';

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please log in again.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!admin_validate_csrf($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid form submission. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error_message = 'Please enter both username and password.';
        } else {
            $result = admin_login($mysqli, $username, $password);
            if ($result['success']) {
                header('Location: posts.php');
                exit;
            } else {
                $error_message = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <title>Blog Admin - Login</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="stylesheet" href="/styles.css">
    <style type="text/css">
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: #f0e6ff;
            border: 3px solid #292f32;
            box-shadow: 4px 4px 0px rgba(0,0,0,0.2);
        }
        .login-title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            color: #292f32;
        }
        .login-form table {
            width: 100%;
        }
        .login-form td {
            padding: 8px 0;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 2px solid #292f32;
            font-size: 14px;
            box-sizing: border-box;
        }
        .login-form input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #292f32;
            color: #fff;
            border: none;
            font-size: 16px;
            cursor: pointer;
        }
        .login-form input[type="submit"]:hover {
            background-color: #444;
        }
        .error-message {
            background-color: #ffcccc;
            border: 2px solid #ff0000;
            color: #cc0000;
            padding: 10px;
            margin-bottom: 15px;
            text-align: center;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1 class="login-title">Blog Admin</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php" class="login-form">
            <?php admin_csrf_field(); ?>
            <table cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <label for="username"><strong>Username:</strong></label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="text" id="username" name="username"
                               value="<?php echo htmlspecialchars($username); ?>"
                               required autofocus>
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="password"><strong>Password:</strong></label>
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="password" id="password" name="password" required>
                    </td>
                </tr>
                <tr>
                    <td style="padding-top: 15px;">
                        <input type="submit" value="Log In">
                    </td>
                </tr>
            </table>
        </form>

        <div class="back-link">
            <a href="../">&laquo; Back to Blog</a>
        </div>
    </div>
</body>
</html>
