<?php
// Start output buffering to catch any errors
ob_start();

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    require_once '../functions/auth.php';
    require_once '../functions/bids.php';
    require_once '../functions/purchase_order.php';

    // Check if user is logged in and has proper role
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        throw new Exception('Not authenticated');
    }
    
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'procurement') {
        throw new Exception('Unauthorized access');
    }
} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}

// Clear output buffer - we want clean JSON output
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Only handle GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

try {
    $po_id = $_GET['po_id'] ?? 0;
    
    if (!$po_id) {
        throw new Exception('Missing PO ID');
    }
    
    // Get fresh bid data
    $bids = getBidsForPO($po_id);
    
    // Get PO status and deadline
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT status, ends_at FROM purchase_orders WHERE id = ?");
    $stmt->bind_param("i", $po_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $po = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    
    if (!$po) {
        throw new Exception('Purchase order not found');
    }
    
    // Check if deadline has passed (using Philippines timezone)
    $is_expired = false;
    if (!empty($po['ends_at'])) {
        // Parse the database datetime as if it's in Philippines timezone, then convert to UTC for comparison
        $deadline = new DateTime($po['ends_at'], new DateTimeZone('Asia/Manila'));
        $deadline_utc = $deadline->setTimezone(new DateTimeZone('UTC'));
        $now_utc = new DateTime('now', new DateTimeZone('UTC'));
        $is_expired = $deadline_utc <= $now_utc;
    }
    
    echo json_encode([
        'success' => true,
        'bids' => $bids,
        'po_status' => $po['status'],
        'ends_at' => $po['ends_at'],
        'is_expired' => $is_expired
    ]);
    
} catch (Exception $e) {
    // Clear any output that might have been generated
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    // Catch PHP fatal errors too
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?> 