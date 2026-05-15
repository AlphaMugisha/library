<?php
/**
 * Fixed Database Migration Runner
 * Executes statements in correct order to avoid dependency issues
 */

require 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>NGA Library - Fixed Database Migration</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class='bg-gray-50 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6 flex items-center gap-3'>
                <i class='bx bx-database text-green-600'></i>
                NGA Library Fixed Database Migration
            </h1>";

echo "<div class='bg-green-50 border-l-4 border-green-500 p-4 mb-6'>
        <p class='text-green-700'><strong>Fixed Migration:</strong> Adding Academic System Integration Fields in Correct Order</p>
        <p class='text-green-600 text-sm mt-1'>This will add fields necessary for SSO integration with proper dependency order.</p>
      </div>";

try {
    echo "<div class='space-y-2 mb-6'>";
    echo "<h3 class='text-lg font-semibold text-gray-700 mb-3'>Executing Fixed Migration Statements:</h3>";
    
    $successCount = 0;
    $errorCount = 0;
    
    // Step 1: Add columns to users table first
    $columnAdditions = [
        "ALTER TABLE `users` ADD COLUMN `academic_id` VARCHAR(50) UNIQUE AFTER `id`",
        "ALTER TABLE `users` ADD COLUMN `sync_status` ENUM('synced', 'pending', 'error', 'manual') DEFAULT 'pending' AFTER `two_factor_expires`",
        "ALTER TABLE `users` ADD COLUMN `last_sync` TIMESTAMP NULL DEFAULT NULL AFTER `sync_status`",
        "ALTER TABLE `users` ADD COLUMN `academic_role` VARCHAR(50) NULL AFTER `last_sync`",
        "ALTER TABLE `users` ADD COLUMN `sync_error_message` TEXT NULL AFTER `academic_role`",
        "ALTER TABLE `users` ADD COLUMN `academic_department` VARCHAR(100) NULL AFTER `sync_error_message`",
        "ALTER TABLE `users` ADD COLUMN `academic_level` VARCHAR(50) NULL AFTER `academic_department`"
    ];
    
    echo "<h4 class='font-semibold text-gray-600 mb-2'>Step 1: Adding Columns to Users Table</h4>";
    
    foreach ($columnAdditions as $index => $statement) {
        try {
            $pdo->exec($statement);
            echo "<div class='flex items-center gap-2 text-green-600'>
                    <i class='bx bx-check-circle'></i>
                    <span class='font-mono text-sm'>" . substr($statement, 0, 80) . "...</span>
                  </div>";
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "<div class='flex items-center gap-2 text-yellow-600'>
                        <i class='bx bx-info-circle'></i>
                        <span class='font-mono text-sm'>Column already exists - skipped</span>
                      </div>";
                $successCount++;
            } else {
                echo "<div class='flex items-center gap-2 text-red-600'>
                        <i class='bx bx-error-circle'></i>
                        <span class='font-mono text-sm'>" . $e->getMessage() . "</span>
                      </div>";
                $errorCount++;
            }
        }
    }
    
    // Step 2: Create tables (without foreign keys first)
    echo "<h4 class='font-semibold text-gray-600 mb-2 mt-4'>Step 2: Creating New Tables</h4>";
    
    $tableCreations = [
        "CREATE TABLE IF NOT EXISTS `integration_logs` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `action` enum('sync', 'create', 'update', 'login', 'error') NOT NULL,
          `academic_id` varchar(50) DEFAULT NULL,
          `status` enum('success', 'error', 'pending') NOT NULL,
          `message` text DEFAULT NULL,
          `data_sent` json DEFAULT NULL,
          `response_received` json DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_academic_id` (`academic_id`),
          KEY `idx_action` (`action`),
          KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
        
        "CREATE TABLE IF NOT EXISTS `academic_sessions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `academic_token` varchar(500) NOT NULL,
          `token_expires` datetime NOT NULL,
          `session_data` json DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `last_used` timestamp NULL DEFAULT NULL,
          `is_active` tinyint(1) DEFAULT 1,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_academic_token` (`academic_token`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_token_expires` (`token_expires`),
          KEY `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    ];
    
    foreach ($tableCreations as $index => $statement) {
        try {
            $pdo->exec($statement);
            echo "<div class='flex items-center gap-2 text-green-600'>
                    <i class='bx bx-check-circle'></i>
                    <span class='font-mono text-sm'>Created table successfully</span>
                  </div>";
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<div class='flex items-center gap-2 text-yellow-600'>
                        <i class='bx bx-info-circle'></i>
                        <span class='font-mono text-sm'>Table already exists - skipped</span>
                      </div>";
                $successCount++;
            } else {
                echo "<div class='flex items-center gap-2 text-red-600'>
                        <i class='bx bx-error-circle'></i>
                        <span class='font-mono text-sm'>" . $e->getMessage() . "</span>
                      </div>";
                $errorCount++;
            }
        }
    }
    
    // Step 3: Add indexes to users table
    echo "<h4 class='font-semibold text-gray-600 mb-2 mt-4'>Step 3: Adding Indexes</h4>";
    
    $indexAdditions = [
        "CREATE INDEX `idx_academic_id` ON `users` (`academic_id`)",
        "CREATE INDEX `idx_sync_status` ON `users` (`sync_status`)",
        "CREATE INDEX `idx_last_sync` ON `users` (`last_sync`)"
    ];
    
    foreach ($indexAdditions as $index => $statement) {
        try {
            $pdo->exec($statement);
            echo "<div class='flex items-center gap-2 text-green-600'>
                    <i class='bx bx-check-circle'></i>
                    <span class='font-mono text-sm'>" . substr($statement, 0, 80) . "...</span>
                  </div>";
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='flex items-center gap-2 text-yellow-600'>
                        <i class='bx bx-info-circle'></i>
                        <span class='font-mono text-sm'>Index already exists - skipped</span>
                      </div>";
                $successCount++;
            } else {
                echo "<div class='flex items-center gap-2 text-red-600'>
                        <i class='bx bx-error-circle'></i>
                        <span class='font-mono text-sm'>" . $e->getMessage() . "</span>
                      </div>";
                $errorCount++;
            }
        }
    }
    
    // Step 4: Add foreign keys (last)
    echo "<h4 class='font-semibold text-gray-600 mb-2 mt-4'>Step 4: Adding Foreign Keys</h4>";
    
    $foreignKeys = [
        "ALTER TABLE `integration_logs` ADD CONSTRAINT `fk_integration_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE",
        "ALTER TABLE `academic_sessions` ADD CONSTRAINT `fk_academic_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE"
    ];
    
    foreach ($foreignKeys as $index => $statement) {
        try {
            $pdo->exec($statement);
            echo "<div class='flex items-center gap-2 text-green-600'>
                    <i class='bx bx-check-circle'></i>
                    <span class='font-mono text-sm'>Added foreign key successfully</span>
                  </div>";
            $successCount++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "<div class='flex items-center gap-2 text-yellow-600'>
                        <i class='bx bx-info-circle'></i>
                        <span class='font-mono text-sm'>Foreign key already exists - skipped</span>
                      </div>";
                $successCount++;
            } else {
                echo "<div class='flex items-center gap-2 text-red-600'>
                        <i class='bx bx-error-circle'></i>
                        <span class='font-mono text-sm'>" . $e->getMessage() . "</span>
                      </div>";
                $errorCount++;
            }
        }
    }
    
    // Step 5: Update existing users
    echo "<h4 class='font-semibold text-gray-600 mb-2 mt-4'>Step 5: Updating Existing Users</h4>";
    
    try {
        $updateStmt = $pdo->query("UPDATE `users` SET `sync_status` = 'manual' WHERE `academic_id` IS NULL");
        $affectedRows = $updateStmt->rowCount();
        echo "<div class='flex items-center gap-2 text-green-600'>
                <i class='bx bx-check-circle'></i>
                <span class='font-mono text-sm'>Updated {$affectedRows} existing users to manual sync status</span>
              </div>";
        $successCount++;
    } catch (PDOException $e) {
        echo "<div class='flex items-center gap-2 text-red-600'>
                <i class='bx bx-error-circle'></i>
                <span class='font-mono text-sm'>" . $e->getMessage() . "</span>
              </div>";
        $errorCount++;
    }
    
    echo "</div>";
    
    echo "<div class='bg-gray-50 rounded-lg p-4 mb-6'>
            <h3 class='text-lg font-semibold text-gray-700 mb-3'>Migration Summary:</h3>
            <div class='grid grid-cols-2 gap-4'>
                <div class='text-center p-4 bg-green-50 rounded-lg'>
                    <div class='text-3xl font-bold text-green-600'>{$successCount}</div>
                    <div class='text-green-700'>Successful</div>
                </div>
                <div class='text-center p-4 bg-red-50 rounded-lg'>
                    <div class='text-3xl font-bold text-red-600'>{$errorCount}</div>
                    <div class='text-red-700'>Failed</div>
                </div>
            </div>
          </div>";
    
    if ($errorCount === 0) {
        echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-6'>
                <div class='flex items-center gap-2 text-green-800'>
                    <i class='bx bx-check-shield text-2xl'></i>
                    <div>
                        <strong>Migration completed successfully!</strong><br>
                        <span class='text-green-700'>Your database is now ready for academic system integration.</span>
                    </div>
                </div>
              </div>";
        
        // Verify the new columns exist
        echo "<div class='bg-gray-50 rounded-lg p-4 mb-6'>
                <h3 class='text-lg font-semibold text-gray-700 mb-3'>Verification:</h3>";
        
        try {
            $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            $newColumns = ['academic_id', 'sync_status', 'last_sync', 'academic_role', 'sync_error_message', 'academic_department', 'academic_level'];
            
            echo "<div class='grid grid-cols-2 gap-2 mb-4'>";
            foreach ($newColumns as $column) {
                if (in_array($column, $columns)) {
                    echo "<div class='flex items-center gap-2 text-green-600'>
                            <i class='bx bx-check'></i>
                            <span>{$column}</span>
                          </div>";
                } else {
                    echo "<div class='flex items-center gap-2 text-red-600'>
                            <i class='bx bx-x'></i>
                            <span>{$column}</span>
                          </div>";
                }
            }
            echo "</div>";
            
            // Check if new tables exist
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $newTables = ['integration_logs', 'academic_sessions'];
            
            echo "<h4 class='font-semibold text-gray-700 mb-2'>New Tables:</h4>";
            echo "<div class='grid grid-cols-2 gap-2'>";
            foreach ($newTables as $table) {
                if (in_array($table, $tables)) {
                    echo "<div class='flex items-center gap-2 text-green-600'>
                            <i class='bx bx-check'></i>
                            <span>{$table}</span>
                          </div>";
                } else {
                    echo "<div class='flex items-center gap-2 text-red-600'>
                            <i class='bx bx-x'></i>
                            <span>{$table}</span>
                          </div>";
                }
            }
            echo "</div>";
            
        } catch (PDOException $e) {
            echo "<p class='text-red-600'>Verification failed: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
        echo "<div class='bg-blue-50 border border-blue-200 rounded-lg p-4'>
                <h3 class='text-lg font-semibold text-blue-800 mb-3'>Next Steps:</h3>
                <ol class='list-decimal list-inside text-blue-700 space-y-1'>
                    <li>Database schema updated successfully</li>
                    <li>Test the enhanced login: <a href='login_enhanced.php' class='underline'>login_enhanced.php</a></li>
                    <li>Run integration tests: <a href='test_integration.php' class='underline'>test_integration.php</a></li>
                    <li>Configure API credentials from Levi</li>
                </ol>
              </div>";
        
    } else {
        echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4'>
                <div class='flex items-center gap-2 text-red-800'>
                    <i class='bx bx-error text-2xl'></i>
                    <div>
                        <strong>Migration completed with errors!</strong><br>
                        <span class='text-red-700'>Please review the errors above and fix them manually.</span>
                    </div>
                </div>
              </div>";
    }
    
} catch (Exception $e) {
    echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4'>
            <div class='flex items-center gap-2 text-red-800'>
                <i class='bx bx-error text-2xl'></i>
                <div>
                    <strong>Migration failed:</strong> " . $e->getMessage() . "
                </div>
            </div>
          </div>";
}

echo "<div class='mt-6 flex gap-4'>
        <a href='test_integration.php' class='bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2'>
            <i class='bx bx-test-tube'></i>
            Test Integration
        </a>
        <a href='login_enhanced.php' class='bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2'>
            <i class='bx bx-log-in'></i>
            Try Enhanced Login
        </a>
        <a href='index.php' class='bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center gap-2'>
            <i class='bx bx-home'></i>
            Library Home
        </a>
      </div>";

echo "</div>
    </div>
</body>
</html>";
?>
