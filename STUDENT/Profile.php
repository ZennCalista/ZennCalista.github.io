<?php
session_start();
require 'db.php'; // your DB connection file

// Assuming you store user id in session after login
$user_id = $_SESSION['user_id'] ?? null;
$user = null;

if ($user_id) {
    $stmt = $conn->prepare("SELECT firstname, lastname FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($firstname, $lastname);
    if ($stmt->fetch()) {
        $user = ['firstname' => $firstname, 'lastname' => $lastname];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Profile - eTRACKER</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="Profile.css" />
  <style>
    .profile-card {
      background: #fff8cc;
      padding: 30px;
      border-radius: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      max-width: 600px;
      margin: 40px auto;
    }

    .profile-title {
      text-align: center;
      color: #2e6e1e;
      font-size: 24px;
      margin-bottom: 20px;
    }

    .profile-info {
      display: grid;
      grid-template-columns: 1fr 2fr;
      gap: 10px 20px;
      font-size: 18px;
    }

    .profile-info div {
      padding: 6px 0;
    }

    .label {
      font-weight: bold;
      color: #333;
    }

    .value {
      color: #555;
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
  <div class="container">
        <aside class="sidebar">
        <div class="logo">
            <img src="logo.png" alt="eTRACKER Logo" />
            <span>eTRACKER</span>
        </div>
        <nav class="nav">
            <a href="index.php" class="nav-item "><i class="fas fa-home"></i> Dashboard</a>
            <a href="Programs.php" class="nav-item"><i class="fas fa-list-alt"></i> Programs</a>
            <a href="Attendance.php" class="nav-item"><i class="fas fa-calendar-check"></i> Attendance</a>
            <a href="Feedback.php" class="nav-item"><i class="fas fa-comment-dots"></i> Feedback</a>
            <a href="Reports.php" class="nav-item"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="Profile.php" class="nav-item active"><i class="fas fa-user"></i> Profile</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span>
                    <?php
                        if ($user) {
                            echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']);
                        } else {
                            echo "Guest";
                        }
                    ?>
                </span>
            </div>
            <a href="../register/logout.php" class="btn logout-btn">
                <i class="fas fa-sign-out-alt"></i> Log Out
            </a>
        </div>
    </aside>

    <main class="main-content">
      <header class="header">
        <h1>CVSU IMUS - EXTENSION SERVICES</h1>
      </header>

      <section class="profile-card">
        <div class="profile-title">Student Profile</div>
        <div class="profile-info">
          <div class="label">Name:</div>
          <div class="value" id="profile-name">Loading...</div>
          
          <div class="label">Student Number:</div>
          <div class="value" id="profile-student-id">Loading...</div>

          <div class="label">Program:</div>
          <div class="value" id="profile-course">Loading...</div>

          <div class="label">Email:</div>
          <div class="value" id="profile-email">Loading...</div>

          <div class="label">Contact:</div>
          <div class="value" id="profile-phone">Loading...</div>

          <div class="label">Emergency Contact:</div>
          <div class="value" id="profile-emergency">Loading...</div>
        </div>
      </section>

      <!-- Security Settings Card -->
      <section class="profile-card" style="margin-top: 20px;">
        <div class="profile-title">
          <i class="fas fa-shield-alt"></i> Security Settings
        </div>
        <div style="padding: 20px 0;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <span style="font-weight: bold; color: #333;">Password</span>
            <button class="change-password-btn" onclick="PasswordChangeModal.open()" title="Change Password">
              <i class="fas fa-key"></i> Change Password
            </button>
          </div>
          <p style="color: #6b7280; font-size: 0.9rem; margin-top: 8px;">
            Keep your account secure by regularly updating your password
          </p>
        </div>
      </section>
    </main>
  </div>

  <script>
document.addEventListener('DOMContentLoaded', function() {
  fetch('get_profile.php')
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        const p = data.profile;
        document.getElementById('profile-name').textContent = p.full_name || '-';
        document.getElementById('profile-student-id').textContent = p.student_id || '-';
        document.getElementById('profile-course').textContent = p.course || '-';
        document.getElementById('profile-email').textContent = p.contact_email || '-';
        document.getElementById('profile-phone').textContent = p.contact_no || '-';
        document.getElementById('profile-emergency').textContent = p.emergency_contact || '-';
      } else {
        document.querySelectorAll('.profile-info .value').forEach(el => el.textContent = 'Not found');
      }
    })
    .catch(() => {
      document.querySelectorAll('.profile-info .value').forEach(el => el.textContent = 'Error');
    });
});
  </script>

  <!-- Include Password Change Modal -->
  <?php include '../portal/shared/password_change_modal.php'; ?>
  <script src="../portal/shared/password_change_modal.js"></script>
</body>
</html>
