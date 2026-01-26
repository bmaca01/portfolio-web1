<?php
/**
 * Guestbook Admin
 * Web 1.0 Portfolio Site - Blog Admin
 */

$page_title = 'Guestbook';
$admin_page = 'guestbook';

// Include auth first to get $mysqli connection (auth.php includes db-config.php)
require_once __DIR__ . '/auth.php';
admin_check_auth();

require_once __DIR__ . '/../../includes/guestbook-functions.php';

// Handle actions
$action = $_GET['action'] ?? '';
$entry_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$success_message = '';
$error_message = '';

// Handle approve/unapprove (POST with CSRF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_validate_csrf($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid form submission.';
    } else {
        $post_action = $_POST['action'] ?? '';
        $post_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        if ($post_action === 'approve' && $post_id > 0) {
            if (guestbook_approve($mysqli, $post_id)) {
                $success_message = 'Entry approved.';
            } else {
                $error_message = 'Failed to approve entry.';
            }
        }

        if ($post_action === 'unapprove' && $post_id > 0) {
            if (guestbook_unapprove($mysqli, $post_id)) {
                $success_message = 'Entry unapproved.';
            } else {
                $error_message = 'Failed to unapprove entry.';
            }
        }

        if ($post_action === 'delete' && $post_id > 0) {
            if (guestbook_delete($mysqli, $post_id)) {
                $success_message = 'Entry deleted successfully.';
            } else {
                $error_message = 'Failed to delete entry.';
            }
        }
    }
}

