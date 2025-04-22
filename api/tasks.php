<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once 'config.php';

try {
    // Get all tasks
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT * FROM tasks ORDER BY created_at DESC";
        $result = mysqli_query($conn, $sql);
        
        if (!$result) {
            throw new Exception(mysqli_error($conn));
        }
        
        $tasks = array();
        while($row = mysqli_fetch_assoc($result)) {
            $row['completed'] = (bool)$row['completed'];
            $tasks[] = $row;
        }
        
        echo json_encode($tasks);
    }

    // Create new task
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$data || !isset($data->task) || !isset($data->priority)) {
            throw new Exception("Task and priority are required");
        }
        
        $task = sanitizeInput($data->task);
        $priority = sanitizeInput($data->priority);
        $dueDate = !empty($data->dueDate) ? sanitizeInput($data->dueDate) : null;
        
        $sql = "INSERT INTO tasks (task, priority, due_date) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "sss", $task, $priority, $dueDate);
        
        if(!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        
        $newId = mysqli_insert_id($conn);
        
        echo json_encode([
            "status" => "success",
            "message" => "Task created successfully",
            "task" => [
                "id" => $newId,
                "task" => $task,
                "priority" => $priority,
                "due_date" => $dueDate,
                "completed" => false
            ]
        ]);
    }

    // Update task completion status
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$data || !isset($data->id)) {
            throw new Exception("Task ID is required");
        }
        
        $id = intval($data->id);
        $completed = isset($data->completed) ? ($data->completed ? 1 : 0) : 0;
        
        // First check if task exists and get current status
        $check_sql = "SELECT completed FROM tasks WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if (!$check_stmt) {
            throw new Exception(mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($check_stmt, "i", $id);
        
        if (!mysqli_stmt_execute($check_stmt)) {
            throw new Exception(mysqli_stmt_error($check_stmt));
        }
        
        $result = mysqli_stmt_get_result($check_stmt);
        
        if (!$row = mysqli_fetch_assoc($result)) {
            throw new Exception("Task not found");
        }
        
        // Toggle completion status
        $new_status = $row['completed'] ? 0 : 1;
        
        // Update the task
        $sql = "UPDATE tasks SET completed = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "ii", $new_status, $id);
        
        if(!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        
        echo json_encode([
            "status" => "success",
            "message" => "Task updated successfully",
            "task" => [
                "id" => $id,
                "completed" => (bool)$new_status
            ]
        ]);
    }

    // Delete task
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id'])) {
            throw new Exception("Task ID is required");
        }
        
        $id = intval($_GET['id']);
        
        $sql = "DELETE FROM tasks WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            throw new Exception(mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, "i", $id);
        
        if(!mysqli_stmt_execute($stmt)) {
            throw new Exception(mysqli_stmt_error($stmt));
        }
        
        if (mysqli_affected_rows($conn) === 0) {
            throw new Exception("Task not found");
        }
        
        echo json_encode([
            "status" => "success",
            "message" => "Task deleted successfully",
            "id" => $id
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    closeConnection();
}
?> 