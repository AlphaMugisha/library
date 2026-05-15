<?php
/**
 * Academic System Integration Configuration
 * Handles communication with Levi's academic system (rtal.sowiseafrica.org)
 */

class AcademicIntegration {
    private $pdo;
    private $apiBaseUrl;
    private $apiKey;
    private $apiSecret;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        // Configuration - update these with actual values from Levi
        $this->apiBaseUrl = 'https://rtal.sowiseafrica.org/api/v1';
        $this->apiKey = 'YOUR_API_KEY_HERE'; // Get from Levi
        $this->apiSecret = 'YOUR_API_SECRET_HERE'; // Get from Levi
    }
    
    /**
     * Validate academic system token
     * @param string $token - JWT or session token from academic system
     * @return array|false - User data if valid, false if invalid
     */
    public function validateToken($token) {
        try {
            $response = $this->makeRequest('POST', '/auth/validate', [
                'token' => $token
            ]);
            
            if ($response && isset($response['success']) && $response['success']) {
                return $response['user'];
            }
            
            return false;
        } catch (Exception $e) {
            $this->logIntegration('error', 'token_validation', null, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync user data from academic system
     * @param string $academicId - User ID from academic system
     * @return array|false - Synced user data
     */
    public function syncUser($academicId) {
        try {
            $response = $this->makeRequest('GET', "/users/{$academicId}");
            
            if ($response && isset($response['success']) && $response['success']) {
                return $this->updateLocalUser($response['user']);
            }
            
            return false;
        } catch (Exception $e) {
            $this->logIntegration('error', 'user_sync', $academicId, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create or update local user from academic system data
     * @param array $academicUser - User data from academic system
     * @return array - Updated local user data
     */
    private function updateLocalUser($academicUser) {
        try {
            // Check if user exists
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE academic_id = ?");
            $stmt->execute([$academicUser['id']]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser) {
                // Update existing user
                $stmt = $this->pdo->prepare("
                    UPDATE users SET 
                        full_name = ?, 
                        email = ?, 
                        academic_role = ?, 
                        academic_department = ?, 
                        academic_level = ?, 
                        sync_status = 'synced', 
                        last_sync = NOW()
                    WHERE academic_id = ?
                ");
                
                $stmt->execute([
                    $academicUser['full_name'],
                    $academicUser['email'],
                    $academicUser['role'],
                    $academicUser['department'] ?? null,
                    $academicUser['level'] ?? null,
                    $academicUser['id']
                ]);
                
                $userId = $existingUser['id'];
            } else {
                // Create new user
                $stmt = $this->pdo->prepare("
                    INSERT INTO users (
                        academic_id, full_name, email, role, 
                        academic_role, academic_department, academic_level,
                        sync_status, last_sync, is_activated
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'synced', NOW(), 1)
                ");
                
                // Map academic role to library role
                $libraryRole = $this->mapAcademicRoleToLibraryRole($academicUser['role']);
                
                $stmt->execute([
                    $academicUser['id'],
                    $academicUser['full_name'],
                    $academicUser['email'],
                    $libraryRole,
                    $academicUser['role'],
                    $academicUser['department'] ?? null,
                    $academicUser['level'] ?? null
                ]);
                
                $userId = $this->pdo->lastInsertId();
            }
            
            // Log successful sync
            $this->logIntegration('sync', 'user_sync', $academicUser['id'], 'Success', $academicUser);
            
            // Return updated user data
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
            
        } catch (Exception $e) {
            $this->logIntegration('error', 'user_update', $academicUser['id'] ?? null, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Map academic system role to library system role
     * @param string $academicRole - Role from academic system
     * @return string - Mapped library role
     */
    private function mapAcademicRoleToLibraryRole($academicRole) {
        $roleMapping = [
            'student' => 'student',
            'teacher' => 'teacher', 
            'lecturer' => 'teacher',
            'professor' => 'teacher',
            'librarian' => 'librarian',
            'admin' => 'librarian', // Admins get librarian access
            'staff' => 'teacher'
        ];
        
        return $roleMapping[strtolower($academicRole)] ?? 'student';
    }
    
    /**
     * Make HTTP request to academic system API
     * @param string $method - HTTP method
     * @param string $endpoint - API endpoint
     * @param array $data - Request data
     * @return array - Response data
     */
    private function makeRequest($method, $endpoint, $data = []) {
        $url = $this->apiBaseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'X-API-Secret: ' . $this->apiSecret
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception('API request failed');
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode !== 200) {
            throw new Exception('API error: ' . ($responseData['message'] ?? 'Unknown error'));
        }
        
        return $responseData;
    }
    
    /**
     * Log integration activities
     * @param string $action - Action type
     * @param string $type - Type of operation
     * @param string $academicId - Academic user ID
     * @param string $message - Log message
     * @param array $data - Additional data
     */
    private function logIntegration($action, $type, $academicId, $message, $data = []) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO integration_logs (
                    user_id, action, academic_id, status, message, 
                    data_sent, response_received
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Try to find user_id if academic_id is provided
            $userId = null;
            if ($academicId) {
                $userStmt = $this->pdo->prepare("SELECT id FROM users WHERE academic_id = ?");
                $userStmt->execute([$academicId]);
                $userId = $userStmt->fetchColumn();
            }
            
            $status = $action === 'error' ? 'error' : 'success';
            
            $stmt->execute([
                $userId,
                $action,
                $academicId,
                $status,
                $message,
                json_encode($data),
                json_encode(['timestamp' => date('Y-m-d H:i:s')])
            ]);
        } catch (Exception $e) {
            // Log error fails silently to avoid infinite loops
            error_log("Integration log failed: " . $e->getMessage());
        }
    }
    
    /**
     * Store academic session token
     * @param int $userId - Local user ID
     * @param string $token - Academic system token
     * @param array $sessionData - Session data
     */
    public function storeAcademicSession($userId, $token, $sessionData = []) {
        try {
            // Remove any existing sessions for this user
            $stmt = $this->pdo->prepare("DELETE FROM academic_sessions WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Insert new session
            $stmt = $this->pdo->prepare("
                INSERT INTO academic_sessions (
                    user_id, academic_token, token_expires, session_data
                ) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 8 HOUR), ?)
            ");
            
            $stmt->execute([
                $userId,
                $token,
                json_encode($sessionData)
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to store academic session: " . $e->getMessage());
        }
    }
    
    /**
     * Validate academic session token
     * @param string $token - Session token
     * @return array|false - Session data if valid
     */
    public function validateAcademicSession($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT s.*, u.full_name, u.role, u.academic_role 
                FROM academic_sessions s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.academic_token = ? 
                AND s.token_expires > NOW() 
                AND s.is_active = 1
            ");
            
            $stmt->execute([$token]);
            $session = $stmt->fetch();
            
            if ($session) {
                // Update last used time
                $updateStmt = $this->pdo->prepare("UPDATE academic_sessions SET last_used = NOW() WHERE academic_token = ?");
                $updateStmt->execute([$token]);
                
                return $session;
            }
            
            return false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get integration statistics
     * @return array - Integration stats
     */
    public function getIntegrationStats() {
        try {
            $stats = [];
            
            // User sync stats
            $stmt = $this->pdo->query("
                SELECT 
                    sync_status,
                    COUNT(*) as count
                FROM users 
                WHERE academic_id IS NOT NULL
                GROUP BY sync_status
            ");
            $stats['sync_status'] = $stmt->fetchAll();
            
            // Recent activity
            $stmt = $this->pdo->query("
                SELECT 
                    action,
                    status,
                    COUNT(*) as count
                FROM integration_logs 
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY action, status
            ");
            $stats['recent_activity'] = $stmt->fetchAll();
            
            // Active sessions
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as active_sessions
                FROM academic_sessions 
                WHERE token_expires > NOW() AND is_active = 1
            ");
            $stats['active_sessions'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
}

// Helper function to get integration instance
function getAcademicIntegration($pdo) {
    static $instance = null;
    if ($instance === null) {
        $instance = new AcademicIntegration($pdo);
    }
    return $instance;
}
?>
