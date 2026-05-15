<?php
// setup.php
require 'config/db.php';

try {
    // 1. Create the Users table
    $query = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('librarian', 'student', 'teacher', 'admin') DEFAULT 'student',
        status ENUM('active', 'suspended') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($query);
    echo "✅ Users table created successfully.<br>";

    // 2. Generate the default Librarian account
    $email = 'librarian@nga.rw';
    $plain_password = 'password123';
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
    
    // Check if the user already exists to prevent duplicate errors if run twice
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    
    if ($check->rowCount() == 0) {
        $insert = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $insert->execute(['System Librarian', $email, $hashed_password, 'librarian']);
        
        echo "✅ Default Librarian account created!<br>";
        echo "<strong>Email:</strong> " . $email . "<br>";
        echo "<strong>Password:</strong> " . $plain_password . "<br>";
        echo "<br><a href='login.php'>Click here to go to Login</a>";
    } else {
        echo "⚠️ Librarian account already exists. Go log in!";
    }

} catch (PDOException $e) {
    die("❌ Setup Error: " . $e->getMessage());
}
?>