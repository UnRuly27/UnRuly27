<?php
// Test database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=final", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Database connection successful!<br>";
    
    // Check if users table exists
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "Users table exists!<br>";
    } else {
        echo "Users table doesn't exist. Run the SQL script first.<br>";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>