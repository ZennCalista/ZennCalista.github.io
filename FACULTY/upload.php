<?php
require_once 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../register/login.php');
    exit();
}

// Fetch user info for display
$user_id = $_SESSION['user_id'];
$user_fullname = 'Unknown User';
$user_email = 'unknown@cvsu.edu.ph';

$user_sql = "SELECT firstname, lastname, email FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
if ($user_row = $user_result->fetch_assoc()) {
    $user_fullname = $user_row['firstname'] . ' ' . $user_row['lastname'];
    $user_email = $user_row['email'];
}
$user_stmt->close();

// Fetch faculty_id for the logged-in user
$faculty_id = null;
$stmt = $conn->prepare("SELECT id, department FROM faculty WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($faculty_id, $faculty_department);
$stmt->fetch();
$stmt->close();

// Fetch programs assigned to this faculty or department
$programs = [];
// Option 1: Programs assigned directly to faculty
$prog_stmt = $conn->prepare("SELECT id, program_name FROM programs WHERE faculty_id = ?");
$prog_stmt->bind_param("i", $faculty_id);
$prog_stmt->execute();
$prog_stmt->bind_result($pid, $pname);
while ($prog_stmt->fetch()) {
    $programs[] = ['id' => $pid, 'name' => $pname];
}
$prog_stmt->close();

// Option 2: If you want to show all programs in the same department (if that's your logic)
// $prog_stmt = $conn->prepare("SELECT id, program_name FROM programs WHERE department = ?");
// $prog_stmt->bind_param("s", $faculty_department);
// ... (same as above)
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
    /* Only main-content and its children */
  /* MAIN CENTER CONTENT */
  .main-content {
    display: flex;
    flex-direction: column;
    flex: 1;
  }

.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 18px;
}

.role-label {
  font-size: 1.3rem;
  font-weight: 600;
  color: #247a37;
  letter-spacing: 1px;
}

.last-login {
  font-size: 0.98rem;
  color: #888;
}

h2 {
  margin-bottom: 8px;
  color: #247a37;
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: 1px;
}

.page-desc {
  color: #1e3927;
  font-size: 1.03rem;
  margin-bottom: 22px;
}

.upload-form {
  background: #fff;
  border-radius: 18px;
  box-shadow: 0 2px 8px rgba(36, 122, 55, 0.08);
  padding: 28px 28px 18px 28px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 18px;
  animation: fadeInUp 1.1s;
}

.upload-form label {
  font-weight: 600;
  color: #247a37;
  margin-bottom: 4px;
}

.upload-form select,
.upload-form input[type="file"] {
  padding: 8px 12px;
  border-radius: 12px;
  border: 1px solid #b2b2b2;
  font-size: 1rem;
  margin-bottom: 8px;
}

