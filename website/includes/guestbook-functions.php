<?php
/**
 * Guestbook Admin Helper Functions
 * Web 1.0 Portfolio Site
 *
 * Provides database CRUD operations for guestbook entry management.
 */

// ============================================================================
// Retrieval Functions
// ============================================================================

/**
 * Get paginated list of guestbook entries
 *
 * @param mysqli $mysqli Database connection
 * @param int $limit Number of entries to retrieve
 * @param int $offset Offset for pagination
 * @param string|null $filter Filter by approval status: 'pending', 'approved', or null for all
 * @return array List of entries (newest first)
 */
function guestbook_get_entries(mysqli $mysqli, int $limit = 20, int $offset = 0, ?string $filter = null): array
{
    $sql = "SELECT * FROM entries";
    $params = [];
    $types = '';

    if ($filter === 'pending') {
        $sql .= " WHERE approved = 0";
    } elseif ($filter === 'approved') {
        $sql .= " WHERE approved = 1";
    }

    $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        error_log("Guestbook get entries error: " . $mysqli->error);
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $entries = [];
    while ($row = $result->fetch_assoc()) {
        $entries[] = $row;
    }

    return $entries;
}

/**
 * Get total count of guestbook entries
 *
 * @param mysqli $mysqli Database connection
 * @param string|null $filter Filter by approval status: 'pending', 'approved', or null for all
 * @return int Total count
 */
function guestbook_count_entries(mysqli $mysqli, ?string $filter = null): int
{
    $sql = "SELECT COUNT(*) as count FROM entries";

    if ($filter === 'pending') {
        $sql .= " WHERE approved = 0";
    } elseif ($filter === 'approved') {
        $sql .= " WHERE approved = 1";
    }

    $result = $mysqli->query($sql);

    if (!$result) {
        error_log("Guestbook count entries error: " . $mysqli->error);
        return 0;
    }

    return (int) $result->fetch_assoc()['count'];
}

/**
 * Get a single guestbook entry by ID
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Entry ID
 * @return array|null Entry data or null if not found
 */
function guestbook_get_entry_by_id(mysqli $mysqli, int $id): ?array
{
    $stmt = $mysqli->prepare("SELECT * FROM entries WHERE id = ?");

    if (!$stmt) {
        error_log("Guestbook get entry error: " . $mysqli->error);
        return null;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc() ?: null;
}

/**
 * Count pending (unapproved) guestbook entries
 *
 * @param mysqli $mysqli Database connection
 * @return int Number of pending entries
 */
function guestbook_count_pending(mysqli $mysqli): int
{
    $result = $mysqli->query("SELECT COUNT(*) as count FROM entries WHERE approved = 0");

    if (!$result) {
        error_log("Guestbook count pending error: " . $mysqli->error);
        return 0;
    }

    return (int) $result->fetch_assoc()['count'];
}

// ============================================================================
// Modification Functions
// ============================================================================

/**
 * Approve a guestbook entry
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Entry ID
 * @return bool True on success
 */
function guestbook_approve(mysqli $mysqli, int $id): bool
{
    $stmt = $mysqli->prepare("UPDATE entries SET approved = 1 WHERE id = ?");

    if (!$stmt) {
        error_log("Guestbook approve error: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

/**
 * Unapprove a guestbook entry
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Entry ID
 * @return bool True on success
 */
function guestbook_unapprove(mysqli $mysqli, int $id): bool
{
    $stmt = $mysqli->prepare("UPDATE entries SET approved = 0 WHERE id = ?");

    if (!$stmt) {
        error_log("Guestbook unapprove error: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}

/**
 * Delete a guestbook entry
 *
 * @param mysqli $mysqli Database connection
 * @param int $id Entry ID
 * @return bool True on success
 */
function guestbook_delete(mysqli $mysqli, int $id): bool
{
    $stmt = $mysqli->prepare("DELETE FROM entries WHERE id = ?");

    if (!$stmt) {
        error_log("Guestbook delete error: " . $mysqli->error);
        return false;
    }

    $stmt->bind_param('i', $id);
    $stmt->execute();

    return $stmt->affected_rows > 0;
}
