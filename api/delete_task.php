<?php
require_once 'config.php';
header('Content-Type: application/json');

try {
    // Get and validate task_id
    $taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($taskId <= 0) {
        throw new Exception("Invalid task ID");
    }

    // Delete the task
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $taskId);
    
    if (!$stmt->execute()) {
        throw new Exception("Error deleting task: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("Task not found");
    }

    // Return success response
    echo json_encode([
        "status" => "success",
        "message" => "Task deleted successfully"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    closeConnection();
}
?> 