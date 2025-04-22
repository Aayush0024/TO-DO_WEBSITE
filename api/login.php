<?php
require_once 'config.php';
header('Content-Type: application/json');

// Enable error logging
error_log("Login attempt started");

try {
    // Get and sanitize input data
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    error_log("Login attempt for email: " . $email);

    // Validate input
    if (empty($email) || empty($password)) {
        throw new Exception("All fields are required");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Check user credentials
    $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
    if (!$stmt) {
        error_log("Database error in prepare statement: " . $conn->error);
        throw new Exception("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    if (!$stmt->execute()) {
        error_log("Database error in execute: " . $stmt->error);
        throw new Exception("Database error: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    error_log("Query executed. Found rows: " . $result->num_rows);
    
    if ($result->num_rows === 0) {
        $stmt->close();
        throw new Exception("Invalid email or password");
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    error_log("Verifying password for user: " . $user['full_name']);
    if (!password_verify($password, $user['password'])) {
        error_log("Password verification failed");
        throw new Exception("Invalid email or password");
    }
    error_log("Password verified successfully");

    // Remove session_start() as it's already called in config.php
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $email;

    error_log("Session data set. User ID: " . $_SESSION['user_id']);

    // Return success response
    echo json_encode([
        "status" => "success",
        "message" => "Login successful",
        "user" => [
            "id" => $user['id'],
            "full_name" => $user['full_name'],
            "email" => $email
        ]
    ]);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    closeConnection();
}
?> 