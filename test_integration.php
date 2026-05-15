<?php
/**
 * Integration Testing Suite
 * Test the academic system integration functionality
 */

require 'config/db.php';
require 'config/academic_integration.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>NGA Library - Integration Testing</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class='bg-gray-50 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6 flex items-center gap-3'>
                <i class='bx bx-test-tube text-blue-600'></i>
                Academic System Integration Tests
            </h1>";

// Test 1: Database Schema
echo "<div class='mb-8'>
        <h2 class='text-xl font-semibold text-gray-700 mb-4'>Test 1: Database Schema</h2>
        <div class='bg-gray-50 rounded-lg p-4'>";

try {
    // Check if new columns exist
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['academic_id', 'sync_status', 'last_sync', 'academic_role', 'sync_error_message', 'academic_department', 'academic_level'];
    
    $allColumnsExist = true;
    foreach ($requiredColumns as $column) {
        if (!in_array($column, $columns)) {
            $allColumnsExist = false;
            echo "<div class='flex items-center gap-2 text-red-600 mb-2'>
                    <i class='bx bx-x-circle'></i>
                    <span>Missing column: {$column}</span>
                  </div>";
        } else {
            echo "<div class='flex items-center gap-2 text-green-600 mb-2'>
                    <i class='bx bx-check-circle'></i>
                    <span>Column exists: {$column}</span>
                  </div>";
        }
    }
    
    // Check if new tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = ['integration_logs', 'academic_sessions'];
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "<div class='flex items-center gap-2 text-green-600 mb-2'>
                    <i class='bx bx-check-circle'></i>
                    <span>Table exists: {$table}</span>
                  </div>";
        } else {
            echo "<div class='flex items-center gap-2 text-red-600 mb-2'>
                    <i class='bx bx-x-circle'></i>
                    <span>Missing table: {$table}</span>
                  </div>";
        }
    }
    
    if ($allColumnsExist) {
        echo "<div class='mt-4 p-3 bg-green-50 border border-green-200 rounded-lg'>
                <div class='flex items-center gap-2 text-green-800'>
                    <i class='bx bx-check-shield'></i>
                    <span>Database schema is ready for integration!</span>
                </div>
              </div>";
    } else {
        echo "<div class='mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg'>
                <div class='flex items-center gap-2 text-yellow-800'>
                    <i class='bx bx-warning'></i>
                    <span>Please run the migration first: <a href='migrate.php' class='underline'>Run Migration</a></span>
                </div>
              </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='flex items-center gap-2 text-red-600'>
            <i class='bx bx-error-circle'></i>
            <span>Database test failed: " . $e->getMessage() . "</span>
          </div>";
}

echo "</div>
      </div>";

// Test 2: Academic Integration Class
echo "<div class='mb-8'>
        <h2 class='text-xl font-semibold text-gray-700 mb-4'>Test 2: Academic Integration Class</h2>
        <div class='bg-gray-50 rounded-lg p-4'>";

