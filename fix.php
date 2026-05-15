<?php
require 'config/db.php';

try {
    // 1. Drop the old conflicting table
    $pdo->exec("DROP TABLE IF EXISTS borrowings");

    // 2. Create the exact correct table mapping to the users and books tables
    $pdo->exec("CREATE TABLE borrowings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        issue_date DATE NOT NULL,
        due_date DATE NOT NULL,
        return_date DATE NULL,
        status ENUM('Issued', 'Returned', 'Overdue') DEFAULT 'Issued',
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
    )");

    echo "<h2 style='color: green; font-family: sans-serif;'>✅ Database Fixed! The borrowings table is now perfectly synced.</h2>";
    echo "<a href='librarian/transactions.php' style='font-family: sans-serif; font-weight: bold;'>Go back to Transactions</a>";

} catch(PDOException $e) {
    echo "<h2 style='color: red;'>Error: " . $e->getMessage() . "</h2>";
}
?>