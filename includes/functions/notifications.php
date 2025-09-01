<?php
// Logistic1/includes/functions/notifications.php
require_once __DIR__ . '/../config/db.php';

/**
 * Creates a notification for a specific supplier.
 * @param int $supplier_id The ID of the supplier to notify.
 * @param string $message The notification message.
 * @return bool True on success, false on failure.
 */
function createNotification($supplier_id, $message) {
    if (empty($supplier_id) || empty($message)) {
        return false;
    }
    $conn = getDbConnection();
    $stmt = $conn->prepare("INSERT INTO notifications (supplier_id, message) VALUES (?, ?)");
    $stmt->bind_param("is", $supplier_id, $message);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

/**
 * Retrieves ALL notifications for a specific supplier, with unread ones first.
 * @param int $supplier_id The supplier's ID.
 * @return array An array of all notification records.
 */
function getAllNotificationsBySupplier($supplier_id) {
    if (!$supplier_id) return [];
    $conn = getDbConnection();
    // Order by is_read (unread first) and then by date (newest first)
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE supplier_id = ? ORDER BY is_read ASC, created_at DESC");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    $conn->close();
    return $notifications;
}

/**
 * Counts the number of UNREAD notifications for a specific supplier.
 * @param int $supplier_id The supplier's ID.
 * @return int The count of unread notifications.
 */
function getUnreadNotificationCountBySupplier($supplier_id) {
    
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE supplier_id = ? AND is_read = 0");
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result ? (int)$result->fetch_assoc()['count'] : 0;
    $stmt->close();
    $conn->close();
    return $count;
}


/**
 * Marks all unread notifications for a supplier as read.
 * @param int $supplier_id The supplier's ID.
 * @return bool True on success, false on failure.
 */
function markAllNotificationsAsRead($supplier_id) {
    if (empty($supplier_id)) return false;
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE supplier_id = ? AND is_read = 0");
    $stmt->bind_param("i", $supplier_id);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

/**
 * Clears (deletes) all notifications for a supplier.
 * @param int $supplier_id The supplier's ID.
 * @return bool True on success, false on failure.
 */
function clearAllSupplierNotifications($supplier_id) {
    if (empty($supplier_id)) return false;
    $conn = getDbConnection();
    $stmt = $conn->prepare("DELETE FROM notifications WHERE supplier_id = ?");
    $stmt->bind_param("i", $supplier_id);
    $success = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $success;
}

// === ADMIN AND PSM NOTIFICATION FUNCTIONS ===

/**
 * Creates a notification for admin and procurement users.
 * @param string $message The notification message.
 * @param string $type The notification type (info, success, warning, error).
 * @param int $related_id Optional related record ID.
 * @param string $related_type Optional related record type (supplier, bid, po).
 * @return bool True on success, false on failure.
 */
function createAdminNotification($message, $type = 'info', $related_id = null, $related_type = null) {
    if (empty($message)) {
        return false;
    }
    
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("INSERT INTO admin_notifications (message, type, related_id, related_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $message, $type, $related_id, $related_type);
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
        
        return $success;
    } catch (Exception $e) {
        // Silently fail if table doesn't exist
        error_log("Admin notification creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Gets all notifications for admin and procurement users with read status for specific user.
 * @param int $user_id The current user's ID.
 * @return array Array of notifications with read status.
 */
function getAdminNotifications($user_id) {
    if (!$user_id) return [];
    
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT 
                an.*,
                COALESCE(ars.is_read, 0) as is_read,
                ars.read_at
            FROM admin_notifications an
            LEFT JOIN admin_notification_read_status ars ON an.id = ars.notification_id AND ars.user_id = ?
            WHERE COALESCE(ars.is_read, 0) != 2
            ORDER BY 
                COALESCE(ars.is_read, 0) ASC, 
                an.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $notifications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        $conn->close();
        
        return $notifications;
    } catch (Exception $e) {
        // Return empty array if table doesn't exist
        error_log("Get admin notifications failed: " . $e->getMessage());
        return [];
    }
}

/**
 * Gets the count of unread notifications for admin and procurement users.
 * @param int $user_id The current user's ID.
 * @return int Count of unread notifications.
 */
function getUnreadAdminNotificationCount($user_id) {
    if (!$user_id) return 0;
    
    try {
        $conn = getDbConnection();
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM admin_notifications an
            LEFT JOIN admin_notification_read_status ars ON an.id = ars.notification_id AND ars.user_id = ?
            WHERE COALESCE(ars.is_read, 0) = 0
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result ? (int)$result->fetch_assoc()['count'] : 0;
        $stmt->close();
        $conn->close();
        
        return $count;
    } catch (Exception $e) {
        // Return 0 if table doesn't exist
        error_log("Get unread admin notification count failed: " . $e->getMessage());
        return 0;
    }
}

/**
 * Marks all notifications as read for a specific user.
 * @param int $user_id The current user's ID.
 * @return bool True on success, false on failure.
 */
function markAllAdminNotificationsAsRead($user_id) {
    if (!$user_id) return false;
    
    $conn = getDbConnection();
    
    // First, get all unread notifications for this user
    $stmt = $conn->prepare("
        SELECT an.id 
        FROM admin_notifications an
        LEFT JOIN admin_notification_read_status ars ON an.id = ars.notification_id AND ars.user_id = ?
        WHERE COALESCE(ars.is_read, 0) = 0
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $unread_notifications = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Mark each as read
    foreach ($unread_notifications as $notification) {
        $insert_stmt = $conn->prepare("
            INSERT INTO admin_notification_read_status (notification_id, user_id, is_read, read_at)
            VALUES (?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE is_read = 1, read_at = NOW()
        ");
        $insert_stmt->bind_param("ii", $notification['id'], $user_id);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    $conn->close();
    return true;
}

/**
 * Helper function to get user ID by username.
 * @param string $username The username.
 * @return int|null The user ID or null if not found.
 */
function getUserIdByUsername($username) {
    if (!$username) return null;
    
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    return $user ? (int)$user['id'] : null;
}

/**
 * Clears all notifications for admin and procurement users (role-based).
 * Marks notifications as "cleared" for the current user without affecting other users.
 * @param int $user_id The current user's ID.
 * @return bool True on success, false on failure.
 */
function clearAllAdminNotifications($user_id) {
    if (!$user_id) return false;
    
    $conn = getDbConnection();
    
    // Get all notification IDs for this user
    $notifications_stmt = $conn->prepare("
        SELECT an.id 
        FROM admin_notifications an
        WHERE an.id NOT IN (
            SELECT notification_id FROM admin_notification_read_status 
            WHERE user_id = ? AND is_read = 2
        )
    ");
    $notifications_stmt->bind_param("i", $user_id);
    $notifications_stmt->execute();
    $result = $notifications_stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    $notifications_stmt->close();
    
    // Mark all notifications as "cleared" (is_read = 2) for this user
    foreach ($notifications as $notification) {
        $insert_stmt = $conn->prepare("
            INSERT INTO admin_notification_read_status (notification_id, user_id, is_read, read_at)
            VALUES (?, ?, 2, NOW())
            ON DUPLICATE KEY UPDATE is_read = 2, read_at = NOW()
        ");
        $insert_stmt->bind_param("ii", $notification['id'], $user_id);
        $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    // Optional: Clean up notifications that have been cleared by both admin and procurement users
    $cleanup_stmt = $conn->prepare("
        DELETE an FROM admin_notifications an
        WHERE (
            SELECT COUNT(*) FROM admin_notification_read_status ars 
            WHERE ars.notification_id = an.id AND ars.is_read = 2
        ) >= (
            SELECT COUNT(*) FROM users u 
            WHERE u.role IN ('admin', 'procurement')
        )
        AND (
            SELECT COUNT(*) FROM admin_notification_read_status ars 
            WHERE ars.notification_id = an.id AND ars.is_read = 2
        ) > 0
    ");
    $cleanup_stmt->execute();
    $cleanup_stmt->close();
    
    $conn->close();
    return true;
}

/**
 * Helper function to check if user is admin or procurement.
 * @param string $role The user role.
 * @return bool True if user should receive admin notifications.
 */
function canReceiveAdminNotifications($role) {
    return in_array($role, ['admin', 'procurement']);
}
?>