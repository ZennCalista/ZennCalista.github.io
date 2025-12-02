<?php
class PasswordUtils {
    /**
     * Validate password meets all requirements
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'requirements' => [
                'length' => strlen($password) >= 8,
                'uppercase' => preg_match('/[A-Z]/', $password) === 1,
                'lowercase' => preg_match('/[a-z]/', $password) === 1,
                'number' => preg_match('/[0-9]/', $password) === 1
            ]
        ];
    }
    
    /**
     * Check if password is same as current password
     */
    public static function isSameAsCurrentPassword($user_id, $new_password, $conn) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($current_hash);
        $stmt->fetch();
        $stmt->close();
        
        return password_verify($new_password, $current_hash);
    }
    
    /**
     * Verify current password
     */
    public static function verifyCurrentPassword($user_id, $password, $conn) {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($password_hash);
        $stmt->fetch();
        $stmt->close();
        
        return password_verify($password, $password_hash);
    }
    
    /**
     * Update user password
     */
    public static function updatePassword($user_id, $new_password, $conn) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Log password change
     */
    public static function logPasswordChange($user_id, $conn) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt = $conn->prepare("INSERT INTO password_change_history (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>
