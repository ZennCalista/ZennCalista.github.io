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
    .profile-cards-container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin: 40px auto;
      max-width: 1400px;
      padding: 0 20px;
    }

    @media (max-width: 968px) {
      .profile-cards-container {
        grid-template-columns: 1fr;
      }
    }

    .profile-card {
      background: linear-gradient(135deg, #eafbe7 0%, #f0fdf4 100%);
      padding: 35px;
      border-radius: 20px;
      box-shadow: 0 4px 20px rgba(36, 122, 55, 0.12);
      border: 2px solid #d1f4e8;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .profile-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 30px rgba(36, 122, 55, 0.18);
    }

    .profile-title {
      text-align: center;
      color: #114d2e;
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 3px solid #b7e4c7;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
    }

    .profile-title i {
      font-size: 28px;
      color: #247a37;
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

    /* Edit Profile Styles */
    .edit-btn {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .edit-btn:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .save-btn {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-right: 10px;
    }

    .save-btn:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .cancel-btn {
      background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .cancel-btn:hover {
      background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
    }

    .profile-input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #000;
      border-radius: 8px;
      font-size: 16px;
      color: #333;
      transition: all 0.3s ease;
      background: #ffffff;
    }

    .profile-input:focus {
      outline: none;
      border-color: #000;
      background: #ffffff;
    }

    .profile-input:disabled {
      background-color: #f9fafb;
      border: 1px solid #e5e7eb;
      padding: 8px 12px;
      color: #555;
    }

    select.profile-input {
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      padding-right: 36px;
    }

    select.profile-input:disabled {
      cursor: not-allowed;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23999' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    }

    .button-group {
      display: flex;
      justify-content: center;
      margin-top: 20px;
      gap: 10px;
    }



    .readonly-note {
      color: #9ca3af;
      font-size: 0.85rem;
      font-style: italic;
      margin-top: 4px;
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
            <!-- <a href="Profile.php" class="nav-item active"><i class="fas fa-user"></i> Profile</a> -->
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

      <div class="profile-cards-container">
        <section class="profile-card">
          <div class="profile-title">
            <i class="fas fa-user-circle"></i>
            <span>Student Profile</span>
          </div>

        <div class="profile-info">
          <div class="label">Student Number:</div>
          <div class="value">
            <input type="text" class="profile-input" id="profile-student-id" disabled>
          </div>

          <div class="label">First Name:</div>
          <div class="value">
            <input type="text" class="profile-input" id="profile-firstname" disabled>
          </div>

          <div class="label">Last Name:</div>
          <div class="value">
            <input type="text" class="profile-input" id="profile-lastname" disabled>
          </div>

          <div class="label">Program:</div>
          <div class="value">
            <select class="profile-input" id="profile-course" disabled>
              <option value="Bachelor Of Arts In Journalism">Bachelor Of Arts In Journalism</option>
              <option value="Bachelor Of Early Childhood Education">Bachelor Of Early Childhood Education</option>
              <option value="Bachelor Of Elementary Education">Bachelor Of Elementary Education</option>
              <option value="Bachelor Of Science In Business Administration">Bachelor Of Science In Business Administration</option>
              <option value="Bachelor Of Science In Computer Science">Bachelor Of Science In Computer Science</option>
              <option value="Bachelor Of Science In Entrepreneurship">Bachelor Of Science In Entrepreneurship</option>
              <option value="Bachelor Of Science In Hospitality Management">Bachelor Of Science In Hospitality Management</option>
              <option value="Bachelor Of Science In Information Technology">Bachelor Of Science In Information Technology</option>
              <option value="Bachelor Of Science In Office Administration">Bachelor Of Science In Office Administration</option>
              <option value="Bachelor Of Science In Psychology">Bachelor Of Science In Psychology</option>
              <option value="Bachelor Of Secondary Education">Bachelor Of Secondary Education</option>
            </select>
          </div>

          <div class="label">Email:</div>
          <div class="value">
            <input type="email" class="profile-input" id="profile-email" disabled readonly>
          </div>

          <div class="label">Contact:</div>
          <div class="value">
            <input type="text" class="profile-input" id="profile-phone" disabled>
          </div>

          <div class="label">Emergency Contact:</div>
          <div class="value">
            <input type="text" class="profile-input" id="profile-emergency" disabled>
          </div>
        </div>

        <div class="button-group" id="edit-button-group">
          <button class="edit-btn" id="edit-btn" onclick="toggleEditMode()">
            <i class="fas fa-edit"></i> Edit Profile
          </button>
        </div>

        <div class="button-group" id="save-buttons" style="display: none;">
          <button class="save-btn" onclick="saveProfile()">
            <i class="fas fa-save"></i> Save Changes
          </button>
          <button class="cancel-btn" onclick="cancelEdit()">
            <i class="fas fa-times"></i> Cancel
          </button>
        </div>
        </section>

        <!-- Security Settings Card -->
        <section class="profile-card">
          <div class="profile-title">
            <i class="fas fa-shield-alt"></i>
            <span>Security Settings</span>
          </div>
          <div style="padding: 25px 0;">
            <div style="background: white; padding: 25px; border-radius: 15px; border: 2px solid #b7e4c7; box-shadow: 0 2px 10px rgba(36, 122, 55, 0.08);">
              <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);">
                  <i class="fas fa-key" style="color: white; font-size: 22px;"></i>
                </div>
                <div style="flex: 1;">
                  <h3 style="margin: 0 0 5px 0; color: #114d2e; font-size: 18px; font-weight: 600;">Password Management</h3>
                  <p style="margin: 0; color: #6b7280; font-size: 0.9rem; line-height: 1.5;">
                    Keep your account secure by regularly updating your password
                  </p>
                </div>
              </div>
              <button class="change-password-btn" onclick="PasswordChangeModal.open()" title="Change Password" style="width: 100%; justify-content: center; padding: 12px 20px; font-size: 1rem;">
                <i class="fas fa-key"></i> Change Password
              </button>
            </div>
            
            <div style="margin-top: 25px; padding: 20px; background: white; border-radius: 15px; border: 2px solid #b7e4c7; box-shadow: 0 2px 10px rgba(36, 122, 55, 0.08);">
              <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                  <i class="fas fa-info-circle" style="color: white; font-size: 22px;"></i>
                </div>
                <div style="flex: 1;">
                  <h3 style="margin: 0 0 8px 0; color: #114d2e; font-size: 16px; font-weight: 600;">Security Tips</h3>
                  <ul style="margin: 0; padding-left: 20px; color: #6b7280; font-size: 0.85rem; line-height: 1.8;">
                    <li>Use a strong, unique password</li>
                    <li>Change your password regularly</li>
                    <li>Never share your password with anyone</li>
                    <li>Enable two-factor authentication when available</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>

  <script>
let originalProfileData = {};
let isEditMode = false;

document.addEventListener('DOMContentLoaded', function() {
  loadProfile();
});

function loadProfile() {
  fetch('get_profile.php')
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        const p = data.profile;
        originalProfileData = { ...p };
        
        // Use firstname and lastname directly from backend
        document.getElementById('profile-firstname').value = p.firstname || '-';
        document.getElementById('profile-lastname').value = p.lastname || '-';
        document.getElementById('profile-student-id').value = p.student_id || '-';
        document.getElementById('profile-course').value = p.course || '-';
        document.getElementById('profile-email').value = p.contact_email || '-';
        document.getElementById('profile-phone').value = p.contact_no || '-';
        document.getElementById('profile-emergency').value = p.emergency_contact || '-';
      } else {
        showNotification('Failed to load profile', 'error');
      }
    })
    .catch(() => {
      showNotification('Error loading profile', 'error');
    });
}

function toggleEditMode() {
  isEditMode = true;
  
  // Enable editable fields
  document.getElementById('profile-firstname').disabled = false;
  document.getElementById('profile-lastname').disabled = false;
  document.getElementById('profile-student-id').disabled = false;
  document.getElementById('profile-course').disabled = false;
  document.getElementById('profile-phone').disabled = false;
  document.getElementById('profile-emergency').disabled = false;
  
  // Show save/cancel buttons, hide edit button
  document.getElementById('save-buttons').style.display = 'flex';
  document.getElementById('edit-button-group').style.display = 'none';
}

function cancelEdit() {
  isEditMode = false;
  
  // Restore original values
  document.getElementById('profile-firstname').value = originalProfileData.firstname || '-';
  document.getElementById('profile-lastname').value = originalProfileData.lastname || '-';
  document.getElementById('profile-student-id').value = originalProfileData.student_id || '-';
  document.getElementById('profile-course').value = originalProfileData.course || '-';
  document.getElementById('profile-phone').value = originalProfileData.contact_no || '-';
  document.getElementById('profile-emergency').value = originalProfileData.emergency_contact || '-';
  
  // Disable fields
  document.getElementById('profile-firstname').disabled = true;
  document.getElementById('profile-lastname').disabled = true;
  document.getElementById('profile-student-id').disabled = true;
  document.getElementById('profile-course').disabled = true;
  document.getElementById('profile-phone').disabled = true;
  document.getElementById('profile-emergency').disabled = true;
  
  // Hide save/cancel buttons, show edit button
  document.getElementById('save-buttons').style.display = 'none';
  document.getElementById('edit-button-group').style.display = 'flex';
}

function saveProfile() {
  const firstName = document.getElementById('profile-firstname').value.trim();
  const lastName = document.getElementById('profile-lastname').value.trim();
  const studentId = document.getElementById('profile-student-id').value.trim();
  const course = document.getElementById('profile-course').value.trim();
  const contactNo = document.getElementById('profile-phone').value.trim();
  const emergencyContact = document.getElementById('profile-emergency').value.trim();
  
  // Validation
  if (!firstName || !lastName) {
    showNotification('First name and last name are required', 'error');
    return;
  }
  
  if (!contactNo || !emergencyContact) {
    showNotification('Contact number and emergency contact are required', 'error');
    return;
  }
  
  // Validate phone numbers (basic validation)
  const phoneRegex = /^[\d\s\-\+\(\)]+$/;
  if (!phoneRegex.test(contactNo)) {
    showNotification('Please enter a valid contact number', 'error');
    return;
  }
  if (!phoneRegex.test(emergencyContact)) {
    showNotification('Please enter a valid emergency contact', 'error');
    return;
  }
  
  const profileData = {
    firstname: firstName,
    lastname: lastName,
    student_id: studentId,
    course: course,
    contact_no: contactNo,
    emergency_contact: emergencyContact
  };
  
  // Show loading state
  const saveBtn = document.querySelector('.save-btn');
  const originalText = saveBtn.innerHTML;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  saveBtn.disabled = true;
  
  fetch('update_profile.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(profileData)
  })
  .then(res => res.json())
  .then(data => {
    saveBtn.innerHTML = originalText;
    saveBtn.disabled = false;
    
    if (data.status === 'success') {
      showNotification(data.message || 'Profile updated successfully', 'success');
      
      // Update original data
      originalProfileData.firstname = firstName;
      originalProfileData.lastname = lastName;
      originalProfileData.full_name = firstName + ' ' + lastName;
      originalProfileData.student_id = studentId;
      originalProfileData.course = course;
      originalProfileData.contact_no = contactNo;
      originalProfileData.emergency_contact = emergencyContact;
      
      // Exit edit mode
      isEditMode = false;
      document.getElementById('profile-firstname').disabled = true;
      document.getElementById('profile-lastname').disabled = true;
      document.getElementById('profile-student-id').disabled = true;
      document.getElementById('profile-course').disabled = true;
      document.getElementById('profile-phone').disabled = true;
      document.getElementById('profile-emergency').disabled = true;
      document.getElementById('save-buttons').style.display = 'none';
      document.getElementById('edit-button-group').style.display = 'flex';
    } else {
      showNotification(data.message || 'Failed to update profile', 'error');
    }
  })
  .catch(error => {
    saveBtn.innerHTML = originalText;
    saveBtn.disabled = false;
    console.error('Error:', error);
    showNotification('Error updating profile. Please try again.', 'error');
  });
}

function showNotification(message, type) {
  // Create notification element
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 15px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    z-index: 10000;
    animation: slideIn 0.3s ease-out;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    display: flex;
    align-items: center;
    gap: 10px;
  `;
  
  if (type === 'success') {
    notification.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
    notification.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
  } else {
    notification.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
    notification.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
  }
  
  document.body.appendChild(notification);
  
  // Remove after 3 seconds
  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
  </script>

  <!-- Include Password Change Modal -->
  <?php include '../portal/shared/password_change_modal.php'; ?>
  <script src="../portal/shared/password_change_modal.js"></script>
</body>
</html>
