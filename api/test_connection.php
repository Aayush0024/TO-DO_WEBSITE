<?php
require_once 'config.php';

try {
    // Test database connection
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "✓ Successfully connected to MySQL server\n";
    
    // Test database creation/selection
    if ($conn->select_db(DB_NAME)) {
        echo "✓ Successfully connected to database '" . DB_NAME . "'\n";
    } else {
        // Try to create the database if it doesn't exist
        $sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
        if ($conn->query($sql) === TRUE) {
            echo "✓ Database '" . DB_NAME . "' created successfully\n";
            $conn->select_db(DB_NAME);
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    }
    
    // Test tasks table creation
    $sql = "SHOW TABLES LIKE 'tasks'";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "✓ Table 'tasks' exists\n";
        
        // Check table structure
        $sql = "DESCRIBE tasks";
        $result = $conn->query($sql);
        $columns = [];
        while($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        echo "✓ Table structure verified with columns: " . implode(", ", $columns) . "\n";
    } else {
        // Create the tasks table
        $sql = "CREATE TABLE tasks (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            task VARCHAR(255) NOT NULL,
            priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
            due_date DATE,
            completed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if ($conn->query($sql) === TRUE) {
            echo "✓ Table 'tasks' created successfully\n";
        } else {
            throw new Exception("Error creating table: " . $conn->error);
        }
    }
    
    // Test inserting a sample task
    $sql = "INSERT INTO tasks (task, priority, due_date) VALUES ('Test task', 'medium', DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY))";
    if ($conn->query($sql) === TRUE) {
        echo "✓ Successfully inserted a test task\n";
        
        // Clean up the test task
        $sql = "DELETE FROM tasks WHERE task = 'Test task'";
        $conn->query($sql);
        echo "✓ Successfully cleaned up test data\n";
    } else {
        throw new Exception("Error inserting test task: " . $conn->error);
    }
    
    echo "\n✅ All database tests passed successfully!";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
        echo "\nDatabase connection closed.";
    }
}
?> 