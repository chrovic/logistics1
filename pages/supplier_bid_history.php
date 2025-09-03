<?php
require_once '../includes/functions/auth.php';
require_once '../includes/functions/bids.php';
require_once '../includes/functions/notifications.php';
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

// Handle AJAX request to mark specific award notifications as read
if (isset($_GET['mark_award_notifications_as_read']) && $_GET['mark_award_notifications_as_read'] === 'true') {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    $notification_ids = $input['notification_ids'] ?? [];
    
    if (!empty($notification_ids)) {
        $conn = getDbConnection();
        $placeholders = str_repeat('?,', count($notification_ids) - 1) . '?';
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders)");
        $stmt->bind_param(str_repeat('i', count($notification_ids)), ...$notification_ids);
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
        echo json_encode(['success' => $success]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
}
if ($_SESSION['role'] !== 'supplier') {
    header("Location: dashboard.php");
    exit();
}

$supplier_id = getSupplierIdFromUsername($_SESSION['username']);
$bid_history = getBidsBySupplier($supplier_id); // New function
$recent_award_notifications = getRecentAwardNotificationsBySupplier($supplier_id, 3, true); // Get recent unread award notifications
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bid History - SLATE Logistics</title>
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
                    <h2 class="content-title">My Bid History</h2>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>PO Item</th>
                                    <th>My Bid Amount</th>
                                    <th>Date Submitted</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bid_history)): ?>
                                    <tr>
                                        <td colspan="4" class="empty-state">
                                            <div class="empty-state-content">
                                                <i data-lucide="history" class="empty-state-icon"></i>
                                                <p>You have not submitted any bids yet.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: foreach($bid_history as $bid): ?>
                                <tr>
                                    <td class="table-item-name"><?php echo htmlspecialchars($bid['item_name']); ?></td>
                                    <td class="table-amount">₱<?php echo number_format($bid['bid_amount'], 2); ?></td>
                                    <td class="table-date"><?php echo date("F j, Y", strtotime($bid['bid_date'])); ?></td>
                                    <td>
                                        <span class="status-badge <?php 
                                            if ($bid['status'] === 'Awarded') echo 'status-awarded';
                                            elseif ($bid['status'] === 'Rejected') echo 'status-rejected';
                                            elseif ($bid['status'] === 'Pending') echo 'status-pending';
                                            else echo 'bg-gray-100 text-gray-700'; 
                                        ?>">
                                            <?php echo htmlspecialchars($bid['status']); ?>
                                        </span>
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
    </script>
    <?php include 'modals/supplier.php'; ?>
    
    <script src="../assets/js/custom-alerts.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/supplier_portal.js"></script>
    
    <?php if (!empty($recent_award_notifications)): ?>
    <script>
        // Show Information alert about recent bid awards/rejections
        function showBidResultsAlert() {
            if (window.showCustomAlert && typeof window.showCustomAlert === 'function') {
                <?php
                    // Get the recent award notifications for the alert
                    $alert_messages = [];
                    $notification_ids = [];
                    foreach ($recent_award_notifications as $notif) {
                        $alert_messages[] = "• " . $notif['message'];
                        $notification_ids[] = $notif['id'];
                    }
                    $items_text = implode("\n", $alert_messages);
                    
                    $alert_message = "Recent Bid Results:\n\n" . $items_text;
                ?>
                
                window.showCustomAlert(
                    <?php echo json_encode($alert_message); ?>,
                    'info',
                    8000,
                    'Bid Status Updates'
                );
                
                // Mark these notifications as read after showing the alert
                setTimeout(function() {
                    fetch('?mark_award_notifications_as_read=true', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            notification_ids: <?php echo json_encode($notification_ids); ?>
                        })
                    });
                }, 1000);
            } else {
                // Retry after a short delay if not ready
                setTimeout(showBidResultsAlert, 100);
            }
        }

        // Show alert after page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(showBidResultsAlert, 500);
            });
        } else {
            setTimeout(showBidResultsAlert, 500);
        }
    </script>
    <?php endif; ?>
</body>
</html>