// Supplier Portal Notifications
function clearAllSupplierNotifications() {
    const notificationButton = document.getElementById('notification-button');
    const notificationPanel = document.getElementById('notification-panel');
    
    fetch('?clear_supplier_notifications=true')
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
                const clearButton = document.getElementById('supplier-clear-notifications-btn');
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

document.addEventListener('DOMContentLoaded', () => {
    const notificationButton = document.getElementById('notification-button');
    const notificationPanel = document.getElementById('notification-panel');

    if (notificationButton && notificationPanel) {
        
        notificationButton.addEventListener('click', (event) => {
            event.stopPropagation();
            const isHidden = notificationPanel.style.display === 'none' || notificationPanel.style.display === '';
            
            notificationPanel.style.display = isHidden ? 'block' : 'none';

            if (isHidden && notificationButton.querySelector('.notification-count')) {
                fetch('?mark_notifications_as_read=true')
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
                    .catch(error => console.error('Error marking notifications as read:', error));
            }
        });

        window.addEventListener('click', (event) => {
            if (notificationPanel.style.display === 'block' && !notificationPanel.contains(event.target)) {
                notificationPanel.style.display = 'none';
            }
        });
        
        // Clear All button functionality
        const clearButton = document.getElementById('supplier-clear-notifications-btn');
        if (clearButton) {
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
                    iconColor: 'text-[--text-color]',
                    iconSize: 'w-24 h-24',
                    confirmButtonClass: 'btn-primary-danger'
                });
                
                if (confirmed) {
                    clearAllSupplierNotifications();
                }
            });
        }
    }
});