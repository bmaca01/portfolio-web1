<?php
/**
 * Security Headers
 * Web 1.0 Portfolio Site
 *
 * Include this file at the top of PHP pages to set security headers.
 * For static HTML pages, configure these in your web server (nginx/apache).
 */

// Prevent MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Prevent clickjacking
header('X-Frame-Options: DENY');

// Enable XSS filter in older browsers
header('X-XSS-Protection: 1; mode=block');

// Control referrer information
header('Referrer-Policy: strict-origin-when-cross-origin');

// Permissions Policy (replaces Feature-Policy)
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
