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

// Function to map backend status to display status
function getDisplayStatus($status) {
    $statusMap = [
        'approved' => 'endorsed',
        'pending' => 'pending',
        'rejected' => 'rejected'
    ];
    return isset($statusMap[$status]) ? $statusMap[$status] : $status;
}

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $proposal_id = $_POST['proposal_id'];
    $action = $_POST['action'];
    $review_notes = isset($_POST['review_notes']) ? $_POST['review_notes'] : '';

    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';

        try {
            $update_sql = "UPDATE program_proposals SET status = ?, reviewed_at = NOW(), reviewed_by = ?, review_notes = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            
            if (!$update_stmt) {
                throw new Exception("Failed to prepare update statement: " . $conn->error);
            }
            
            $update_stmt->bind_param('sisi', $status, $user_id, $review_notes, $proposal_id);

            if ($update_stmt->execute()) {
                // Create notification for faculty
                $faculty_query = $conn->prepare("SELECT u.firstname, u.lastname, pp.faculty_id FROM program_proposals pp JOIN faculty f ON pp.faculty_id = f.id JOIN users u ON f.user_id = u.id WHERE pp.id = ?");
                
                if ($faculty_query) {
                    $faculty_query->bind_param("i", $proposal_id);
                    $faculty_query->execute();
                    $faculty_query->bind_result($faculty_firstname, $faculty_lastname, $faculty_id);
                    
                    if ($faculty_query->fetch()) {
                        $faculty_query->close();
                        
                        $faculty_name = $faculty_firstname . ' ' . $faculty_lastname;
                        $message = "Your proposal has been " . ($status === 'approved' ? 'endorsed' : 'rejected') . " by admin.";
                        if (!empty($review_notes)) {
                            $message .= " Notes: " . $review_notes;
                        }

                        $notif_sql = "INSERT INTO notifications (message, priority, audience, recipient_id, is_active, created_at) VALUES (?, ?, 'faculty', ?, 1, NOW())";
                        $priority = ($status === 'approved') ? 'low' : 'medium';
                        $notif_stmt = $conn->prepare($notif_sql);
                        
                        if ($notif_stmt) {
                            $notif_stmt->bind_param('ssis', $message, $priority, $faculty_id);
                            $notif_stmt->execute();
                            $notif_stmt->close();
                        }
                    } else {
                        $faculty_query->close();
                    }
                }

                $_SESSION['success_message'] = "Proposal " . ($status === 'approved' ? 'endorsed' : 'rejected') . " successfully!";
            } else {
                throw new Exception("Failed to execute update: " . $update_stmt->error);
            }
            $update_stmt->close();
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
            
        } catch (Exception $e) {
            error_log("Error in proposal approval: " . $e->getMessage());
            $_SESSION['error_message'] = "Failed to update proposal status. Please try again.";
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}

