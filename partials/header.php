<?php
// Enhanced FOUC prevention with CSS load detection
require_once __DIR__ . '/../includes/functions/notifications.php';

// Get notification data for admin and procurement users
$current_user_id = null;
$admin_notifications = [];
$admin_notification_count = 0;
$show_notification_icon = false;

// Show notification icon for all roles except supplier
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'supplier') {
    $show_notification_icon = true;
    
    // Only load actual notification data for admin and procurement users
    if (canReceiveAdminNotifications($_SESSION['role'] ?? '')) {
        try {
            $current_user_id = getUserIdByUsername($_SESSION['username']);
            if ($current_user_id) {
                $admin_notifications = getAdminNotifications($current_user_id);
                $admin_notification_count = getUnreadAdminNotificationCount($current_user_id);
            }
        } catch (Exception $e) {
            // Silently fail if database tables don't exist yet
            error_log("Admin notifications error: " . $e->getMessage());
            $admin_notifications = [];
            $admin_notification_count = 0;
        }
    }
}
?>
<script>
(function() {
  document.documentElement.classList.add('loading', 'preload');

  const theme = localStorage.getItem('theme');
  if (theme === 'dark') {
    document.documentElement.classList.add('dark-mode');
  }
  
  function showContent() {
    document.documentElement.classList.remove('loading');
    document.documentElement.classList.add('loaded');
    setTimeout(() => {
      document.documentElement.classList.remove('preload');
    }, 150);
  }
  
  if (document.readyState === 'complete') {
    showContent();
  } else {
    window.addEventListener('load', showContent);
    setTimeout(showContent, 500);
  }
})();
</script>
<script>
  window.addEventListener('pageshow', function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
</script>

<div class="header">
  <div class="w-10 h-10 flex items-center justify-center mr-2.5 rounded-full text-[var(--text-color)] cursor-pointer hover:bg-[var(--dropdown-item-hover)] transition-colors duration-300" id="hamburger">
    <i class="fa-solid fa-bars-staggered text-xl" id="barsIcon"></i>
    <i class="fa-solid fa-bars text-xl hidden" id="xmarkIcon"></i>
  </div>
  <div>
    <h1><?php echo ($_SESSION['role'] === 'admin') ? 'Admin Panel' : 'Staff Panel'; ?> <span class="system-title">| LOGISTICS 1</span></h1>
  </div>
    <div class="theme-toggle-container">
        <!-- Notification Icon for All Non-Supplier Users -->
        <?php if ($show_notification_icon): ?>
        <div class="notification-container" id="admin-notification-button">
            <i data-lucide="bell" class="notification-bell"></i>
            <?php if ($admin_notification_count > 0): ?>
            <span class="notification-count">
                <?php echo $admin_notification_count; ?>
            </span>
            <?php endif; ?>
            <div id="admin-notification-panel" class="notification-panel">
                <div class="notification-header">
                    <span>Notifications</span>
                    <?php if (canReceiveAdminNotifications($_SESSION['role'] ?? '') && !empty($admin_notifications)): ?>
                    <button class="notification-clear-btn" id="clear-notifications-btn" data-action="clear">Clear All</button>
                    <?php endif; ?>
                </div>
                <ul class="notification-list">
                    <?php if (canReceiveAdminNotifications($_SESSION['role'] ?? '')): ?>
                        <?php if (empty($admin_notifications)): ?>
                            <li class="no-notifications">You have no notifications.</li>
                        <?php else: ?>
                            <?php foreach ($admin_notifications as $notif): ?>
                                <li class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-read="<?php echo $notif['is_read']; ?>">
                                    <div class="notification-content">
                                        <?php if (!$notif['is_read']): ?>
                                          <span class="unread-dot"></span>
                                        <?php endif; ?>
                                        <div class="notification-text">
                                          <p class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></p>
                                          <p class="notification-time"><?php echo date("F j, Y, g:i a", strtotime($notif['created_at'])); ?></p>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Non-functional notification panel for other roles -->
                        <li class="no-notifications">Notification functionality coming soon for your role.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="admin-profile-dropdown">
            <div class="admin-profile flex items-center bg-[var(--card-bg)] rounded-full shadow-[inset_0_0_0_2px_var(--border-color)] p-2 pr-2" id="adminProfileToggle">
                <span class="admin-name ml-2 mr-1 text-[var(--text-color)]"><?php echo ($_SESSION['role'] === 'admin') ? 'Administrator' : ucfirst($_SESSION['username'] ?? 'User'); ?></span>
                <img src="../assets/images/admin.png" alt="Admin Avatar" class="admin-avatar h-7 w-7 rounded-full">
                <svg class="w-4 h-4 text-[var(--text-color)] mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
            <div class="dropdown-menu" id="adminDropdownMenu">
                <a href="#"><i data-lucide="scroll-text" class="w-5 h-5 mr-3"></i> Reports</a>
                <a href="#" id="logoutButton" onclick="sessionStorage.setItem('logout_in_progress', 'true');"><i data-lucide="log-out" class="w-5 h-5 mr-3"></i> Logout</a>
            </div>
        </div>
        <span class="theme-label ml-4"></span>
        <label class="theme-switch">
            <input type="checkbox" id="themeToggle">
            <span class="slider"></span>
        </label>
    </div>
</div>
<div class="header-line"></div>

<div id="logoutConfirmModal" class="modal hidden fixed inset-0 flex items-center justify-center">
    <div class="modal-content bg-[var(--card-bg)] p-10 rounded-3xl shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto relative flex flex-col items-center justify-center text-center">
      <i data-lucide="user-round-minus" class="w-24 h-24 mb-4"></i>
      <h2 class="modal-title mb-4">Confirm Logout</h2>
      <p class="mb-6 text-[var(--text-color)]">Are you sure you want to log out? You will need to login again to continue.</p>
      <div class="form-actions flex justify-center pt-4 space-x-2 border-gray-200 dark:border-gray-700">
        <button type="button" class="btn bg-[var(--cancel-btn-bg)] hover:bg-gray-400 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" onclick="window.closeModal(document.getElementById('logoutConfirmModal'))">No, cancel</button>
        <button id="confirmLogoutBtn" class="btn btn-danger bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Yes, logout</button>
      </div>
    </div>
</div>

<div id="customConfirmModal" class="modal hidden fixed inset-0 flex items-center justify-center">
    <div class="modal-content bg-[var(--card-bg)] p-10 rounded-3xl shadow-xl w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto relative flex flex-col items-center justify-center text-center">
        <div id="confirmModalIcon" class="w-24 h-24 mb-4 flex items-center justify-center">
            <i data-lucide="message-square-warning" class="w-24 h-24"></i>
        </div>
        <h2 id="confirmModalTitle" class="modal-title mb-4">Confirm Action</h2>
        <p id="confirmModalMessage" class="mb-6 text-[var(--text-color)]">Are you sure you want to continue?</p>
        <div class="form-actions flex justify-center pt-4 space-x-2 border-gray-200 dark:border-gray-700">
            <button type="button" id="confirmModalCancel" class="btn bg-[var(--cancel-btn-bg)] hover:bg-gray-400 font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Cancel</button>
            <button type="button" id="confirmModalConfirm" class="btn btn-danger bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Confirm</button>
        </div>
    </div>
</div>

<script src="../assets/js/custom-alerts.js"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script src="../assets/js/custom-dropdown.js"></script>
<script src="../assets/js/custom-datepicker.js"></script>
<script src="../assets/js/admin-notifications.js"></script>