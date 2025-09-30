<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/db.php';

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['action'])) {
            throw new Exception('Action is required');
        }
        
        switch ($input['action']) {
            case 'create_task':
                if (!isset($input['title'], $input['description'], $input['priority'], $input['due_date'])) {
                    throw new Exception('Title, description, priority, and due date are required');
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO tasks (title, description, assigned_to, assigned_by, priority, due_date, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->bind_param(
                    "ssiiss", 
                    $input['title'], 
                    $input['description'], 
                    $input['assigned_to'], 
                    $input['assigned_by'], 
                    $input['priority'], 
                    $input['due_date']
                );
                
                if ($stmt->execute()) {
                    // Log the task creation
                    $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
                    $user_id = $input['assigned_by'] ?? 0;
                    $details = "Task created: " . $input['title'];
                    $action = 'create_task';
                    $log_stmt->bind_param("iss", $user_id, $action, $details);
                    $log_stmt->execute();
                    
                    $response['success'] = true;
                    $response['message'] = 'Task created successfully';
                    $response['data'] = ['task_id' => $conn->insert_id];
                } else {
                    throw new Exception('Failed to create task');
                }
                break;
                
            case 'update_status':
                if (!isset($input['task_id'], $input['new_status'])) {
                    throw new Exception('Task ID and new status are required');
                }

                // If status is completed, require remarks
                if ($input['new_status'] === 'completed') {
                    if (empty($input['remarks'])) {
                        throw new Exception('Remarks are required when completing a task');
                    }
                    $stmt = $conn->prepare("
                        UPDATE tasks 
                        SET status = ?, remarks = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("ssi", $input['new_status'], $input['remarks'], $input['task_id']);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE tasks 
                        SET status = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->bind_param("si", $input['new_status'], $input['task_id']);
                }
                
                if ($stmt->execute()) {
                    // Log the status change
                   // $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action, details) VALUES (?, ?, ?)");
                   // $user_id = $input['user_id'] ?? 0;
                   // $details = "Task #" . $input['task_id'] . " status changed to: " . $input['new_status'];
                   // if (!empty($input['remarks'])) {
                   //     $details .= " | Remarks: " . $input['remarks'];
                   // }
                    $action = 'update_status';
                    //$log_stmt->bind_param("iss", $user_id, $action, $details);
                    //$log_stmt->execute();

                    $response['success'] = true;
                    $response['message'] = 'Task status updated successfully';
                } else {
                    throw new Exception('Failed to update task status');
                }
                break;
                
            case 'get_tasks':
                $status = $input['status'] ?? null;
                $user_id = $input['user_id'] ?? null;
                
                $where_conditions = [];
                $params = [];
                $types = '';
                
                if ($status) {
                    $where_conditions[] = "t.status = ?";
                    $params[] = $status;
                    $types .= 's';
                }
                
                if ($user_id) {
                    $where_conditions[] = "t.assigned_to = ?";
                    $params[] = $user_id;
                    $types .= 'i';
                }
                
                $where_clause = '';
                if (!empty($where_conditions)) {
                    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
                }
                
                $query = "
                    SELECT 
                        t.*, 
                        u.full_name AS assigned_to_name, 
                        u2.full_name AS assigned_by_name
                    FROM tasks t
                    LEFT JOIN users u ON t.assigned_to = u.id
                    LEFT JOIN users u2 ON t.assigned_by = u2.id
                    $where_clause
                    ORDER BY t.priority DESC, t.created_at ASC
                ";
                
                $stmt = $conn->prepare($query);
                if (!empty($params)) {
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                
                $tasks = [];
                while ($row = $result->fetch_assoc()) {
                    $tasks[] = $row;
                }
                
                $response['success'] = true;
                $response['data'] = $tasks;
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } else {
        throw new Exception('Only POST method is allowed');
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
?>
