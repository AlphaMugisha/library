<?php
// upgrade_activation.php
require 'config/db.php';

try {
    // Add activation columns
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_activated TINYINT(1) DEFAULT 1 AFTER library_status");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS activation_token VARCHAR(100) NULL AFTER is_activated");

    // Set existing users to activated so you don't get locked out of your current accounts!
    $pdo->exec("UPDATE users SET is_activated = 1 WHERE is_activated IS NULL");

    echo "<h2 style='color: #27ae60; font-family: sans-serif;'>✅ Database Upgraded for Account Activation!</h2>";
} catch(PDOException $e) {
    echo "<h2 style='color: #e74c3c;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>