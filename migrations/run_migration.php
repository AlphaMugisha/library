<?php
/**
 * Migration Runner
 * Safely applies database migrations for the NGA Library System
 * Run this script to update your database schema for academic integration
 */

require '../config/db.php';

echo "<h2>NGA Library System Migration Runner</h2>";
echo "<p>Applying migration: 001_add_academic_integration_fields.sql</p>";

try {
    // Read the migration file
    $migrationFile = __DIR__ . '/001_add_academic_integration_fields.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: " . $migrationFile);
    }
    
    $migrationSQL = file_get_contents($migrationFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
    
    echo "<h3>Executing Migration Statements:</h3>";
    echo "<ol>";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty($statement) || preg_match('/^--/', $statement)) {
            continue; // Skip comments and empty statements
        }
        
        try {
            $pdo->exec($statement);
            echo "<li style='color: green;'>{$index}: SUCCESS - " . substr($statement, 0, 50) . "...</li>";
            $successCount++;
        } catch (PDOException $e) {
            // Check if it's a "duplicate column" error (which is OK if re-running)
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "<li style='color: orange;'>{$index}: SKIPPED - Column already exists</li>";
                $successCount++;
            } else {
                echo "<li style='color: red;'>{$index}: ERROR - " . $e->getMessage() . "</li>";
                $errorCount++;
            }
        }
    }
    
    echo "</ol>";
    
    echo "<h3>Migration Summary:</h3>";
    echo "<p>Successful statements: <strong style='color: green;'>{$successCount}</strong></p>";
    echo "<p>Failed statements: <strong style='color: red;'>{$errorCount}</strong></p>";
    
    if ($errorCount === 0) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>Migration completed successfully!</strong><br>";
        echo "Your database is now ready for academic system integration.";
        echo "</div>";
        
        // Verify the new columns exist
        echo "<h3>Verification:</h3>";
        try {
            $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
            $newColumns = ['academic_id', 'sync_status', 'last_sync', 'academic_role', 'sync_error_message', 'academic_department', 'academic_level'];
            
            echo "<ul>";
            foreach ($newColumns as $column) {
                if (in_array($column, $columns)) {
                    echo "<li style='color: green;'>{$column}: EXISTS</li>";
                } else {
                    echo "<li style='color: red;'>{$column}: MISSING</li>";
                }
            }
            echo "</ul>";
            
            // Check if new tables exist
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            $newTables = ['integration_logs', 'academic_sessions'];
            
            echo "<h4>New Tables:</h4>";
            echo "<ul>";
            foreach ($newTables as $table) {
                if (in_array($table, $tables)) {
                    echo "<li style='color: green;'>{$table}: EXISTS</li>";
                } else {
                    echo "<li style='color: red;'>{$table}: MISSING</li>";
                }
            }
            echo "</ul>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>Verification failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<strong>Migration completed with errors!</strong><br>";
        echo "Please review the errors above and fix them manually.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>Migration failed:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Verify all new columns and tables were created successfully</li>";
echo "<li>Run the API integration setup script (next step)</li>";
echo "<li>Test the SSO login functionality</li>";
echo "<li>Configure connection to Levi's academic system</li>";
echo "</ol>";

echo "<p><a href='../index.php'>Return to Library System</a></p>";
?>
