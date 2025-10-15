<?php
$currentPage = 'guestbook';
$htmlTitle = 'benjmacaro.dev - guestbook';
$metaDescription = 'Sign my guestbook!';
$metaKeywords = 'guestbook, visitor signatures, comments, web 1.0';

require_once 'includes/db-config.php';

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_entry'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $homepage = trim($_POST['homepage'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $visitor_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIPs = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $visitor_ip = trim($forwardedIPs[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $visitor_ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    if (!filter_var($visitor_ip, FILTER_VALIDATE_IP)) {
        $visitor_ip = '0.0.0.0';
    }

    $errors = [];

    if (empty($name) || strlen($name) < 2) {
        $errors[] = "Name must be at least 2 characters";
    }
    if (strlen($name) > 100) {
        $errors[] = "Name must be less than 100 characters";
    }

    if (empty($message) || strlen($message) < 10) {
        $errors[] = "Message must be at least 10 characters";
    }
    if (strlen($message) > 1000) {
        $errors[] = "Message must be less than 1000 characters";
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address";
    }

    if (!empty($homepage)) {
        if (strlen($homepage) > 255) {
            $errors[] = "Homepage URL too long";
        }
        if (!preg_match("~^(?:f|ht)tps?://~i", $homepage)) {
            $homepage = "http://" . $homepage;
        }
    }

    if (!empty($location) && strlen($location) > 100) {
        $errors[] = "Location must be less than 100 characters";
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare("INSERT INTO entries (name, email, homepage, location, message, ip_address) VALUES (?, ?, ?, ?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("ssssss", $name, $email, $homepage, $location, $message, $visitor_ip);

            if ($stmt->execute()) {
                $success_message = "Thank you for signing my guestbook!";
                $name = $email = $homepage = $location = $message = '';
            } else {
                $error_message = "Sorry, there was an error saving your entry. Please try again.";
                error_log("Guestbook insert error: " . $stmt->error);
            }

            $stmt->close();
        } else {
            $error_message = "Sorry, there was an error. Please try again.";
            error_log("Guestbook prepare error: " . $mysqli->error);
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Fetch recent guestbook entries
$entries = [];
$query = "SELECT name, email, homepage, location, message, created_at FROM entries WHERE approved = 1 ORDER BY created_at DESC LIMIT 50";
$result = $mysqli->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }
    $result->free();
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

<h2 class="section-heading">Sign My Guestbook!</h2>

<form action="guestbook.php" method="post" name="guestbook">
    <table width="100%" cellpadding="5" cellspacing="0">
        <tr>
            <td class="form-label" width="30%" align="right">
                <strong>Your Name:</strong> *
            </td>
            <td width="70%">
                <input class="form-input" type="text" name="name" size="40" maxlength="100"
                       value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>Your Email:</strong>
            </td>
            <td>
                <input class="form-input" type="email" name="email" size="40" maxlength="255"
                       value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                <font size="2">(optional, not shown publicly)</font>
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>Your Homepage:</strong>
            </td>
            <td>
                <input class="form-input" type="text" name="homepage" size="40" maxlength="255"
                       value="<?php echo isset($homepage) && $homepage !== 'http://' ? htmlspecialchars($homepage) : 'http://'; ?>">
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right">
                <strong>Location:</strong>
            </td>
            <td>
                <input class="form-input" type="text" name="location" size="40" maxlength="100"
                       placeholder="City, Country"
                       value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>">
            </td>
        </tr>
        <tr>
            <td class="form-label" align="right" valign="top">
                <strong>Your Message:</strong> *
            </td>
            <td>
                <textarea class="form-textarea" name="message" rows="6" cols="50" maxlength="1000" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                <br><font size="2">(Max 1000 characters)</font>
            </td>
        </tr>
        <tr>
            <td colspan="2" align="center">
                <br>
                <input class="form-button" type="submit" name="submit_entry" value="Sign Guestbook!">
                &nbsp;&nbsp;
                <input class="form-button" type="reset" value="Clear Form">
            </td>
        </tr>
    </table>
</form>

<br><br>

<h2 class="section-heading">Guestbook Signatures</h2>

<?php if (empty($entries)): ?>
    <p class="text-primary">
        <em>No entries yet. Be the first to sign my guestbook!</em>
    </p>
<?php else: ?>
    <?php foreach ($entries as $entry): ?>
        <table class="guestbook-entry" width="100%" cellpadding="10" cellspacing="0">
            <tr>
                <td class="text-primary" width="25%" valign="top">
                    <?php if (!empty($entry['homepage']) && $entry['homepage'] !== 'http://'): ?>
                        <strong><a href="<?php echo htmlspecialchars($entry['homepage']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($entry['name']); ?></a></strong>
                    <?php else: ?>
                        <strong><?php echo htmlspecialchars($entry['name']); ?></strong>
                    <?php endif; ?>
                    <br>
                    <font size="2">
                        <?php if (!empty($entry['location'])): ?>
                            <?php echo htmlspecialchars($entry['location']); ?><br>
                        <?php endif; ?>
                        <?php echo date('M j, Y', strtotime($entry['created_at'])); ?>
                    </font>
                </td>
                <td class="text-primary" width="75%">
                    <?php echo nl2br(htmlspecialchars($entry['message'])); ?>
                </td>
            </tr>
        </table>
    <?php endforeach; ?>
<?php endif; ?>

<p class="small-text" style="margin-top: 20px;">
    <em>Total Signatures: <?php echo count($entries); ?></em>
</p>

<?php
$mysqli->close();
include 'includes/page-footer.php';
?>
