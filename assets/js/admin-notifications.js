// Admin Notifications JavaScript
// Clean implementation with custom confirmation modal

window.AdminNotifications = (function() {
    'use strict';
    
    let observer;
    let currentButtonId = null;
    let initTimeout;
    let isInitializing = false;
    
    function attachNotificationListeners() {
        if (isInitializing) return false;
        isInitializing = true;
        
        const notificationButton = document.getElementById('admin-notification-button');
        const notificationPanel = document.getElementById('admin-notification-panel');
        
        if (!notificationButton || !notificationPanel) {
            isInitializing = false;
            return false;
        }
        
        // Check if we already attached to this button instance
        const buttonInstanceId = notificationButton.dataset.listenerAttached;
        if (buttonInstanceId) {
            isInitializing = false;
            return false;
        }
        
        // Mark this button as having listeners
        const instanceId = Date.now() + Math.random();
        notificationButton.dataset.listenerAttached = instanceId;
        currentButtonId = instanceId;
        
        // Attach click listener to button
        notificationButton.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            toggleNotificationPanel();
        });
        
        // Attach clear button listener if it exists
        const clearButton = document.getElementById('clear-notifications-btn');
        if (clearButton && !clearButton.dataset.listenerAttached) {
            clearButton.dataset.listenerAttached = 'true';
            clearButton.addEventListener('click', async function(event) {
                event.preventDefault();
                event.stopPropagation();
                
                // Use custom confirmation modal with danger styling
                const confirmed = await window.showCustomConfirm({
                    title: 'Clear All Notifications?',
                    message: 'This action cannot be undone and will <strong>permanently remove</strong> all notification history.',
                    confirmText: 'Clear All',
                    cancelText: 'Cancel',
                    icon: 'message-square-warning',
                    iconColor: 'text-[var(--text-color)]',
                    iconSize: 'w-24 h-24',
                    confirmButtonClass: 'btn-primary-danger'
                });
                
                if (confirmed) {
                    clearAllNotifications();
                }
            });
        }
        
        // Close panel when clicking outside (only attach once)
        if (!document.body.dataset.notificationOutsideListener) {
            document.body.dataset.notificationOutsideListener = 'true';
            document.addEventListener('click', function(event) {
                const panel = document.getElementById('admin-notification-panel');
                if (panel && 
                    !event.target.closest('#admin-notification-button') && 
                    !event.target.closest('#admin-notification-panel')) {
                    panel.style.display = 'none';
                }
            });
        }
        
        isInitializing = false;
        return true;
    }
    
    function toggleNotificationPanel() {
        const notificationButton = document.getElementById('admin-notification-button');
        const notificationPanel = document.getElementById('admin-notification-panel');
        
        if (!notificationButton || !notificationPanel) {
            return;
        }
        
        const isHidden = notificationPanel.style.display === 'none' || notificationPanel.style.display === '';
        notificationPanel.style.display = isHidden ? 'block' : 'none';

        if (isHidden && notificationButton.querySelector('.notification-count')) {
            markNotificationsAsRead();
        }
    }
    
    function markNotificationsAsRead() {
        const notificationButton = document.getElementById('admin-notification-button');
        const notificationPanel = document.getElementById('admin-notification-panel');
        
        fetch('?mark_admin_notifications_as_read=true')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the count badge
                    const countBadge = notificationButton.querySelector('.notification-count');
                    if (countBadge) {
                        countBadge.remove();
                    }
                    // Visually mark all items as read
                    const unreadItems = notificationPanel.querySelectorAll('.notification-item[data-read="0"]');
                    unreadItems.forEach(item => {
                        item.classList.remove('unread');
                        const dot = item.querySelector('.unread-dot');
                        if (dot) {
                            dot.remove();
                        }
                        item.setAttribute('data-read', '1');
                    });
                }
            })
            .catch(error => console.error('Error marking admin notifications as read:', error));
    }
    
    function clearAllNotifications() {
        const notificationButton = document.getElementById('admin-notification-button');
        const notificationPanel = document.getElementById('admin-notification-panel');
        
        fetch('?clear_admin_notifications=true')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear the notification panel
                    const notificationList = notificationPanel.querySelector('.notification-list');
                    if (notificationList) {
                        notificationList.innerHTML = '<li class="no-notifications">You have no notifications.</li>';
                    }
                    
                    // Remove count badge
                    const countBadge = notificationButton.querySelector('.notification-count');
                    if (countBadge) {
                        countBadge.remove();
                    }
                    
                    // Remove clear button
                    const clearButton = document.getElementById('clear-notifications-btn');
                    if (clearButton) {
                        clearButton.remove();
                    }
                    
                    // Close the panel
                    notificationPanel.style.display = 'none';
                    
                    // Show success message
                    if (window.showCustomAlert) {
                        window.showCustomAlert('All notifications have been cleared successfully.', 'success', 3000, 'Notifications Cleared');
                    }
                } else {
                    if (window.showCustomAlert) {
                        window.showCustomAlert('Failed to clear notifications. Please try again.', 'error', 4000, 'Error');
                    } else {
                        alert('Failed to clear notifications. Please try again.');
                    }
                }
            })
            .catch(error => {
                console.error('Error clearing notifications:', error);
                if (window.showCustomAlert) {
                    window.showCustomAlert('Failed to clear notifications. Please try again.', 'error', 4000, 'Error');
                } else {
                    alert('Failed to clear notifications. Please try again.');
                }
            });
    }
    
    function debouncedInit() {
        if (initTimeout) {
            clearTimeout(initTimeout);
        }
        
        initTimeout = setTimeout(() => {
            attachNotificationListeners();
        }, 50);
    }
    
    function startObserver() {
        if (observer) {
            observer.disconnect();
        }
        
        observer = new MutationObserver(function(mutations) {
            let shouldInit = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    const addedNodes = Array.from(mutation.addedNodes);
                    
                    const hasNotificationButton = addedNodes.some(node => 
                        node.nodeType === 1 && (
                            node.id === 'admin-notification-button' ||
                            (node.querySelector && node.querySelector('#admin-notification-button'))
                        )
                    );
                    
                    if (hasNotificationButton) {
                        shouldInit = true;
                    }
                }
            });
            
            if (shouldInit) {
                debouncedInit();
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    return {
        init: function() {
            startObserver();
            setTimeout(() => attachNotificationListeners(), 100);
        },
        
        reinit: function() {
            setTimeout(() => attachNotificationListeners(), 150);
        }
    };
})();

window.initAdminNotifications = function() {
    window.AdminNotifications.reinit();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.AdminNotifications.init();
    });
} else {
    window.AdminNotifications.init();
} 