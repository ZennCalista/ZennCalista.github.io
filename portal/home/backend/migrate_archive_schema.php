<?php
// Migration script: adds is_archived + archived_at to programs and creates images_archive table.
// Run this once from the browser or CLI. It is idempotent.

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$results = ['ok' => true, 'actions' => []];

try {
    // include DB
    if (file_exists(__DIR__ . '/no_cache.php')) include __DIR__ . '/no_cache.php';
    if (!file_exists(__DIR__ . '/../db.php')) throw new Exception('Could not find ../db.php');
    include __DIR__ . '/../db.php';

    if (!isset($conn)) throw new Exception('Database connection ($conn) not found');
    if ($conn->connect_error) throw new Exception('DB connect error: ' . $conn->connect_error);

    // 1) Ensure programs table exists
    $res = $conn->query("SHOW TABLES LIKE 'programs'");
    if (!$res || $res->num_rows === 0) {
        throw new Exception('Table `programs` not found in database');
    }

    // 2) Add is_archived column if missing
    $res = $conn->query("SHOW COLUMNS FROM programs LIKE 'is_archived'");
    if (!$res || $res->num_rows === 0) {
        $sql = "ALTER TABLE programs ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0";
        if ($conn->query($sql) === TRUE) {
            $results['actions'][] = 'Added column programs.is_archived';
        } else {
            throw new Exception('Failed to add is_archived: ' . $conn->error);
        }
    } else {
        $results['actions'][] = 'programs.is_archived already exists';
    }

    // 3) Add archived_at column if missing
    $res = $conn->query("SHOW COLUMNS FROM programs LIKE 'archived_at'");
    if (!$res || $res->num_rows === 0) {
        $sql = "ALTER TABLE programs ADD COLUMN archived_at DATETIME NULL";
        if ($conn->query($sql) === TRUE) {
            $results['actions'][] = 'Added column programs.archived_at';
        } else {
            throw new Exception('Failed to add archived_at: ' . $conn->error);
        }
    } else {
        $results['actions'][] = 'programs.archived_at already exists';
    }

    // 4) Create images_archive table if missing
    $res = $conn->query("SHOW TABLES LIKE 'images_archive'");
    if (!$res || $res->num_rows === 0) {
        $sql = "CREATE TABLE IF NOT EXISTS images_archive (
            archive_image_id INT AUTO_INCREMENT PRIMARY KEY,
            archive_program_id INT NOT NULL,
            image_data LONGBLOB,
            image_desc VARCHAR(255),
            uploaded_at DATETIME,
            INDEX idx_archive_program_id (archive_program_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if ($conn->query($sql) === TRUE) {
            $results['actions'][] = 'Created table images_archive';
        } else {
            throw new Exception('Failed to create images_archive: ' . $conn->error);
        }
    } else {
        $results['actions'][] = 'images_archive already exists';
    }

    // 6) Add original_program_id to programs_archive if missing (helps restore)
    $res = $conn->query("SHOW TABLES LIKE 'programs_archive'");
    if ($res && $res->num_rows > 0) {
        $res2 = $conn->query("SHOW COLUMNS FROM programs_archive LIKE 'original_program_id'");
        if (!$res2 || $res2->num_rows === 0) {
            $sql = "ALTER TABLE programs_archive ADD COLUMN original_program_id INT NULL";
            if ($conn->query($sql) === TRUE) {
                $results['actions'][] = 'Added column programs_archive.original_program_id';
            } else {
                throw new Exception('Failed to add programs_archive.original_program_id: ' . $conn->error);
            }
        } else {
            $results['actions'][] = 'programs_archive.original_program_id already exists';
        }
    } else {
        $results['actions'][] = 'programs_archive table does not exist yet';
    }

    // 5) Optionally create index on programs.is_archived
    $res = $conn->query("SHOW INDEX FROM programs WHERE Key_name = 'idx_programs_is_archived'");
    if (!$res || $res->num_rows === 0) {
        $sql = "CREATE INDEX idx_programs_is_archived ON programs(is_archived)";
        if ($conn->query($sql) === TRUE) {
            $results['actions'][] = 'Created index idx_programs_is_archived';
        } else {
            // non-fatal
            $results['actions'][] = 'Could not create index idx_programs_is_archived: ' . $conn->error;
        }
    } else {
        $results['actions'][] = 'Index idx_programs_is_archived already exists';
    }

    $conn->close();
    echo json_encode($results, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $results['ok'] = false;
    $results['error'] = $e->getMessage();
    http_response_code(500);
    echo json_encode($results, JSON_PRETTY_PRINT);
}

?>
