/**
 * Admin Notifications Module
 * Floating notification system for admin pages
 */

// Global variables
let notificationModal = null;
let notificationButton = null;
let notificationBadge = null;
let notificationsData = [];
let unreadCount = 0;
let markedAsRead = false; // Flag to track if notifications are marked as read

// Initialize the notification module
function initAdminNotifications() {
    createNotificationElements();
    // Load marked as read state from localStorage
    markedAsRead = localStorage.getItem('adminNotificationsMarkedAsRead') === 'true';
    updateNotificationBadge();
    setupEventListeners();

    // Auto-refresh notifications every 30 seconds
    setInterval(updateNotificationBadge, 30000);
}

// Create the floating button and modal elements dynamically
function createNotificationElements() {
    // Create floating button
    notificationButton = document.createElement('button');
    notificationButton.className = 'notification-float';
    notificationButton.innerHTML = '<i class="fas fa-bell"></i>';
    notificationButton.title = 'Notifications';

    // Create badge
    notificationBadge = document.createElement('span');
    notificationBadge.className = 'notification-badge hidden';
    notificationBadge.textContent = '0';
    notificationButton.appendChild(notificationBadge);

    // Create modal overlay
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'notification-modal-overlay';
    modalOverlay.id = 'notificationModalOverlay';

    modalOverlay.innerHTML = `
        <div class="notification-modal">
            <div class="notification-modal-header">
                <h3 class="notification-modal-title">
                    <i class="fas fa-bell"></i> Notifications
                </h3>
                <button class="notification-modal-close" onclick="closeNotificationModal()">&times;</button>
            </div>
            <div class="notification-modal-body">
                <div class="notification-loading">
                    <div class="notification-loading-spinner"></div>
                    <p>Loading notifications...</p>
                </div>
            </div>
            <div class="notification-modal-footer">
                <div class="notification-count">0 notifications</div>
                <div class="notification-actions">
                    <button class="btn-mark-read" onclick="markAllAsRead()">Mark All Read</button>
                    <button class="btn-clear-all" onclick="clearAllNotifications()">Clear All</button>
                </div>
            </div>
        </div>
    `;

    // Add to page
    document.body.appendChild(notificationButton);
    document.body.appendChild(modalOverlay);

    // Get modal reference
    notificationModal = modalOverlay;
}

// Set up event listeners
function setupEventListeners() {
    // Button click
    notificationButton.addEventListener('click', openNotificationModal);

    // Close modal when clicking overlay
    notificationModal.addEventListener('click', function(e) {
        if (e.target === notificationModal) {
            closeNotificationModal();
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && notificationModal.classList.contains('show')) {
            closeNotificationModal();
        }
    });
}

// Update the notification badge with current count
function updateNotificationBadge() {
    fetch('api_notifications.php?for=admin')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                notificationsData = data.data;
                const actualCount = notificationsData.length;

                // If marked as read but there are new notifications, reset the read status
                if (markedAsRead && actualCount > 0) {
                    markedAsRead = false;
                    localStorage.setItem('adminNotificationsMarkedAsRead', 'false');
                }

                // Update count and badge only if not marked as read
                if (!markedAsRead) {
                    unreadCount = actualCount;
                    if (unreadCount > 0) {
                        notificationBadge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                        notificationBadge.classList.remove('hidden');
                    } else {
                        notificationBadge.classList.add('hidden');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error fetching notifications:', error);
        });
}

// Open the notification modal
function openNotificationModal() {
    markedAsRead = false; // Reset read status when opening modal
    localStorage.setItem('adminNotificationsMarkedAsRead', 'false');
    notificationModal.classList.add('show');
    loadNotifications();
}

// Close the notification modal
function closeNotificationModal() {
    notificationModal.classList.remove('show');
}

