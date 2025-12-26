<?php
/**
 * Contact Form Helper Functions
 * Web 1.0 Portfolio Site
 *
 * Provides validation, database CRUD, and email notification for contact form.
 */

require_once __DIR__ . '/mail-config.php';

// Only require PHPMailer if it exists (for production)
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ============================================================================
// Validation Functions
// ============================================================================

/**
 * Validate contact form name field
 *
 * @param string $name The name to validate
 * @return array Array of error messages (empty if valid)
 */
function contact_validate_name(string $name): array
{
    $errors = [];
    $name = trim($name);

    if (empty($name)) {
        $errors[] = 'Name is required';
        return $errors;
    }

    if (mb_strlen($name) < 2) {
        $errors[] = 'Name must be at least 2 characters';
    }

    if (mb_strlen($name) > 100) {
        $errors[] = 'Name must be less than 100 characters';
    }

    return $errors;
}

/**
 * Validate contact form email field
 *
 * @param string $email The email to validate
 * @return array Array of error messages (empty if valid)
 */
function contact_validate_email(string $email): array
{
    $errors = [];
    $email = trim($email);

    if (empty($email)) {
        $errors[] = 'Email is required';
        return $errors;
    }

    if (mb_strlen($email) > 255) {
        $errors[] = 'Email must be less than 255 characters';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }

    return $errors;
}

/**
 * Validate contact form message field
 *
 * @param string $message The message to validate
 * @return array Array of error messages (empty if valid)
 */
function contact_validate_message(string $message): array
{
    $errors = [];
    $message = trim($message);

    if (empty($message)) {
        $errors[] = 'Message is required';
        return $errors;
    }

    if (mb_strlen($message) < 10) {
        $errors[] = 'Message must be at least 10 characters';
    }

    if (mb_strlen($message) > 5000) {
        $errors[] = 'Message must be less than 5000 characters';
    }

    return $errors;
}

/**
 * Validate honeypot field (bot detection)
 *
 * @param string|null $value The honeypot field value
 * @return bool True if valid (empty), false if filled (bot detected)
 */
function contact_validate_honeypot(?string $value): bool
{
    // If null or empty, it's valid (human)
    // If filled, it's a bot
    return empty($value);
}

/**
 * Validate complete contact form data
 *
 * @param array $data Form data array
 * @return array Array of errors, or ['honeypot' => true] if honeypot triggered
 */
function contact_validate_form(array $data): array
{
    // Check honeypot first (silent rejection)
    $honeypot = $data['website_url'] ?? '';
    if (!contact_validate_honeypot($honeypot)) {
        return ['honeypot' => true];
    }

    $errors = [];

    // Validate name
    $nameErrors = contact_validate_name($data['name'] ?? '');
    $errors = array_merge($errors, $nameErrors);

    // Validate email
    $emailErrors = contact_validate_email($data['email'] ?? '');
    $errors = array_merge($errors, $emailErrors);

    // Validate message
    $messageErrors = contact_validate_message($data['message'] ?? '');
    $errors = array_merge($errors, $messageErrors);

    return $errors;
}

// ============================================================================
// Database CRUD Functions
// ============================================================================

/**
 * Save a contact message to the database
 *
 * @param mysqli $mysqli Database connection
 * @param array $data Message data
 * @return int|false Message ID on success, false on failure
 */
function contact_save_message(mysqli $mysqli, array $data): int|false
{
    $stmt = $mysqli->prepare(
        "INSERT INTO contact_messages
         (name, email, subject, message, referral_source, ip_address, is_read)
         VALUES (?, ?, ?, ?, ?, ?, 0)"
    );

    if (!$stmt) {
        error_log("Contact form prepare error: " . $mysqli->error);
        return false;
    }

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? '');
    $message = trim($data['message'] ?? '');
    $referral = isset($data['referral_source']) && $data['referral_source'] !== ''
        ? $data['referral_source']
        : null;
    $ip = $data['ip_address'] ?? '0.0.0.0';

    $stmt->bind_param('ssssss', $name, $email, $subject, $message, $referral, $ip);

    if (!$stmt->execute()) {
        error_log("Contact form insert error: " . $stmt->error);
        return false;
    }

    return (int) $mysqli->insert_id;
}

/**
 * Get paginated list of contact messages
 *
 * @param mysqli $mysqli Database connection
 * @param int $limit Number of messages to retrieve
 * @param int $offset Offset for pagination
 * @return array List of messages (newest first)
 */
