<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../includes/db.php';

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON: ' . json_last_error_msg());
    }
    if (!isset($input['action'])) {
        throw new Exception('Action is required');
    }

    switch ($input['action']) {
        /* ================= TASKS ================= */
        case 'create_task':
            if (!isset($input['title'], $input['description'], $input['priority'], $input['due_date'], $input['assigned_to'], $input['assigned_by'])) {
                throw new Exception('Missing required fields for create_task');
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
                $response['success'] = true;
                $response['message'] = 'Task created successfully';
                $response['data'] = ['task_id' => $conn->insert_id];
            } else {
                throw new Exception('Failed to create task: ' . $stmt->error);
            }
            break;

        case 'update_status':
            if (!isset($input['task_id'], $input['new_status'])) {
                throw new Exception('Task ID and new status are required');
            }

            if ($input['new_status'] === 'completed') {
                if (empty($input['remarks'])) {
                    throw new Exception('Remarks are required when completing a task');
                }
                $stmt = $conn->prepare("UPDATE tasks SET status = ?, remarks = ? WHERE id = ?");
                $stmt->bind_param("ssi", $input['new_status'], $input['remarks'], $input['task_id']);
            } else {
                $stmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $input['new_status'], $input['task_id']);
            }

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Task status updated successfully';
            } else {
                throw new Exception('Failed to update task: ' . $stmt->error);
            }
            break;

        case 'get_tasks':
            $status = $input['status'] ?? null;
            $user_id = $input['user_id'] ?? null;

            $where = [];
            $params = [];
            $types = '';

            if ($status) { $where[] = "t.status = ?"; $params[] = $status; $types .= 's'; }
            if ($user_id) { $where[] = "t.assigned_to = ?"; $params[] = $user_id; $types .= 'i'; }

            $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

            $query = "
                SELECT t.*, u.full_name AS assigned_to_name, u2.full_name AS assigned_by_name
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

        /* ================= MAINTENANCE ================= */
        case 'create_maintenance':
            if (!isset($input['equipment_id'], $input['equipment_type'], $input['technician_id'], $input['maintenance_type'])) {
                throw new Exception('Missing required fields for create_maintenance');
            }

            // Optional fields
            $description = $input['description'] ?? '';
            $cost = $input['cost'] ?? 0;
            $start_date = $input['start_date'] ?? date('Y-m-d');
            $end_date = $input['end_date'] ?? date('Y-m-d');

            $stmt = $conn->prepare("
                INSERT INTO maintenance_records 
                    (equipment_id, equipment_type, technician_id, maintenance_type, description, cost, start_date, end_date, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->bind_param(
                "isissdss",
                $input['equipment_id'],
                $input['equipment_type'],
                $input['technician_id'],
                $input['maintenance_type'],
                $description,
                $cost,
                $start_date,
                $end_date
            );

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Maintenance scheduled successfully';
                $response['data'] = ['maintenance_id' => $conn->insert_id];
            } else {
                throw new Exception('Failed to schedule maintenance: ' . $stmt->error);
            }
            break;

        case 'update_maintenance_status':
            if (!isset($input['maintenance_id'], $input['new_status'])) {
                throw new Exception('Maintenance ID and new status are required');
            }

            if ($input['new_status'] === 'completed') {
                if (empty($input['remarks'])) {
                    throw new Exception('Remarks are required when completing maintenance');
                }
                $stmt = $conn->prepare("UPDATE maintenance_records SET status = ?, remarks = ? WHERE id = ?");
                $stmt->bind_param("ssi", $input['new_status'], $input['remarks'], $input['maintenance_id']);
            } else {
                $stmt = $conn->prepare("UPDATE maintenance_records SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $input['new_status'], $input['maintenance_id']);
            }

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Maintenance status updated successfully';
            } else {
                throw new Exception('Failed to update maintenance: ' . $stmt->error);
            }
            break;

        case 'get_maintenance':
            $user_id = $input['user_id'] ?? null;

            $where = '';
            $params = [];
            $types = '';

            if ($user_id) {
                $where = "WHERE mr.technician_id = ?";
                $params[] = $user_id;
                $types = 'i';
            }

            $query = "
                SELECT mr.*, u.full_name AS assigned_to_name
                FROM maintenance_records mr
                LEFT JOIN users u ON mr.technician_id = u.id
                $where
                ORDER BY mr.created_at DESC
            ";

            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $records = [];
            while ($row = $result->fetch_assoc()) {
                if ($row['status'] === 'scheduled') $row['status'] = 'pending';
                $records[] = $row;
            }

            $response['success'] = true;
            $response['data'] = $records;
            break;

        default:
            throw new Exception('Invalid action: ' . $input['action']);
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
