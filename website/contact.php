<?php
$currentPage = 'contact';
$htmlTitle = 'benjmacaro.dev - contact';
$metaDescription = 'Contact BenjMacaro for web development, collaborations, or just to say hi!';
$metaKeywords = 'contact, email, hire, freelance, web developer';

require_once 'includes/db-config.php';
require_once 'includes/contact-functions.php';

$success_message = '';
$error_message = '';

// Preserve form values
$form_name = '';
$form_email = '';
$form_subject = 'Just Saying Hi!';
$form_message = '';
$form_found = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $form_name = trim($_POST['name'] ?? '');
    $form_email = trim($_POST['email'] ?? '');
    $form_subject = trim($_POST['subject'] ?? '');
    $form_message = trim($_POST['message'] ?? '');
    $form_found = trim($_POST['found'] ?? '');
    $honeypot = $_POST['website_url'] ?? '';

    // Validate form
    $errors = contact_validate_form([
        'name' => $form_name,
        'email' => $form_email,
        'subject' => $form_subject,
        'message' => $form_message,
        'website_url' => $honeypot,
    ]);

    // Check for honeypot (bot detection)
    if (isset($errors['honeypot']) && $errors['honeypot'] === true) {
        // Silent rejection - pretend success to bots
        $success_message = "Thank you for your message! I'll get back to you soon.";
        $form_name = $form_email = $form_message = '';
    } elseif (empty($errors)) {
        // Get visitor IP
        $ip_address = contact_get_visitor_ip();

        // Prepare data for saving
        $data = [
            'name' => $form_name,
            'email' => $form_email,
            'subject' => $form_subject,
            'message' => $form_message,
            'referral_source' => $form_found ?: null,
            'ip_address' => $ip_address,
        ];

        // Save to database
        $messageId = contact_save_message($mysqli, $data);

        if ($messageId !== false) {
            // Try to send email notification (best-effort)
            $data['ip_address'] = $ip_address;
            contact_send_notification($data);

            $success_message = "Thank you for your message! I'll get back to you soon.";
            // Clear form
            $form_name = $form_email = $form_message = '';
            $form_subject = 'Just Saying Hi!';
            $form_found = '';
        } else {
            $error_message = "Sorry, there was an error sending your message. Please try again.";
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

include 'includes/page-header.php';
?>

<?php if (!empty($success_message)): ?>
    <p style="color: #00ff00; background: #003300; padding: 10px; border: 2px solid #00ff00; text-align: center;">
        <strong><?php echo htmlspecialchars($success_message); ?></strong>
    </p>
    <br>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <p style="color: #ff0000; background: #330000; padding: 10px; border: 2px solid #ff0000; text-align: center;">
        <strong>Error:</strong> <?php echo $error_message; ?>
    </p>
    <br>
<?php endif; ?>

<!-- Main Contact Box -->
<h2 class="section-heading">Let's Connect!</h2>
<table width="100%" cellpadding="15" cellspacing="0">
    <tr>
        <td class="text-primary text-center" width="50%">
            <strong>Email:</strong><br>
            <a href="mailto:info@benjmacaro.dev">info@benjmacaro.dev</a><br>
        </td>
        <td class="text-primary text-center" width="50%">
            <strong>GitHub:</strong><br>
            <a href="https://github.com/bmaca01">@bmaca01</a><br>
        </td>
    </tr>
</table>

<br>

<h2 class="section-heading">Send Me a Message</h2>
<form action="contact.php" method="post" name="contact">
    <!-- Honeypot field - hidden from humans, filled by bots -->
    <div style="position: absolute; left: -9999px;" aria-hidden="true">
        <label for="website_url">Leave this field empty</label>
        <input type="text" name="website_url" id="website_url" tabindex="-1" autocomplete="off" value="">
    </div>

    <table width="100%" cellpadding="5" cellspacing="0">
        <tr>
            <td class="form-label" width="30%" align="right">
                <strong>Your Name:</strong> *
            </td>
            <td width="70%">
                <input class="form-input" type="text" name="name" size="40" maxlength="100"
                       value="<?php echo htmlspecialchars($form_name); ?>" required>
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>Your Email:</strong> *
            </td>
            <td>
                <input class="form-input" type="email" name="email" size="40" maxlength="255"
                       value="<?php echo htmlspecialchars($form_email); ?>" required>
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>Subject:</strong>
            </td>
            <td>
                <select class="form-select" name="subject">
                    <option <?php echo $form_subject === 'Freelance Project' ? 'selected' : ''; ?>>Freelance Project</option>
                    <option <?php echo $form_subject === 'Full-Time Opportunity' ? 'selected' : ''; ?>>Full-Time Opportunity</option>
                    <option <?php echo $form_subject === 'Collaboration' ? 'selected' : ''; ?>>Collaboration</option>
                    <option <?php echo $form_subject === 'Bug Report' ? 'selected' : ''; ?>>Bug Report</option>
                    <option <?php echo $form_subject === 'Just Saying Hi!' ? 'selected' : ''; ?>>Just Saying Hi!</option>
                    <option <?php echo $form_subject === 'Link Exchange' ? 'selected' : ''; ?>>Link Exchange</option>
                    <option <?php echo $form_subject === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right" valign="top">
                <strong>Message:</strong> *
            </td>
            <td>
                <textarea class="form-textarea" name="message" rows="8" cols="50" maxlength="5000" required><?php echo htmlspecialchars($form_message); ?></textarea>
                <br><font size="2">(10-5000 characters)</font>
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>How did you find me?</strong>
            </td>
            <td class="text-primary">
                <input type="radio" name="found" value="search" <?php echo $form_found === 'search' ? 'checked' : ''; ?>> Search Engine
                <input type="radio" name="found" value="webring" <?php echo $form_found === 'webring' ? 'checked' : ''; ?>> Web Ring
                <input type="radio" name="found" value="friend" <?php echo $form_found === 'friend' ? 'checked' : ''; ?>> Friend
                <input type="radio" name="found" value="other" <?php echo $form_found === 'other' ? 'checked' : ''; ?>> Other
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <br>
                <input class="form-button" type="submit" name="submit_contact" value="Send Message!">
                &nbsp;&nbsp;
                <input class="form-button" type="reset" value="Clear">
            </td>
        </tr>
    </table>
</form>

<br>

<p class="body-text">
    <strong>Current Status:</strong> Available for hire
</p>

<br>

<!-- Social Media -->
<h2 class="section-heading">Socials</h2>
<p class="body-text">
    <a href="https://linkedin.com/in/benedikt-macaro">LinkedIn</a> |
    <a href="https://www.instagram.com/bn.mcr/">Instagram</a> |
    <a href="https://open.spotify.com/user/myemail12318-us?si=290ff16a78ab4c2d">Spotify</a>
</p>

<?php
$mysqli->close();
include 'includes/page-footer.php';
?>
