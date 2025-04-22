<?php
require_once 'config.php';

try {
    // Drop existing tables
    $conn->query("DROP TABLE IF EXISTS tasks");
    $conn->query("DROP TABLE IF EXISTS users");
    
    // Create users table
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "✓ Users table created successfully\n";
    } else {
        throw new Exception("Error creating users table: " . $conn->error);
    }
    
    // Create tasks table
    $sql = "CREATE TABLE tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        task VARCHAR(255) NOT NULL,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        due_date DATE,
        completed BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($sql)) {
        echo "✓ Tasks table created successfully\n";
    } else {
        throw new Exception("Error creating tasks table: " . $conn->error);
    }
    
    echo "\n✅ Database reset completed successfully!";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} finally {
    closeConnection();
}
?> 