// Filter
$filter = $_GET['filter'] ?? null;
if ($filter && !in_array($filter, ['pending', 'approved'])) {
    $filter = null;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Get entries
$entries = guestbook_get_entries($mysqli, $per_page, $offset, $filter);
$total_entries = guestbook_count_entries($mysqli, $filter);
$total_pages = ceil($total_entries / $per_page);
$pending_count = guestbook_count_pending($mysqli);

// View single entry
$viewing_entry = null;
if ($action === 'view' && $entry_id > 0) {
    $viewing_entry = guestbook_get_entry_by_id($mysqli, $entry_id);
}

// Delete confirmation
$deleting_entry = null;
if ($action === 'delete' && $entry_id > 0) {
    $deleting_entry = guestbook_get_entry_by_id($mysqli, $entry_id);
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

<?php if ($viewing_entry): ?>
    <!-- Viewing Single Entry -->
    <h2>Guestbook Entry Details</h2>
    <p><a href="guestbook.php<?php echo $filter ? '?filter=' . $filter : ''; ?>">&laquo; Back to Guestbook</a></p>

    <table class="admin-table" style="margin-bottom: 20px;">
        <tr>
            <th width="20%">Status</th>
            <td>
                <?php if ((int) $viewing_entry['approved'] === 1): ?>
                    <span style="color: #006600;">Approved</span>
                <?php else: ?>
                    <span style="color: #cc0000;">Pending</span>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Name</th>
            <td><?php echo htmlspecialchars($viewing_entry['name']); ?></td>
        </tr>
        <tr>
            <th>Email</th>
            <td>
                <?php if (!empty($viewing_entry['email'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($viewing_entry['email']); ?>"><?php echo htmlspecialchars($viewing_entry['email']); ?></a>
                <?php else: ?>
                    <em>Not provided</em>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Homepage</th>
            <td>
                <?php if (!empty($viewing_entry['homepage']) && $viewing_entry['homepage'] !== 'http://'): ?>
                    <a href="<?php echo htmlspecialchars($viewing_entry['homepage']); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($viewing_entry['homepage']); ?></a>
                <?php else: ?>
                    <em>Not provided</em>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Location</th>
            <td>
                <?php if (!empty($viewing_entry['location'])): ?>
                    <?php echo htmlspecialchars($viewing_entry['location']); ?>
                <?php else: ?>
                    <em>Not provided</em>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Date</th>
            <td><?php echo date('F j, Y \a\t g:i A', strtotime($viewing_entry['created_at'])); ?></td>
        </tr>
        <tr>
            <th>IP Address</th>
            <td><?php echo htmlspecialchars($viewing_entry['ip_address']); ?></td>
        </tr>
    </table>

    <h3>Message</h3>
    <div style="background: #f9f9f9; border: 1px solid #292f32; padding: 15px; margin-bottom: 20px;">
        <?php echo nl2br(htmlspecialchars($viewing_entry['message'])); ?>
    </div>

    <form method="post" action="guestbook.php" style="display: inline;">
        <?php admin_csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $viewing_entry['id']; ?>">
        <?php if ((int) $viewing_entry['approved'] === 1): ?>
            <button type="submit" name="action" value="unapprove" class="btn btn-secondary">Unapprove</button>
        <?php else: ?>
            <button type="submit" name="action" value="approve" class="btn">Approve</button>
        <?php endif; ?>
    </form>
    <a href="guestbook.php?action=delete&id=<?php echo $viewing_entry['id']; ?>" class="btn btn-danger">Delete</a>

<?php elseif ($deleting_entry): ?>
    <!-- Delete Confirmation -->
    <h2>Confirm Delete</h2>
    <p>Are you sure you want to delete this guestbook entry from <strong><?php echo htmlspecialchars($deleting_entry['name']); ?></strong>?</p>

    <div style="background: #f9f9f9; border: 1px solid #292f32; padding: 15px; margin-bottom: 20px;">
        <?php echo nl2br(htmlspecialchars(substr($deleting_entry['message'], 0, 200))); ?>
        <?php if (strlen($deleting_entry['message']) > 200): ?>...<?php endif; ?>
    </div>

    <form method="post" action="guestbook.php" style="display: inline;">
        <?php admin_csrf_field(); ?>
        <input type="hidden" name="id" value="<?php echo $deleting_entry['id']; ?>">
        <button type="submit" name="action" value="delete" class="btn btn-danger">Yes, Delete</button>
    </form>
    <a href="guestbook.php" class="btn btn-secondary">Cancel</a>

<?php else: ?>
    <!-- Entries List -->
    <h2>Guestbook Entries <?php if ($pending_count > 0): ?><span style="color: #cc0000;">(<?php echo $pending_count; ?> pending)</span><?php endif; ?></h2>

    <!-- Filter Tabs -->
    <p style="margin-bottom: 15px;">
        <a href="guestbook.php" class="btn btn-small <?php echo !$filter ? '' : 'btn-secondary'; ?>">All (<?php echo guestbook_count_entries($mysqli); ?>)</a>
        <a href="guestbook.php?filter=pending" class="btn btn-small <?php echo $filter === 'pending' ? '' : 'btn-secondary'; ?>">Pending (<?php echo $pending_count; ?>)</a>
        <a href="guestbook.php?filter=approved" class="btn btn-small <?php echo $filter === 'approved' ? '' : 'btn-secondary'; ?>">Approved (<?php echo guestbook_count_entries($mysqli, 'approved'); ?>)</a>
    </p>

    <?php if (empty($entries)): ?>
        <p>No guestbook entries<?php echo $filter ? ' matching this filter' : ' yet'; ?>.</p>
    <?php else: ?>
        <table class="admin-table">
            <tr>
                <th width="5%">Status</th>
                <th width="15%">Name</th>
                <th width="15%">Email</th>
                <th width="30%">Message</th>
                <th width="12%">Date</th>
                <th width="23%">Actions</th>
            </tr>
            <?php foreach ($entries as $entry): ?>
                <?php $is_pending = (int) $entry['approved'] === 0; ?>
                <tr style="<?php echo $is_pending ? 'font-weight: bold; background-color: #fffacd;' : ''; ?>">
                    <td style="text-align: center;">
                        <?php if ($is_pending): ?>
                            <span style="color: #cc0000;" title="Pending">&#9679;</span>
                        <?php else: ?>
                            <span style="color: #006600;" title="Approved">&#9679;</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($entry['name']); ?></td>
                    <td style="font-weight: normal;">
                        <?php if (!empty($entry['email'])): ?>
                            <?php echo htmlspecialchars($entry['email']); ?>
                        <?php else: ?>
                            <em>-</em>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: normal;"><?php echo htmlspecialchars(substr($entry['message'], 0, 60)) . (strlen($entry['message']) > 60 ? '...' : ''); ?></td>
                    <td style="font-weight: normal;"><?php echo date('M j, Y', strtotime($entry['created_at'])); ?></td>
                    <td class="actions">
                        <a href="guestbook.php?action=view&id=<?php echo $entry['id']; ?>" class="btn btn-small">View</a>
                        <form method="post" action="guestbook.php<?php echo $filter ? '?filter=' . $filter : ''; ?>" style="display: inline;">
                            <?php admin_csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                            <?php if ($is_pending): ?>
                                <button type="submit" name="action" value="approve" class="btn btn-small" title="Approve">&#10003;</button>
                            <?php else: ?>
                                <button type="submit" name="action" value="unapprove" class="btn btn-small btn-secondary" title="Unapprove">&#10005;</button>
                            <?php endif; ?>
                        </form>
                        <a href="guestbook.php?action=delete&id=<?php echo $entry['id']; ?>" class="btn btn-small btn-danger" title="Delete">&#128465;</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <p style="margin-top: 15px;">
                <?php
                $filter_param = $filter ? '&filter=' . $filter : '';
                ?>
                <?php if ($page > 1): ?>
                    <a href="guestbook.php?page=<?php echo $page - 1; ?><?php echo $filter_param; ?>">&laquo; Previous</a>
                <?php endif; ?>
                &nbsp;
                Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                &nbsp;
                <?php if ($page < $total_pages): ?>
                    <a href="guestbook.php?page=<?php echo $page + 1; ?><?php echo $filter_param; ?>">Next &raquo;</a>
                <?php endif; ?>
            </p>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