.upload-form .submit {
  background: linear-gradient(90deg, #59a96a 60%, #247a37 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 10px 0;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.18s, transform 0.18s;
  margin-top: 8px;
}

.upload-form .submit:hover {
  background: linear-gradient(90deg, #247a37 60%, #59a96a 100%);
  transform: translateY(-2px) scale(1.03);
}

.file-drop-zone {
  border: 2px dashed #b2b2b2;
  border-radius: 12px;
  padding: 40px 20px;
  text-align: center;
  background: #fafafa;
  transition: border-color 0.3s, background 0.3s;
  cursor: pointer;
  margin-bottom: 16px;
}

.file-drop-zone:hover,
.file-drop-zone.dragover {
  border-color: #59a96a;
  background: #eafbe7;
}

.file-drop-zone i {
  font-size: 3rem;
  color: #b2b2b2;
  margin-bottom: 10px;
}

.file-drop-zone p {
  margin: 0;
  color: #666;
  font-size: 1rem;
}

.file-select-link {
  color: #247a37;
  text-decoration: underline;
  cursor: pointer;
}

.file-preview {
  margin-top: 10px;
}

.file-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #f0f0f0;
  padding: 8px 12px;
  border-radius: 8px;
  margin-bottom: 8px;
}

.file-item .file-name {
  flex: 1;
  font-size: 0.9rem;
}

.file-item .file-size {
  color: #666;
  font-size: 0.8rem;
  margin-left: 10px;
}

.file-item .remove-file {
  color: #b30000;
  cursor: pointer;
  margin-left: 10px;
}

.info-box {
  background: #eafbe7;
  color: #247a37;
  border-left: 5px solid #59a96a;
  border-radius: 10px;
  padding: 12px 18px;
  margin: 18px 0 0 0;
  font-size: 1rem;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 1px 4px rgba(36, 122, 55, 0.07);
  animation: fadeInUp 1.2s;
}

.info-box i {
  color: #59a96a;
  font-size: 1.2em;
}

.view-uploads-btn {
  background: linear-gradient(90deg, #247a37 60%, #59a96a 100%);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 10px 0;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 12px;
  margin-top: 2px;
  width: 20%;
  transition: background 0.18s, transform 0.18s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}
.view-uploads-btn:hover {
  background: linear-gradient(90deg, #59a96a 60%, #247a37 100%);
  transform: translateY(-2px) scale(1.03);
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(40px);}
  to { opacity: 1; transform: translateY(0);}
}

.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(30, 57, 39, 0.25);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: fadeInUp 0.3s;
}
.modal-content {
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 8px 32px rgba(36,122,55,0.18);
  width: 90vw;
  max-width: 900px;
  height: 80vh;
  position: relative;
  display: flex;
  flex-direction: column;
  animation: fadeInUp 0.4s;
}
.modal-content iframe {
  flex: 1;
  width: 100%;
  height: 100%;
  border: none;
  border-radius: 0 0 16px 16px;
}
.modal-close {
  position: absolute;
  top: 10px; right: 18px;
  background: none;
  border: none;
  font-size: 2rem;
  color: #247a37;
  cursor: pointer;
  z-index: 2;
  transition: color 0.2s;
}
.modal-close:hover {
  color: #b30000;
}

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

/* Document Viewer Modal */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.7);
  z-index: 10000;
  display: none !important;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
}

.modal.show {
  display: flex !important;
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
  background: #114d2e !important;
  border-radius: 12px !important;
  overflow: hidden !important;
}

.modal-content.large-modal .modal-header {
  flex: 0 0 auto !important;
  padding: 12px 18px !important;
  background: #114d2e !important;
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  border-bottom: 1px solid rgba(255,255,255,0.1) !important;
}

.modal-content.large-modal .modal-header h3 {
  margin: 0 !important;
  color: #fff !important;
  font-size: 1.2rem !important;
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
}

.modal-content.large-modal .modal-body {
  flex: 1 1 auto !important;
  height: auto !important;
  overflow: hidden !important;
  padding: 0 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  background: #1a1a1a !important;
}

#documentViewer {
  width: 100% !important;
  height: 100% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

#documentViewer .paper-wrap {
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

#documentViewer iframe, #documentViewer img {
  width: 100% !important;
  height: 100% !important;
  border: 0 !important;
  display: block !important;
}

.modal-content.large-modal .view-controls {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
}

.modal-content.large-modal .view-controls span#viewFilename {
  color: #fff !important;
  opacity: 0.95 !important;
  font-weight: 600 !important;
  max-width: 60vw !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  white-space: nowrap !important;
  display: inline-block !important;
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
  transition: all 0.2s !important;
}

.modal-content.large-modal .view-controls a.download-btn:hover {
  transform: translateY(-1px) !important;
  box-shadow: 0 8px 20px rgba(0,0,0,0.22) !important;
}

.modal-content.large-modal .view-controls a.download-btn i.fas {
  color: #114d2e !important;
  font-size: 1rem !important;
}

.modal-content.large-modal .close-btn {
  background: none !important;
  border: none !important;
  color: #fff !important;
  font-size: 2rem !important;
  cursor: pointer !important;
  padding: 0 !important;
  width: 32px !important;
  height: 32px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  transition: color 0.2s !important;
}

