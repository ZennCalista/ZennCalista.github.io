<?php
/**
 * Performance Optimization: Add Database Indexes
 * Run once via browser or command line
 * Safe to run multiple times - checks if indexes exist before creating
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = ['success' => true, 'actions' => [], 'errors' => []];

try {
    include __DIR__ . '/../db.php';

    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    $indexes = [
        ['table' => 'images', 'name' => 'idx_images_program_id', 'column' => 'program_id'],
        ['table' => 'images_archive', 'name' => 'idx_images_archive_program_id', 'column' => 'archive_program_id'],
        ['table' => 'programs', 'name' => 'idx_programs_department_id', 'column' => 'department_id'],
        ['table' => 'programs', 'name' => 'idx_programs_faculty_id', 'column' => 'faculty_id'],
        ['table' => 'programs', 'name' => 'idx_programs_status', 'column' => 'status'],
        ['table' => 'programs_archive', 'name' => 'idx_programs_archive_original_id', 'column' => 'original_program_id'],
        ['table' => 'participants', 'name' => 'idx_participants_program_id', 'column' => 'program_id'],
        ['table' => 'participants', 'name' => 'idx_participants_user_id', 'column' => 'user_id']
    ];

    foreach ($indexes as $index) {
        $table = $index['table'];
        $name = $index['name'];
        $column = $index['column'];

        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if (!$check_table || $check_table->num_rows === 0) {
            $results['actions'][] = "Table `$table` does not exist - skipped";
            continue;
        }

        $check_index = $conn->query("SHOW INDEX FROM `$table` WHERE Key_name = '$name'");
        if ($check_index && $check_index->num_rows > 0) {
            $results['actions'][] = "Index `$name` already exists";
            continue;
        }

        $sql = "CREATE INDEX `$name` ON `$table`(`$column`)";
        if ($conn->query($sql) === TRUE) {
            $results['actions'][] = "Created index `$name` on `$table`.`$column`";
        } else {
            $results['errors'][] = "Failed to create index `$name`: " . $conn->error;
        }
    }

    $conn->close();
    $results['summary'] = count($results['errors']) > 0 ? 'Completed with errors' : 'All indexes created!';

} catch (Exception $e) {
    $results['success'] = false;
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>
