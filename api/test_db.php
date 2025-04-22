<?php
require_once 'config.php';

try {
    // Check if users table exists and its structure
    $result = $conn->query("DESCRIBE users");
    if ($result) {
        echo "Users table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "Column: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
        }
    } else {
        echo "Error checking users table: " . $conn->error . "\n";
    }

    // Check if tasks table exists and its structure
    $result = $conn->query("DESCRIBE tasks");
    if ($result) {
        echo "\nTasks table structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo "Column: " . $row['Field'] . " | Type: " . $row['Type'] . "\n";
        }
    } else {
        echo "Error checking tasks table: " . $conn->error . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    closeConnection();
}
?> 