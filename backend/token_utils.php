<?php
/**
 * Token Utility Functions
 * Handles token generation, validation, and management
 */

/**
 * Generate a cryptographically secure random token
 * @return string 64-character hexadecimal token
 */
function generateToken() {
    return bin2hex(random_bytes(32));
}

/**
 * Create a new authentication token for a user
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param int $expiryDays Number of days until token expires (default 30)
 * @return string|false Generated token or false on failure
 */
function createAuthToken($conn, $userId, $expiryDays = 30) {
    try {
        $token = generateToken();
        $deviceInfo = isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : 'Unknown';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
        
        // Check session limit before creating new token
        if (!checkSessionLimit($conn, $userId)) {
            // Remove oldest token if limit exceeded
            removeOldestToken($conn, $userId);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO user_tokens (user_id, token, device_info, ip_address, expires_at) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            return false;
        }
        
        $stmt->bind_param("issss", $userId, $token, $deviceInfo, $ipAddress, $expiresAt);
        
        if ($stmt->execute()) {
            $stmt->close();
            return $token;
        } else {
            error_log("Token creation failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception in createAuthToken: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate a token and return user data if valid
 * @param mysqli $conn Database connection
 * @param string $token Authentication token
 * @return array|null User data or null if invalid
 */
function validateToken($conn, $token) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                u.id, u.firstname, u.lastname, u.email, u.role,
                t.id as token_id, t.created_at as token_created,
                t.last_activity
            FROM user_tokens t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.token = ? 
            AND t.expires_at > NOW()
        ");
        
        if (!$stmt) {
            error_log("Prepare failed in validateToken: " . $conn->error);
            return null;
        }
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $userData = $result->fetch_assoc();
        $stmt->close();
        
        // Update last activity
        updateTokenActivity($conn, $token);
        
        return $userData;
        
    } catch (Exception $e) {
        error_log("Exception in validateToken: " . $e->getMessage());
        return null;
    }
}

/**
 * Update the last activity timestamp for a token
 * @param mysqli $conn Database connection
 * @param string $token Authentication token
 * @return bool Success status
 */
function updateTokenActivity($conn, $token) {
    try {
        $stmt = $conn->prepare("UPDATE user_tokens SET last_activity = NOW() WHERE token = ?");
        if (!$stmt) return false;
        
        $stmt->bind_param("s", $token);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Exception in updateTokenActivity: " . $e->getMessage());
        return false;
    }
}

/**
 * Invalidate a specific token (logout)
 * @param mysqli $conn Database connection
 * @param string $token Authentication token
 * @return bool Success status
 */
function invalidateToken($conn, $token) {
    try {
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE token = ?");
        if (!$stmt) return false;
        
        $stmt->bind_param("s", $token);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Exception in invalidateToken: " . $e->getMessage());
        return false;
    }
}

/**
 * Invalidate all tokens for a user (logout all devices)
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return bool Success status
 */
function invalidateAllUserTokens($conn, $userId) {
    try {
        $stmt = $conn->prepare("DELETE FROM user_tokens WHERE user_id = ?");
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Exception in invalidateAllUserTokens: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up expired tokens
 * @param mysqli $conn Database connection
 * @return int Number of tokens deleted
 */
function cleanupExpiredTokens($conn) {
    try {
        $result = $conn->query("DELETE FROM user_tokens WHERE expires_at < NOW()");
        return $result ? $conn->affected_rows : 0;
    } catch (Exception $e) {
        error_log("Exception in cleanupExpiredTokens: " . $e->getMessage());
        return 0;
    }
}

/**
 * Check if user has reached session limit (max 5 devices)
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param int $maxSessions Maximum allowed sessions (default 5)
 * @return bool True if within limit, false if exceeded
 */
function checkSessionLimit($conn, $userId, $maxSessions = 5) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as token_count 
            FROM user_tokens 
            WHERE user_id = ? 
            AND expires_at > NOW()
        ");
        
        if (!$stmt) return true; // Allow if check fails
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return ($row['token_count'] < $maxSessions);
    } catch (Exception $e) {
        error_log("Exception in checkSessionLimit: " . $e->getMessage());
        return true; // Allow if check fails
    }
}

/**
 * Remove the oldest token for a user
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return bool Success status
 */
function removeOldestToken($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            DELETE FROM user_tokens 
            WHERE user_id = ? 
            ORDER BY last_activity ASC 
            LIMIT 1
        ");
        
        if (!$stmt) return false;
        
        $stmt->bind_param("i", $userId);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log("Exception in removeOldestToken: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all active tokens for a user
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @return array List of active tokens with device info
 */
function getUserTokens($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                id, device_info, ip_address, 
                created_at, last_activity, expires_at
            FROM user_tokens 
            WHERE user_id = ? 
            AND expires_at > NOW()
            ORDER BY last_activity DESC
        ");
        
        if (!$stmt) return [];
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tokens = [];
        while ($row = $result->fetch_assoc()) {
            $tokens[] = $row;
        }
        
        $stmt->close();
        return $tokens;
    } catch (Exception $e) {
        error_log("Exception in getUserTokens: " . $e->getMessage());
        return [];
    }
}

/**
 * Set authentication cookie with token
 * @param string $token Authentication token
 * @param int $expiryDays Number of days until cookie expires
 * @param bool $secure Use secure flag (requires HTTPS)
 */
function setAuthCookie($token, $expiryDays = 30, $secure = false) {
    setcookie('auth_token', $token, [
        'expires' => time() + ($expiryDays * 24 * 60 * 60),
        'path' => '/',
        'httponly' => true,
        'secure' => $secure, // Set to false for development (no HTTPS)
        'samesite' => 'Lax' // Changed from Strict to Lax for better compatibility
    ]);
}

/**
 * Clear authentication cookie
 */
function clearAuthCookie() {
    setcookie('auth_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * Get token from cookie
 * @return string|null Token or null if not found
 */
function getTokenFromCookie() {
    return $_COOKIE['auth_token'] ?? null;
}
