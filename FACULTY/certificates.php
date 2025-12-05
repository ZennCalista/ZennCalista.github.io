<?php
require_once 'db.php';
session_start();

// Get logged-in user and faculty info
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../register/login.php');
    exit();
}
$faculty_id = null;
$stmt = $conn->prepare("SELECT id FROM faculty WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($faculty_id);
$stmt->fetch();
$stmt->close();

// Check if faculty_id is found
if (!$faculty_id) {
    // Instead of dying, show a user-friendly message
    $error_message = "Faculty record not found. Please contact your administrator to set up your faculty profile.";
    $show_error = true;
    $programs = [];
    $certificates = [];
    $notifications = [];
} else {
    $show_error = false;

// Fetch user info for display (top right)
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

// Fetch only programs managed by this faculty
$programs = [];
$program_query = "SELECT id, program_name, start_date
                  FROM programs
                  WHERE faculty_id = ?
                  ORDER BY start_date";
$stmt = $conn->prepare($program_query);
$stmt->bind_param("i", $faculty_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $programs[] = $row;
}
$stmt->close();

// Set default to "all" programs unless a program_id is provided via GET
$selected_program_id = isset($_GET['program_id']) && $_GET['program_id'] != 'all' ? $_GET['program_id'] : 'all';

// Fetch faculty certificates for programs managed by this faculty
$certificates = [];
if (!empty($programs)) {
    $program_ids = array_column($programs, 'id');
    if ($selected_program_id != 'all' && in_array($selected_program_id, $program_ids)) {
        // Filter by selected program - get faculty certificate from programs table
        $certificate_query = "SELECT program_name, faculty_certificate_issued_on as issue_date, 
                              faculty_certificate_file as certificate_file
                              FROM programs
                              WHERE id = ? AND faculty_certificate_file IS NOT NULL
                              ORDER BY faculty_certificate_issued_on DESC";
        $stmt = $conn->prepare($certificate_query);
        $stmt->bind_param("i", $selected_program_id);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Show all faculty certificates for all programs managed by this faculty
        $in = implode(',', array_fill(0, count($program_ids), '?'));
        $types = str_repeat('i', count($program_ids));
        $certificate_query = "SELECT program_name, faculty_certificate_issued_on as issue_date, 
                              faculty_certificate_file as certificate_file
                              FROM programs
                              WHERE id IN ($in) AND faculty_certificate_file IS NOT NULL
                              ORDER BY faculty_certificate_issued_on DESC";
        $stmt = $conn->prepare($certificate_query);
        $stmt->bind_param($types, ...$program_ids);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    while ($row = $result->fetch_assoc()) {
        $certificates[] = $row;
    }
    $stmt->close();
}

// Fetch active notifications
$notifications = [];
$notifications_query = "SELECT message, priority
                       FROM notifications
                       WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())
                       ORDER BY created_at DESC
                       LIMIT 5";
$notifications_result = $conn->query($notifications_query);
if ($notifications_result) {
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $notifications_result->free();
}
} // End of else block
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>eTracker Faculty Certificates</title>
  <link rel="stylesheet" href="sample.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
          <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
          <li><a href="Programs.php"><i class="fas fa-tasks"></i> Program</a></li>
          <li><a href="Projects.php"><i class="fas fa-project-diagram"></i> Projects</a></li>
          <li><a href="Attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
          <li><a href="Evaluation.php"><i class="fas fa-star-half-alt"></i> Evaluation</a></li>
          
          <li class="active"><a href="certificates.php"><i class="fas fa-certificate"></i> Certificate</a></li>
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
          <div class="role-label">Faculty Certificates</div>
          <div class="last-login">Last login: <?php echo date('m-d-y H:i:s'); ?></div>
        </header>

        <?php if ($show_error): ?>
          <div class="error-message" style="background: #ffeaea; border: 1px solid #e74c3c; color: #e74c3c; padding: 20px; border-radius: 5px; margin: 20px 0; text-align: center;">
            <h3>⚠️ <?php echo htmlspecialchars($error_message); ?></h3>
            <p>Please contact your system administrator to complete your faculty profile setup.</p>
          </div>
        <?php else: ?>

        <!-- Program Selection -->
        <div class="program-selection">
          <label for="program-select">Select Program</label>
          <select id="program-select" name="program_id" onchange="window.location.href='certificates.php?program_id=' + this.value">
            <option value="all" <?php echo ($selected_program_id == 'all') ? 'selected' : ''; ?>>All Programs</option>
            <?php foreach ($programs as $program): ?>
              <option value="<?php echo htmlspecialchars($program['id']); ?>" <?php echo ($selected_program_id == $program['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($program['program_name']) . ' (' . date('m/d/y', strtotime($program['start_date'])) . ')'; ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      
        <!-- Certificates Table -->
        <table class="certificate-table">
          <thead>
            <tr>
              <th>Faculty Member</th>
              <th>Program</th>
              <th>Issue Date</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($certificates)): ?>
              <tr><td colspan="4">No certificates found.</td></tr>
            <?php else: ?>
              <?php foreach ($certificates as $certificate): ?>
                <tr>
                  <td><?php echo htmlspecialchars($user_fullname); ?></td>
                  <td><?php echo htmlspecialchars($certificate['program_name']); ?></td>
                  <td><?php echo $certificate['issue_date'] ? htmlspecialchars(date('m-d-Y', strtotime($certificate['issue_date']))) : 'N/A'; ?></td>
                  <td>
                    <?php if (!empty($certificate['certificate_file'])): ?>
                      <button class="btn" onclick="viewCertificate('<?php echo htmlspecialchars($certificate['certificate_file']); ?>')">View</button>
                    <?php else: ?>
                      <span>Not available</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
        <?php endif; // End of error check ?>
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
            <div class="note no-notifications">No notifications at this time.</div>
          <?php else: ?>
            <?php foreach ($notifications as $notification): 
              // Priority icon, label, and class
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
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <style>
    .program-selection { margin: 20px 0; display: flex; align-items: center; gap: 10px; }
    .program-selection label { font-weight: bold; color: #247a37; }
    .program-selection select { padding: 5px; border-radius: 15px; border: 1px solid #ccc; width: 300px; }
  
    .btn {
      padding: 8px 16px;
      background-color: #d2eac8;
      border: none;
      border-radius: 15px;
      cursor: pointer;
      color: #1e3927;
    }
    .btn:hover {
      background-color: #247a37;
      color: #fff;
    }

    /* Document Viewer Modal Styles */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.7);
      z-index: 9999;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background: #fff;
      border-radius: 8px;
      position: relative;
    }
    .modal-content.large-modal {
      width: 95vw !important;
      height: 95vh !important;
      max-width: 95vw !important;
      max-height: 95vh !important;
      box-sizing: border-box !important;
      padding: 0 !important;
      display: flex !important;
      flex-direction: column !important;
    }
    .modal-content.large-modal .modal-header {
      flex: 0 0 auto !important;
      padding: 8px 14px !important;
      background: #114d2e;
      color: white;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-content.large-modal .modal-body {
      flex: 1 1 auto !important;
      height: auto !important;
      overflow: hidden !important;
      padding: 0 !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      background: #f5f5f5 !important;
    }
    #certificateViewer {
      width: 100% !important;
      height: 100% !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
    }
    #certificateViewer .paper-wrap {
      width: 95% !important;
      height: 96% !important;
      max-width: none !important;
      background: #fff !important;
      box-shadow: 0 8px 24px rgba(0,0,0,0.35) !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      overflow: hidden !important;
    }
    #certificateViewer iframe, #certificateViewer img {
      width: 100% !important;
      height: 100% !important;
      border: 0 !important;
      display: block !important;
    }
    .modal-content.large-modal .view-controls {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .modal-content.large-modal .view-controls span#viewFilename {
      color: #fff;
      opacity: 0.95;
      font-weight: 600;
      max-width: 60vw;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    .modal-content.large-modal .view-controls a.download-btn {
      background: #ffffff !important;
      color: #114d2e !important;
      border: 0 !important;
      padding: 8px 12px !important;
      border-radius: 6px !important;
      text-decoration: none !important;
      font-weight: 700 !important;
      box-shadow: 0 6px 18px rgba(0,0,0,0.18) !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 8px !important;
      cursor: pointer !important;
    }
    .modal-content.large-modal .view-controls a.download-btn:hover {
      transform: translateY(-1px) !important;
      box-shadow: 0 8px 20px rgba(0,0,0,0.22) !important;
    }
    .close-btn {
      background: transparent;
      border: none;
      color: white;
      font-size: 2rem;
      cursor: pointer;
      line-height: 1;
    }
    .close-btn:hover {
      color: #ffcccc;
    }
    .certificate-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    .certificate-table th,
    .certificate-table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }
    .certificate-table th {
      background-color: #d2eac8;
      color: #1e3927;
    }
    .certificate-table td {
      background-color: #fff;
    }
    .certificate-table td:last-child {
      text-align: center;
    }
    .certificate-table .btn {
      background-color: #3b82f6;
      color: white;
    }
    .certificate-table .btn:hover {
      background-color: #2563eb;
      color: white;
    }
    .status-generated { color: green; font-weight: bold; }
    .status-pending { color: #f1c40f; font-weight: bold; }
    .note.priority-low { border-left-color: #59a96a; }
    .note.priority-medium { border-left-color: #f1c40f; }
    .note.priority-high { border-left-color: #e74c3c; }
  </style>

  <!-- Certificate Viewer Modal -->
  <div id="certificateModal" class="modal" style="display: none;">
    <div class="modal-content large-modal">
      <div class="modal-header">
        <h3><i class="fas fa-certificate"></i> View Certificate</h3>
        <div class="view-controls">
          <span id="viewFilename"></span>
          <a id="downloadLink" class="download-btn" target="_blank" rel="noopener">
            <i class="fas fa-download"></i>
            <span>Download</span>
          </a>
        </div>
        <button class="close-btn" onclick="closeCertificateViewer()">&times;</button>
      </div>
      <div class="modal-body">
        <div id="certificateViewer">
          <!-- content injected dynamically -->
        </div>
      </div>
    </div>
  </div>

  <script>
    // Certificate Viewer Functions
    function viewCertificate(filePath) {
      const viewer = document.getElementById('certificateViewer');
      const extension = (filePath || '').split('.').pop().toLowerCase();
      
      // Construct proper view URL using relative path from current directory
      let viewUrl;
      if (filePath.startsWith('http://') || filePath.startsWith('https://')) {
        viewUrl = filePath;
      } else if (filePath.startsWith('certificates/')) {
        viewUrl = '../' + filePath;
      } else if (filePath.startsWith('/certificates/')) {
        viewUrl = '..' + filePath;
      } else if (filePath.startsWith('/')) {
        viewUrl = '..' + filePath;
      } else {
        viewUrl = '../certificates/' + filePath;
      }
      
      if (extension === 'pdf') {
        viewer.innerHTML = `
          <div class="paper-wrap">
            <iframe src="${viewUrl}" allowfullscreen></iframe>
          </div>
        `;
      } else if (extension === 'docx') {
        // Use Google viewer for Word documents
        const fullUrl = window.location.origin + viewUrl;
        viewer.innerHTML = `
          <div class="paper-wrap">
            <iframe src="https://docs.google.com/gview?url=${encodeURIComponent(fullUrl)}&embedded=true" allowfullscreen></iframe>
          </div>
        `;
      } else if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
        viewer.innerHTML = `
          <div class="paper-wrap" style="background:#f5f5f5;">
            <img src="${viewUrl}" alt="Certificate" style="max-width:100%; height:auto; display:block; margin:0 auto;"/>
          </div>
        `;
      } else {
        viewer.innerHTML = `<div class="paper-wrap">Unsupported file type for preview.</div>`;
      }
      
      // Set filename and download link
      const filename = filePath.split('/').pop();
      document.getElementById('viewFilename').textContent = filename;
      const downloadLink = document.getElementById('downloadLink');
      downloadLink.href = viewUrl;
      downloadLink.setAttribute('download', filename);
      
      // Show modal
      document.getElementById('certificateModal').style.display = 'block';
    }
    
    function closeCertificateViewer() {
      document.getElementById('certificateModal').style.display = 'none';
      document.getElementById('certificateViewer').innerHTML = '';
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
      const modal = document.getElementById('certificateModal');
      if (e.target === modal) {
        closeCertificateViewer();
      }
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
              const noNotif = document.createElement('div');
              noNotif.className = 'note no-notifications';
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
</body>
</html>