<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

include 'db.php';

// Hardcoded faculty emails
$facultyEmails = [
    'ic.mheladelnicole.defensor@cvsu.edu.ph',
    'faculty2@cvsu.edu.ph',
    // Add more as needed
];

// Get the raw POST data
// Accept JSON body or traditional form POST
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// If body isn't valid JSON, fall back to $_POST (form submit)
if (json_last_error() !== JSON_ERROR_NONE || $data === null) {
    if (!empty($_POST)) {
        $data = $_POST;
        // normalize full_name -> firstname/lastname
        if (!empty($data['full_name']) && empty($data['firstname']) && empty($data['lastname'])) {
            $parts = preg_split('/\s+/', trim($data['full_name']));
            $data['firstname'] = array_shift($parts);
            $data['lastname'] = count($parts) ? implode(' ', $parts) : '';
        }
    } else {
        error_log('register.php: invalid JSON input: ' . json_last_error_msg() . ' raw=' . substr($raw,0,500));
        echo json_encode(["status" => "error", "message" => "Invalid JSON input"]);
        $conn->close();
        exit;
    }
}

// If streamlined registration payload (with user_info from master tables)
if (!empty($data['email']) && !empty($data['password']) && !empty($data['user_info']) && !empty($data['role'])) {
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $user_info = $data['user_info'];
    $role = $data['role'];

    // Extract user info based on role
    if ($role === 'student') {
        $firstname = $user_info['firstname'];
        $lastname = $user_info['lastname'];
        $mi = $user_info['middle_initial'] ?? '';
        $student_id = $user_info['student_id'];
        $course = $user_info['course'];
    } else if ($role === 'faculty') {
        $firstname = $user_info['firstname'];
        $lastname = $user_info['lastname'];
        $mi = $user_info['middle_initial'] ?? '';
        $faculty_id = $user_info['faculty_id'];
        $department = $user_info['department'];
        $position = $user_info['position'];
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid role specified"]);
        $conn->close();
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        $conn->close();
        exit;
    }

    // Check if email already exists
    $email_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $email_check->bind_param("s", $email);
    $email_check->execute();
    $email_result = $email_check->get_result();

    if ($email_result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already registered"]);
        $email_check->close();
        $conn->close();
        exit;
    }
    $email_check->close();

    // Insert into the users table with verification_status = unverified
    $sql = "INSERT INTO users (firstname, lastname, middle_initial, email, password, role, verification_status) VALUES (?, ?, ?, ?, ?, ?, 'unverified')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('register.php prepare failed: ' . $conn->error);
        echo json_encode(["status" => "error", "message" => "Server error: could not prepare statement", "detail" => $conn->error]);
        $conn->close();
        exit;
    }
    $stmt->bind_param("ssssss", $firstname, $lastname, $mi, $email, $password, $role);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        // Insert role-specific data
        if ($role === 'student') {
            $student_sql = "INSERT INTO students (user_id, student_id, course) VALUES (?, ?, ?)";
            $student_stmt = $conn->prepare($student_sql);
            $student_stmt->bind_param("iss", $user_id, $student_id, $course);
            if (!$student_stmt->execute()) {
                error_log('register.php student insert failed: ' . $student_stmt->error);
                echo json_encode(["status" => "error", "message" => "Failed to create student record", "detail" => $student_stmt->error]);
                $student_stmt->close();
                $conn->close();
                exit;
            }
            $student_stmt->close();
        } else if ($role === 'faculty') {
            $faculty_sql = "INSERT INTO faculty (user_id, faculty_id, department, position) VALUES (?, ?, ?, ?)";
            $faculty_stmt = $conn->prepare($faculty_sql);
            $faculty_stmt->bind_param("isss", $user_id, $faculty_id, $department, $position);
            if (!$faculty_stmt->execute()) {
                error_log('register.php faculty insert failed: ' . $faculty_stmt->error);
                echo json_encode(["status" => "error", "message" => "Failed to create faculty record", "detail" => $faculty_stmt->error]);
                $faculty_stmt->close();
                $conn->close();
                exit;
            }
            $faculty_stmt->close();
        }

        echo json_encode([
            "status" => "success",
            "message" => "Registration initiated successfully.",
            "user_id" => $user_id,
            "role" => $role
        ]);
    } else {
        error_log('register.php user insert failed: ' . $stmt->error);
        echo json_encode(["status" => "error", "message" => "Failed to register user", "detail" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// If initial registration payload (legacy support)
if (!empty($data['firstname']) && !empty($data['lastname']) && !empty($data['email']) && !empty($data['password'])) {
    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    $mi = $data['mi'] ?? ''; // Middle initial, optional field
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT); // Hash the password
    // Determine role based on email
    $role = in_array($email, $facultyEmails) ? 'faculty' : 'student';

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format"]);
        $conn->close();
        exit;
    }

    // Validate email domain - only @cvsu.edu.ph emails allowed for students and faculty
    if (($role === 'student' || $role === 'faculty') && !preg_match('/@cvsu\.edu\.ph$/', $email)) {
        echo json_encode(["status" => "error", "message" => "Students and faculty must use @cvsu.edu.ph email addresses"]);
        $conn->close();
        exit;
    }

    // Check if email already exists
    $email_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $email_check->bind_param("s", $email);
    $email_check->execute();
    $email_result = $email_check->get_result();

    if ($email_result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already registered"]);
        $email_check->close();
        $conn->close();
        exit;
    }
    $email_check->close();

    // Insert into the users table with verification_status = unverified
    $sql = "INSERT INTO users (firstname, lastname, middle_initial, email, password, role, verification_status) VALUES (?, ?, ?, ?, ?, ?, 'unverified')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('register.php prepare failed: ' . $conn->error);
        echo json_encode(["status" => "error", "message" => "Server error: could not prepare statement", "detail" => $conn->error]);
        $conn->close();
        exit;
    }
    $stmt->bind_param("ssssss", $firstname, $lastname, $mi, $email, $password, $role);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        echo json_encode([
            "status" => "success",
            "message" => "Registration initiated successfully.",
            "user_id" => $user_id,
            "role" => $role
        ]);
    } else {
        error_log('register.php user insert failed: ' . $stmt->error);
        echo json_encode(["status" => "error", "message" => "Failed to register user", "detail" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

// Handle role-specific form submission (Student or Faculty)
if (!empty($data['user_id']) && !empty($data['role'])) {
    $user_id = $data['user_id'];
    $role = $data['role'];

    // Check if user exists and is verified
    $user_check = $conn->prepare("SELECT id, verification_status FROM users WHERE id = ?");
    $user_check->bind_param("i", $user_id);
    $user_check->execute();
    $user_result = $user_check->get_result();

    if ($user_result->num_rows === 0) {
        echo json_encode(["status" => "error", "message" => "User not found"]);
        $user_check->close();
        $conn->close();
        exit;
    }

    $user_row = $user_result->fetch_assoc();
    if ($user_row['verification_status'] !== 'verified') {
        echo json_encode(["status" => "error", "message" => "Email not verified. Please complete OTP verification first."]);
        $user_check->close();
        $conn->close();
        exit;
    }
    $user_check->close();

    if ($role === 'student' || $role === 'non_acad') {
        if ($role === 'student') {
            if (empty($data['contact_no']) || empty($data['emergency_contact']) || empty($data['student_id'])) {
                echo json_encode(["status" => "error", "message" => "Missing student details"]);
                $conn->close();
                exit;
            }
            $student_id = $data['student_id'];
            // Check uniqueness of student_id
            $sid_check = $conn->prepare("SELECT id FROM students WHERE student_id = ?");
            $sid_check->bind_param("s", $student_id);
            $sid_check->execute();
            $sid_result = $sid_check->get_result();
            if ($sid_result->num_rows > 0) {
                echo json_encode(["status" => "error", "message" => "Student ID already exists"]);
                $sid_check->close();
                $conn->close();
                exit;
            }
            $sid_check->close();
        } elseif ($role === 'non_acad') {
            if (empty($data['contact_no']) || empty($data['emergency_contact'])) {
                echo json_encode(["status" => "error", "message" => "Missing non-academic details"]);
                $conn->close();
                exit;
            }
            $student_id = null;
        }
        $contact_no = $data['contact_no'];
        $emergency_contact = $data['emergency_contact'];

        // For students and non_acad, insert into students table (course can be NULL)
        $course = $data['course'] ?? null;

        $sql = "INSERT INTO students (user_id, student_id, course, contact_no, emergency_contact) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log('register.php prepare failed (' . $role . '): ' . $conn->error);
            echo json_encode(["status" => "error", "message" => "Server error"]);
            $conn->close();
            exit;
        }
        $stmt->bind_param("issss", $user_id, $student_id, $course, $contact_no, $emergency_contact);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => ucfirst($role) . " registration completed"]);
        } else {
            error_log('register.php ' . $role . ' insert failed: ' . $stmt->error);
            echo json_encode(["status" => "error", "message" => "Failed to register " . $role . " details", "detail" => $stmt->error]);
        }

        $stmt->close();
        $conn->close();
        exit;
    }

    if ($role === 'faculty') {
        if (empty($data['department']) || empty($data['position'])) {
            echo json_encode(["status" => "error", "message" => "Missing faculty details"]);
            $conn->close();
            exit;
        }
        $department = $data['department'];
        $position = $data['position'];

        // department_id may be passed or department name may be passed
        $department_id = null;
        $dept_name = '';
        if (!empty($data['department_id'])) {
            $department_id = intval($data['department_id']);
            // lookup name
            $dept_stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
            if ($dept_stmt) {
                $dept_stmt->bind_param('i', $department_id);
                $dept_stmt->execute();
                $res = $dept_stmt->get_result();
                if ($row = $res->fetch_assoc()) $dept_name = $row['department_name'];
                $dept_stmt->close();
            }
        } elseif (!empty($data['department'])) {
            // department passed as name, try to find id
            $dept_name_in = $data['department'];
            $dept_stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_name = ? LIMIT 1");
            if ($dept_stmt) {
                $dept_stmt->bind_param('s', $dept_name_in);
                $dept_stmt->execute();
                $res = $dept_stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $department_id = (int)$row['department_id'];
                    $dept_name = $dept_name_in;
                }
                $dept_stmt->close();
            }
            // If not found, insert new department
            if (empty($department_id)) {
                $insd = $conn->prepare("INSERT INTO departments (department_name) VALUES (?)");
                if ($insd) {
                    $insd->bind_param('s', $dept_name_in);
                    if ($insd->execute()) {
                        $department_id = $insd->insert_id;
                        $dept_name = $dept_name_in;
                    }
                    $insd->close();
                }
            }
        }

        // If still no department id, set defaults
        if (empty($department_id)) {
            $department_id = 0;
        }

        $sql = "INSERT INTO faculty (user_id, department, position) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log('register.php prepare failed (faculty): ' . $conn->error);
            echo json_encode(["status" => "error", "message" => "Server error"]);
            $conn->close();
            exit;
        }
        $stmt->bind_param("iss", $user_id, $dept_name, $position);

        if ($stmt->execute()) {
            // Update users table with department_id
            $update_sql = "UPDATE users SET department_id = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param("ii", $department_id, $user_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
            echo json_encode([
                "status" => "success",
                "message" => "Faculty registration completed",
                "department" => $dept_name,
                "department_id" => $department_id
            ]);
        } else {
            error_log('register.php faculty insert failed: ' . $stmt->error);
            echo json_encode(["status" => "error", "message" => "Failed to register faculty details", "detail" => $stmt->error]);
        }

        $stmt->close();
        $conn->close();
        exit;
    }
}

// If we reach here, nothing matched
error_log('register.php: no action matched for input: ' . substr($raw,0,500));
echo json_encode(["status" => "error", "message" => "No valid registration action found"]);
$conn->close();
exit;
?>