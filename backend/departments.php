<?php
require_once 'db.php';

// Set content type for JSON response
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? 'list';

        switch ($action) {
            case 'list':
                // Get departments from database
                $sql = "SELECT department_id as id, department_name as name, department_name as text FROM departments ORDER BY department_name";
                $result = $conn->query($sql);
                $departments = [];
                while ($row = $result->fetch_assoc()) {
                    $departments[] = $row;
                }
                echo json_encode($departments);
                break;

            case 'options':
                // Get departments for dropdown options
                $sql = "SELECT department_id as value, department_name as text, department_name as name FROM departments ORDER BY department_name";
                $result = $conn->query($sql);
                $options = [];
                while ($row = $result->fetch_assoc()) {
                    $options[] = $row;
                }

                // If no departments in database, return fallback
                if (empty($options)) {
                    $options = [
                        ['value' => 'Department of Hospitality Management', 'text' => 'Department of Hospitality Management'],
                        ['value' => 'Department of Language and Mass Communication', 'text' => 'Department of Language and Mass Communication'],
                        ['value' => 'Department of Physical Education', 'text' => 'Department of Physical Education'],
                        ['value' => 'Department of Social Sciences and Humanities', 'text' => 'Department of Social Sciences and Humanities'],
                        ['value' => 'Teacher Education Department', 'text' => 'Teacher Education Department'],
                        ['value' => 'Department of Administration - ENTREP', 'text' => 'Department of Administration - ENTREP'],
                        ['value' => 'Department of Administration - BSOA', 'text' => 'Department of Administration - BSOA'],
                        ['value' => 'Department of Administration - BM', 'text' => 'Department of Administration - BM'],
                        ['value' => 'Department of Computer Studies', 'text' => 'Department of Computer Studies']
                    ];
                }
                echo json_encode($options);
                break;

            case 'stats':
                if (isset($_GET['dept_id'])) {
                    $dept_id = intval($_GET['dept_id']);
                    $sql = "SELECT 
                                d.name as department_name,
                                COUNT(p.id) as total_programs,
                                COUNT(CASE WHEN p.status = 'ongoing' THEN 1 END) as active_programs,
                                COUNT(CASE WHEN p.status = 'ended' THEN 1 END) as completed_programs,
                                COUNT(CASE WHEN p.status = 'planning' THEN 1 END) as planning_programs,
                                SUM(p.budget) as total_budget,
                                AVG(p.max_students) as avg_capacity
                            FROM departments d
                            LEFT JOIN programs p ON d.id = p.department_id
                            WHERE d.id = ?
                            GROUP BY d.id, d.name";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $dept_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    echo json_encode($result->fetch_assoc());
                    $stmt->close();
                } else {
                    echo json_encode(['error' => 'Department ID required']);
                }
                break;
                
            case 'programs':
                if (isset($_GET['dept_id'])) {
                    $dept_id = intval($_GET['dept_id']);
                    $status = $_GET['status'] ?? null;
                    
                    if ($status) {
                        $sql = "SELECT p.*, d.name as department_name FROM programs p 
                               LEFT JOIN departments d ON p.department_id = d.id 
                               WHERE p.department_id = ? AND p.status = ? 
                               ORDER BY p.created_at DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("is", $dept_id, $status);
                    } else {
                        $sql = "SELECT p.*, d.name as department_name FROM programs p 
                               LEFT JOIN departments d ON p.department_id = d.id 
                               WHERE p.department_id = ? 
                               ORDER BY p.created_at DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $dept_id);
                    }
                    
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $programs = [];
                    
                    while ($row = $result->fetch_assoc()) {
                        $programs[] = $row;
                    }
                    
                    echo json_encode($programs);
                    $stmt->close();
                } else {
                    echo json_encode(['error' => 'Department ID required']);
                }
                break;

            default:
                echo json_encode(['error' => 'Unknown action']);
        }
    }

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>
