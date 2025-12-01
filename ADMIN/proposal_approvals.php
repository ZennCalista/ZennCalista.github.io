<?php
require_once '../backend/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../register/login.php');
    exit();
}

// Verify admin role
$user_id = $_SESSION['user_id'];
$admin_check = $conn->prepare("SELECT role FROM users WHERE id = ?");
$admin_check->bind_param("i", $user_id);
$admin_check->execute();
$admin_check->bind_result($user_role);
$admin_check->fetch();
$admin_check->close();

if ($user_role !== 'admin') {
    die('Access denied. Admin privileges required.');
}

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $proposal_id = $_POST['proposal_id'];
    $action = $_POST['action'];
    $review_notes = isset($_POST['review_notes']) ? $_POST['review_notes'] : '';

    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        $update_sql = "UPDATE program_proposals SET status = ?, reviewed_at = NOW(), reviewed_by = ?, review_notes = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('sisi', $status, $user_id, $review_notes, $proposal_id);

        if ($update_stmt->execute()) {
            // Create notification for faculty
            $faculty_query = $conn->prepare("SELECT u.firstname, u.lastname, pp.faculty_id FROM program_proposals pp JOIN faculty f ON pp.faculty_id = f.id JOIN users u ON f.user_id = u.id WHERE pp.id = ?");
            $faculty_query->bind_param("i", $proposal_id);
            $faculty_query->execute();
            $faculty_query->bind_result($faculty_firstname, $faculty_lastname, $faculty_id);
            $faculty_query->fetch();
            $faculty_query->close();

            $faculty_name = $faculty_firstname . ' ' . $faculty_lastname;
            $message = "Your proposal has been " . ($status === 'approved' ? 'approved' : 'rejected') . " by admin.";
            if (!empty($review_notes)) {
                $message .= " Notes: " . $review_notes;
            }

            $notif_sql = "INSERT INTO notifications (message, priority, audience, recipient_id, is_active, created_at) VALUES (?, ?, 'faculty', ?, 1, NOW())";
            $priority = ($status === 'approved') ? 'low' : 'medium';
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param('ssis', $message, $priority, $faculty_id);
            $notif_stmt->execute();
            $notif_stmt->close();

            $success_message = "Proposal " . ($status === 'approved' ? 'approved' : 'rejected') . " successfully!";
        } else {
            $error_message = "Failed to update proposal status.";
        }
        $update_stmt->close();
    }
}

// Fetch all proposals with faculty details
$proposals_sql = "SELECT pp.*, f.department, u.firstname, u.lastname, u.email,
                         COUNT(du.id) as document_count,
                         p.program_name
                  FROM program_proposals pp
                  JOIN faculty f ON pp.faculty_id = f.id
                  JOIN users u ON f.user_id = u.id
                  LEFT JOIN document_uploads du ON du.proposal_id = pp.id
                  LEFT JOIN programs p ON pp.program_id = p.id
                  GROUP BY pp.id
                  ORDER BY pp.submitted_at DESC";

