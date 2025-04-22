<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Get and validate user_id
    $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    
    if ($userId <= 0) {
        throw new Exception("Invalid user ID");
    }

    // Validate user exists
    $check_sql = "SELECT id FROM users WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    
    if (!$check_stmt) {
        throw new Exception(mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $userId);
    
    if (!mysqli_stmt_execute($check_stmt)) {
        throw new Exception(mysqli_stmt_error($check_stmt));
    }
    
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception("Invalid user");
    }
    mysqli_stmt_close($check_stmt);
    $check_stmt = null; // Set to null after closing

    // Get tasks for user
    $sql = "SELECT id, task, priority, due_date, completed, created_at, updated_at 
            FROM tasks 
            WHERE user_id = ? 
            ORDER BY created_at DESC";
            
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $userId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    $stmt = null; // Set to null after closing
    
    $tasks = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tasks[] = [
            'id' => $row['id'],
            'task' => $row['task'],
            'priority' => $row['priority'],
            'due_date' => $row['due_date'],
            'completed' => (bool)$row['completed'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    // Return success response
    echo json_encode([
        "status" => "success",
        "tasks" => $tasks
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    // Only close statements if they still exist and haven't been closed
    if (isset($stmt) && $stmt !== null) {
        mysqli_stmt_close($stmt);
    }
    if (isset($check_stmt) && $check_stmt !== null) {
        mysqli_stmt_close($check_stmt);
    }
    closeConnection();
} 