<?php
// upgrade_library.php
require 'config/db.php';

try {
    // 1. Upgrade the borrowings table to handle Pending requests and Fines
    $pdo->exec("ALTER TABLE borrowings 
                MODIFY COLUMN status ENUM('Pending', 'Issued', 'Returned', 'Overdue', 'Rejected') DEFAULT 'Pending'");
    
    // Add fine tracking (Using INT for RWF)
    $pdo->exec("ALTER TABLE borrowings ADD COLUMN IF NOT EXISTS fine_amount INT DEFAULT 0 AFTER status");
    
    // 2. Upgrade the users table to handle Library Suspensions and Total Fines
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS library_status ENUM('active', 'suspended') DEFAULT 'active' AFTER status");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS total_fines INT DEFAULT 0 AFTER library_status");

    echo "<h2 style='color: #27ae60; font-family: sans-serif;'>✅ Database Upgraded Successfully!</h2>";
    echo "<p style='font-family: sans-serif;'>Your system is now ready for Reservations, Fines, and Suspensions.</p>";
    echo "<a href='student/catalog.php' style='font-family: sans-serif; font-weight: bold;'>Go to Student Catalog</a>";

} catch(PDOException $e) {
    echo "<h2 style='color: #e74c3c;'>❌ Error Upgrading Database: " . $e->getMessage() . "</h2>";
}
?>