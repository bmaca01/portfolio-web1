<?php
/**
 * Contact Messages Admin
 * Web 1.0 Portfolio Site - Blog Admin
 */

$page_title = 'Messages';
$admin_page = 'messages';

// Include auth first to get $mysqli connection (auth.php includes db-config.php)
require_once __DIR__ . '/auth.php';
admin_check_auth();

require_once __DIR__ . '/../../includes/contact-functions.php';

// Handle actions
$action = $_GET['action'] ?? '';
$message_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$success_message = '';
$error_message = '';

// Handle mark as read/unread
if ($action === 'mark_read' && $message_id > 0) {
    if (contact_mark_read($mysqli, $message_id)) {
        $success_message = 'Message marked as read.';
    } else {
        $error_message = 'Failed to mark message as read.';
    }
}

if ($action === 'mark_unread' && $message_id > 0) {
    if (contact_mark_unread($mysqli, $message_id)) {
        $success_message = 'Message marked as unread.';
    } else {
        $error_message = 'Failed to mark message as unread.';
    }
}

// Handle delete
if ($action === 'delete' && $message_id > 0) {
    if (isset($_GET['confirm']) && $_GET['confirm'] === '1') {
        if (contact_delete_message($mysqli, $message_id)) {
            $success_message = 'Message deleted successfully.';
        } else {
            $error_message = 'Failed to delete message.';
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get messages
$messages = contact_get_messages($mysqli, $per_page, $offset);
$unread_count = contact_count_unread($mysqli);

// View single message
$viewing_message = null;
if ($action === 'view' && $message_id > 0) {
    $viewing_message = contact_get_message_by_id($mysqli, $message_id);
    if ($viewing_message && (int) $viewing_message['is_read'] === 0) {
        contact_mark_read($mysqli, $message_id);
        $viewing_message['is_read'] = 1;
    }
}

require_once __DIR__ . '/header.php';
?>

<?php if (!empty($success_message)): ?>
    <div class="success-message">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<?php if ($viewing_message): ?>
    <!-- Viewing Single Message -->
    <h2>Message Details</h2>
    <p><a href="messages.php">&laquo; Back to Messages</a></p>

    <table class="admin-table" style="margin-bottom: 20px;">
        <tr>
            <th width="20%">From</th>
            <td><?php echo htmlspecialchars($viewing_message['name']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td><a href="mailto:<?php echo htmlspecialchars($viewing_message['email']); ?>"><?php echo htmlspecialchars($viewing_message['email']); ?></a></td>
        </tr>
        <tr>
            <th>Subject</th>
            <td><?php echo htmlspecialchars($viewing_message['subject']); ?></td>
        </tr>
        <tr>
            <th>Date</th>
            <td><?php echo date('F j, Y \a\t g:i A', strtotime($viewing_message['created_at'])); ?></td>
        </tr>
        <tr>
            <th>Referral Source</th>
            <td><?php echo htmlspecialchars($viewing_message['referral_source'] ?? 'Not specified'); ?></td>
        </tr>
        <tr>
            <th>IP Address</th>
            <td><?php echo htmlspecialchars($viewing_message['ip_address']); ?></td>
        </tr>
    </table>

    <h3>Message</h3>
    <div style="background: #f9f9f9; border: 1px solid #292f32; padding: 15px; margin-bottom: 20px;">
        <?php echo nl2br(htmlspecialchars($viewing_message['message'])); ?>
    </div>

    <p>
        <a href="mailto:<?php echo htmlspecialchars($viewing_message['email']); ?>?subject=Re: <?php echo htmlspecialchars($viewing_message['subject']); ?>" class="btn">Reply via Email</a>
        <?php if ((int) $viewing_message['is_read'] === 1): ?>
            <a href="messages.php?action=mark_unread&id=<?php echo $viewing_message['id']; ?>" class="btn btn-secondary">Mark as Unread</a>
        <?php else: ?>
            <a href="messages.php?action=mark_read&id=<?php echo $viewing_message['id']; ?>" class="btn btn-secondary">Mark as Read</a>
        <?php endif; ?>
        <a href="messages.php?action=delete&id=<?php echo $viewing_message['id']; ?>&confirm=1" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this message?');">Delete</a>
    </p>

<?php elseif ($action === 'delete' && $message_id > 0 && !isset($_GET['confirm'])): ?>
    <!-- Delete Confirmation -->
    <?php $msg = contact_get_message_by_id($mysqli, $message_id); ?>
    <?php if ($msg): ?>
        <h2>Confirm Delete</h2>
        <p>Are you sure you want to delete this message from <strong><?php echo htmlspecialchars($msg['name']); ?></strong>?</p>
        <p>
            <a href="messages.php?action=delete&id=<?php echo $message_id; ?>&confirm=1" class="btn btn-danger">Yes, Delete</a>
            <a href="messages.php" class="btn btn-secondary">Cancel</a>
        </p>
    <?php else: ?>
        <p>Message not found.</p>
        <p><a href="messages.php">&laquo; Back to Messages</a></p>
    <?php endif; ?>

<?php else: ?>
    <!-- Messages List -->
    <h2>Contact Messages <?php if ($unread_count > 0): ?><span style="color: #cc0000;">(<?php echo $unread_count; ?> unread)</span><?php endif; ?></h2>

    <?php if (empty($messages)): ?>
        <p>No contact messages yet.</p>
    <?php else: ?>
        <table class="admin-table">
            <tr>
                <th width="5%">Status</th>
                <th width="20%">From</th>
                <th width="20%">Subject</th>
                <th width="25%">Preview</th>
                <th width="15%">Date</th>
                <th width="15%">Actions</th>
            </tr>
            <?php foreach ($messages as $msg): ?>
                <?php $is_unread = (int) $msg['is_read'] === 0; ?>
                <tr style="<?php echo $is_unread ? 'font-weight: bold; background-color: #fffacd;' : ''; ?>">
                    <td style="text-align: center;">
                        <?php if ($is_unread): ?>
                            <span style="color: #cc0000;" title="Unread">&#9679;</span>
                        <?php else: ?>
                            <span style="color: #999;" title="Read">&#9675;</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($msg['name']); ?><br>
                        <small style="font-weight: normal;"><?php echo htmlspecialchars($msg['email']); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                    <td style="font-weight: normal;"><?php echo htmlspecialchars(substr($msg['message'], 0, 60)) . (strlen($msg['message']) > 60 ? '...' : ''); ?></td>
                    <td style="font-weight: normal;"><?php echo date('M j, Y', strtotime($msg['created_at'])); ?></td>
                    <td class="actions">
                        <a href="messages.php?action=view&id=<?php echo $msg['id']; ?>" class="btn btn-small">View</a>
                        <?php if ($is_unread): ?>
                            <a href="messages.php?action=mark_read&id=<?php echo $msg['id']; ?>" class="btn btn-small btn-secondary" title="Mark as Read">&#10003;</a>
                        <?php else: ?>
                            <a href="messages.php?action=mark_unread&id=<?php echo $msg['id']; ?>" class="btn btn-small btn-secondary" title="Mark as Unread">&#9679;</a>
                        <?php endif; ?>
                        <a href="messages.php?action=delete&id=<?php echo $msg['id']; ?>" class="btn btn-small btn-danger" title="Delete">&#10005;</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Pagination -->
        <?php if (count($messages) >= $per_page): ?>
            <p style="margin-top: 15px;">
                <?php if ($page > 1): ?>
                    <a href="messages.php?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                <?php endif; ?>
                &nbsp;
                Page <?php echo $page; ?>
                &nbsp;
                <a href="messages.php?page=<?php echo $page + 1; ?>">Next &raquo;</a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
