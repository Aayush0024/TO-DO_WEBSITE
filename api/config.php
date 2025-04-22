<?php
// Start session for all API requests
session_start();

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'todo_app');

// Error reporting (only in development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set timezone
date_default_timezone_set('UTC');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to MySQL. " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (!mysqli_query($conn, $sql)) {
    die("ERROR: Could not create database. " . mysqli_error($conn));
}

// Select the database
if (!mysqli_select_db($conn, DB_NAME)) {
    die("ERROR: Could not select database. " . mysqli_error($conn));
}

// Set charset to ensure proper encoding
if (!mysqli_set_charset($conn, "utf8mb4")) {
    die("ERROR: Could not set charset. " . mysqli_error($conn));
}

// Create users table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!mysqli_query($conn, $sql)) {
    // Log the error but don't die
    error_log("Warning: Could not create users table. " . mysqli_error($conn));
}

// Create tasks table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS tasks (
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

if (!mysqli_query($conn, $sql)) {
    // Log the error but don't die
    error_log("Warning: Could not create tasks table. " . mysqli_error($conn));
}

// Function to safely close database connection
function closeConnection() {
    global $conn;
    if($conn) {
        mysqli_close($conn);
        $conn = null;
    }
}

// Function to sanitize input
function sanitizeInput($data) {
    global $conn;
    if (is_string($data)) {
        return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
    }
    return $data;
}

// Function to handle database errors
function handleDBError($error) {
    error_log("Database Error: " . $error);
    return json_encode([
        "status" => "error",
        "message" => "An error occurred. Please try again later."
    ]);
}
?> 