$proposals_result = $conn->query($proposals_sql);
$proposals = [];
if ($proposals_result) {
    while ($row = $proposals_result->fetch_assoc()) {
        $proposals[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposal Approvals - Admin</title>
    <link rel="stylesheet" href="Document.css">
    <link rel="stylesheet" href="admin_notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-used { background: #e2e3e5; color: #383d41; }

        .proposal-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }

        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .proposal-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .proposal-meta {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .proposal-description {
            color: #495057;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .proposal-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-view-docs {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .review-notes {
            width: 100%;
            padding: 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-top: 5px;
            font-size: 0.9rem;
        }

        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .filter-section {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-form select {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
    </style>
        <style>
            /* Viewer modal overrides (copied from Document.html) to ensure consistent look */
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
            }
            .modal-content.large-modal .modal-body {
                flex: 1 1 auto !important;
                height: auto !important;
                overflow: hidden !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                background: transparent !important;
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
            .modal-content.large-modal .view-controls span#viewFilename {
                color: #fff;
                opacity: 0.95;
            }
            .modal-content.large-modal .view-controls a#downloadLink:hover {
                background: rgba(255,255,255,0.05);
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
            .modal-content.large-modal .view-controls a.download-btn i.fas {
                color: #114d2e !important;
                font-size: 1rem !important;
            }
            .modal-content.large-modal .view-controls a.download-btn:hover {
                transform: translateY(-1px) !important;
                box-shadow: 0 8px 20px rgba(0,0,0,0.22) !important;
            }
        </style>
</head>
<body>
    <div class="sidebar">
        <h2>eTracker Admin</h2>
        <a href="Dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="Reports.html"><i class="fas fa-chart-bar"></i> Reports & Analytics</a>
        <a href="User.html"><i class="fas fa-users"></i> User Management</a>
        <a href="Programs.html"><i class="fas fa-calendar-alt"></i> Project Management</a>
        <!-- <a href="ProjectEvaluation.html"><i class="fas fa-clipboard-check"></i> Project Evaluation</a> -->
        <a href="proposal_approvals.php" class="active"><i class="fas fa-check-circle"></i> Proposal Approvals</a>
        <a href="Attendance.html"><i class="fas fa-check-square"></i> Attendance Tracker</a>
        <a href="Evaluation.html"><i class="fas fa-poll"></i> Evaluation & Feedback</a>
        <a href="Document.html"><i class="fas fa-folder"></i> Document Management</a>
        <a href="Certificates.html"><i class="fas fa-certificate"></i> Certificates</a>
        <a href="Notifications.html"><i class="fas fa-bell"></i> Notifications</a>
        <a href="../portal/home/home.html"><i class="fas fa-external-link-alt"></i> Portal</a>
        <div style="margin-top: auto; padding-top: 20px;">
            <a href="../register/logout.php" style="color: none; text-decoration: none; display: block; padding: 12px 20px; text-align: center; border-top: 1px solid rgba(255,255,255,0.1);">
                <i class="fas fa-sign-out-alt"></i> Sign Out
            </a>
        </div>
    </div>

    <div class="main">
        <h1><i class="fas fa-check-circle"></i> Proposal Approvals</h1>

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-section">
            <?php
            $stats = array_reduce($proposals, function($carry, $proposal) {
                $carry[$proposal['status']]++;
                return $carry;
            }, ['pending' => 0, 'approved' => 0, 'rejected' => 0]);
            ?>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Reviews</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($proposals); ?></div>
                <div class="stat-label">Total Proposals</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <h2><i class="fas fa-filter"></i> Filter Proposals</h2>
            <div class="filter-form">
                <select id="statusFilter" onchange="filterProposals()">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
                <select id="departmentFilter" onchange="filterProposals()">
                    <option value="">All Departments</option>
                    <?php
                    $dept_sql = "SELECT DISTINCT department FROM faculty ORDER BY department";
                    $dept_result = $conn->query($dept_sql);
                    while ($dept = $dept_result->fetch_assoc()) {
                        echo "<option value='" . htmlspecialchars($dept['department']) . "'>" . htmlspecialchars($dept['department']) . "</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

        <!-- Proposals List -->
        <div id="proposalsContainer">
            <?php foreach ($proposals as $proposal): ?>
                <div class="proposal-card" data-status="<?php echo $proposal['status']; ?>" data-department="<?php echo htmlspecialchars($proposal['department']); ?>">
                    <div class="proposal-header">
                        <div>
                            <div class="proposal-title"><?php echo htmlspecialchars($proposal['proposal_title']); ?></div>
                            <div class="proposal-meta">
                                <strong><?php echo htmlspecialchars($proposal['firstname'] . ' ' . $proposal['lastname']); ?></strong>
                                (<?php echo htmlspecialchars($proposal['department']); ?>) •
                                Submitted: <?php echo date('M j, Y g:i A', strtotime($proposal['submitted_at'])); ?>
                            </div>
                        </div>
                        <span class="status-badge status-<?php echo $proposal['status']; ?>">
                            <?php echo ucfirst($proposal['status']); ?>
                        </span>
                    </div>

                    <?php if (!empty($proposal['description'])): ?>
                        <div class="proposal-description">
                            <?php echo htmlspecialchars($proposal['description']); ?>
                        </div>
                    <?php endif; ?>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                        <div style="color: #6c757d; font-size: 0.9rem;">
                            <i class="fas fa-file"></i> <?php echo $proposal['document_count']; ?> document(s) uploaded
                        </div>

                        <div class="proposal-actions">
                            <?php if ($proposal['status'] === 'pending'): ?>
                                <button class="btn-view-docs" onclick="viewProposalDocuments(<?php echo $proposal['id']; ?>)">
                                    <i class="fas fa-folder-open"></i> View Documents
                                </button>

                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <textarea name="review_notes" class="review-notes" placeholder="Optional approval notes..." rows="2"></textarea>
                                    <button type="submit" class="btn-approve" onclick="return confirm('Approve this proposal?')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                </form>

                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <textarea name="review_notes" class="review-notes" placeholder="Rejection reason..." rows="2" required></textarea>
                                    <button type="submit" class="btn-reject" onclick="return confirm('Reject this proposal?')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn-view-docs" onclick="viewProposalDocuments(<?php echo $proposal['id']; ?>)">
                                    <i class="fas fa-folder-open"></i> View Documents
                                </button>

                                <?php if (!empty($proposal['review_notes'])): ?>
                                    <div style="margin-left: 10px; color: #6c757d; font-size: 0.9rem;">
                                        <strong>Review Notes:</strong> <?php echo htmlspecialchars($proposal['review_notes']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($proposal['status'] === 'used' && !empty($proposal['program_name'])): ?>
                                    <div style="margin-left: 10px; color: #28a745; font-size: 0.9rem;">
                                        <strong>Used for Program:</strong> <?php echo htmlspecialchars($proposal['program_name']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($proposal['reviewed_at']): ?>
                                    <div style="margin-left: 10px; color: #6c757d; font-size: 0.8rem;">
                                        Reviewed: <?php echo date('M j, Y g:i A', strtotime($proposal['reviewed_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($proposals)): ?>
                <div style="text-align: center; padding: 50px; color: #6c757d;">
                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px;"></i>
                    <h3>No proposals found</h3>
                    <p>Faculty members haven't submitted any proposals yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for viewing proposal documents -->
    <div id="documentsModal" class="modal" style="display: none;">
        <div class="modal-content large-modal">
            <div class="modal-header">
                <h3><i class="fas fa-eye"></i> View Document</h3>
                <div class="view-controls" style="display:flex;align-items:center;gap:12px;">
                  <span id="viewFilename" style="font-weight:600; color:#fff; max-width:60vw; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:inline-block;"></span>
                  <a id="downloadLink" class="download-btn" target="_blank" rel="noopener">
                    <i class="fas fa-download" aria-hidden="true"></i>
                    <span style="margin-left:6px;">Download</span>
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

    <script>
        function filterProposals() {
            const statusFilter = document.getElementById('statusFilter').value;
            const departmentFilter = document.getElementById('departmentFilter').value;
            const cards = document.querySelectorAll('.proposal-card');

            cards.forEach(card => {
                const status = card.dataset.status;
                const department = card.dataset.department;
                const statusMatch = !statusFilter || status === statusFilter;
                const departmentMatch = !departmentFilter || department === departmentFilter;

                card.style.display = (statusMatch && departmentMatch) ? 'block' : 'none';
            });
        }

        function viewProposalDocuments(proposalId) {
            // Fetch documents for this proposal
            fetch(`../backend/get_proposal_documents.php?proposal_id=${proposalId}`)
                .then(response => response.json())
                .then(documents => {
                    if (documents.length > 0) {
                        // Show the first document using the shared viewer UI
                        const doc = documents[0];
                        showViewDocumentModal(doc.file_path, doc.id);
                    } else {
                        alert('No documents found for this proposal.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching documents:', error);
                    alert('Error loading documents.');
                });
        }

        // Reusable viewer adapted from Document.html
        function showViewDocumentModal(path, docId) {
            const viewer = document.getElementById('documentViewer');
            const extension = (path || '').split('.').pop().toLowerCase();

            let viewUrl;
            if (docId) {
                viewUrl = `view_document.php?id=${docId}`;
            } else {
                viewUrl = path && path.startsWith('/') ? path : `../${path}`;
            }

            if (extension === 'pdf') {
                viewer.innerHTML = `\n                  <div class="paper-wrap">\n                    <iframe src="${viewUrl}" allowfullscreen></iframe>\n                  </div>\n                `;
            } else if (extension === 'docx') {
                // Use Google viewer as fallback
                const fullUrl = window.location.origin + window.location.pathname.replace('proposal_approvals.php', '') + viewUrl;
                viewer.innerHTML = `\n                  <div class="paper-wrap">\n                    <iframe src="https://docs.google.com/gview?url=${encodeURIComponent(fullUrl)}&embedded=true" allowfullscreen></iframe>\n                  </div>\n                `;
            } else if (['jpg', 'jpeg', 'png'].includes(extension)) {
                viewer.innerHTML = `\n                  <div class="paper-wrap" style="background:#f5f5f5;">\n                    <img src="${viewUrl}" alt="Document Image" style="max-width:100%; height:auto; display:block; margin:0 auto;"/>\n                  </div>\n                `;
            } else {
                // For unknown types, open in a new tab
                if (docId) {
                    window.open(viewUrl, '_blank');
                    return;
                }
                viewer.innerHTML = `<div class="paper-wrap">Unsupported preview for this file type.</div>`;
            }

            // populate filename and download link in header
            try {
                const filenameEl = document.getElementById('viewFilename');
                const downloadEl = document.getElementById('downloadLink');
                const filename = (path && path.split('/').pop()) || (docId ? `document_${docId}` : 'document');
                if (filenameEl) filenameEl.textContent = filename;
                const downloadHref = docId ? `view_document.php?id=${docId}` : (viewUrl || path);
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

        function closeDocumentsModal() {
            const modal = document.getElementById('documentsModal');
            modal.classList.remove('show');
            document.getElementById('documentFrame').src = '';
        }

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('documentsModal');
            if (e.target === modal) {
                closeDocumentsModal();
            }
        });
    </script>

<script src="admin_notifications.js"></script>
<script>
  // Initialize admin notifications when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    initAdminNotifications();
  });
</script>
</body>
</html>