.modal-content.large-modal .close-btn:hover {
  color: #ff6b6b !important;
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

  <!-- Upload Status Modal -->
  <div class="clear-modal-overlay" id="uploadStatusModal" style="display: none;">
    <div class="clear-modal">
      <div class="clear-modal-header" id="uploadStatusHeader">
        <i class="fas fa-check-circle" id="uploadStatusIcon" style="color: #28a745;"></i>
        <h3 id="uploadStatusTitle">Upload Successful</h3>
      </div>
      <div class="clear-modal-body" id="uploadStatusMessage">
        Your documents have been uploaded successfully!
      </div>
      <div class="clear-modal-actions">
        <button class="clear-modal-btn" style="background: #247a37; color: #fff;" onclick="closeUploadStatusModal()">OK</button>
      </div>
    </div>
  </div>

<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
 <div class="logo">
        <img src="logo.png" alt="Logo" class="logo-img" />
        <span class="logo-text">eTRACKER</span>
      </div>      <nav>
        <ul>
          <li><a href="Dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
          <li><a href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
          <li><a href="Programs.php"><i class="fas fa-tasks"></i> Program</a></li>
          <li><a href="Projects.php"><i class="fas fa-project-diagram"></i> Projects</a></li>

          <li><a href="Attendance.php"><i class="fas fa-calendar-check"></i> Attendance</a></li>
          
          <li><a href="Evaluation.php"><i class="fas fa-star-half-alt"></i> Evaluation</a></li>
          <li><a href="certificates.php"><i class="fas fa-certificate"></i> Certificate</a></li>
        <li class="active"><a href="upload.php"><i class="fas fa-upload"></i> Documents </a></li>  
          <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
          <li><a href="../portal/home/home.html"><i class="fas fa-external-link-alt"></i> Portal</a></li>
        </ul>
 <div class="sign-out" style="position: absolute; bottom: 30px; left: 0; width: 100%; text-align: center;">
          <a href="../register/logout.php" style="color: inherit; text-decoration: none; display: block; padding: 12px 0;">Sign Out</a>
        </div>      </nav>
    </aside>

    <!-- Main Grid -->
    <div class="main-grid">
      <!-- Center Content -->
      <div class="main-content">
        <header class="topbar">
          <div class="role-label">Faculty Certificates</div>
          <div class="last-login">Last login: <?php echo date('m-d-y H:i:s'); ?></div>
          <div class="top-actions"></div>
        </header>

        <div class="upload-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <h2 style="margin-bottom: 0;">Upload Required Documents</h2>
          <button type="button" class="view-uploads-btn" onclick="openUploadsModal()">
            <i class="fas fa-folder-open"></i> View My Uploads
          </button>
        </div>
        <p class="page-desc">
          Please upload all required documents for your extension programs. Select the program, document type, and attach the file. You can track the status of your uploads below.
        </p>

        <form class="upload-form" id="uploadForm" enctype="multipart/form-data">
          <div id="program-field">
            <label for="program">Program</label>
            <select name="program_id" id="program" required>
              <option value="" disabled selected>Select Program</option>
              <?php foreach ($programs as $p): ?>
                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <label for="document-type">Document Type</label>
          <select name="document_type" id="document-type" required>
            <option value="" disabled selected>Select Document Type</option>
            <option value="proposal">Extension Activity Proposal</option>
            <option value="report">Activity and Implementation Report</option>
            <option value="attendance">Attendance Sheet</option>
            <option value="photos">Photo Documentation</option>
            <option value="feedback">Evaluation and Feedback Forms</option>
            <option value="workload">Summary of Workload Hours</option>
            <option value="accomplishments">Summary of Accomplishments</option>
            <option value="other">Other Supporting Documents</option>
          </select>

          <div id="proposal-fields" style="display: none;">
            <label for="proposal-title">Proposal Title</label>
            <input type="text" id="proposal-title" name="proposal_title" placeholder="Enter proposal title">

            <label for="proposal-description">Proposal Description</label>
            <textarea id="proposal-description" name="proposal_description" rows="4" placeholder="Describe your extension activity proposal"></textarea>
          </div>

          <label for="document-file">Select File</label>
          <div id="file-drop-zone" class="file-drop-zone">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Drag & drop files here or <span class="file-select-link">browse</span></p>
            <input type="file" name="document_file[]" id="document-file" multiple style="display: none;">
          </div>
          <div id="file-preview" class="file-preview"></div>
          <button type="submit" class="submit"><i class="fas fa-upload"></i> Upload</button>
          <div class="info-box" style="margin: 16px 0;">
            <i class="fas fa-info-circle"></i>
            Allowed file types: PDF, DOCX, JPG, PNG. Max size: 10MB per file. Multiple files allowed for images (JPG, PNG). PDF and DOCX limited to one file.
          </div>

          
        </form>
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
          <?php
          // Fetch active notifications for faculty only
          $notifications = [];
          $notifications_query = "SELECT message, priority FROM notifications WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) AND (audience = 'faculty' OR audience = 'all') ORDER BY created_at DESC LIMIT 5";
          $notifications_result = $conn->query($notifications_query);
          if ($notifications_result) {
              while ($row = $notifications_result->fetch_assoc()) {
                  $notifications[] = $row;
              }
          }
          ?>
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

  <!-- Modal HTML (place after .main-content, before </body>) -->
<div id="uploadsModal" class="modal-overlay" style="display:none;">
  <div class="modal-content">
    <button class="modal-close" onclick="closeUploadsModal()">&times;</button>
    <iframe id="uploadsIframe" src="my_uploads.php" frameborder="0"></iframe>
  </div>
</div>

<!-- Document Viewer Modal -->
<div id="documentsModal" class="modal" style="display: none;">
  <div class="modal-content large-modal">
    <div class="modal-header">
      <h3><i class="fas fa-eye"></i> View Document</h3>
      <div class="view-controls">
        <span id="viewFilename"></span>
        <a id="downloadLink" class="download-btn" target="_blank" rel="noopener">
          <i class="fas fa-download" aria-hidden="true"></i>
          <span>Download</span>
        </a>
      </div>
      <button class="close-btn" onclick="closeViewDocumentModal()">&times;</button>
    </div>
    <div class="modal-body">
      <div id="documentViewer">
        <!-- content injected dynamically -->
      </div>
    </div>
  </div>
</div>

<!-- Modal JS (add before </body>) -->
<script>
// Document Viewer Functions
function showViewDocumentModal(path, docId) {
  const viewer = document.getElementById('documentViewer');
  const extension = (path || '').split('.').pop().toLowerCase();

  let viewUrl;
  // Handle different path formats
  if (path.startsWith('http://') || path.startsWith('https://')) {
    viewUrl = path;
  } else if (path.startsWith('uploads/')) {
    viewUrl = path; // Already relative to FACULTY directory
  } else if (path.startsWith('FACULTY/uploads/')) {
    viewUrl = path.replace('FACULTY/', ''); // Remove FACULTY prefix
  } else {
    viewUrl = path;
  }

  if (extension === 'pdf') {
    viewer.innerHTML = `
      <div class="paper-wrap">
        <iframe src="${viewUrl}" allowfullscreen></iframe>
      </div>
    `;
  } else if (extension === 'docx') {
    // Use Google viewer as fallback - construct full URL for external access
    const currentPath = window.location.pathname;
    const basePath = currentPath.substring(0, currentPath.lastIndexOf('/'));
    const fullUrl = window.location.origin + basePath + '/' + viewUrl;
    viewer.innerHTML = `
      <div class="paper-wrap">
        <iframe src="https://docs.google.com/gview?url=${encodeURIComponent(fullUrl)}&embedded=true" allowfullscreen></iframe>
      </div>
    `;
  } else if (['jpg', 'jpeg', 'png'].includes(extension)) {
    viewer.innerHTML = `
      <div class="paper-wrap" style="background:#f5f5f5;">
        <img src="${viewUrl}" alt="Document Image" style="max-width:100%; height:auto; display:block; margin:0 auto;"/>
      </div>
    `;
  } else {
    // For unknown types, show message
    viewer.innerHTML = `<div class="paper-wrap" style="display:flex;align-items:center;justify-content:center;color:#666;">Unsupported preview for this file type. Please download to view.</div>`;
  }

  // populate filename and download link in header
  try {
    const filenameEl = document.getElementById('viewFilename');
    const downloadEl = document.getElementById('downloadLink');
    const filename = (path && path.split('/').pop()) || (docId ? `document_${docId}` : 'document');
    if (filenameEl) filenameEl.textContent = filename;
    const downloadHref = viewUrl || path;
    if (downloadEl) {
      downloadEl.href = downloadHref;
      downloadEl.setAttribute('download', filename);
    }
  } catch (e) {
    console.warn('Could not set filename/download link', e);
  }

  const modal = document.getElementById('documentsModal');
  if (modal) modal.classList.add('show');
}

function closeViewDocumentModal() {
  const modal = document.getElementById('documentsModal');
  if (modal) modal.classList.remove('show');
  const viewer = document.getElementById('documentViewer');
  if (viewer) viewer.innerHTML = '';
}

// Listen for messages from iframe to open document viewer
window.addEventListener('message', function(event) {
  // Security check - only accept messages from same origin
  if (event.origin !== window.location.origin) return;
  
  if (event.data.action === 'viewDocument') {
    showViewDocumentModal(event.data.path, event.data.docId);
  }
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
  const modal = document.getElementById('documentsModal');
  if (e.target === modal) {
    closeViewDocumentModal();
  }
});

const dropZone = document.getElementById('file-drop-zone');
const fileInput = document.getElementById('document-file');
const filePreview = document.getElementById('file-preview');
const docTypeSelect = document.getElementById('document-type');
let selectedFiles = [];

// Update file input based on document type
docTypeSelect.addEventListener('change', function() {
  const isProposal = this.value === 'proposal';
  const isPhotos = this.value === 'photos';
  const programField = document.getElementById('program-field');
  const proposalFields = document.getElementById('proposal-fields');
  const programSelect = document.getElementById('program');

  if (isProposal) {
    // Hide program selection, show proposal fields
    programField.style.display = 'none';
    proposalFields.style.display = 'block';
    programSelect.required = false;
    document.getElementById('proposal-title').required = true;
  } else {
    // Show program selection, hide proposal fields
    programField.style.display = 'block';
    proposalFields.style.display = 'none';
    programSelect.required = true;
    document.getElementById('proposal-title').required = false;
  }

  fileInput.multiple = true; // Allow multiple for all types
  dropZone.querySelector('p').innerHTML = 'Drag & drop files here or <span class="file-select-link">browse</span> (multiple allowed)';
});

// Handle file selection link
dropZone.addEventListener('click', () => fileInput.click());
dropZone.querySelector('.file-select-link').addEventListener('click', (e) => {
  e.stopPropagation();
  fileInput.click();
});

// Drag and drop events
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
  dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
  dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
  dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight() {
  dropZone.classList.add('dragover');
}

function unhighlight() {
  dropZone.classList.remove('dragover');
}

dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
  const dt = e.dataTransfer;
  const files = dt.files;
  handleFiles(files);
}