// Load and display notifications in the modal
function loadNotifications() {
    const modalBody = notificationModal.querySelector('.notification-modal-body');
    const modalFooter = notificationModal.querySelector('.notification-modal-footer');
    const countDisplay = modalFooter.querySelector('.notification-count');

    // Show loading
    modalBody.innerHTML = `
        <div class="notification-loading">
            <div class="notification-loading-spinner"></div>
            <p>Loading notifications...</p>
        </div>
    `;

    fetch('api_notifications.php?for=admin')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                notificationsData = data.data;
                unreadCount = notificationsData.length;

                // Update count display
                countDisplay.textContent = `${unreadCount} notification${unreadCount !== 1 ? 's' : ''}`;

                if (notificationsData.length === 0) {
                    // Empty state
                    modalBody.innerHTML = `
                        <div class="notification-empty">
                            <div class="notification-empty-icon">
                                <i class="fas fa-bell-slash"></i>
                            </div>
                            <p class="notification-empty-text">No notifications at this time.</p>
                        </div>
                    `;
                } else {
                    // Display notifications
                    const notificationList = document.createElement('ul');
                    notificationList.className = 'notification-list';

                    notificationsData.forEach(notification => {
                        const listItem = createNotificationItem(notification);
                        notificationList.appendChild(listItem);
                    });

                    modalBody.innerHTML = '';
                    modalBody.appendChild(notificationList);
                }

                // Update badge
                updateNotificationBadge();
            } else {
                // Error state
                modalBody.innerHTML = `
                    <div class="notification-error">
                        <div class="notification-error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <p class="notification-error-text">Failed to load notifications. Please try again.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            modalBody.innerHTML = `
                <div class="notification-error">
                    <div class="notification-error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <p class="notification-error-text">Network error. Please check your connection and try again.</p>
                </div>
            `;
        });
}

// Create a notification list item
function createNotificationItem(notification) {
    const listItem = document.createElement('li');
    listItem.className = 'notification-item';
    listItem.dataset.id = notification.id;

    // Determine icon and priority class based on priority
    let iconClass, priorityClass, priorityLabel;
    switch (notification.priority) {
        case 'high':
            iconClass = 'fas fa-exclamation-circle';
            priorityClass = 'high';
            priorityLabel = 'Urgent';
            break;
        case 'medium':
            iconClass = 'fas fa-exclamation-triangle';
            priorityClass = 'medium';
            priorityLabel = 'Reminder';
            break;
        default:
            iconClass = 'fas fa-check-circle';
            priorityClass = 'low';
            priorityLabel = 'FYI';
    }

    // Format date
    let dateDisplay = '';
    if (notification.created_at) {
        const date = new Date(notification.created_at);
        dateDisplay = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    listItem.innerHTML = `
        <div class="notification-icon">
            <i class="${iconClass}"></i>
        </div>
        <div class="notification-content">
            <p class="notification-message">${escapeHtml(notification.message)}</p>
            <div class="notification-meta">
                <span class="notification-priority ${priorityClass}">${priorityLabel}</span>
                ${dateDisplay ? `<span class="notification-date">${dateDisplay}</span>` : ''}
            </div>
        </div>
    `;

    return listItem;
}

// Mark all notifications as read
function markAllAsRead() {
    if (confirm('Mark all notifications as read?')) {
        // Mark as read by resetting the badge count
        markedAsRead = true;
        localStorage.setItem('adminNotificationsMarkedAsRead', 'true');
        unreadCount = 0;
        // Manually update badge without fetching
        if (notificationBadge) {
            notificationBadge.classList.add('hidden');
            notificationBadge.textContent = '0';
        }
        closeNotificationModal();
        alert('All notifications marked as read.');
    }
}

// Clear all notifications
function clearAllNotifications() {
    if (confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
        // This would typically call a clear API endpoint
        // For now, we'll simulate it
        fetch('api_notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_all'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                notificationsData = [];
                unreadCount = 0;
                markedAsRead = false;
                localStorage.setItem('adminNotificationsMarkedAsRead', 'false');
                updateNotificationBadge();
                loadNotifications(); // Refresh modal
                alert('All notifications cleared successfully.');
            } else {
                alert('Failed to clear notifications: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error clearing notifications:', error);
            alert('Network error. Please try again.');
        });
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Export functions for global access
window.initAdminNotifications = initAdminNotifications;
window.closeNotificationModal = closeNotificationModal;
window.markAllAsRead = markAllAsRead;
window.clearAllNotifications = clearAllNotifications;