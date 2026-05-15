<?php
/**
 * SSO Login Handler
 * Handles Single Sign-On from the academic system
 * This endpoint will be called when users click "Login with Academic System"
 */

session_start();
require 'config/db.php';
require 'config/academic_integration.php';

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'validate_token':
            handleTokenValidation();
            break;
            
        case 'sso_redirect':
            handleSSORedirect();
            break;
            
        case 'academic_callback':
            handleAcademicCallback();
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Login error: ' . $e->getMessage()]);
}

/**
 * Handle token validation from academic system
 */
function handleTokenValidation() {
    $token = $_POST['token'] ?? '';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Token is required']);
        return;
    }
    
    $integration = getAcademicIntegration($pdo);
    
    // First check if we have a valid session already
    $existingSession = $integration->validateAcademicSession($token);
    if ($existingSession) {
        // User already logged in, set session and redirect
        $_SESSION['user_id'] = $existingSession['user_id'];
        $_SESSION['role'] = $existingSession['role'];
        $_SESSION['name'] = $existingSession['full_name'];
        $_SESSION['academic_login'] = true;
        
        echo json_encode([
            'success' => true, 
            'step' => 'redirect',
            'url' => getDashboardUrl($existingSession['role'])
        ]);
        return;
    }
    
    // Validate token with academic system
    $academicUser = $integration->validateToken($token);
    
    if (!$academicUser) {
        echo json_encode(['success' => false, 'message' => 'Invalid academic token']);
        return;
    }
    
    // Sync user data from academic system
    $localUser = $integration->updateLocalUser($academicUser);
    
    if (!$localUser) {
        echo json_encode(['success' => false, 'message' => 'Failed to sync user data']);
        return;
    }
    
    // Check user status
    if ($localUser['library_status'] === 'suspended') {
        echo json_encode(['success' => false, 'message' => 'Library access suspended. Contact administration.']);
        return;
    }
    
    if ($localUser['is_activated'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Account not activated. Please activate your account first.']);
        return;
    }
    
    // Store academic session
    $integration->storeAcademicSession($localUser['id'], $token, $academicUser);
    
    // Set local session
    $_SESSION['user_id'] = $localUser['id'];
    $_SESSION['role'] = $localUser['role'];
    $_SESSION['name'] = $localUser['full_name'];
    $_SESSION['academic_login'] = true;
    $_SESSION['academic_id'] = $localUser['academic_id'];
    
    echo json_encode([
        'success' => true,
        'step' => 'redirect',
        'url' => getDashboardUrl($localUser['role']),
        'user' => [
            'name' => $localUser['full_name'],
            'role' => $localUser['role'],
            'academic_role' => $localUser['academic_role']
        ]
    ]);
}

/**
 * Handle SSO redirect to academic system
 */
function handleSSORedirect() {
    // This would redirect to the academic system's login page
    // For now, we'll simulate the redirect
    
    $redirectUrl = 'https://rtal.sowiseafrica.org/auth/sso?callback=' . urlencode('http://localhost/library/sso_login.php?action=academic_callback');
    
    echo json_encode([
        'success' => true,
        'step' => 'redirect',
        'url' => $redirectUrl
    ]);
}

/**
 * Handle callback from academic system after authentication
 */
function handleAcademicCallback() {
    // This would handle the callback after user authenticates with academic system
    // The academic system would send back a token or authorization code
    
    $token = $_GET['token'] ?? $_POST['token'] ?? '';
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'No token received from academic system']);
        return;
    }
    
    // Process the token (same as token validation)
    $_POST['action'] = 'validate_token';
    $_POST['token'] = $token;
    handleTokenValidation();
}

/**
 * Get dashboard URL based on user role
 */
function getDashboardUrl($role) {
    $role = strtolower(trim($role));
    
    switch ($role) {
        case 'librarian':
            return 'librarian/dashboard.php';
        case 'student':
            return 'student/dashboard.php';
        case 'teacher':
            return 'teacher/dashboard.php';
        default:
            return 'index.php';
    }
}
?>
