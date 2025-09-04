<?php
/**
 * Deadline Handler - Server-side deadline enforcement for bidding
 * Handles automatic status updates when bidding deadlines expire
 */

require_once '../config/db.php';
require_once '../functions/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is authenticated
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the action
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'close_expired_bidding':
        closeExpiredBidding();
        break;
    
    case 'check_expired_pos':
        checkExpiredPOs();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Close bidding for a specific PO that has exceeded its deadline
 */
function closeExpiredBidding() {
    $po_id = (int)($_POST['po_id'] ?? 0);
    
    if ($po_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid PO ID']);
        return;
    }
    
    $conn = getDbConnection();
    
    try {
        // Get current PO status and deadline
        $stmt = $conn->prepare("SELECT status, ends_at FROM purchase_orders WHERE id = ?");
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Purchase order not found');
        }
        
        $po = $result->fetch_assoc();
        $stmt->close();
        
        // Check if PO is currently open for bidding and has a deadline
        if ($po['status'] !== 'Open for Bidding') {
            echo json_encode(['success' => true, 'message' => 'PO is not open for bidding']);
            return;
        }
        
        if (empty($po['ends_at'])) {
            echo json_encode(['success' => false, 'message' => 'PO has no deadline set']);
            return;
        }
        
        // Check if deadline has actually passed (using Philippines timezone)
        $deadline = new DateTime($po['ends_at'], new DateTimeZone('Asia/Manila'));
        $deadline_utc = $deadline->setTimezone(new DateTimeZone('UTC'));
        $now_utc = new DateTime('now', new DateTimeZone('UTC'));
        
        if ($deadline_utc > $now_utc) {
            echo json_encode(['success' => false, 'message' => 'Deadline has not yet passed']);
            return;
        }
        
        // Update PO status to "Bidding Closed"
        $stmt = $conn->prepare("UPDATE purchase_orders SET status = 'Bidding Closed' WHERE id = ?");
        $stmt->bind_param("i", $po_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update purchase order status');
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Bidding closed due to deadline expiry',
            'po_id' => $po_id,
            'new_status' => 'Bidding Closed'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
}

/**
 * Check and update multiple expired POs
 */
function checkExpiredPOs() {
    $po_ids_str = $_POST['po_ids'] ?? '';
    $po_ids = array_filter(array_map('intval', explode(',', $po_ids_str)));
    
    if (empty($po_ids)) {
        echo json_encode(['success' => false, 'message' => 'No valid PO IDs provided']);
        return;
    }
    
    $conn = getDbConnection();
    $updated_pos = [];
    
    try {
        // Get current time in Philippines timezone
        $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $now_utc = clone $now;
        $now_utc->setTimezone(new DateTimeZone('UTC'));
        
        // Prepare placeholders for IN clause
        $placeholders = str_repeat('?,', count($po_ids) - 1) . '?';
        
        // Get POs that are still open for bidding and have expired deadlines
        $query = "SELECT id, ends_at FROM purchase_orders 
                  WHERE id IN ($placeholders) 
                  AND status = 'Open for Bidding' 
                  AND ends_at IS NOT NULL 
                  AND ends_at <= ?";
        
        $stmt = $conn->prepare($query);
        $params = array_merge($po_ids, [$now_utc->format('Y-m-d H:i:s')]);
        $types = str_repeat('i', count($po_ids)) . 's';
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $expired_pos = [];
        while ($row = $result->fetch_assoc()) {
            $expired_pos[] = $row['id'];
        }
        $stmt->close();
        
        // Update expired POs
        if (!empty($expired_pos)) {
            $update_placeholders = str_repeat('?,', count($expired_pos) - 1) . '?';
            $update_query = "UPDATE purchase_orders SET status = 'Bidding Closed' WHERE id IN ($update_placeholders)";
            $stmt = $conn->prepare($update_query);
            $update_types = str_repeat('i', count($expired_pos));
            $stmt->bind_param($update_types, ...$expired_pos);
            
            if ($stmt->execute()) {
                $updated_pos = $expired_pos;
            }
            
            $stmt->close();
        }
        
        echo json_encode([
            'success' => true,
            'updated_pos' => $updated_pos,
            'total_checked' => count($po_ids),
            'total_updated' => count($updated_pos)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->close();
    }
}

/**
 * Check for any expired POs and close them (can be called via cron job)
 */
function checkAllExpiredPOs() {
    $conn = getDbConnection();
    
    try {
        // Get current time in Philippines timezone
        $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
        $now_utc = clone $now;
        $now_utc->setTimezone(new DateTimeZone('UTC'));
        
        // Find all POs that are open for bidding but have expired deadlines
        $query = "SELECT id FROM purchase_orders 
                  WHERE status = 'Open for Bidding' 
                  AND ends_at IS NOT NULL 
                  AND ends_at <= ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $now_utc->format('Y-m-d H:i:s'));
        $stmt->execute();
        $result = $stmt->get_result();
        
        $expired_po_ids = [];
        while ($row = $result->fetch_assoc()) {
            $expired_po_ids[] = $row['id'];
        }
        $stmt->close();
        
        // Update all expired POs
        if (!empty($expired_po_ids)) {
            $placeholders = str_repeat('?,', count($expired_po_ids) - 1) . '?';
            $update_query = "UPDATE purchase_orders SET status = 'Bidding Closed' WHERE id IN ($placeholders)";
            $stmt = $conn->prepare($update_query);
            $types = str_repeat('i', count($expired_po_ids));
            $stmt->bind_param($types, ...$expired_po_ids);
            $stmt->execute();
            $stmt->close();
        }
        
        return $expired_po_ids;
        
    } catch (Exception $e) {
        error_log("Error in checkAllExpiredPOs: " . $e->getMessage());
        return [];
    } finally {
        $conn->close();
    }
}

// If called directly (e.g., via cron), check all expired POs
if (php_sapi_name() === 'cli') {
    $expired = checkAllExpiredPOs();
    echo "Checked and closed " . count($expired) . " expired POs\n";
}
?> 