fileInput.addEventListener('change', function(e) {
  handleFiles(e.target.files);
});

function handleFiles(files) {
  const hasNonImages = Array.from(files).some(file => {
    const ext = file.name.split('.').pop().toLowerCase();
    return ['pdf', 'docx'].includes(ext);
  });
  if (hasNonImages && files.length > 1) {
    alert('Only one file allowed for PDF or DOCX. Multiple files are only allowed for images (JPG, PNG).');
    return;
  }
  selectedFiles = Array.from(files);
  updateFilePreview();
}

function updateFilePreview() {
  filePreview.innerHTML = '';
  selectedFiles.forEach((file, index) => {
    const fileItem = document.createElement('div');
    fileItem.className = 'file-item';
    fileItem.innerHTML = `
      <span class="file-name">${file.name}</span>
      <span class="file-size">${formatFileSize(file.size)}</span>
      <span class="remove-file" onclick="removeFile(${index})">&times;</span>
    `;
    filePreview.appendChild(fileItem);
  });
}

function removeFile(index) {
  selectedFiles.splice(index, 1);
  updateFilePreview();
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function openUploadsModal() {
  document.getElementById('uploadsModal').style.display = 'flex';
}
function closeUploadsModal() {
  document.getElementById('uploadsModal').style.display = 'none';
}
// Optional: close modal when clicking outside content
window.addEventListener('click', function(e) {
  var modal = document.getElementById('uploadsModal');
  if (modal && e.target === modal) closeUploadsModal();
});

// Modal functions
function showClearModal() {
  document.getElementById('clearModalOverlay').style.display = 'block';
}

function closeClearModal() {
  document.getElementById('clearModalOverlay').style.display = 'none';
}

function showUploadStatusModal(success, message) {
  const modal = document.getElementById('uploadStatusModal');
  const icon = document.getElementById('uploadStatusIcon');
  const title = document.getElementById('uploadStatusTitle');
  const messageEl = document.getElementById('uploadStatusMessage');
  
  if (success) {
    icon.className = 'fas fa-check-circle';
    icon.style.color = '#28a745';
    title.textContent = 'Upload Successful';
  } else {
    icon.className = 'fas fa-exclamation-circle';
    icon.style.color = '#e53935';
    title.textContent = 'Upload Failed';
  }
  
  messageEl.textContent = message;
  modal.style.display = 'block';
}

function closeUploadStatusModal() {
  document.getElementById('uploadStatusModal').style.display = 'none';
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
  
  const uploadStatusOverlay = document.getElementById('uploadStatusModal');
  if (uploadStatusOverlay) {
    uploadStatusOverlay.addEventListener('click', function(e) {
      if (e.target === uploadStatusOverlay) {
        closeUploadStatusModal();
      }
    });
  }

  // Close modal with Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeClearModal();
      closeUploadStatusModal();
    }
  });
});

