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
  <title>eTRACKER Reports</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <link rel="stylesheet" href="Reports.css" />
  <style>
    .summary-cards {
      display: flex;
      flex-wrap: wrap;
      gap: 32px;
      justify-content: center;
      margin-top: 32px;
    }
    .report-type-card {
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08), 0 1px 4px rgba(0,0,0,0.04);
      border: 2px solid #e8f5e9;
      padding: 32px 36px;
      min-width: 240px;
      max-width: 320px;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: transform 0.25s cubic-bezier(.4,2,.6,1), box-shadow 0.25s, border-color 0.25s;
      opacity: 0;
      transform: translateY(40px) scale(0.97);
      animation: cardIn 0.7s forwards;
    }
    .report-type-card:nth-child(2) { animation-delay: 0.1s; }
    .report-type-card:nth-child(3) { animation-delay: 0.2s; }
    .report-type-card:nth-child(4) { animation-delay: 0.3s; }
    @keyframes cardIn {
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    .report-type-card:hover {
      box-shadow: 0 8px 24px rgba(0,0,0,0.12), 0 4px 12px rgba(0,0,0,0.08);
      border-color: #66bb6a;
      transform: translateY(-8px) scale(1.03);
    }
    .report-type-card img {
      width: 56px;
      height: 56px;
      margin-bottom: 18px;
      filter: drop-shadow(0 2px 4px rgba(46,110,30,0.08));
    }
    .report-type-card h3 {
      color: #114d2e;
      font-size: 1.35rem;
      margin-bottom: 12px;
      letter-spacing: 0.5px;
      font-weight: 700;
    }
    .report-type-card p {
      font-size: 1.08rem;
      color: #555;
      margin: 4px 0;
      font-weight: 400;
      letter-spacing: 0.1px;
    }
    .report-type-card p strong {
      color: #333;
      font-weight: 600;
    }
    @media (max-width: 900px) {
      .summary-cards { flex-direction: column; align-items: center; }
      .report-type-card { width: 90vw; max-width: 400px; }
    }
    
    .reports-container h2 {
      color: #114d2e;
      font-size: 1.8rem;
      margin-bottom: 10px;
      text-align: center;
      letter-spacing: 0.5px;
      font-weight: 700;
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

            <a href="Reports.php" class="nav-item active"><i class="fas fa-chart-bar"></i> Reports</a>
                                                <a href="certificates.php" class="nav-item"><i class="fas fa-certificate"></i> Certificates</a>

            <!-- <a href="Profile.php" class="nav-item"><i class="fas fa-user"></i> Profile</a> -->
        </nav>
        <div class="sidebar-bottom">
            <a href="Profile.php" class="user-info" style="text-decoration: none; color: inherit; cursor: pointer;">
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
            </a>
            <a href="../register/logout.php" class="btn logout-btn">
                <i class="fas fa-sign-out-alt"></i> Log Out
            </a>
        </div>
    </aside>

    <main class="main-content">
      <header class="header">
        <h1>CVSU IMUS - EXTENSION SERVICES</h1>
      </header>

      <section class="reports-container">
        <h2>Reports & Analytics</h2>
        <div class="summary-cards" id="summaryCards">
          <!-- Cards will be filled by JS -->
        </div>
      </section>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Promise.all([
        fetch('get_attendance_report.php').then(res => res.json()),
        fetch('get_participation_report.php').then(res => res.json()),
        fetch('get_feedback_report.php').then(res => res.json()),
        fetch('get_certificates.php').then(res => res.json())
      ]).then(([attendance, participation, feedback, certificates]) => {
        document.getElementById('summaryCards').innerHTML = `
          <div class="report-type-card">
            <!-- Attendance Icon - Calendar Check (FontAwesome) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="#66bb6a" viewBox="0 0 448 512" width="56" height="56">
              <path d="M128 0c13.3 0 24 10.7 24 24V64H296V24c0-13.3 10.7-24 24-24s24 10.7 24 24V64h40c35.3 0 64 28.7 64 64v16 48V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V192 144 128C0 92.7 28.7 64 64 64h40V24c0-13.3 10.7-24 24-24zM400 192H48V448c0 8.8 7.2 16 16 16H384c8.8 0 16-7.2 16-16V192zM329 297L217 409c-9.4 9.4-24.6 9.4-33.9 0l-64-64c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0l47 47 95-95c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9z"/>
            </svg>
            <h3>Attendance</h3>
            <p><strong>Sessions Attended:</strong> ${attendance.attended ?? 0}</p>
            <p><strong>Total Sessions:</strong> ${attendance.total_sessions ?? 0}</p>
            <p><strong>Attendance Rate:</strong> ${attendance.attendance_rate ?? 0}%</p>
          </div>
          <div class="report-type-card">
            <!-- Participation Icon - Users Group -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="#66bb6a" viewBox="0 0 24 24" width="56" height="56">
              <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5s-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
            </svg>
            <h3>Participation</h3>
            <p><strong>Enrolled Programs:</strong> ${participation.total ?? 0}</p>
            <p><strong>Active:</strong> ${participation.active ?? 0}</p>
            <p><strong>Completed:</strong> ${participation.completed ?? 0}</p>
            <p><strong>Pending:</strong> ${participation.pending ?? 0}</p>
          </div>
          <div class="report-type-card">
            <!-- Feedback Icon - Chat Bubble -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="#66bb6a" viewBox="0 0 24 24" width="56" height="56">
              <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12zM7 9h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2z"/>
            </svg>
            <h3>Feedback</h3>
            <p><strong>Feedback Submitted:</strong> ${feedback.total ?? 0}</p>
            <p><strong>Avg. Satisfaction:</strong> ${feedback.avg_satisfaction ?? 0} / 5</p>
          </div>
          <div class="report-type-card">
            <!-- Certificate Icon (FontAwesome) -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="#66bb6a" viewBox="0 0 512 512" width="56" height="56">
              <path d="M211 7.3C205 1 196-1.4 187.6 .8s-14.9 8.9-17.1 17.3L154.7 80.6l-62-17.5c-8.4-2.4-17.4 0-23.5 6.1s-8.5 15.1-6.1 23.5l17.5 62L18.1 170.6c-8.4 2.1-15 8.7-17.3 17.1S1 205 7.3 211l46.2 45L7.3 301C1 307-1.4 316 .8 324.4s8.9 14.9 17.3 17.1l62.5 15.8-17.5 62c-2.4 8.4 0 17.4 6.1 23.5s15.1 8.5 23.5 6.1l62-17.5 15.8 62.5c2.1 8.4 8.7 15 17.1 17.3s17.3-.2 23.4-6.4l45-46.2 45 46.2c6.1 6.2 15 8.7 23.4 6.4s14.9-8.9 17.1-17.3l15.8-62.5 62 17.5c8.4 2.4 17.4 0 23.5-6.1s8.5-15.1 6.1-23.5l-17.5-62 62.5-15.8c8.4-2.1 15-8.7 17.3-17.1s-.2-17.4-6.4-23.4l-46.2-45 46.2-45c6.2-6.1 8.7-15 6.4-23.4s-8.9-14.9-17.3-17.1l-62.5-15.8 17.5-62c2.4-8.4 0-17.4-6.1-23.5s-15.1-8.5-23.5-6.1l-62 17.5L341.4 18.1c-2.1-8.4-8.7-15-17.1-17.3S307 1 301 7.3L256 53.5 211 7.3z"/>
            </svg>
            <h3>Certificates</h3>
            <p><strong>Certificates Earned:</strong> ${certificates.total ?? 0}</p>
          </div>
        `;
      });
    });
  </script>
</body>
</html>
