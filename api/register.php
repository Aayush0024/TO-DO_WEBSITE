<?php
// Disable error display to prevent HTML output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config.php';

// Set JSON header
header('Content-Type: application/json');

try {
    // Get and sanitize input data
    $fullName = sanitizeInput($_POST['fullName'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validate input
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        throw new Exception("All fields are required");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    if ($password !== $confirmPassword) {
        throw new Exception("Passwords do not match");
    }

    if (strlen($password) < 8) {
        throw new Exception("Password must be at least 8 characters long");
    }

    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$checkStmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $checkStmt->close();
        throw new Exception("Email already registered");
    }
    $checkStmt->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $insertStmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
    if (!$insertStmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $insertStmt->bind_param("sss", $fullName, $email, $hashedPassword);
    
    if (!$insertStmt->execute()) {
        $error = $insertStmt->error;
        $insertStmt->close();
        throw new Exception("Error registering user: " . $error);
    }

    $insertStmt->close();

    // Return success response
    echo json_encode([
        "status" => "success",
        "message" => "Registration successful"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "An internal server error occurred"
    ]);
    error_log("Registration Error: " . $e->getMessage());
} finally {
    closeConnection();
}
?> 