// Get messages from session and clear them
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : null;
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : null;
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Fetch all proposals with faculty details
// Sort by: pending first, then approved/rejected, ordered by submission date (newest first)
$proposals_sql = "SELECT pp.*, f.department, u.firstname, u.lastname, u.email,
                         COUNT(du.id) as document_count,
                         p.program_name
                  FROM program_proposals pp
                  JOIN faculty f ON pp.faculty_id = f.id
                  JOIN users u ON f.user_id = u.id
                  LEFT JOIN document_uploads du ON du.proposal_id = pp.id
                  LEFT JOIN programs p ON pp.program_id = p.id
                  GROUP BY pp.id
                  ORDER BY 
                    CASE pp.status 
                      WHEN 'pending' THEN 1 
                      WHEN 'approved' THEN 2 
                      WHEN 'rejected' THEN 3 
                      ELSE 4 
                    END,
                    pp.submitted_at DESC";

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
    <title>Proposal Endorsement - Admin</title>
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

        .proposals-table {
            width: 100%;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            border-collapse: collapse;
        }

        .proposals-table th {
            background: #114d2e;
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .proposals-table td {
            padding: 15px 12px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        .proposals-table tr:last-child td {
            border-bottom: none;
        }

        .proposals-table tr:hover {
            background-color: #f8f9fa;
        }

        .proposal-title-cell {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .proposal-description-text {
            color: #6c757d;
            font-size: 0.85rem;
            line-height: 1.4;
            max-width: 300px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .faculty-info {
            color: #495057;
            font-size: 0.9rem;
        }

        .department-badge {
            display: inline-block;
            background: #e7f3ff;
            color: #0066cc;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-top: 4px;
        }

        .proposal-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 140px;
        }

        .btn-approve {
            background: #28a745;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            width: 100%;
            white-space: nowrap;
        }

        .btn-reject {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            width: 100%;
            white-space: nowrap;
        }

        .btn-view-docs {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            width: 100%;
            white-space: nowrap;
        }

        .btn-approve:hover {
            background: #218838;
        }

        .btn-reject:hover {
            background: #c82333;
        }

        .btn-view-docs:hover {
            background: #138496;
        }

        .review-notes {
            width: 100%;
            padding: 6px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            margin-bottom: 5px;
            font-size: 0.8rem;
            resize: vertical;
            min-height: 50px;
        }

        .notes-display {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            color: #495057;
            margin-top: 5px;
        }

        .date-info {
            color: #6c757d;
            font-size: 0.8rem;
            margin-top: 4px;
        }

        .doc-count {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .doc-count i {
            color: #007bff;
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

        .stat-number.pending {
            color: #ffc107;
        }

        .stat-number.approved {
            color: #28a745;
        }

        .stat-number.rejected {
            color: #dc3545;
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

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 15px 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .pagination-info {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .pagination-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pagination-controls button {
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            background: #fff;
            color: #495057;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .pagination-controls button:hover:not(:disabled) {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .pagination-controls button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-controls button.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
            font-weight: 600;
        }

        .pagination-controls select {
            padding: 6px 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
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
        <a href="proposal_approvals.php" class="active"><i class="fas fa-check-circle"></i> Proposal Endorsement</a>
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
        <h1><i class="fas fa-check-circle"></i> Proposal Endorsement</h1>

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
                <div class="stat-number pending"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Reviews</div>
            </div>
            <div class="stat-card">
                <div class="stat-number approved"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Endorsed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number rejected"><?php echo $stats['rejected']; ?></div>
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
                    <option value="approved">Endorsed</option>
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

        <!-- Proposals Table -->
        <div id="tableWrapper">
        <?php if (!empty($proposals)): ?>
        <table class="proposals-table" id="proposalsTable">
            <thead>
                <tr>
                    <th style="width: 25%;">Proposal Title</th>
                    <th style="width: 18%;">Faculty / Department</th>
                    <th style="width: 20%;">Description</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 10%;">Documents</th>
                    <th style="width: 17%;">Actions</th>
                </tr>
            </thead>
            <tbody id="proposalsContainer">
                <?php foreach ($proposals as $proposal): ?>
                    <tr data-status="<?php echo $proposal['status']; ?>" data-department="<?php echo htmlspecialchars($proposal['department']); ?>">
                        <!-- Proposal Title -->
                        <td>
                            <div class="proposal-title-cell">
                                <?php echo htmlspecialchars($proposal['proposal_title']); ?>
                            </div>
                            <div class="date-info">
                                <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($proposal['submitted_at'])); ?>
                            </div>
                        </td>

                        <!-- Faculty / Department -->
                        <td>
                            <div class="faculty-info">
                                <strong><?php echo htmlspecialchars($proposal['firstname'] . ' ' . $proposal['lastname']); ?></strong>
                            </div>
                            <div class="department-badge">
                                <?php echo htmlspecialchars($proposal['department']); ?>
                            </div>
                        </td>

                        <!-- Description -->
                        <td>
                            <?php if (!empty($proposal['description'])): ?>
                                <div class="proposal-description-text" title="<?php echo htmlspecialchars($proposal['description']); ?>">
                                    <?php echo htmlspecialchars($proposal['description']); ?>
                                </div>
                            <?php else: ?>
                                <span style="color: #adb5bd; font-style: italic;">No description</span>
                            <?php endif; ?>
                        </td>

                        <!-- Status -->
                        <td>
                            <span class="status-badge status-<?php echo $proposal['status']; ?>">
                                <?php echo ucfirst(getDisplayStatus($proposal['status'])); ?>
                            </span>
                            <?php if ($proposal['reviewed_at']): ?>
                                <div class="date-info">
                                    <i class="fas fa-check-circle"></i> <?php echo date('M j, Y', strtotime($proposal['reviewed_at'])); ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Documents -->
                        <td>
                            <div class="doc-count">
                                <i class="fas fa-file"></i> <?php echo $proposal['document_count']; ?> file<?php echo $proposal['document_count'] != 1 ? 's' : ''; ?>
                            </div>
                            <button class="btn-view-docs" onclick="viewProposalDocuments(<?php echo $proposal['id']; ?>)" style="margin-top: 8px;">
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>

                        <!-- Actions -->
                        <td>
                            <?php if ($proposal['status'] === 'pending'): ?>
                                <div class="proposal-actions">
                                    <form method="post" id="approveForm<?php echo $proposal['id']; ?>">
                                        <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="review_notes" id="approveNotesInput<?php echo $proposal['id']; ?>" value="">
                                        <button type="button" class="btn-approve" onclick="showConfirmModal('approve', <?php echo $proposal['id']; ?>)">
                                            <i class="fas fa-check"></i> Endorse
                                        </button>
                                    </form>

                                    <form method="post" id="rejectForm<?php echo $proposal['id']; ?>" style="margin-top: 8px;">
                                        <input type="hidden" name="proposal_id" value="<?php echo $proposal['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="review_notes" id="rejectNotesInput<?php echo $proposal['id']; ?>" value="">
                                        <button type="button" class="btn-reject" onclick="showConfirmModal('reject', <?php echo $proposal['id']; ?>)">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <?php if (!empty($proposal['review_notes'])): ?>
                                    <div class="notes-display">
                                        <strong style="color: #495057;">Review Notes:</strong><br>
                                        <?php echo htmlspecialchars($proposal['review_notes']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($proposal['status'] === 'used' && !empty($proposal['program_name'])): ?>
                                    <div style="margin-top: 8px; padding: 8px; background: #d4edda; border-radius: 4px; font-size: 0.85rem; color: #155724;">
                                        <strong>Used for:</strong><br>
                                        <?php echo htmlspecialchars($proposal['program_name']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($proposal['status'] !== 'pending' && empty($proposal['review_notes']) && $proposal['status'] !== 'used'): ?>
                                    <span style="color: #6c757d; font-style: italic; font-size: 0.85rem;">No review notes</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination Controls -->
        <div class="pagination-container" id="paginationContainer">
            <div class="pagination-info">
                <span id="paginationInfo">Showing 0-0 of 0 proposals</span>
            </div>
            <div class="pagination-controls">
                <label for="rowsPerPage" style="margin-right: 8px; color: #6c757d;">Rows per page:</label>
                <select id="rowsPerPage" onchange="changeRowsPerPage()">
                    <option value="5" selected>5</option>
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <button onclick="goToFirstPage()" id="firstPageBtn">
                    <i class="fas fa-angle-double-left"></i>
                </button>
                <button onclick="goToPreviousPage()" id="prevPageBtn">
                    <i class="fas fa-angle-left"></i> Previous
                </button>
                <span id="pageNumbers" style="display: flex; gap: 4px;"></span>
                <button onclick="goToNextPage()" id="nextPageBtn">
                    Next <i class="fas fa-angle-right"></i>
                </button>
                <button onclick="goToLastPage()" id="lastPageBtn">
                    <i class="fas fa-angle-double-right"></i>
                </button>
            </div>
        </div>

        <?php else: ?>
            <div style="text-align: center; padding: 50px; color: #6c757d; background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
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

    <!-- Confirmation Modal for Approve/Reject -->
    <div id="confirmationModal" class="modal" onclick="if(event.target===this)closeConfirmationModal()">
        <div class="modal-content" style="background:white; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.15); max-width:450px; width:90%; max-height:90vh; overflow-y:auto;">
            <div style="padding:20px; border-bottom:1px solid #e2e8f0;">
                <h2 style="margin:0; color:#1f2937; font-size:18px;" id="confirmationTitle">Confirm Action</h2>
                <span style="position:absolute; top:15px; right:15px; cursor:pointer; font-size:24px; color:#6b7280;" onclick="closeConfirmationModal()">&times;</span>
            </div>
            <div style="padding:20px;">
                <div style="text-align:center; margin-bottom:20px;">
                    <div id="confirmationIcon" style="font-size:48px; margin-bottom:16px;">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <p id="confirmationMessage" style="margin:0; color:#374151; line-height:1.5;">Are you sure?</p>
                </div>
                <div id="confirmationNotesContainer" style="margin-top:20px; display:none;">
                    <label for="confirmationNotes" style="display:block; margin-bottom:8px; color:#374151; font-weight:600;" id="confirmationNotesLabel">Notes:</label>
                    <textarea id="confirmationNotes" style="width:100%; padding:10px; border:1px solid #d1d5db; border-radius:6px; font-size:14px; resize:vertical; min-height:80px; font-family:inherit;" placeholder="Enter notes here..."></textarea>
                </div>
            </div>
            <div style="padding:20px; border-top:1px solid #e2e8f0; display:flex; gap:12px; justify-content:flex-end;">
                <button style="padding:8px 16px; border:1px solid #d1d5db; background:white; color:#374151; border-radius:6px; cursor:pointer;" onclick="closeConfirmationModal()">Cancel</button>
                <button id="confirmationButton" style="padding:8px 16px; border:none; color:white; border-radius:6px; cursor:pointer;" onclick="executeConfirmAction()">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Alert Modal -->
    <div id="alertModal" class="modal" onclick="if(event.target===this)closeAlertModal()">
        <div class="modal-content" style="background:white; border-radius:8px; box-shadow:0 4px 20px rgba(0,0,0,0.15); max-width:400px; width:90%; max-height:90vh; overflow-y:auto;">
            <div style="padding:20px; border-bottom:1px solid #e2e8f0;">
                <h2 style="margin:0; color:#1f2937; font-size:18px;" id="alertTitle">Notice</h2>
                <span style="position:absolute; top:15px; right:15px; cursor:pointer; font-size:24px; color:#6b7280;" onclick="closeAlertModal()">&times;</span>
            </div>
            <div style="padding:20px;">
                <div style="text-align:center;">
                    <div id="alertIcon" style="font-size:48px; margin-bottom:16px;">
                        <i class="fas fa-info-circle"></i>
                    </div>
                    <p id="alertMessage" style="margin:0; color:#374151; line-height:1.5;"></p>
                </div>
            </div>
            <div style="padding:20px; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end;">
                <button style="padding:8px 16px; border:none; background:#007bff; color:white; border-radius:6px; cursor:pointer;" onclick="closeAlertModal()">OK</button>
            </div>
        </div>
    </div>

    <script>
        // Pagination variables
        let currentPage = 1;
        let rowsPerPage = 5;
        let allRows = [];
        let filteredRows = [];

        // Status display mapping function
        function getDisplayStatus(status) {
            const statusMap = {
                'approved': 'endorsed',
                'pending': 'pending',
                'rejected': 'rejected'
            };
            return statusMap[status] || status;
        }

        // Initialize pagination on page load
        document.addEventListener('DOMContentLoaded', function() {
            allRows = Array.from(document.querySelectorAll('#proposalsContainer tr[data-status]'));
            filteredRows = [...allRows];
            updatePagination();
        });

        function filterProposals() {
            const statusFilter = document.getElementById('statusFilter').value;
            const departmentFilter = document.getElementById('departmentFilter').value;

            // Filter rows and store them
            filteredRows = allRows.filter(row => {
                const status = row.dataset.status;
                const department = row.dataset.department;
                const statusMatch = !statusFilter || status === statusFilter;
                const departmentMatch = !departmentFilter || department === departmentFilter;
                return statusMatch && departmentMatch;
            });

            // Reset to first page when filtering
            currentPage = 1;
            updatePagination();
        }

        function updatePagination() {
            const totalRows = filteredRows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            
            // Ensure current page is valid
            if (currentPage > totalPages) currentPage = totalPages;
            if (currentPage < 1) currentPage = 1;

            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;

            // Hide all rows first
            allRows.forEach(row => row.style.display = 'none');

            // Show only rows for current page
            filteredRows.slice(startIndex, endIndex).forEach(row => {
                row.style.display = '';
            });

            // Update pagination info
            const start = totalRows > 0 ? startIndex + 1 : 0;
            const end = Math.min(endIndex, totalRows);
            document.getElementById('paginationInfo').textContent = 
                `Showing ${start}-${end} of ${totalRows} proposal${totalRows !== 1 ? 's' : ''}`;

            // Update pagination controls
            updatePaginationControls(totalPages);
        }

        function updatePaginationControls(totalPages) {
            const firstPageBtn = document.getElementById('firstPageBtn');
            const prevPageBtn = document.getElementById('prevPageBtn');
            const nextPageBtn = document.getElementById('nextPageBtn');
            const lastPageBtn = document.getElementById('lastPageBtn');
            const pageNumbers = document.getElementById('pageNumbers');

            // Disable/enable navigation buttons
            firstPageBtn.disabled = currentPage === 1;
            prevPageBtn.disabled = currentPage === 1;
            nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
            lastPageBtn.disabled = currentPage === totalPages || totalPages === 0;

            // Generate page number buttons
            pageNumbers.innerHTML = '';
            
            if (totalPages <= 7) {
                // Show all pages if 7 or fewer
                for (let i = 1; i <= totalPages; i++) {
                    pageNumbers.appendChild(createPageButton(i));
                }
            } else {
                // Show smart pagination with ellipsis
                if (currentPage <= 3) {
                    for (let i = 1; i <= 5; i++) {
                        pageNumbers.appendChild(createPageButton(i));
                    }
                    pageNumbers.appendChild(createEllipsis());
                    pageNumbers.appendChild(createPageButton(totalPages));
                } else if (currentPage >= totalPages - 2) {
                    pageNumbers.appendChild(createPageButton(1));
                    pageNumbers.appendChild(createEllipsis());
                    for (let i = totalPages - 4; i <= totalPages; i++) {
                        pageNumbers.appendChild(createPageButton(i));
                    }
                } else {
                    pageNumbers.appendChild(createPageButton(1));
                    pageNumbers.appendChild(createEllipsis());
                    for (let i = currentPage - 1; i <= currentPage + 1; i++) {
                        pageNumbers.appendChild(createPageButton(i));
                    }
                    pageNumbers.appendChild(createEllipsis());
                    pageNumbers.appendChild(createPageButton(totalPages));
                }
            }
        }

        function createPageButton(pageNum) {
            const button = document.createElement('button');
            button.textContent = pageNum;
            button.className = pageNum === currentPage ? 'active' : '';
            button.onclick = () => goToPage(pageNum);
            return button;
        }

        function createEllipsis() {
            const span = document.createElement('span');
            span.textContent = '...';
            span.style.padding = '8px 4px';
            span.style.color = '#6c757d';
            return span;
        }

        function goToPage(page) {
            currentPage = page;
            updatePagination();
        }

        function goToFirstPage() {
            goToPage(1);
        }

        function goToPreviousPage() {
            if (currentPage > 1) {
                goToPage(currentPage - 1);
            }
        }

        function goToNextPage() {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            if (currentPage < totalPages) {
                goToPage(currentPage + 1);
            }
        }

        function goToLastPage() {
            const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
            goToPage(totalPages);
        }

        function changeRowsPerPage() {
            rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);
            currentPage = 1;
            updatePagination();
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
                        showAlertModal('No Documents', 'No documents found for this proposal.', 'info');
                    }
                })
                .catch(error => {
                    console.error('Error fetching documents:', error);
                    showAlertModal('Error', 'Error loading documents. Please try again.', 'error');
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

        // Confirmation Modal Functions
        let pendingAction = null;
        let pendingProposalId = null;

        function showConfirmModal(action, proposalId) {
            const modal = document.getElementById('confirmationModal');
            const title = document.getElementById('confirmationTitle');
            const message = document.getElementById('confirmationMessage');
            const icon = document.getElementById('confirmationIcon');
            const button = document.getElementById('confirmationButton');
            const notesContainer = document.getElementById('confirmationNotesContainer');
            const notesTextarea = document.getElementById('confirmationNotes');
            const notesLabel = document.getElementById('confirmationNotesLabel');
            
            pendingAction = action;
            pendingProposalId = proposalId;

            // Clear previous notes
            notesTextarea.value = '';

            if (action === 'approve') {
                title.textContent = 'Endorse Proposal';
                message.textContent = 'Are you sure you want to endorse this proposal?';
                icon.innerHTML = '<i class="fas fa-check-circle"></i>';
                icon.style.color = '#28a745';
                button.style.background = '#28a745';
                button.innerHTML = '<i class="fas fa-check"></i> Endorse';
                
                // Show notes as optional
                notesContainer.style.display = 'block';
                notesLabel.innerHTML = 'Notes <span style="color:#6b7280; font-weight:400;">(optional)</span>:';
                notesTextarea.placeholder = 'Add optional notes...';
                notesTextarea.required = false;
            } else if (action === 'reject') {
                title.textContent = 'Reject Proposal';
                message.textContent = 'Are you sure you want to reject this proposal? This action will notify the faculty member.';
                icon.innerHTML = '<i class="fas fa-times-circle"></i>';
                icon.style.color = '#dc3545';
                button.style.background = '#dc3545';
                button.innerHTML = '<i class="fas fa-times"></i> Reject';
                
                // Show notes as required
                notesContainer.style.display = 'block';
                notesLabel.innerHTML = 'Rejection Reason <span style="color:#dc3545;">*</span>:';
                notesTextarea.placeholder = 'Please provide a reason for rejection...';
                notesTextarea.required = true;
            }

            modal.classList.add('show');
        }

        function closeConfirmationModal() {
            const modal = document.getElementById('confirmationModal');
            modal.classList.remove('show');
            pendingAction = null;
            pendingProposalId = null;
        }

        function executeConfirmAction() {
            if (pendingAction && pendingProposalId) {
                const notesTextarea = document.getElementById('confirmationNotes');
                const notes = notesTextarea.value.trim();
                
                // Validate rejection notes
                if (pendingAction === 'reject' && !notes) {
                    showAlertModal('Required Field', 'Please provide a rejection reason before rejecting.', 'warning');
                    return;
                }
                
                // Set the notes value in the hidden input
                const notesInputId = pendingAction + 'NotesInput' + pendingProposalId;
                const notesInput = document.getElementById(notesInputId);
                if (notesInput) {
                    notesInput.value = notes;
                }
                
                // Submit the form
                const formId = pendingAction + 'Form' + pendingProposalId;
                const form = document.getElementById(formId);
                if (form) {
                    form.submit();
                }
            }
            closeConfirmationModal();
        }

        // Alert Modal Functions
        function showAlertModal(title, message, type = 'info') {
            const modal = document.getElementById('alertModal');
            const titleEl = document.getElementById('alertTitle');
            const messageEl = document.getElementById('alertMessage');
            const iconEl = document.getElementById('alertIcon');

            titleEl.textContent = title;
            messageEl.textContent = message;

            // Set icon and color based on type
            if (type === 'error') {
                iconEl.innerHTML = '<i class="fas fa-exclamation-circle"></i>';
                iconEl.style.color = '#dc3545';
            } else if (type === 'warning') {
                iconEl.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                iconEl.style.color = '#ffc107';
            } else if (type === 'success') {
                iconEl.innerHTML = '<i class="fas fa-check-circle"></i>';
                iconEl.style.color = '#28a745';
            } else {
                iconEl.innerHTML = '<i class="fas fa-info-circle"></i>';
                iconEl.style.color = '#17a2b8';
            }

            modal.classList.add('show');
        }

        function closeAlertModal() {
            const modal = document.getElementById('alertModal');
            modal.classList.remove('show');
        }

        // Keyboard support for modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeConfirmationModal();
                closeAlertModal();
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