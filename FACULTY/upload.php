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
  </style>
</head>
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
          <li><a href="../portal/home/home.html"><i class="fas fa-external-link-alt"></i> Portal</a></li>
          <li><a href="Evaluation.php"><i class="fas fa-star-half-alt"></i> Evaluation</a></li>
          <li><a href="certificates.php"><i class="fas fa-certificate"></i> Certificate</a></li>
        <li class="active"><a href="upload.php"><i class="fas fa-upload"></i> Documents </a></li>  
          <li><a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a></li>
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
          <label for="program">Program</label>
          <select name="program_id" id="program" required>
            <option value="" disabled selected>Select Program</option>
            <?php foreach ($programs as $p): ?>
              <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
            <?php endforeach; ?>
          </select>

          <label for="document-type">Document Type</label>
          <select name="document_type" id="document-type" required>
            <option value="" disabled selected>Select Document Type</option>
            <option value="proposal">Approved Extension Activity Proposal</option>
            <option value="report">Activity and Implementation Report</option>
            <option value="attendance">Attendance Sheet</option>
            <option value="photos">Photo Documentation</option>
            <option value="feedback">Evaluation and Feedback Forms</option>
            <option value="workload">Summary of Workload Hours</option>
            <option value="accomplishments">Summary of Accomplishments</option>
            <option value="other">Other Supporting Documents</option>
          </select>

          <label for="document-file">Select File</label>
          <div id="file-drop-zone" class="file-drop-zone">
            <i class="fas fa-cloud-upload-alt"></i>
            <p>Drag & drop files here or <span class="file-select-link">browse</span></p>
            <input type="file" name="document_file[]" id="document-file" multiple style="display: none;">
          </div>
          <div id="file-preview" class="file-preview"></div>

          <button type="submit" class="submit"><i class="fas fa-upload"></i> Upload</button>
        </form>
        <div id="upload-message" style="margin-top:10px;font-weight:bold;color:#247a37;"></div>

        <div class="info-box">
          <i class="fas fa-info-circle"></i>
          Allowed file types: PDF, DOCX, JPG, PNG. Max size: 10MB per file. Multiple files allowed for images (JPG, PNG). PDF and DOCX limited to one file.
        </div>
      </div>

      <!-- Right Side -->
      <div class="right-panel">
        <div class="user-info">
          <div class="name"><?php echo htmlspecialchars($user_fullname); ?></div>
          <div class="email"><?php echo htmlspecialchars($user_email); ?></div>
        </div>
        <div class="notifications">
          <h3>🔔 Notifications</h3>
          <?php
          // Fetch active notifications (add this PHP block at the top if not present)
          $notifications = [];
          $notifications_query = "SELECT message, priority FROM notifications WHERE is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE()) ORDER BY created_at DESC LIMIT 5";
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
    <iframe src="my_uploads.php" frameborder="0"></iframe>
  </div>
</div>

<!-- Modal JS (add before </body>) -->
<script>
const dropZone = document.getElementById('file-drop-zone');
const fileInput = document.getElementById('document-file');
const filePreview = document.getElementById('file-preview');
const docTypeSelect = document.getElementById('document-type');
let selectedFiles = [];

// Update file input based on document type
docTypeSelect.addEventListener('change', function() {
  const isPhotos = this.value === 'photos';
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
document.getElementById('uploadForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var form = e.target;
  var data = new FormData();
  
  // Append form fields
  data.append('program_id', form.program_id.value);
  data.append('document_type', form.document_type.value);
  
  // Append selected files
  selectedFiles.forEach(file => {
    data.append('document_file[]', file);
  });
  
  var msgDiv = document.getElementById('upload-message');
  msgDiv.textContent = 'Uploading...';

  fetch('upload_handler.php', {
    method: 'POST',
    body: data
  })
  .then(response => response.text())
  .then(text => {
    msgDiv.textContent = text;
    form.reset();
    selectedFiles = [];
    updateFilePreview();
  })
  .catch(() => {
    msgDiv.textContent = 'Upload failed. Please try again.';
  });
});
</script>
</body>
</html>