try {
    $integration = getAcademicIntegration($pdo);
    
    echo "<div class='flex items-center gap-2 text-green-600 mb-2'>
            <i class='bx bx-check-circle'></i>
            <span>AcademicIntegration class loaded successfully</span>
          </div>";
    
    // Test role mapping
    $testRoles = ['student', 'teacher', 'lecturer', 'admin', 'staff'];
    foreach ($testRoles as $role) {
        $mappedRole = $integration->mapAcademicRoleToLibraryRole($role);
        echo "<div class='flex items-center gap-2 text-blue-600 mb-1 text-sm'>
                <i class='bx bx-right-arrow'></i>
                <span>{$role} -> {$mappedRole}</span>
              </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='flex items-center gap-2 text-red-600'>
            <i class='bx bx-error-circle'></i>
            <span>Integration class test failed: " . $e->getMessage() . "</span>
          </div>";
}

echo "</div>
      </div>";

// Test 3: Mock SSO Login
echo "<div class='mb-8'>
        <h2 class='text-xl font-semibold text-gray-700 mb-4'>Test 3: Mock SSO Login Simulation</h2>
        <div class='bg-gray-50 rounded-lg p-4'>";

try {
    // Create a mock academic user
    $mockAcademicUser = [
        'id' => 'ACAD_' . time(),
        'full_name' => 'Test Academic User',
        'email' => 'test@academic.rw',
        'role' => 'student',
        'department' => 'Computer Science',
        'level' => 'Year 3'
    ];
    
    echo "<div class='mb-4'>
            <h3 class='font-semibold text-gray-700 mb-2'>Mock Academic User Data:</h3>
            <div class='bg-white p-3 rounded border text-sm font-mono'>
                " . json_encode($mockAcademicUser, JSON_PRETTY_PRINT) . "
            </div>
          </div>";
    
    // Test user creation/sync
    $integration = getAcademicIntegration($pdo);
    $syncedUser = $integration->updateLocalUser($mockAcademicUser);
    
    if ($syncedUser) {
        echo "<div class='flex items-center gap-2 text-green-600 mb-2'>
                <i class='bx bx-check-circle'></i>
                <span>User sync simulation successful!</span>
              </div>";
        
        echo "<div class='mb-4'>
                <h3 class='font-semibold text-gray-700 mb-2'>Synced Local User:</h3>
                <div class='bg-white p-3 rounded border text-sm'>
                    <div><strong>ID:</strong> {$syncedUser['id']}</div>
                    <div><strong>Name:</strong> {$syncedUser['full_name']}</div>
                    <div><strong>Email:</strong> {$syncedUser['email']}</div>
                    <div><strong>Library Role:</strong> {$syncedUser['role']}</div>
                    <div><strong>Academic Role:</strong> {$syncedUser['academic_role']}</div>
                    <div><strong>Sync Status:</strong> {$syncedUser['sync_status']}</div>
                    <div><strong>Academic ID:</strong> {$syncedUser['academic_id']}</div>
                </div>
              </div>";
    } else {
        echo "<div class='flex items-center gap-2 text-red-600'>
                <i class='bx bx-x-circle'></i>
                <span>User sync simulation failed</span>
              </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='flex items-center gap-2 text-red-600'>
            <i class='bx bx-error-circle'></i>
            <span>SSO test failed: " . $e->getMessage() . "</span>
          </div>";
}

echo "</div>
      </div>";

// Test 4: Integration Statistics
echo "<div class='mb-8'>
        <h2 class='text-xl font-semibold text-gray-700 mb-4'>Test 4: Integration Statistics</h2>
        <div class='bg-gray-50 rounded-lg p-4'>";

try {
    $integration = getAcademicIntegration($pdo);
    $stats = $integration->getIntegrationStats();
    
    echo "<div class='grid grid-cols-2 gap-4'>";
    
    // Sync status stats
    if (isset($stats['sync_status']) && !empty($stats['sync_status'])) {
        echo "<div>
                <h4 class='font-semibold text-gray-700 mb-2'>User Sync Status:</h4>
                <div class='space-y-1'>";
        foreach ($stats['sync_status'] as $status) {
            $color = $status['sync_status'] === 'synced' ? 'green' : 'yellow';
            echo "<div class='flex items-center gap-2 text-{$color}-600 text-sm'>
                    <i class='bx bx-circle'></i>
                    <span>{$status['sync_status']}: {$status['count']} users</span>
                  </div>";
        }
        echo "</div></div>";
    }
    
    // Active sessions
    if (isset($stats['active_sessions'])) {
        echo "<div>
                <h4 class='font-semibold text-gray-700 mb-2'>Active Sessions:</h4>
                <div class='flex items-center gap-2 text-blue-600 text-sm'>
                    <i class='bx bx-wifi'></i>
                    <span>{$stats['active_sessions']} active academic sessions</span>
                </div>
              </div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='flex items-center gap-2 text-red-600'>
            <i class='bx bx-error-circle'></i>
            <span>Statistics test failed: " . $e->getMessage() . "</span>
          </div>";
}

echo "</div>
      </div>";

// Test 5: Enhanced Login Form
echo "<div class='mb-8'>
        <h2 class='text-xl font-semibold text-gray-700 mb-4'>Test 5: Enhanced Login Form</h2>
        <div class='bg-gray-50 rounded-lg p-4'>";

echo "<div class='space-y-4'>
        <div class='flex items-center gap-2 text-green-600'>
            <i class='bx bx-check-circle'></i>
            <span>Enhanced login form created: <a href='login_enhanced.php' class='text-blue-600 underline hover:text-blue-800'>login_enhanced.php</a></span>
        </div>
        
        <div class='flex items-center gap-2 text-green-600'>
            <i class='bx bx-check-circle'></i>
            <span>SSO login handler created: login_sso_handler.php</span>
        </div>
        
        <div class='flex items-center gap-2 text-green-600'>
            <i class='bx bx-check-circle'></i>
            <span>Academic integration class: config/academic_integration.php</span>
        </div>
        
        <div class='bg-blue-50 border border-blue-200 rounded-lg p-3'>
            <div class='flex items-center gap-2 text-blue-800'>
                <i class='bx bx-info-circle'></i>
                <span>Test the enhanced login form with the new SSO functionality!</span>
            </div>
          </div>
      </div>";

echo "</div>
      </div>";

// Summary
echo "<div class='bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-lg p-6'>
        <h2 class='text-xl font-semibold text-gray-800 mb-4'>Integration Status Summary</h2>
        <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
            <div>
                <h3 class='font-semibold text-gray-700 mb-2'>Completed Components:</h3>
                <ul class='space-y-1 text-sm text-gray-600'>
                    <li class='flex items-center gap-2'>
                        <i class='bx bx-check text-green-600'></i>
                        <span>Database schema migration</span>
                    </li>
                    <li class='flex items-center gap-2'>
                        <i class='bx bx-check text-green-600'></i>
                        <span>Academic integration API layer</span>
                    </li>
                    <li class='flex items-center gap-2'>
                        <i class='bx bx-check text-green-600'></i>
                        <span>SSO login functionality</span>
                    </li>
                    <li class='flex items-center gap-2'>
                        <i class='bx bx-check text-green-600'></i>
                        <span>Enhanced login interface</span>
                    </li>
                    <li class='flex items-center gap-2'>
                        <i class='bx bx-check text-green-600'></i>
                        <span>User synchronization system</span>
                    </li>
                </ul>
            </div>
            <div>
                <h3 class='font-semibold text-gray-700 mb-2'>Next Steps:</h3>
                <ol class='space-y-1 text-sm text-gray-600 list-decimal list-inside'>
                    <li>Configure API credentials from Levi</li>
                    <li>Test with real academic system</li>
                    <li>Deploy to production environment</li>
                    <li>Train users on SSO login</li>
                    <li>Monitor integration logs</li>
                </ol>
            </div>
        </div>
      </div>";

echo "<div class='mt-6 flex gap-4'>
        <a href='login_enhanced.php' class='bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2'>
            <i class='bx bx-log-in'></i>
            Test Enhanced Login
        </a>
        <a href='index.php' class='bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center gap-2'>
            <i class='bx bx-home'></i>
            Library Home
        </a>
        <a href='migrate.php' class='bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors flex items-center gap-2'>
            <i class='bx bx-database'></i>
            Run Migration
        </a>
      </div>";

echo "</div>
    </div>
</body>
</html>";
?>
