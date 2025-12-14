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
                    <button class="btn-mark-read" onclick="showMarkReadModal()">Mark All Read</button>
                    <button class="btn-clear-all" onclick="showClearAllModal()">Clear All</button>
                </div>
            </div>
        </div>
    `;

    // Add to page
    document.body.appendChild(notificationButton);
    document.body.appendChild(modalOverlay);

    // Create confirmation modals
    createConfirmationModals();

    // Get modal reference
    notificationModal = modalOverlay;
}

// Create confirmation modals for mark all read and clear all actions
function createConfirmationModals() {
    // Mark All Read confirmation modal
    const markReadModal = document.createElement('div');
    markReadModal.className = 'confirmation-modal-overlay';
    markReadModal.id = 'markReadConfirmationModal';
    markReadModal.innerHTML = `
        <div class="confirmation-modal">
            <div class="confirmation-modal-header">
                <h3 class="confirmation-modal-title">
                    <i class="fas fa-check-circle"></i> Mark All as Read
                </h3>
            </div>
            <div class="confirmation-modal-body">
                <p>Are you sure you want to mark all notifications as read? This will reset the notification badge.</p>
            </div>
            <div class="confirmation-modal-footer">
                <button class="btn-cancel" onclick="hideMarkReadModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmMarkAllAsRead()">Mark as Read</button>
            </div>
        </div>
    `;

    // Clear All confirmation modal
    const clearAllModal = document.createElement('div');
    clearAllModal.className = 'confirmation-modal-overlay';
    clearAllModal.id = 'clearAllConfirmationModal';
    clearAllModal.innerHTML = `
        <div class="confirmation-modal">
            <div class="confirmation-modal-header">
                <h3 class="confirmation-modal-title">
                    <i class="fas fa-trash-alt"></i> Clear All Notifications
                </h3>
            </div>
            <div class="confirmation-modal-body">
                <p>Are you sure you want to clear all notifications? This action cannot be undone.</p>
            </div>
            <div class="confirmation-modal-footer">
                <button class="btn-cancel" onclick="hideClearAllModal()">Cancel</button>
                <button class="btn-confirm" onclick="confirmClearAllNotifications()">Clear All</button>
            </div>
        </div>
    `;

    // Add to page
    document.body.appendChild(markReadModal);
    document.body.appendChild(clearAllModal);
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
        // Close confirmation modals on Escape
        if (e.key === 'Escape') {
            hideMarkReadModal();
            hideClearAllModal();
        }
    });

    // Close confirmation modals when clicking overlay
    document.getElementById('markReadConfirmationModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            hideMarkReadModal();
        }
    });

    document.getElementById('clearAllConfirmationModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            hideClearAllModal();
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

// Show mark all read confirmation modal
function showMarkReadModal() {
    const modal = document.getElementById('markReadConfirmationModal');
    if (modal) {
        modal.classList.add('show');
    }
}

// Hide mark all read confirmation modal
function hideMarkReadModal() {
    const modal = document.getElementById('markReadConfirmationModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Show clear all confirmation modal
function showClearAllModal() {
    const modal = document.getElementById('clearAllConfirmationModal');
    if (modal) {
        modal.classList.add('show');
    }
}

// Hide clear all confirmation modal
function hideClearAllModal() {
    const modal = document.getElementById('clearAllConfirmationModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Confirm mark all as read
function confirmMarkAllAsRead() {
    // Mark as read by resetting the badge count
    markedAsRead = true;
    localStorage.setItem('adminNotificationsMarkedAsRead', 'true');
    unreadCount = 0;
    // Manually update badge without fetching
    if (notificationBadge) {
        notificationBadge.classList.add('hidden');
        notificationBadge.textContent = '0';
    }
    hideMarkReadModal();
    closeNotificationModal();
    showSuccessMessage('All notifications marked as read.');
}

// Confirm clear all notifications
function confirmClearAllNotifications() {
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
            hideClearAllModal();
            closeNotificationModal();
            showSuccessMessage('All notifications cleared successfully.');
        } else {
            showErrorMessage('Failed to clear notifications: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error clearing notifications:', error);
        showErrorMessage('Network error. Please try again.');
    });
}

// Show success message
function showSuccessMessage(message) {
    showNotificationModal(message, 'success');
}

// Show error message
function showErrorMessage(message) {
    showNotificationModal(message, 'error');
}

// Show notification modal (create if doesn't exist)
function showNotificationModal(message, type = 'info') {
    let modal = document.getElementById('admin-notification-modal');
    let titleEl, iconEl, messageEl;

    // Create modal if it doesn't exist
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'admin-notification-modal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content" style="background:white; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.15); max-width:400px; width:90%; max-height:90vh; overflow-y:auto;">
                <div style="padding:20px; border-bottom:1px solid #e2e8f0;">
                    <h2 id="admin-notification-title" style="margin:0; color:#1f2937; font-size:18px;">Notification</h2>
                    <span style="position:absolute; top:15px; right:15px; cursor:pointer; font-size:24px; color:#6b7280;" onclick="closeAdminNotificationModal()">&times;</span>
                </div>
                <div style="padding:20px;">
                    <div style="text-align:center; margin-bottom:20px;">
                        <div id="admin-notification-icon" style="font-size:48px; margin-bottom:16px;"></div>
                        <p id="admin-notification-message" style="margin:0; color:#374151; line-height:1.5;"></p>
                    </div>
                </div>
                <div style="padding:20px; border-top:1px solid #e2e8f0; display:flex; gap:12px; justify-content:flex-end;">
                    <button style="padding:8px 16px; border:none; background:#007bff; color:white; border-radius:6px; cursor:pointer;" onclick="closeAdminNotificationModal()">OK</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Add modal styles if not already present
        if (!document.getElementById('admin-notification-styles')) {
            const style = document.createElement('style');
            style.id = 'admin-notification-styles';
            style.textContent = `
                .modal.show {
                    display: flex !important;
                    align-items: center;
                    justify-content: center;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 10000;
                }
            `;
            document.head.appendChild(style);
        }

        // Add event listeners for the modal
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeAdminNotificationModal();
            }
        });
    }

    // Get elements
    titleEl = document.getElementById('admin-notification-title');
    iconEl = document.getElementById('admin-notification-icon');
    messageEl = document.getElementById('admin-notification-message');

    // Set title and icon based on type
    let title, iconClass, iconColor;
    switch (type) {
        case 'success':
            title = 'Success';
            iconClass = 'fa-check-circle';
            iconColor = '#10b981';
            break;
        case 'error':
            title = 'Error';
            iconClass = 'fa-exclamation-triangle';
            iconColor = '#ef4444';
            break;
        case 'warning':
            title = 'Warning';
            iconClass = 'fa-exclamation-circle';
            iconColor = '#f59e0b';
            break;
        default:
            title = 'Information';
            iconClass = 'fa-info-circle';
            iconColor = '#3b82f6';
    }

    titleEl.textContent = title;
    iconEl.innerHTML = `<i class="fas ${iconClass}" style="color: ${iconColor};"></i>`;
    messageEl.textContent = message;

    modal.classList.add('show');

    // Add Escape key listener
    const escapeHandler = function(e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) {
            closeAdminNotificationModal();
            document.removeEventListener('keydown', escapeHandler);
        }
    };
    document.addEventListener('keydown', escapeHandler);
}

// Close admin notification modal
function closeAdminNotificationModal() {
    const modal = document.getElementById('admin-notification-modal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Mark all notifications as read (legacy function - now uses modal)
function markAllAsRead() {
    showMarkReadModal();
}

// Clear all notifications (legacy function - now uses modal)
function clearAllNotifications() {
    showClearAllModal();
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
window.closeAdminNotificationModal = closeAdminNotificationModal;
window.markAllAsRead = markAllAsRead;
window.clearAllNotifications = clearAllNotifications;
window.showMarkReadModal = showMarkReadModal;
window.hideMarkReadModal = hideMarkReadModal;
window.showClearAllModal = showClearAllModal;
window.hideClearAllModal = hideClearAllModal;
window.confirmMarkAllAsRead = confirmMarkAllAsRead;
window.confirmClearAllNotifications = confirmClearAllNotifications;