<?php
/**
 * Web-based Migration Runner
 * Access this file in your browser: http://localhost/library/migrate.php
 */

require 'config/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>NGA Library - Database Migration</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body class='bg-gray-50 p-8'>
    <div class='max-w-4xl mx-auto'>
        <div class='bg-white rounded-lg shadow-lg p-6'>
            <h1 class='text-3xl font-bold text-gray-800 mb-6 flex items-center gap-3'>
                <i class='bx bx-database text-blue-600'></i>
                NGA Library Database Migration
            </h1>";

echo "<div class='bg-blue-50 border-l-4 border-blue-500 p-4 mb-6'>
        <p class='text-blue-700'><strong>Migration:</strong> Adding Academic System Integration Fields</p>
        <p class='text-blue-600 text-sm mt-1'>This will add fields necessary for SSO integration with Levi's academic system.</p>
      </div>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/migrations/001_add_academic_integration_fields.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: " . $migrationFile);
    }
    
    $migrationSQL = file_get_contents($migrationFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
    
    echo "<div class='space-y-2 mb-6'>";
    echo "<h3 class='text-lg font-semibold text-gray-700 mb-3'>Executing Migration Statements:</h3>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty($statement) || preg_match('/^--/', $statement)) {
            continue; // Skip comments and empty statements
        }
        
        try {
            $pdo->exec($statement);
            echo "<div class='flex items-center gap-2 text-green-600'>
                    <i class='bx bx-check-circle'></i>
                    <span class='font-mono text-sm'>" . substr($statement, 0, 80) . "...</span>
                  </div>";
            $successCount++;
        } catch (PDOException $e) {
            // Check if it's a "duplicate column" error (which is OK if re-running)
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
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
                    <li>Next: Build API integration layer</li>
                    <li>Then: Implement SSO login modification</li>
                    <li>Finally: Test integration with academic system</li>
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
        <a href='index.php' class='bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2'>
            <i class='bx bx-home'></i>
            Return to Library System
        </a>
        <a href='login.php' class='bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors flex items-center gap-2'>
            <i class='bx bx-log-in'></i>
            Go to Login
        </a>
      </div>";

echo "</div>
    </div>
</body>
</html>";
?>