document.getElementById('uploadForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var form = e.target;
  var data = new FormData();

  // Append form fields
  if (form.program_id.value) {
    data.append('program_id', form.program_id.value);
  }
  data.append('document_type', form.document_type.value);

  // Append proposal fields if this is a proposal
  if (form.document_type.value === 'proposal') {
    data.append('proposal_title', form.proposal_title.value);
    if (form.proposal_description.value) {
      data.append('proposal_description', form.proposal_description.value);
    }
  }

  // Append selected files
  selectedFiles.forEach(file => {
    data.append('document_file[]', file);
  });

  // Show loading state
  const submitBtn = form.querySelector('.submit');
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
  submitBtn.disabled = true;

  fetch('upload_handler.php', {
    method: 'POST',
    body: data
  })
  .then(response => response.text())
  .then(text => {
    // Check if upload was successful based on response text
    const isSuccess = text.toLowerCase().includes('success') || text.toLowerCase().includes('uploaded');
    showUploadStatusModal(isSuccess, text);
    
    if (isSuccess) {
      form.reset();
      selectedFiles = [];
      updateFilePreview();
      // Reset form visibility
      document.getElementById('program-field').style.display = 'block';
      document.getElementById('proposal-fields').style.display = 'none';
      document.getElementById('program').required = true;
      document.getElementById('proposal-title').required = false;
    }
    
    // Restore button
    submitBtn.innerHTML = originalBtnText;
    submitBtn.disabled = false;
  })
  .catch(() => {
    showUploadStatusModal(false, 'Upload failed. Please check your connection and try again.');
    // Restore button
    submitBtn.innerHTML = originalBtnText;
    submitBtn.disabled = false;
  });
});
</script>
</body>
</html>
