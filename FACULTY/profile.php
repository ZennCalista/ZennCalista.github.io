<?php
session_start();
require 'db.php'; // your DB connection

$user_id = $_SESSION['user_id'] ?? null;
$faculty_profile = null;
$notifications = [];
$programs = [];
$attendance = [];

if ($user_id) {
    // Get faculty profile
    $sql = "SELECT 
                u.firstname, u.lastname, u.email, u.phone, u.department AS user_department,
                f.faculty_name, f.faculty_id, f.department AS faculty_department, f.position
            FROM users u
            JOIN faculty f ON u.id = f.user_id
            WHERE u.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $faculty_profile = $result->fetch_assoc();
    $stmt->close();

    // Get notifications for 'all' or this faculty's department
    $faculty_department = $faculty_profile['faculty_department'] ?? '';
    $stmt = $conn->prepare("SELECT message, priority, expires_at FROM notifications WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) AND (audience = 'all' OR audience = ?) ORDER BY created_at DESC LIMIT 10");
    $stmt->bind_param("s", $faculty_department);
    $stmt->execute();
    $stmt->bind_result($message, $priority, $expires_at);
    while ($stmt->fetch()) {
        $notifications[] = [
            'message' => $message,
            'priority' => $priority,
            'expires_at' => $expires_at
        ];
    }
    $stmt->close();

    // Get programs assigned to this faculty (dummy example)
    // $programs = ... (your existing code)

    // Get attendance summary (dummy example)
    // $attendance = ... (your existing code)
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_personal']) && $user_id) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("UPDATE users SET firstname=?, lastname=?, email=?, phone=? WHERE id=?");
    $stmt->bind_param("ssssi", $firstname, $lastname, $email, $phone, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: profile.php?updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>eTracker Faculty Profile</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="profile.css" />
  <style>
    /* Clear Notifications Modal */
    .clear-modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 9999;
      animation: fadeIn 0.2s ease-in-out;
    }

    .clear-modal {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: #fff;
      border-radius: 12px;
      padding: 32px;
      max-width: 450px;
      width: 90%;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
      z-index: 10000;
      animation: slideDown 0.3s ease-out;
    }

    .clear-modal-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
    }

    .clear-modal-header i {
      font-size: 2rem;
      color: #e53935;
    }

    .clear-modal-header h3 {
      font-size: 1.4rem;
      color: #1b472b;
      margin: 0;
    }

    .clear-modal-body {
      margin-bottom: 24px;
      font-size: 1.05rem;
      color: #333;
      line-height: 1.6;
    }

    .clear-modal-actions {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    .clear-modal-btn {
      padding: 10px 24px;
      border: none;
      border-radius: 6px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .clear-modal-btn-cancel {
      background: #e0e0e0;
      color: #333;
    }

    .clear-modal-btn-cancel:hover {
      background: #d0d0d0;
    }

    .clear-modal-btn-confirm {
      background: #b30000;
      color: #fff;
    }

    .clear-modal-btn-confirm:hover {
      background: #990000;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(179, 0, 0, 0.3);
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translate(-50%, -60%);
      }
      to {
        opacity: 1;
        transform: translate(-50%, -50%);
      }
    }

    /* Change Password Button */
    .change-password-btn {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .change-password-btn:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .change-password-btn i {
      font-size: 0.95rem;
    }
  </style>
</head>
<body>
  <!-- Clear Notifications Modal -->
  <div class="clear-modal-overlay" id="clearModalOverlay">
    <div class="clear-modal">
      <div class="clear-modal-header">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Clear All Notifications?</h3>
      </div>
      <div class="clear-modal-body">
        Are you sure you want to clear all notifications? This action cannot be undone.
      </div>
      <div class="clear-modal-actions">
        <button class="clear-modal-btn clear-modal-btn-cancel" onclick="closeClearModal()">Cancel</button>
        <button class="clear-modal-btn clear-modal-btn-confirm" onclick="confirmClearNotifications()">Clear All</button>
      </div>
    </div>
  </div>

  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <img src="logo.png" alt="Logo" class="logo-img" />
        <span class="logo-text">eTRACKER</span>
      </div>
      <nav>
        <ul>
          <li><a href="Dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li class="active"><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
          <li><a href="Programs.php"><i class="fas fa-tasks"></i> Program</a></li>
          <li><a href="Projects.php"><i class="fas fa-project-diagram"></i> Projects</a></li>
          <li><a href="Attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
          <li><a href="Evaluation.php"><i class="fas fa-star-half-alt"></i> Evaluation</a></li>
          <li><a href="certificates.php"><i class="fas fa-certificate"></i> Certificate</a></li>
          <li><a href="upload.php"><i class="fas fa-upload"></i> Documents </a></li>  
          <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
           <li><a href="../portal/home/home.html"><i class="fas fa-external-link-alt"></i> Portal</a></li>
        </ul>
        <div class="sign-out" style="position: absolute; bottom: 30px; left: 0; width: 100%; text-align: center;">
          <a href="../register/logout.php" style="color: inherit; text-decoration: none; display: block; padding: 12px 0;">Sign Out</a>
        </div>
      </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="main-grid">
      <div class="main-content">
        <div class="topbar">
          <div>
            <h2>Faculty Profile</h2>
            <div class="last-login">Last login: <?php echo date('m-d-y H:i:s'); ?></div>
          </div>
        </div>

      

       

        <!-- Profile Sections Grid -->
        <div class="profile-sections-grid">
          <!-- Personal Info Card -->
          <div class="profile-card-section">
            <div class="section-header">
              <i class="fas fa-user"></i>
              <span>Personal Info</span>
              <button class="edit-btn" onclick="editPersonalInfo()" title="Edit Personal Info">
                <i class="fas fa-edit"></i>
              </button>
            </div>
            <div class="section-content">
              <div class="info-row"><span>First Name:</span> <?php echo htmlspecialchars($faculty_profile['firstname'] ?? ''); ?></div>
              <div class="info-row"><span>Last Name:</span> <?php echo htmlspecialchars($faculty_profile['lastname'] ?? ''); ?></div>
              <div class="info-row"><span>Email:</span> <?php echo htmlspecialchars($faculty_profile['email'] ?? ''); ?></div>
              <div class="info-row"><span>Phone:</span> <?php echo htmlspecialchars($faculty_profile['phone'] ?? ''); ?></div>
            </div>
          </div>
          <!-- Faculty Profile Card -->
          <div class="profile-card-section">
            <div class="section-header">
              <i class="fas fa-id-badge"></i>
              <span>Faculty Profile</span>
            </div>
            <div class="section-content">
              <!-- <div class="info-row"><span>Faculty Name:</span> <?php echo htmlspecialchars($faculty_profile['faculty_name'] ?? ''); ?></div> -->
              <!-- <div class="info-row"><span>Faculty ID:</span> <?php echo htmlspecialchars($faculty_profile['faculty_id'] ?? ''); ?></div> -->
              <div class="info-row"><span>Department:</span> <?php echo htmlspecialchars($faculty_profile['faculty_department'] ?? ''); ?></div>
              <div class="info-row"><span>Position:</span> <?php echo htmlspecialchars($faculty_profile['position'] ?? ''); ?></div>
            </div>
          </div>

          <!-- Security Settings Card -->
          <div class="profile-card-section">
            <div class="section-header">
              <i class="fas fa-shield-alt"></i>
              <span>Security Settings</span>
            </div>
            <div class="section-content">
              <div class="info-row" style="display: flex; justify-content: space-between; align-items: center;">
                <span>Password</span>
                <button class="change-password-btn" onclick="PasswordChangeModal.open()" title="Change Password">
                  <i class="fas fa-key"></i> Change Password
                </button>
              </div>
              <p style="color: #6b7280; font-size: 0.875rem; margin-top: 8px;">
                Keep your account secure by regularly updating your password
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Panel -->
      <div class="right-panel">
        <div class="top-actions">
          <div class="user-info">
            <div class="name"><?php echo htmlspecialchars(($faculty_profile['firstname'] ?? '') . ' ' . ($faculty_profile['lastname'] ?? '')); ?></div>
            <div class="email"><?php echo htmlspecialchars($faculty_profile['email'] ?? ''); ?></div>
          </div>
        </div>
        <div class="notifications">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3>🔔 Notifications</h3>
            <button id="clear-notifications-btn" style="background: #b30000; color: #fff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 0.9rem; cursor: pointer; font-weight: bold;">Clear All</button>
          </div>
          <?php if (empty($notifications)) { ?>
            <p class="no-notifications">No notifications at this time.</p>
          <?php } else { ?>
            <?php foreach ($notifications as $notification) { 
              // Icon, label, and class for priority
              switch ($notification['priority']) {
                case 'high':
                  $icon = '<i class="fas fa-exclamation-circle" style="color:#e53935;"></i>';
                  $label = 'Urgent';
                  $class = 'notif-high';
                  break;
                case 'medium':
                  $icon = '<i class="fas fa-exclamation-triangle" style="color:#fbc02d;"></i>';
                  $label = 'Reminder';
                  $class = 'notif-medium';
                  break;
                default:
                  $icon = '<i class="fas fa-check-circle" style="color:#43a047;"></i>';
                  $label = 'FYI';
                  $class = 'notif-low';
              }
            ?>
              <div class="note <?php echo $class; ?>">
                <span class="notif-icon"><?php echo $icon; ?></span>
                <span class="notif-label"><?php echo $label; ?></span>
                <?php echo htmlspecialchars($notification['message']); ?>
                <?php if ($notification['expires_at']): ?>
                  <div class="notif-date">Expires: <?php echo htmlspecialchars($notification['expires_at']); ?></div>
                <?php endif; ?>
              </div>
            <?php } ?>
          <?php } ?>
        </div>
      </div>

      <!-- Profile Update Modal -->
      <div id="updateModal" class="modal">
        <div class="modal-content">
          <h3>Profile Updated</h3>
          <p>Your profile has been successfully updated.</p>
          <button onclick="closeModal()">OK</button>
        </div>
      </div>

      <!-- Edit Personal Info Modal -->
      <div id="editPersonalModal" class="modal-overlay">
        <div class="modal-card">
          <h3><i class="fas fa-user-edit"></i> Edit Personal Information</h3>
          <form method="POST" action="profile.php" id="editPersonalForm">
            <input type="hidden" name="edit_personal" value="1">
            <div class="modal-row">
              <label>First Name</label>
              <input type="text" name="firstname" value="<?php echo htmlspecialchars($faculty_profile['firstname'] ?? ''); ?>" required>
            </div>
            <div class="modal-row">
              <label>Last Name</label>
              <input type="text" name="lastname" value="<?php echo htmlspecialchars($faculty_profile['lastname'] ?? ''); ?>" required>
            </div>
            <div class="modal-row">
              <label>Email</label>
              <input type="email" name="email" value="<?php echo htmlspecialchars($faculty_profile['email'] ?? ''); ?>" required>
            </div>
            <div class="modal-row">
              <label>Phone</label>
              <input type="text" name="phone" value="<?php echo htmlspecialchars($faculty_profile['phone'] ?? ''); ?>">
            </div>
            <div class="modal-actions">
              <button type="button" onclick="closeEditModal()">Cancel</button>
              <button type="submit" class="save-btn">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Close modal
    function closeModal() {
      document.getElementById('updateModal').style.display = 'none';
    }

    // Show notification
    function showNotification(message, type) {
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.innerHTML = `<span>${message}</span><button onclick="this.parentElement.remove()">OK</button>`;
      document.body.appendChild(notification);
      setTimeout(() => notification.remove(), 3000);
    }

    // Close modal on outside click
    document.getElementById('updateModal').addEventListener('click', (e) => {
      if (e.target === e.currentTarget) {
        closeModal();
      }
    });

    function editPersonalInfo() {
      // Show your modal or form for editing personal info
      document.getElementById('editPersonalModal').style.display = 'flex';
    }

    function closeEditModal() {
      document.getElementById('editPersonalModal').style.display = 'none';
    }

    // Optional: Close modal when clicking outside the card
    document.getElementById('editPersonalModal').addEventListener('click', function(e) {
      if (e.target === this) closeEditModal();
    });

    // Modal functions
    function showClearModal() {
      document.getElementById('clearModalOverlay').style.display = 'block';
    }

    function closeClearModal() {
      document.getElementById('clearModalOverlay').style.display = 'none';
    }

    function confirmClearNotifications() {
      fetch('clear_notifications.php')
        .then(response => response.text())
        .then(text => {
          if (text === 'Notifications cleared successfully') {
            // Hide all notification notes
            document.querySelectorAll('.note').forEach(note => note.style.display = 'none');
            // Show no notifications message if not already present
            if (!document.querySelector('.no-notifications')) {
              const noNotif = document.createElement('p');
              noNotif.className = 'no-notifications';
              noNotif.textContent = 'No notifications at this time.';
              document.querySelector('.notifications').appendChild(noNotif);
            }
            closeClearModal();
            alert('Notifications cleared successfully!');
          } else {
            closeClearModal();
            alert('Failed to clear notifications: ' + text);
          }
        })
        .catch(error => {
          closeClearModal();
          alert('Error clearing notifications: ' + error.message);
        });
    }

    // Clear notifications handler
    document.addEventListener('DOMContentLoaded', function() {
      const clearBtn = document.getElementById('clear-notifications-btn');
      if (clearBtn) {
        clearBtn.addEventListener('click', showClearModal);
      }

      // Close modal when clicking outside
      const overlay = document.getElementById('clearModalOverlay');
      if (overlay) {
        overlay.addEventListener('click', function(e) {
          if (e.target === overlay) {
            closeClearModal();
          }
        });
      }

      // Close modal with Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
          closeClearModal();
        }
      });
    });
  </script>

  <!-- Include Password Change Modal -->
  <?php include '../portal/shared/password_change_modal.php'; ?>
  <script src="../portal/shared/password_change_modal.js"></script>
</body>
</html>



