<?php
// Test page with simulated session for testing password change modal
session_start();

// Simulate logged-in faculty user
$_SESSION['user_id'] = 1; // Change this to a real user ID
$_SESSION['email'] = 'faculty@test.com'; // Change this to a real email
$_SESSION['role'] = 'faculty';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Change Modal Test</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .test-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #1f2937;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 40px;
            font-size: 16px;
        }

        .session-info {
            background: #f3f4f6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .session-info h3 {
            color: #1f2937;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .session-info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .session-info-item:last-child {
            border-bottom: none;
        }

        .session-info-label {
            font-weight: 600;
            color: #6b7280;
        }

        .session-info-value {
            color: #10b981;
            font-weight: 500;
        }

        .test-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .test-button:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.4);
        }

        .test-button i {
            font-size: 24px;
        }

        .instructions {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .instructions h3 {
            color: #92400e;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instructions ol {
            margin-left: 20px;
            color: #78350f;
            line-height: 1.8;
        }

        .instructions li {
            margin-bottom: 8px;
        }

        .checklist {
            background: #f9fafb;
            border-radius: 12px;
            padding: 20px;
        }

        .checklist h3 {
            color: #1f2937;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: white;
            border-radius: 8px;
            margin-bottom: 10px;
            border: 2px solid #e5e7eb;
            transition: all 0.2s;
        }

        .checklist-item:hover {
            border-color: #10b981;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
        }

        .checklist-item i {
            color: #10b981;
            font-size: 18px;
        }

        .links {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .link-btn {
            flex: 1;
            padding: 12px;
            background: #f3f4f6;
            color: #374151;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .link-btn:hover {
            background: #e5e7eb;
            transform: translateY(-2px);
        }

        .link-btn.primary {
            background: #667eea;
            color: white;
        }

        .link-btn.primary:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1><i class="fas fa-vial"></i> Password Change Test</h1>
        <p class="subtitle">Interactive testing environment for the password change feature</p>

        <div class="session-info">
            <h3><i class="fas fa-user-circle"></i> Session Information</h3>
            <div class="session-info-item">
                <span class="session-info-label">User ID:</span>
                <span class="session-info-value"><?php echo $_SESSION['user_id']; ?></span>
            </div>
            <div class="session-info-item">
                <span class="session-info-label">Email:</span>
                <span class="session-info-value"><?php echo $_SESSION['email']; ?></span>
            </div>
            <div class="session-info-item">
                <span class="session-info-label">Role:</span>
                <span class="session-info-value"><?php echo $_SESSION['role']; ?></span>
            </div>
        </div>

        <div class="instructions">
            <h3><i class="fas fa-clipboard-list"></i> Testing Instructions</h3>
            <ol>
                <li>Click the "Open Password Change Modal" button below</li>
                <li>Enter your current password</li>
                <li>Check your email for the 6-digit OTP code</li>
                <li>Enter the OTP code within 10 minutes</li>
                <li>Create a new password meeting all requirements</li>
                <li>Confirm your new password</li>
                <li>Submit and verify password change</li>
            </ol>
        </div>

        <button class="test-button" onclick="PasswordChangeModal.open()">
            <i class="fas fa-key"></i>
            Open Password Change Modal
        </button>

        <div class="checklist">
            <h3><i class="fas fa-tasks"></i> Test Checklist</h3>
            <div class="checklist-item">
                <i class="fas fa-check-circle"></i>
                <span>Modal opens with step 1 visible</span>
            </div>
            <div class="checklist-item">
                <i class="fas fa-check-circle"></i>
                <span>Current password validation works</span>
            </div>
            <div class="checklist-item">
                <i class="fas fa-check-circle"></i>
                <span>OTP sent to email successfully</span>
            </div>
            <div class="checklist-item">
                <i class="fas fa-check-circle"></i>
                <span>OTP countdown timer displays correctly</span>
            </div>
            <div class="checklist-item">
                <i class="fas fa-check-circle"></i>
                <span>Password requirements checklist updates</span>
            </div>
            <div class="checklist-item">
                <i class="fas fa-check-circle"></i>
                <span>Password match indicator works</span>
            </div>
            <div class="checklist-item">
                <i class="fas fa-check-circle"></i>
                <span>Password change completes successfully</span>
            </div>
        </div>

        <div class="links">
            <a href="FACULTY/profile.php" class="link-btn primary">
                <i class="fas fa-user"></i>
                Faculty Profile
            </a>
            <a href="test_password_change.php" class="link-btn">
                <i class="fas fa-info-circle"></i>
                Component Check
            </a>
            <a href="PASSWORD_CHANGE_FEATURE.md" class="link-btn">
                <i class="fas fa-book"></i>
                Documentation
            </a>
        </div>
    </div>

    <!-- Include Password Change Modal -->
    <?php include 'portal/shared/password_change_modal.php'; ?>
    <script src="portal/shared/password_change_modal.js"></script>
</body>
</html>
