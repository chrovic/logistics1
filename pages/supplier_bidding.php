<?php
require_once '../includes/functions/auth.php';
require_once '../includes/functions/bids.php';
require_once '../includes/functions/notifications.php'; // Required for header
requireLogin();

// Handle AJAX request to mark notifications as read
if (isset($_GET['mark_notifications_as_read']) && $_GET['mark_notifications_as_read'] === 'true') {
    header('Content-Type: application/json');
    $supplier_id = getSupplierIdFromUsername($_SESSION['username']);
    if (markAllNotificationsAsRead($supplier_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// Handle AJAX request to clear all notifications
if (isset($_GET['clear_supplier_notifications']) && $_GET['clear_supplier_notifications'] === 'true') {
    header('Content-Type: application/json');
    $supplier_id = getSupplierIdFromUsername($_SESSION['username']);
    if (clearAllSupplierNotifications($supplier_id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

// Handle logout action
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}

// Security: Ensure only suppliers can access this page
if ($_SESSION['role'] !== 'supplier') {
    header("Location: dashboard.php");
    exit();
}

// Handle Form Submission for Placing a Bid
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_bid') {
    $po_id = $_POST['po_id'] ?? 0;
    $bid_amount = $_POST['bid_amount'] ?? 0;
    $notes = $_POST['notes'] ?? '';
    $terms_agreement = isset($_POST['terms_agreement']) ? true : false;
    
    // Validate terms agreement
    if (!$terms_agreement) {
        $_SESSION['message'] = 'You must accept the Terms & Conditions to submit a bid.';
        $_SESSION['message_type'] = 'error';
        header("Location: supplier_bidding.php");
        exit();
    }
    
    // Get the logged-in supplier's ID
    $supplier_id = getSupplierIdFromUsername($_SESSION['username']);

    if ($supplier_id && createBid($po_id, $supplier_id, $bid_amount, $notes)) {
        $_SESSION['message'] = 'Your bid has been submitted successfully! You will be notified if your bid is awarded or rejected.';
        $_SESSION['message_type'] = 'success';
    } else {
        // Check if the PO still exists and is still open for bidding
        $conn = getDbConnection();
        $check_stmt = $conn->prepare("SELECT status, ends_at FROM purchase_orders WHERE id = ?");
        $check_stmt->bind_param("i", $po_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $po_data = $check_result->fetch_assoc();
            
            if ($po_data['status'] !== 'Open for Bidding') {
                $_SESSION['message'] = 'This bidding opportunity is no longer open. The bidding period has ended.';
            } elseif (!empty($po_data['ends_at'])) {
                $deadline = new DateTime($po_data['ends_at'], new DateTimeZone('UTC'));
                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                $now_utc = $now->setTimezone(new DateTimeZone('UTC'));
                
                if ($deadline <= $now_utc) {
                    $_SESSION['message'] = 'The deadline for this bidding opportunity has passed. You can no longer submit bids.';
                } else {
                    $_SESSION['message'] = 'Failed to submit your bid. Please try again or contact support if the issue persists.';
                }
            } else {
                $_SESSION['message'] = 'Failed to submit your bid. Please try again or contact support if the issue persists.';
            }
        } else {
            $_SESSION['message'] = 'This bidding opportunity no longer exists.';
        }
        
        $check_stmt->close();
        $conn->close();
        $_SESSION['message_type'] = 'error';
    }
    // Redirect to prevent form resubmission
    header("Location: supplier_bidding.php");
    exit();
}

// NO AWARD NOTIFICATIONS IN OPEN BIDS TAB - COMPLETELY BLOCKED
$message = '';
$message_type = '';

// Clear any session messages about awards/rejections immediately 
if (isset($_SESSION['message'])) {
    $session_message = $_SESSION['message'];
    
    // NUKE any award/rejection messages - they belong in Bid History only
    if (preg_match('/congratulations|awarded|not selected|rejected/i', $session_message) && 
        !preg_match('/bid.*submitted|failed.*submit/i', $session_message)) {
        // Award notifications detected - redirect to Bid History where they belong
        unset($_SESSION['message'], $_SESSION['message_type']);
        // Don't show anything in Open Bids tab
    } else {
        // Allow bid submission confirmations (success and error)
        if (preg_match('/bid.*submitted|failed.*submit|your bid has been submitted|failed to submit your bid/i', $session_message)) {
            $message = $session_message;
            $message_type = $_SESSION['message_type'];
        }
        unset($_SESSION['message'], $_SESSION['message_type']);
    }
}

// NUCLEAR OPTION: Mark all award notifications as read when visiting Open Bids tab
$supplier_id_for_cleanup = getSupplierIdFromUsername($_SESSION['username']);
if ($supplier_id_for_cleanup) {
    $conn = getDbConnection();
    // Mark ALL award/rejection notifications as read BUT keep bid submission notifications
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE supplier_id = ? AND (message LIKE '%Congratulations%' OR message LIKE '%awarded%' OR message LIKE '%not selected%' OR message LIKE '%rejected%') AND message NOT LIKE '%bid%submitted%' AND message NOT LIKE '%failed%submit%'");
    $stmt->bind_param("i", $supplier_id_for_cleanup);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

// Fetch Data for the Page
$open_purchase_orders = getOpenForBiddingPOs(); // All open opportunities for the table
$fresh_purchase_orders = getOpenForBiddingPOsWithoutBids(); // Fresh opportunities for alert
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bidding Portal - SLATE Logistics</title>
    <link rel="icon" href="../assets/images/slate2.png" type="image/png">
    <link rel="stylesheet" href="../assets/css/supplier_portal.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <div class="supplier-content-full">
        <?php include '../partials/supplier_header.php'; ?>

        <div class="supplier-content-area">
            <!-- Navigation Tabs -->
            <div class="supplier-tabs-container">
                <div class="supplier-tabs-bar">
                    <a href="supplier_dashboard.php" class="supplier-tab-button <?php echo ($currentPage === 'supplier_dashboard.php') ? 'active' : ''; ?>">
                        <i data-lucide="layout-dashboard" class="tab-icon"></i>
                        Dashboard
                    </a>
                    <a href="supplier_bidding.php" class="supplier-tab-button <?php echo ($currentPage === 'supplier_bidding.php') ? 'active' : ''; ?>">
                        <i data-lucide="gavel" class="tab-icon"></i>
                        Open Bids
                    </a>
                    <a href="supplier_bid_history.php" class="supplier-tab-button <?php echo ($currentPage === 'supplier_bid_history.php') ? 'active' : ''; ?>">
                        <i data-lucide="history" class="tab-icon"></i>
                        Bid History
                    </a>
                </div>
            </div>

            <div class="supplier-content-container">
                <div class="content-card">
                    <h2 class="content-title">Open for Bidding</h2>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Date Posted</th>
                                    <th>Ends At</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($open_purchase_orders)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-10 text-gray-500">
                                            <div class="flex flex-col items-center gap-3">
                                                <i data-lucide="gavel" class="w-12 h-12 text-gray-300"></i>
                                                <p>There are no purchase orders currently open for bidding.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: foreach($open_purchase_orders as $po): ?>
                                <tr>
                                    <td class="font-semibold"><?php echo htmlspecialchars($po['item_name']); ?></td>
                                    <td><?php echo $po['quantity']; ?></td>
                                    <td class="text-gray-600"><?php echo date("F j, Y", strtotime($po['order_date'])); ?></td>
                                    <td class="deadline-cell text-gray-600" data-deadline="<?php echo $po['ends_at'] ? $po['ends_at'] : ''; ?>" data-po-id="<?php echo $po['id']; ?>">
                                        <?php 
                                        if ($po['ends_at']): 
                                            echo '<div class="countdown-display" data-target="' . $po['ends_at'] . '">';
                                            echo date("M j, Y g:i A", strtotime($po['ends_at']));
                                            echo '<div class="countdown-timer text-xs text-gray-500 mt-1"></div>';
                                            echo '</div>';
                                        else: 
                                            echo '<span class="text-gray-400">No deadline</span>';
                                        endif; 
                                        ?>
                                    </td>
                                    <td class="text-right">
                                        <button onclick="openBidModal(<?php echo $po['id']; ?>, '<?php echo htmlspecialchars(addslashes($po['item_name'])); ?>')" class="btn-primary">
                                            <i data-lucide="gavel" class="w-4 h-4"></i>
                                            Place Bid
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // COMPLETE NUCLEAR OVERRIDE - Block ALL award notifications in Open Bids tab
        document.addEventListener('DOMContentLoaded', function() {
            // Override showCustomAlert to completely block award notifications
            const originalShowCustomAlert = window.showCustomAlert;
            if (originalShowCustomAlert) {
                window.showCustomAlert = function(message, type, duration, title) {
                    // NUKE any award-related alerts in Open Bids tab BUT allow bid submission messages
                    if (message && typeof message === 'string') {
                        const isBidSubmission = /bid.*submitted|failed.*submit|your bid has been submitted|failed to submit your bid/i.test(message);
                        const isAwardAlert = /congratulations|awarded|not selected|rejected|has been awarded|bid.*for.*awarded|po.*#.*awarded/i.test(message);
                        
                        // Block award alerts but allow bid submission alerts
                        if (isAwardAlert && !isBidSubmission) {
                            return; // COMPLETELY BLOCKED - NO AWARD NOTIFICATIONS IN OPEN BIDS
                        }
                    }
                    // Allow fresh bidding opportunities and bid submission alerts
                    return originalShowCustomAlert.call(this, message, type, duration, title);
                };
            }
            
            // Also override any other potential alert functions
            if (window.alert) {
                const originalAlert = window.alert;
                window.alert = function(message) {
                    if (message && typeof message === 'string') {
                        const isBidSubmission = /bid.*submitted|failed.*submit|your bid has been submitted|failed to submit your bid/i.test(message);
                        const isAwardAlert = /congratulations|awarded|not selected|rejected|has been awarded/i.test(message);
                        
                        // Block award alerts but allow bid submission alerts
                        if (isAwardAlert && !isBidSubmission) {
                            return; // BLOCKED
                        }
                    }
                    return originalAlert.call(this, message);
                };
            }
        });
    </script>
    <?php include 'modals/supplier.php'; ?>
    
    <script src="../assets/js/custom-alerts.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/deadline-countdown.js"></script>
    
    <?php if (!empty($message)): ?>
    <script>
        // Show success/error alert for bid submission
        function showBidResultAlert() {
            if (window.showCustomAlert && typeof window.showCustomAlert === 'function') {
                window.showCustomAlert(
                    <?php echo json_encode($message); ?>,
                    <?php echo json_encode($message_type); ?>,
                    5000,
                    <?php echo json_encode($message_type === 'success' ? 'Bid Submitted' : 'Bid Failed'); ?>
                );
            } else {
                // Retry after a short delay if not ready
                setTimeout(showBidResultAlert, 50);
            }
        }

        // Show bid result alert immediately after page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(showBidResultAlert, 100);
            });
        } else {
            setTimeout(showBidResultAlert, 100);
        }
    </script>
    <?php endif; ?>
    
    <script src="../assets/js/supplier_portal.js"></script>
    
        <?php if (!empty($fresh_purchase_orders)): ?>
    <script>
        // Show Information alert about fresh bidding opportunities (no bids yet)
        function showBiddingOpportunitiesAlert() {
            if (window.showCustomAlert && typeof window.showCustomAlert === 'function') {
                <?php
                    // Get the first few fresh items for the alert
                    $alert_items = array_slice($fresh_purchase_orders, 0, 3);
                    $alert_messages = [];
                    foreach ($alert_items as $po) {
                        $alert_messages[] = "• " . htmlspecialchars($po['item_name']) . " (Qty: " . $po['quantity'] . ")";
                    }
                    $items_text = implode("\n", $alert_messages);
                    
                    if (count($fresh_purchase_orders) > 3) {
                        $items_text .= "\n• And " . (count($fresh_purchase_orders) - 3) . " more fresh opportunities...";
                    }
                    
                    $alert_message = "Fresh bidding opportunities (no bids yet):\n\n" . $items_text;
                ?>
                
                window.showCustomAlert(
                    <?php echo json_encode($alert_message); ?>,
                    'info',
                    8000,
                    'Fresh Bidding Opportunities'
                );
            } else {
                // Retry after a short delay if not ready
                setTimeout(showBiddingOpportunitiesAlert, 100);
            }
        }

        // Show alert after page loads (with delay to allow bid success alerts to show first)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(showBiddingOpportunitiesAlert, 1200);
            });
        } else {
            setTimeout(showBiddingOpportunitiesAlert, 1200);
        }
    </script>
    <?php endif; ?>
</body>
</html>