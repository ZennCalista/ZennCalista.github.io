<?php
require_once 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Not logged in');
}

$user_id = $_SESSION['user_id'];

// Get faculty_id for this user
$faculty_id = null;
$stmt = $conn->prepare("SELECT id FROM faculty WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($faculty_id);
$stmt->fetch();
$stmt->close();

if (!$faculty_id) {
    // Instead of dying, show a user-friendly message
    $error_message = "Faculty record not found. Please contact your administrator to set up your faculty profile.";
    $show_error = true;
    $uploads = [];
} else {
    $show_error = false;

    // Fetch all uploads for this faculty - both proposals and documents
    $uploads = [];

    // Get all proposals with their attached documents
    $proposals_sql = "SELECT pp.id as proposal_id, pp.proposal_title as title, pp.description, pp.status, pp.submitted_at as upload_date,
                             'proposal' as upload_type, 
                             GROUP_CONCAT(du.file_path SEPARATOR '|||') as file_paths,
                             GROUP_CONCAT(du.original_filename SEPARATOR '|||') as original_filenames,
                             GROUP_CONCAT(du.id SEPARATOR '|||') as document_ids,
                             NULL as program_name, pp.review_notes as admin_remarks,
                             COUNT(du.id) as file_count
                      FROM program_proposals pp
                      LEFT JOIN document_uploads du ON du.proposal_id = pp.id
                      WHERE pp.faculty_id = ?
                      GROUP BY pp.id
                      ORDER BY pp.submitted_at DESC";
    $stmt = $conn->prepare($proposals_sql);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $proposals_result = $stmt->get_result();

    while ($row = $proposals_result->fetch_assoc()) {
        $uploads[] = $row;
    }
    $stmt->close();

    // Then, get all document uploads NOT linked to proposals
    $documents_sql = "SELECT du.id, 
                             CASE 
                               WHEN du.program_id IS NOT NULL THEN p.program_name
                               ELSE 'General Document'
                             END as title,
                             du.document_type as description,
                             du.status,
                             du.upload_date,
                             'document' as upload_type,
                             du.file_path as file_paths,
                             du.original_filename as original_filenames,
                             du.id as document_ids,
                             CASE 
                               WHEN du.program_id IS NOT NULL THEN p.program_name
                               ELSE 'General'
                             END as program_name,
                             NULL as admin_remarks,
                             1 as file_count
                      FROM document_uploads du
                      LEFT JOIN programs p ON du.program_id = p.id
                      WHERE du.faculty_id = ? AND du.proposal_id IS NULL
                      ORDER BY du.upload_date DESC";
    $stmt = $conn->prepare($documents_sql);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $documents_result = $stmt->get_result();

    while ($row = $documents_result->fetch_assoc()) {
        $uploads[] = $row;
    }
    $stmt->close();

    // Sort all uploads by date (most recent first)
    usort($uploads, function($a, $b) {
        return strtotime($b['upload_date']) - strtotime($a['upload_date']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Uploaded Documents</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f7faf7; color: #1e3927; }
    .uploads-table { width: 100%; border-collapse: collapse; margin: 40px auto; max-width: 1100px; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(36,122,55,0.10);}
    .uploads-table th, .uploads-table td { padding: 12px 10px; border-bottom: 1px solid #e0e0e0; text-align: left; }
    .uploads-table th { background: #d2eac8; color: #247a37; }
    .uploads-table tr:last-child td { border-bottom: none; }
    .status { padding: 4px 12px; border-radius: 10px; font-weight: 600; font-size: 0.98em; }
    .status.pending { background: #fffbe4; color: #bfa600; }
    .status.approved { background: #eafbe7; color: #247a37; }
    .status.rejected { background: #ffeaea; color: #b30000; }
    .remarks { color: #b30000; font-size: 0.97em; }
    .download-link { color: #247a37; text-decoration: none; font-weight: 500; cursor: pointer; }
    .download-link i { margin-right: 4px; }
    .file-list { padding-left: 8px; }
    .file-count { font-weight: 600; color: #2E7D32; margin-bottom: 6px; font-size: 0.95em; }
    h2 { margin: 40px auto 18px auto; text-align: center; color: #247a37; }
    .delete-btn { background: #dc3545; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; transition: background 0.2s; }
    .delete-btn:hover { background: #c82333; }
    .delete-btn i { margin-right: 4px; }
    .actions-cell { text-align: center; }
    
    /* Delete Confirmation Modal */
    .delete-modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); z-index: 9999; }
    .delete-modal { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 12px; padding: 32px; max-width: 450px; width: 90%; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); z-index: 10000; }
    .delete-modal-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
    .delete-modal-header i { font-size: 2rem; color: #dc3545; }
    .delete-modal-header h3 { font-size: 1.4rem; color: #1b472b; margin: 0; }
    .delete-modal-body { margin-bottom: 24px; font-size: 1.05rem; color: #333; line-height: 1.6; }
    .delete-modal-actions { display: flex; gap: 12px; justify-content: flex-end; }
    .delete-modal-btn { padding: 10px 24px; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
    .delete-modal-btn-cancel { background: #6c757d; color: #fff; }
    .delete-modal-btn-cancel:hover { background: #5a6268; }
    .delete-modal-btn-confirm { background: #dc3545; color: #fff; }
    .delete-modal-btn-confirm:hover { background: #c82333; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4); }
  </style>
</head>
<body>
  <h2>My Uploaded Documents</h2>

  <?php if ($show_error): ?>
    <div style="background: #ffeaea; border: 1px solid #e74c3c; color: #e74c3c; padding: 20px; border-radius: 5px; margin: 20px auto; max-width: 800px; text-align: center;">
      <h3>⚠️ <?php echo htmlspecialchars($error_message); ?></h3>
      <p>Please contact your system administrator to complete your faculty profile setup.</p>
    </div>
  <?php else: ?>

  <table class="uploads-table">
    <thead>
      <tr>
        <th>Type</th>
        <th>Title/Description</th>
        <th>Program/Proposal</th>
        <th>File</th>
        <th>Uploaded</th>
        <th>Status</th>
        <th>Admin Remarks</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($uploads)): ?>
        <tr><td colspan="7" style="text-align:center;">No uploads found.</td></tr>
      <?php else: ?>
        <?php foreach ($uploads as $upload): ?>
          <tr>
            <td>
              <?php 
              if ($upload['upload_type'] === 'proposal') {
                echo '<i class="fas fa-file-alt" style="color:#247a37;"></i> Proposal';
              } elseif ($upload['upload_type'] === 'proposal_document') {
                echo '<i class="fas fa-file" style="color:#59a96a;"></i> Proposal Doc';
              } else {
                echo '<i class="fas fa-file-upload" style="color:#59a96a;"></i> Document';
              }
              ?>
            </td>
            <td>
              <?php 
              if ($upload['upload_type'] === 'proposal') {
                echo htmlspecialchars($upload['title']);
                if (!empty($upload['description'])) {
                  echo '<br><small style="color:#666;">' . htmlspecialchars(substr($upload['description'], 0, 100)) . (strlen($upload['description']) > 100 ? '...' : '') . '</small>';
                }
              } else {
                echo ucfirst(htmlspecialchars($upload['description']));
              }
              ?>
            </td>
            <td><?php echo htmlspecialchars($upload['program_name'] ?? 'N/A'); ?></td>
            <td>
              <?php 
              if (!empty($upload['file_paths']) && !empty($upload['original_filenames'])) {
                $file_paths = explode('|||', $upload['file_paths']);
                $filenames = explode('|||', $upload['original_filenames']);
                $doc_ids = !empty($upload['document_ids']) ? explode('|||', $upload['document_ids']) : [];
                
                // Filter out empty values
                $file_paths = array_filter($file_paths, function($val) { return !empty(trim($val)); });
                $filenames = array_filter($filenames, function($val) { return !empty(trim($val)); });
                $doc_ids = array_filter($doc_ids, function($val) { return !empty(trim($val)); });
                
                if (count($file_paths) > 1) {
                  // Multiple files - show count and list
                  echo '<div class="file-count">' . count($file_paths) . ' files attached:</div>';
                  echo '<div class="file-list">';
                  $file_paths = array_values($file_paths);
                  $filenames = array_values($filenames);
                  $doc_ids = array_values($doc_ids);
                  for ($i = 0; $i < count($file_paths); $i++) {
                    $doc_id = isset($doc_ids[$i]) ? $doc_ids[$i] : '';
                    echo '<a class="download-link view-doc-link" href="javascript:void(0)" onclick="viewDocument(\'' . htmlspecialchars($file_paths[$i], ENT_QUOTES) . '\', \'' . htmlspecialchars($doc_id, ENT_QUOTES) . '\')" style="display:block; margin:2px 0;">';
                    echo '<i class="fas fa-eye"></i> ' . htmlspecialchars($filenames[$i]);
                    echo '</a>';
                  }
                  echo '</div>';
                } elseif (count($file_paths) === 1) {
                  // Single file
                  $file_paths = array_values($file_paths);
                  $filenames = array_values($filenames);
                  $doc_ids = array_values($doc_ids);
                  $doc_id = isset($doc_ids[0]) ? $doc_ids[0] : '';
                  echo '<a class="download-link view-doc-link" href="javascript:void(0)" onclick="viewDocument(\'' . htmlspecialchars($file_paths[0], ENT_QUOTES) . '\', \'' . htmlspecialchars($doc_id, ENT_QUOTES) . '\')">';
                  echo '<i class="fas fa-eye"></i> ' . htmlspecialchars($filenames[0]);
                  echo '</a>';
                } else {
                  echo '<span style="color:#999;">No file attached</span>';
                }
              } else {
                echo '<span style="color:#999;">No file attached</span>';
              }
              ?>
            </td>
            <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($upload['upload_date']))); ?></td>
            <td>
              <span class="status <?php echo htmlspecialchars($upload['status']); ?>">
                <?php echo ucfirst($upload['status']); ?>
              </span>
            </td>
            <td class="remarks"><?php echo htmlspecialchars($upload['admin_remarks'] ?? ''); ?></td>
            <td class="actions-cell">
              <?php
              // Only show delete button for pending uploads
              if ($upload['status'] === 'pending') {
                $delete_id = ($upload['upload_type'] === 'proposal') ? 'p_' . $upload['proposal_id'] : 'd_' . $upload['id'];
                $delete_title = htmlspecialchars($upload['title'], ENT_QUOTES);
                echo '<button class="delete-btn" onclick="showDeleteModal(\'' . $delete_id . '\', \'' . $delete_title . '\')">';
                echo '<i class="fas fa-trash"></i> Delete';
                echo '</button>';
              } else {
                echo '<span style="color:#999; font-size:0.85rem;">—</span>';
              }
              ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <?php endif; ?>

  <!-- Delete Confirmation Modal -->
  <div class="delete-modal-overlay" id="deleteModalOverlay">
    <div class="delete-modal">
      <div class="delete-modal-header">
        <i class="fas fa-exclamation-triangle"></i>
        <h3>Delete Upload?</h3>
      </div>
      <div class="delete-modal-body" id="deleteModalBody">
        Are you sure you want to delete this upload? This action cannot be undone.
      </div>
      <div class="delete-modal-actions">
        <button class="delete-modal-btn delete-modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
        <button class="delete-modal-btn delete-modal-btn-confirm" onclick="confirmDelete()">Delete</button>
      </div>
    </div>
  </div>

  <script>
    let deleteItemId = null;
    let deleteItemTitle = null;

    // Function to show delete confirmation modal
    function showDeleteModal(id, title) {
      deleteItemId = id;
      deleteItemTitle = title;
      const modalBody = document.getElementById('deleteModalBody');
      modalBody.innerHTML = `Are you sure you want to delete "<strong>${title}</strong>"? This action cannot be undone.`;
      document.getElementById('deleteModalOverlay').style.display = 'block';
    }

    // Function to close delete modal
    function closeDeleteModal() {
      document.getElementById('deleteModalOverlay').style.display = 'none';
      deleteItemId = null;
      deleteItemTitle = null;
    }

    // Function to confirm deletion
    function confirmDelete() {
      if (!deleteItemId) return;

      // Show loading state
      const confirmBtn = document.querySelector('.delete-modal-btn-confirm');
      const originalText = confirmBtn.innerHTML;
      confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
      confirmBtn.disabled = true;

      // Send delete request
      fetch('delete_upload.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + encodeURIComponent(deleteItemId)
      })
      .then(response => response.text())
      .then(text => {
        console.log('Delete response:', text); // Debug log
        if (text.includes('success')) {
          // Reload the page to reflect changes
          window.location.reload();
        } else {
          alert('Failed to delete: ' + text);
          confirmBtn.innerHTML = originalText;
          confirmBtn.disabled = false;
        }
      })
      .catch(error => {
        console.error('Delete error:', error); // Debug log
        alert('Error deleting upload: ' + error.message);
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
      });
    }

    // Close modal when clicking outside
    document.getElementById('deleteModalOverlay').addEventListener('click', function(e) {
      if (e.target === this) {
        closeDeleteModal();
      }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeDeleteModal();
      }
    });

    // Function to send message to parent window to open document viewer
    function viewDocument(path, docId) {
      if (window.parent && window.parent !== window) {
        // We're in an iframe, send message to parent
        window.parent.postMessage({
          action: 'viewDocument',
          path: path,
          docId: docId
        }, window.location.origin);
      } else {
        // Standalone page, open in new tab as fallback
        window.open(path, '_blank');
      }
    }
  </script>

</body>
</html>