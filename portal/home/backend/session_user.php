<?php
// Returns JSON of the current logged-in user or 401
// Supports both TOKEN and SESSION authentication
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Include required files
require_once __DIR__ . '/../../../backend/db.php';
require_once __DIR__ . '/../../../backend/token_utils.php';

session_start();

// Priority 1: Check for token authentication (multi-device support)
$token = getTokenFromCookie();
$tokenUser = null;

if ($token) {
	$tokenUser = validateToken($conn, $token);
	if ($tokenUser) {
		// Token is valid - use token authentication
		$_SESSION['user'] = [
			'id' => (int)$tokenUser['id'],
			'firstname' => $tokenUser['firstname'] ?? '',
			'lastname' => $tokenUser['lastname'] ?? '',
			'email' => $tokenUser['email'] ?? '',
			'role' => $tokenUser['role']
		];
		$_SESSION['user_id'] = $tokenUser['id'];
		$_SESSION['role'] = $tokenUser['role'];
		error_log("User authenticated via token: user_id={$tokenUser['id']}");
	} else {
		// Token is invalid or expired - clear it
		clearAuthCookie();
		error_log("Invalid or expired token, cleared cookie");
	}
}

// Priority 2: Fall back to session authentication (backward compatibility)
if (!isset($_SESSION['user'])) {
	http_response_code(401);
	echo json_encode(['authenticated' => false]);
	exit();
}

// Try to enrich session user with department info if possible
$user = $_SESSION['user'];
$user_id = isset($user['id']) ? (int)$user['id'] : null;

// Attempt to include DB and fetch department details; fail gracefully if unavailable
$department_name = null;
$department_id = null;
$db_included = false;
try {
	if (file_exists(__DIR__ . '/../../home/db.php')) {
		include_once __DIR__ . '/../../home/db.php';
		$db_included = true;
	} elseif (file_exists(__DIR__ . '/../db.php')) {
		include_once __DIR__ . '/../db.php';
		$db_included = true;
	}
} catch (Throwable $e) {
	// ignore
}

if ($db_included && $user_id) {
	// Try to read department_id from users table
	$stmt = $conn->prepare('SELECT department_id FROM users WHERE id = ? LIMIT 1');
	if ($stmt) {
		$stmt->bind_param('i', $user_id);
		$stmt->execute();
		$res = $stmt->get_result();
		if ($row = $res->fetch_assoc()) {
			$department_id = isset($row['department_id']) ? $row['department_id'] : null;
		}
		$stmt->close();
	}

	if ($department_id) {
		// Try to get department name
		$stmt2 = $conn->prepare('SELECT department_name FROM departments WHERE department_id = ? LIMIT 1');
		if ($stmt2) {
			$stmt2->bind_param('i', $department_id);
			$stmt2->execute();
			$r2 = $stmt2->get_result();
			if ($row2 = $r2->fetch_assoc()) {
				$department_name = $row2['department_name'];
			}
			$stmt2->close();
		}
	}
}

$user['department_id'] = $department_id;
$user['department_name'] = $department_name;

echo json_encode([
	'authenticated' => true,
	'user' => $user,
]);
?>


