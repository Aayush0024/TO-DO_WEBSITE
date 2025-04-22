<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Get JSON input
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($data['id'])) {
        throw new Exception("Task ID is required");
    }

    // Sanitize input
    $taskId = (int)$data['id'];
    $completed = isset($data['completed']) ? (bool)$data['completed'] : null;

    // First check if task exists
    $check_sql = "SELECT completed FROM tasks WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    
    if (!$check_stmt) {
        throw new Exception(mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($check_stmt, "i", $taskId);
    
    if (!mysqli_stmt_execute($check_stmt)) {
        throw new Exception(mysqli_stmt_error($check_stmt));
    }
    
    $result = mysqli_stmt_get_result($check_stmt);
    
    if (!$row = mysqli_fetch_assoc($result)) {
        throw new Exception("Task not found");
    }
    
    // Toggle completion status if not explicitly set
    if ($completed === null) {
        $completed = !$row['completed'];
    }
    
    // Update the task
    $sql = "UPDATE tasks SET completed = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "ii", $completed, $taskId);
    
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_stmt_error($stmt));
    }

    // Return success response
    echo json_encode([
        "status" => "success",
        "message" => "Task updated successfully",
        "task" => [
            "id" => $taskId,
            "completed" => $completed
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($check_stmt)) {
        mysqli_stmt_close($check_stmt);
    }
    closeConnection();
}
?> 