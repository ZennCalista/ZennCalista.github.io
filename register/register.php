<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');

include 'db.php';

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

// If initial registration payload
if (!empty($data['firstname']) && !empty($data['lastname']) && !empty($data['email']) && !empty($data['password'])) {
    $firstname = $data['firstname'];
    $lastname = $data['lastname'];
    $email = $data['email'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT); // Hash the password
    $role = $data['role'] ?? 'student';

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

    // Insert into the users table with email_verified = false
    $sql = "INSERT INTO users (firstname, lastname, email, password, role, email_verified) VALUES (?, ?, ?, ?, ?, FALSE)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('register.php prepare failed: ' . $conn->error);
        echo json_encode(["status" => "error", "message" => "Server error: could not prepare statement", "detail" => $conn->error]);
        $conn->close();
        exit;
    }
    $stmt->bind_param("sssss", $firstname, $lastname, $email, $password, $role);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        echo json_encode([
            "status" => "success",
            "message" => "Registration initiated. Please verify your email.",
            "user_id" => $user_id,
            "role" => $role,
            "next_step" => "otp_verification"
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

    // Check if user exists and email is verified
    $user_check = $conn->prepare("SELECT id, email_verified FROM users WHERE id = ?");
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
    if (!$user_row['email_verified']) {
        echo json_encode(["status" => "error", "message" => "Email not verified. Please verify your email first."]);
        $user_check->close();
        $conn->close();
        exit;
    }
    $user_check->close();

    if ($role === 'student' || $role === 'non_acad') {
        if (empty($data['contact_no']) || empty($data['emergency_contact'])) {
            echo json_encode(["status" => "error", "message" => "Missing " . ($role === 'student' ? 'student' : 'non-academic') . " details"]);
            $conn->close();
            exit;
        }
        $contact_no = $data['contact_no'];
        $emergency_contact = $data['emergency_contact'];

        // For students and non_acad, insert into students table (student_id and course can be NULL)
        $student_id = $data['student_id'] ?? null;
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
        if (empty($data['faculty_name']) || empty($data['faculty_id']) || empty($data['position'])) {
            echo json_encode(["status" => "error", "message" => "Missing faculty details"]);
            $conn->close();
            exit;
        }
        $faculty_name = $data['faculty_name'];
        $faculty_id = $data['faculty_id'];
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