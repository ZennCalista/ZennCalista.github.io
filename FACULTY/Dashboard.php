<?php
require_once 'db.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../register/login.php');
    exit();
}

// Get faculty info (profile.php/reports.php logic)
$faculty_id = null;
$faculty_department = '';
$faculty_name = '';
$faculty_position = '';
$stmt = $conn->prepare("SELECT id, faculty_name, department, position FROM faculty WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($faculty_id, $faculty_name, $faculty_department, $faculty_position);
$stmt->fetch();
$stmt->close();

// Get faculty name/email
$user_fullname = 'Unknown User';
$user_email = 'unknown@cvsu.edu.ph';
$stmt = $conn->prepare("SELECT firstname, lastname, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $email);
if ($stmt->fetch()) {
    $user_fullname = $firstname . ' ' . $lastname;
    $user_email = $email;
}
$stmt->close();

// Get programs assigned to this faculty with department info
$programs = [];
if ($faculty_id) {
    $stmt = $conn->prepare("SELECT id, program_name, status, start_date, end_date, max_students 
                            FROM programs 
                            WHERE faculty_id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $programs[] = $row;
    }
    $stmt->close();
} else {
    // Handle missing faculty_id
    error_log("Faculty ID not found for user_id: " . $user_id);
    $programs = [];
}

// Get analytics snapshot
$active_programs = 0;
$total_certificates = 0;
$total_attendance = 0;
$total_present = 0;

// Count active programs
foreach ($programs as $program) {
    if ($program['status'] === 'ongoing') $active_programs++;
}

// Get certificate count for all programs at once
if (!empty($programs)) {
    $program_ids = array_column($programs, 'id');
    $in_clause = str_repeat('?,', count($program_ids) - 1) . '?';
    
    // Certificates
    $stmt = $conn->prepare("SELECT COUNT(*) FROM certificates WHERE program_id IN ($in_clause)");
    $stmt->bind_param(str_repeat('i', count($program_ids)), ...$program_ids);
    $stmt->execute();
    $stmt->bind_result($total_certificates);
    $stmt->fetch();
    $stmt->close();
    
    // Attendance total
    $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE program_id IN ($in_clause)");
    $stmt->bind_param(str_repeat('i', count($program_ids)), ...$program_ids);
    $stmt->execute();
    $stmt->bind_result($total_attendance);
    $stmt->fetch();
    $stmt->close();
    
    // Attendance present
    $stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE program_id IN ($in_clause) AND status = 'Present'");
    $stmt->bind_param(str_repeat('i', count($program_ids)), ...$program_ids);
    $stmt->execute();
    $stmt->bind_result($total_present);
    $stmt->fetch();
    $stmt->close();
}

$avg_attendance = $total_attendance > 0 ? round(($total_present / $total_attendance) * 100) : 0;

// Get notifications (deadlines, reminders)
$notifications = [];
$stmt = $conn->prepare("SELECT message, priority, expires_at FROM notifications WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) AND (audience = 'all' OR audience = ?) ORDER BY created_at DESC LIMIT 5");
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

// Upcoming program deadlines (next 3)
$upcoming_events = [];
$stmt = $conn->prepare("SELECT program_name, end_date FROM programs WHERE faculty_id = ? AND end_date >= CURDATE() ORDER BY end_date ASC LIMIT 3");
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$stmt->bind_result($pname, $pend);
while ($stmt->fetch()) {
    $upcoming_events[] = [
        'type' => 'Deadline',
        'title' => $pname,
        'date' => $pend
    ];
}
$stmt->close();

// Upcoming notifications (meetings, reminders) - faculty only
$res = $conn->query("SELECT message, expires_at FROM notifications WHERE is_active = 1 AND (expires_at IS NOT NULL AND expires_at >= CURDATE()) AND (audience = 'faculty' OR audience = 'all') ORDER BY expires_at ASC LIMIT 3");
while ($row = $res->fetch_assoc()) {
    $upcoming_events[] = [
        'type' => 'Reminder',
        'title' => $row['message'],
        'date' => $row['expires_at']
    ];
}

// Sort all events by date
usort($upcoming_events, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// Group programs by status
$program_groups = [
  'Active' => [],
  'Ended' => [],
  'Upcoming' => []
];
foreach ($programs as $program) {
  if ($program['status'] === 'ongoing') {
    $program_groups['Active'][] = $program;
  } elseif ($program['status'] === 'ended') {
    $program_groups['Ended'][] = $program;
  } else {
    $program_groups['Upcoming'][] = $program;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>eTracker Faculty Dashboard</title>
  <link rel="stylesheet" href="Dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    /* Clear Notifications Modal */
    .modal {
      display: none !important;
    }
    .modal.show {
      display: flex !important;
    }
    .modal-content {
      background: white;
      border-radius: 8px;
      max-width: 1400px;
      width: 95%;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      margin: auto;
    }
    .close-modal {
      position: absolute;
      top: 10px;
      right: 15px;
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #999;
      z-index: 1002;
    }
    .close-modal:hover {
      color: #333;
    }
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
          <li class="active"><a href="Dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
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

    <!-- Main Grid -->
    <div class="main-grid">
      <!-- Center Content -->
      <div class="main-content">
        <header class="topbar">
          <div class="last-login">Last login: <?php echo date('m-d-y H:i:s'); ?></div>
        </header>

        <section class="overview">
          <h1 class="role-label">Faculty</h1>
          <h2>Welcome, <?php echo htmlspecialchars($user_fullname); ?>!</h2>
          <div class="overview-box">
            <div class="quick-actions">
              <button class="quick-btn" onclick="window.location.href='Create.php'">Create New Program</button>
              <button class="quick-btn" onclick="window.location.href='Attendance.php'">Mark Attendance</button>
            </div>

            <div class="cards">

              <!-- My Programs with status tabs -->
              <div class="card">
                <h3><i class="fas fa-chalkboard-teacher"></i> My Programs</h3>
                <div class="status-tabs">
                  <?php $statuses = array_keys($program_groups); ?>
                  <?php foreach ($statuses as $i => $status): ?>
                    <button class="tab-btn<?php echo $i === 0 ? ' active' : ''; ?>" onclick="showStatusTab(<?php echo $i; ?>)">
                      <?php echo $status; ?>
                    </button>
                  <?php endforeach; ?>
                </div>
                <?php foreach ($statuses as $i => $status): ?>
                  <div class="tab-content" id="status-tab-<?php echo $i; ?>" style="<?php echo $i === 0 ? '' : 'display:none;'; ?>">
                    <?php if (empty($program_groups[$status])): ?>
                      <div style="padding:12px; color:#888;">No <?php echo strtolower($status); ?> programs.</div>
                    <?php else: ?>
                      <ul style="margin:0; padding-left:0;">
                        <?php foreach ($program_groups[$status] as $program): ?>
                          <li style="margin-bottom:10px; list-style:none; border-bottom:1px solid #eee; padding-bottom:8px;">
                            <strong><?php echo htmlspecialchars($program['program_name']); ?></strong>
                            <br>
                            <div>
                              <button onclick="window.location.href='Programs.php?id=<?php echo $program['id']; ?>'">Manage</button>
                              <button onclick="showParticipants(<?php echo $program['id']; ?>)">View Students</button>
                            </div>
                            <div id="participants-<?php echo $program['id']; ?>" class="participants-list" style="display:none; margin-top:8px;"></div>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>

              <!-- Analytics Snapshot -->
              <div class="card">
                <h3><i class="fas fa-chart-line"></i> Analytics Snapshot</h3>
                <ul>
                  <li>Active Programs: <strong><?php echo $active_programs; ?></strong></li>
                  <li>Avg. Attendance: <strong><?php echo $avg_attendance; ?>%</strong></li>
                  <li>Certificates Issued: <strong><?php echo $total_certificates; ?></strong></li>
                </ul>
                <button onclick="window.location.href='reports.php'">View Detailed Analytics</button>
              </div>

              <!-- Upcoming Events -->
              <div class="card">
                <h3><i class="fas fa-hourglass-half"></i> Upcoming Events</h3>
                <ul>
                  <?php if (empty($upcoming_events)): ?>
                    <li>No upcoming events.</li>
                  <?php else: ?>
                    <?php foreach ($upcoming_events as $event): ?>
                      <li>
                        <strong><?php echo htmlspecialchars($event['title']); ?></strong>
                        <span style="color:orange;">
                          <?php echo date('M d, Y', strtotime($event['date'])); ?>
                        </span>
                        <span style="font-size:0.95em; color:#888;">(<?php echo htmlspecialchars($event['type']); ?>)</span>
                      </li>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </ul>
              </div>
            </div>
          </div>
        </section>
      </div>

      <!-- Right Side -->
      <div class="right-panel">
        <div class="user-info">
          <div class="name"><?php echo htmlspecialchars($user_fullname); ?></div>
          <div class="email"><?php echo htmlspecialchars($user_email); ?></div>
        </div>
        <div class="notifications">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <h3>🔔 Notifications</h3>
            <button id="clear-notifications-btn" style="background: #b30000; color: #fff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 0.9rem; cursor: pointer; font-weight: bold;">Clear All</button>
          </div>
          <?php if (empty($notifications)): ?>
            <div class="note">No notifications.</div>
          <?php else: ?>
            <?php foreach ($notifications as $note): ?>
              <?php
                // Set icon and label based on priority
                switch ($note['priority']) {
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
                <?php echo htmlspecialchars($note['message']); ?>
                <?php if ($note['expires_at']): ?>
                  <div class="notif-date">Expires: <?php echo htmlspecialchars($note['expires_at']); ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <!-- Clear Notifications Modal -->
  <div id="clearModalOverlay" class="clear-modal-overlay" onclick="if(event.target===this)closeClearModal()">
    <div class="clear-modal">
      <div class="clear-modal-header">
        <i class="fas fa-trash-alt"></i>
        <h3>Clear All Notifications</h3>
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

  <!-- Notification Modal -->
  <div id="notification-modal" class="modal" onclick="if(event.target===this)closeModal('notification-modal')" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
    <div class="modal-content" style="background:white; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.15); max-width:400px; width:90%; max-height:90vh; overflow-y:auto;">
      <div style="padding:20px; border-bottom:1px solid #e2e8f0;">
        <h2 id="notification-title" style="margin:0; color:#1f2937; font-size:18px;">Notification</h2>
        <span style="position:absolute; top:15px; right:15px; cursor:pointer; font-size:24px; color:#6b7280;" onclick="closeModal('notification-modal')">&times;</span>
      </div>
      <div style="padding:20px;">
        <div style="text-align:center; margin-bottom:20px;">
          <div id="notification-icon" style="font-size:48px; margin-bottom:16px;"></div>
          <p id="notification-message" style="margin:0; color:#374151; line-height:1.5;"></p>
        </div>
      </div>
      <div style="padding:20px; border-top:1px solid #e2e8f0; display:flex; gap:12px; justify-content:flex-end;">
        <button style="padding:8px 16px; border:none; background:#007bff; color:white; border-radius:6px; cursor:pointer;" onclick="closeModal('notification-modal')">OK</button>
      </div>
    </div>
  </div>
  <script>
  // Show notification modal
  function showNotificationModal(message, type = 'info') {
    const modal = document.getElementById('notification-modal');
    const titleEl = document.getElementById('notification-title');
    const iconEl = document.getElementById('notification-icon');
    const messageEl = document.getElementById('notification-message');
    
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
  }

  // Modal close utility
  function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.remove('show');
    }
  }

  function showStatusTab(idx) {
    document.querySelectorAll('.status-tabs .tab-btn').forEach((btn, i) => {
      btn.classList.toggle('active', i === idx);
    });
    document.querySelectorAll('.card .tab-content').forEach((tab, i) => {
      tab.style.display = i === idx ? '' : 'none';
    });
  }
  function showParticipants(programId) {
    const container = document.getElementById('participants-' + programId);
    container.innerHTML = '<span style="color:gray;">Loading...</span>';
    container.style.display = 'block';
    fetch('get_participants.php?id=' + programId)
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success' && Array.isArray(data.data) && data.data.length > 0) {
          let html = '<ul style="margin:0; padding-left:18px;">';
          data.data.forEach(p => {
            html += `<li>${p.firstname} ${p.lastname} (${p.email})</li>`;
          });
          html += '</ul>';
          container.innerHTML = html;
        } else {
          container.innerHTML = '<span style="color:#e53935;">No students enrolled.</span>';
        }
      })
      .catch(() => {
        container.innerHTML = '<span style="color:#e53935;">Error loading students.</span>';
      });
  }

  // Modal functions
  function showClearModal() {
    document.getElementById('clearModalOverlay').style.display = 'block';
  }

  function closeClearModal() {
    document.getElementById('clearModalOverlay').style.display = 'none';
  }

  function confirmClearNotifications() {
    fetch('clear_notifications.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Hide all notification notes and show no notifications message
        const notes = document.querySelectorAll('.note');
        notes.forEach(note => note.style.display = 'none');
        const noNotifs = document.querySelector('.no-notifications');
        if (noNotifs) {
          noNotifs.style.display = 'block';
        } else {
          // Create no notifications message if it doesn't exist
          const notificationsDiv = document.querySelector('.notifications');
          const newNoNotifs = document.createElement('div');
          newNoNotifs.className = 'note no-notifications';
          newNoNotifs.textContent = 'No notifications at this time.';
          notificationsDiv.appendChild(newNoNotifs);
        }
        closeClearModal();
        showNotificationModal('Notifications cleared successfully!', 'success');
      } else {
        closeClearModal();
        showNotificationModal('Failed to clear notifications: ' + data.message, 'error');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      closeClearModal();
      showNotificationModal('An error occurred while clearing notifications.', 'error');
    });
  }

  // Clear notifications functionality
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
</body>
</html>