function contact_get_messages(mysqli $mysqli, int $limit = 20, int $offset = 0): array
{
    $stmt = $mysqli->prepare(
        "SELECT * FROM contact_messages
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );

    if (!$stmt) {
        error_log("Contact get messages error: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    return $messages;
}

/**
 * Get a single message by ID
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Message ID
 * @return array|null Message data or null if not found
 */
function contact_get_message_by_id(mysqli $mysqli, int $id): ?array
{
    $stmt = $mysqli->prepare("SELECT * FROM contact_messages WHERE id = ?");

    if (!$stmt) {
        error_log("Contact get message error: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?: null;
}

/**
 * Count unread messages
 *
 * @param mysqli $mysqli Database connection
 * @return int Number of unread messages
 */
function contact_count_unread(mysqli $mysqli): int
{
    $result = $mysqli->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");

    if (!$result) {
        error_log("Contact count unread error: " . $mysqli->error);
        return 0;
    }

    return (int) $result->fetch_assoc()['count'];
}

/**
 * Mark a message as read
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Message ID
 * @return bool True on success
 */
function contact_mark_read(mysqli $mysqli, int $id): bool
{
    $stmt = $mysqli->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?");

    if (!$stmt) {
        error_log("Contact mark read error: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

/**
 * Mark a message as unread
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Message ID
 * @return bool True on success
 */
function contact_mark_unread(mysqli $mysqli, int $id): bool
{
    $stmt = $mysqli->prepare("UPDATE contact_messages SET is_read = 0 WHERE id = ?");

    if (!$stmt) {
        error_log("Contact mark unread error: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

/**
 * Delete a contact message
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Message ID
 * @return bool True on success
 */
function contact_delete_message(mysqli $mysqli, int $id): bool
{
    $stmt = $mysqli->prepare("DELETE FROM contact_messages WHERE id = ?");

    if (!$stmt) {
        error_log("Contact delete error: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

// ============================================================================
// IP Detection Function
// ============================================================================

/**
 * Get visitor IP address (Cloudflare-aware)
 *
 * @return string IP address
 */
function contact_get_visitor_ip(): string
{
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIPs = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($forwardedIPs[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '0.0.0.0';
    }

    return $ip;
}

// ============================================================================
// Email Notification Functions
// ============================================================================

/**
 * Format email subject line
 *
 * @param array $data Form data
 * @return string Formatted subject
 */
function contact_format_email_subject(array $data): string
{
    $subject = $data['subject'] ?? 'General Inquiry';
    return "[Contact Form] $subject";
}

/**
 * Format email body
 *
 * @param array $data Form data
 * @return string Formatted email body (plain text)
 */
function contact_format_email_body(array $data): string
{
    $name = htmlspecialchars($data['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($data['email'] ?? '', ENT_QUOTES, 'UTF-8');
    $subject = htmlspecialchars($data['subject'] ?? '', ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($data['message'] ?? '', ENT_QUOTES, 'UTF-8');
    $referral = isset($data['referral_source']) && $data['referral_source']
        ? htmlspecialchars($data['referral_source'], ENT_QUOTES, 'UTF-8')
        : 'Not specified';
    $ip = htmlspecialchars($data['ip_address'] ?? '0.0.0.0', ENT_QUOTES, 'UTF-8');

    $body = <<<EOT
New Contact Form Submission
===========================

Name: $name
Email: $email
Subject: $subject

How they found you: $referral
IP Address: $ip

Message:
--------
$message

---
This message was sent via the contact form at benjmacaro.dev
EOT;

    return $body;
}

/**
 * Send email notification for new contact form submission
 *
 * @param array $data Form data
 * @return bool True on success, false on failure
 */
function contact_send_notification(array $data): bool
{
    // Check if mail is enabled
    if (!is_mail_enabled()) {
        error_log("Contact form: Email notification skipped - SMTP not configured");
        return false;
    }

    $config = get_mail_config();

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $config['port'];

        // Recipients
        $mail->setFrom($config['from'], $config['from_name']);
        $mail->addAddress($config['to']);
        $mail->addReplyTo($data['email'] ?? $config['from'], $data['name'] ?? 'Contact Form');

        // Content
        $mail->isHTML(false);
        $mail->Subject = contact_format_email_subject($data);
        $mail->Body = contact_format_email_body($data);

        $mail->send();
        return true;

    } catch (PHPMailerException $e) {
        error_log("Contact form email error: " . $e->getMessage());
        return false;
    } catch (\Exception $e) {
        error_log("Contact form email error: " . $e->getMessage());
        return false;
    }
}
