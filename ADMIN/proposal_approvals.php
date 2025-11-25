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

            $notif_sql = "INSERT INTO notifications (message, priority, is_active, created_at) VALUES (?, ?, 1, NOW())";
            $priority = ($status === 'approved') ? 'low' : 'medium';
            $notif_stmt = $conn->prepare($notif_sql);
            $notif_stmt->bind_param('ss', $message, $priority);
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
                         COUNT(du.id) as document_count
                  FROM program_proposals pp
                  JOIN faculty f ON pp.faculty_id = f.id
                  JOIN users u ON f.user_id = u.id
                  LEFT JOIN document_uploads du ON du.proposal_id = pp.id
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
</head>
<body>
    <div class="sidebar">
        <h2>eTracker Admin</h2>
        <a href="Dashboard.html"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="User.html"><i class="fas fa-users"></i> User Management</a>
        <a href="Programs.html"><i class="fas fa-calendar-alt"></i> Project Management</a>
        <a href="ProjectEvaluation.html"><i class="fas fa-clipboard-check"></i> Project Evaluation</a>
        <a href="proposal_approvals.php" class="active"><i class="fas fa-check-circle"></i> Proposal Approvals</a>
        <a href="Attendance.html"><i class="fas fa-check-square"></i> Attendance Tracker</a>
        <a href="Evaluation.html"><i class="fas fa-poll"></i> Evaluation & Feedback</a>
        <a href="Reports.html"><i class="fas fa-chart-bar"></i> Reports & Analytics</a>
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
                <span class="modal-title">Proposal Documents</span>
                <button class="modal-close" onclick="closeDocumentsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="documentViewer">
                    <div class="paper-wrap">
                        <iframe id="documentFrame" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
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
                        // For now, just show the first document
                        const doc = documents[0];
                        const modal = document.getElementById('documentsModal');
                        const iframe = document.getElementById('documentFrame');

                        if (doc.file_path) {
                            iframe.src = `../backend/view_document.php?id=${doc.id}`;
                        } else {
                            iframe.src = `../backend/view_document.php?id=${doc.id}`;
                        }

                        modal.style.display = 'block';
                    } else {
                        alert('No documents found for this proposal.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching documents:', error);
                    alert('Error loading documents.');
                });
        }

        function closeDocumentsModal() {
            const modal = document.getElementById('documentsModal');
            modal.style.display = 'none';
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
</body>
</html>