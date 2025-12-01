<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/fpdf186/fpdf.php';
require_once __DIR__ . '/db.php';

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : 'all';
$departmentFilter = isset($_GET['department']) ? $_GET['department'] : 'all';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Build SQL query with filters - simplified to avoid join issues
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.role, u.verification_status,
        s.student_id, s.course, f.faculty_id, f.department as faculty_dept
    FROM users u
    LEFT JOIN students s ON u.id = s.user_id
    LEFT JOIN faculty f ON u.id = f.user_id
    WHERE 1=1";

// Apply filters
if ($roleFilter !== 'all') {
    $sql .= " AND u.role = '" . $conn->real_escape_string($roleFilter) . "'";
}

if ($statusFilter !== 'all') {
    $sql .= " AND u.verification_status = '" . $conn->real_escape_string($statusFilter) . "'";
}

if ($departmentFilter !== 'all') {
    $deptEscaped = $conn->real_escape_string($departmentFilter);
    $sql .= " AND (s.course = '$deptEscaped' OR f.department = '$deptEscaped')";
}

if (!empty($search)) {
    $searchEscaped = $conn->real_escape_string($search);
    $sql .= " AND (u.firstname LIKE '%$searchEscaped%' OR u.lastname LIKE '%$searchEscaped%' OR u.email LIKE '%$searchEscaped%' OR s.course LIKE '%$searchEscaped%' OR f.department LIKE '%$searchEscaped%')";
}

$sql .= " ORDER BY u.role, u.lastname, u.firstname";

$result = $conn->query($sql);

// Check for SQL errors
if (!$result) {
    die("Database error: Unable to retrieve users.");
}

// Create PDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'Users Export Report', 0, 1, 'C');
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
        $this->Ln(5);
        
        // Table header
        $this->SetFont('Arial', 'B', 9);
        $this->SetFillColor(5, 70, 52);
        $this->SetTextColor(255, 255, 255);
        $this->Cell(15, 8, 'ID', 1, 0, 'C', true);
        $this->Cell(40, 8, 'Name', 1, 0, 'C', true);
        $this->Cell(55, 8, 'Email', 1, 0, 'C', true);
        $this->Cell(50, 8, 'Department', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Role', 1, 0, 'C', true);
        $this->Cell(20, 8, 'Status', 1, 1, 'C', true);
        $this->SetTextColor(0, 0, 0);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

$rowCount = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rowCount++;
        
        // Alternate row colors
        if ($rowCount % 2 == 0) {
            $pdf->SetFillColor(240, 240, 240);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        
        // Determine user_id and department based on role
        $userId = $row['role'] === 'faculty' ? ($row['faculty_id'] ?: $row['id']) : ($row['student_id'] ?: $row['id']);
        $department = $row['role'] === 'faculty' ? ($row['faculty_dept'] ?: 'N/A') : ($row['course'] ?: 'N/A');
        
        $pdf->Cell(15, 7, $userId, 1, 0, 'C', true);
        $pdf->Cell(40, 7, substr($row['firstname'] . ' ' . $row['lastname'], 0, 25), 1, 0, 'L', true);
        $pdf->Cell(55, 7, substr($row['email'], 0, 35), 1, 0, 'L', true);
        $pdf->Cell(50, 7, substr($department, 0, 30), 1, 0, 'L', true);
        $pdf->Cell(20, 7, ucfirst($row['role']), 1, 0, 'C', true);
        $pdf->Cell(20, 7, ucfirst($row['verification_status']), 1, 1, 'C', true);
    }
} else {
    $pdf->Cell(0, 10, 'No users found', 1, 1, 'C');
}

// Add summary
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 8, 'Total Users: ' . $rowCount, 0, 1);

// Output PDF
$filename = 'users_export_' . date('Y-m-d_His') . '.pdf';
$pdf->Output('D', $filename);