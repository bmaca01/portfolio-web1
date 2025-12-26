<?php
/**
 * Mail Configuration
 * Web 1.0 Portfolio Site
 *
 * SMTP settings loaded from environment variables.
 * For production, set these in your server environment or .env file.
 */

if (!function_exists('get_mail_config')) {

/**
 * Get mail configuration
 *
 * @return array Mail configuration array
 */
function get_mail_config(): array {
    return [
        'host'     => getenv('SMTP_HOST') ?: '',
        'port'     => (int)(getenv('SMTP_PORT') ?: 587),
        'username' => getenv('SMTP_USER') ?: '',
        'password' => getenv('SMTP_PASSWORD') ?: '',
        'from'     => getenv('SMTP_FROM') ?: 'noreply@benjmacaro.dev',
        'from_name'=> getenv('SMTP_FROM_NAME') ?: 'benjmacaro.dev Contact Form',
        'to'       => getenv('CONTACT_EMAIL') ?: 'info@benjmacaro.dev',
    ];
}

/**
 * Check if mail sending is enabled
 *
 * @return bool True if SMTP is configured
 */
function is_mail_enabled(): bool {
    $config = get_mail_config();
    return !empty($config['host']) && !empty($config['username']);
}

} // end function_exists check
