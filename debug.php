<?php
require 'config/db.php';

echo "<h2>RAW DATABASE DUMP: Borrowings Table</h2>";

try {
    $stmt = $pdo->query("SELECT * FROM borrowings ORDER BY id DESC LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if($data) {
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; font-family: monospace;'>";
        echo "<tr style='background: #f1f1f1;'>";
        // Get column names
        foreach($data[0] as $key => $value) { echo "<th>$key</th>"; }
        echo "</tr>";
        
        foreach($data as $row) {
            echo "<tr>";
            foreach($row as $val) { 
                // Highlight the status so we can see if it actually says 'Pending'
                if($val === 'Pending') {
                    echo "<td style='background: yellow; font-weight: bold;'>$val</td>"; 
                } else {
                    echo "<td>" . htmlspecialchars((string)$val) . "</td>"; 
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h3>Table is completely empty.</h3>";
    }
} catch(PDOException $e) {
    echo "<h3>Error: " . $e->getMessage() . "</h3>";
}
?>