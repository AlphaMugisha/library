<?php
/**
 * SSO Login Handler
 * Separate file to handle SSO login from academic system
 * This avoids modifying the complex login.php file
 */

session_start();
require 'config/db.php';
require 'config/academic_integration.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$action = $_POST['action'];

if ($action === 'sso_login') {
    $token = trim($_POST['token'] ?? '');
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'message' => 'Academic token is required']); exit;
    }
    
    $integration = getAcademicIntegration($pdo);
    
    // Check if we have a valid session already
    $existingSession = $integration->validateAcademicSession($token);
    if ($existingSession) {
        // User already logged in, set session and redirect
        $_SESSION['user_id'] = $existingSession['user_id'];
        $_SESSION['role'] = $existingSession['role'];
        $_SESSION['name'] = $existingSession['full_name'];
        $_SESSION['academic_login'] = true;
        
        $redirect_url = getDashboardUrl($existingSession['role']);
        echo json_encode(['success' => true, 'step' => 'redirect', 'url' => $redirect_url]); exit;
    }
    
    // Validate token with academic system
    $academicUser = $integration->validateToken($token);
    
    if (!$academicUser) {
        echo json_encode(['success' => false, 'message' => 'Invalid academic token. Please try logging in again.']); exit;
    }
    
    // Sync user data from academic system
    try {
        $localUser = $integration->updateLocalUser($academicUser);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to sync user data: ' . $e->getMessage()]); exit;
    }
    
    if (!$localUser) {
        echo json_encode(['success' => false, 'message' => 'Failed to sync user data']); exit;
    }
    
    // Check user status
    if ($localUser['library_status'] === 'suspended') {
        echo json_encode(['success' => false, 'message' => 'Library access suspended. Contact administration.']); exit;
    }
    
    if ($localUser['is_activated'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Account not activated. Please activate your account first.']); exit;
    }
    
    // Store academic session
    $integration->storeAcademicSession($localUser['id'], $token, $academicUser);
    
    // Set local session
    $_SESSION['user_id'] = $localUser['id'];
    $_SESSION['role'] = $localUser['role'];
    $_SESSION['name'] = $localUser['full_name'];
    $_SESSION['academic_login'] = true;
    $_SESSION['academic_id'] = $localUser['academic_id'];
    
    $redirect_url = getDashboardUrl($localUser['role']);
    echo json_encode([
        'success' => true, 
        'step' => 'redirect', 
        'url' => $redirect_url,
        'message' => 'Welcome ' . $localUser['full_name'] . '! You have been logged in via the academic system.'
    ]); exit;
}

// Helper function to get dashboard URL
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
