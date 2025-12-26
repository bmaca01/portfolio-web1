<?php
/**
 * Admin Logout Handler
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/auth.php';

admin_logout();

header('Location: login.php');
exit;
