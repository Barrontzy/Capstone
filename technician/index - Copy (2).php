<?php
session_start();
require_once '../includes/session.php';
require_once '../includes/db.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'technician') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$page_title = 'Kanban Dashboard';
require_once 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tasks"></i> Task Management</h2>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTaskModal" style="display:none">
                        <i class="fas fa-plus"></i> Add Task
                    </button>
                    <button class="btn btn-outline-primary" onclick="refreshTasks()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                        <label class="form-check-label" for="autoRefresh">Auto Refresh</label>
                    </div>
                </div>
            </div>

            <div id="alert-container"></div>

            <div class="row">
                <!-- Pending Tasks -->
                <div class="col-md-4">
                    <div class="card kanban-column">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-clock"></i> Pending
                                <span class="badge bg-dark ms-2" id="pending-count">0</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="task-list" data-status="pending" id="pending-tasks"></div>
                        </div>
                    </div>
                </div>

                <!-- In Progress Tasks -->
                <div class="col-md-4">
                    <div class="card kanban-column">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-cogs"></i> In Progress
                                <span class="badge bg-light text-dark ms-2" id="in-progress-count">0</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="task-list" data-status="in_progress" id="in-progress-tasks"></div>
                        </div>
                    </div>
                </div>

                <!-- Completed Tasks -->
                <div class="col-md-4">
                    <div class="card kanban-column">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle"></i> Completed
                                <span class="badge bg-light text-dark ms-2" id="completed-count">0</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="task-list" data-status="completed" id="completed-tasks"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addTaskForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Task Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Priority</label>
                            <select class="form-control" name="priority" required>
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Due Date</label>
                            <input type="date" class="form-control" name="due_date" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Complete Task Modal -->
<div class="modal fade" id="completeTaskModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-comment"></i> Complete Task - Add Remarks</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="completeTaskForm">
          <input type="hidden" name="task_id" id="completeTaskId">
          <div class="mb-3">
            <label class="form-label">Remarks</label>
            <textarea class="form-control" name="remarks" id="completeRemarks" rows="3" required></textarea>
          </div>
          <button type="submit" class="btn btn-success">
            <i class="fas fa-check"></i> Confirm Complete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let autoRefreshInterval;
let currentUserId = <?php echo $user_id; ?>;

document.addEventListener('DOMContentLoaded', function() {
    loadAllTasks();
    startAutoRefresh();

    document.getElementById('autoRefresh').addEventListener('change', function() {
        this.checked ? startAutoRefresh() : stopAutoRefresh();
    });

    document.getElementById('addTaskForm').addEventListener('submit', function(e) {
        e.preventDefault();
        createTask();
    });
});

// Create Task
function createTask() {
    const formData = new FormData(document.getElementById('addTaskForm'));
    const taskData = {
        action: 'create_task',
        title: formData.get('title'),
        description: formData.get('description'),
        priority: formData.get('priority'),
        due_date: formData.get('due_date'),
        assigned_to: currentUserId,
        assigned_by: currentUserId
    };
    
    fetch('api/task_webhook.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(taskData)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Task created successfully!', 'success');
            document.getElementById('addTaskForm').reset();
            bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide();
            loadAllTasks();
        } else {
            showAlert('Failed: ' + data.message, 'danger');
        }
    })
    .catch(() => showAlert('Error creating task', 'danger'));
}

// Auto Refresh
function startAutoRefresh() {
    autoRefreshInterval = setInterval(loadAllTasks, 10000);
}
function stopAutoRefresh() {
    clearInterval(autoRefreshInterval);
}

function loadAllTasks() {
    ['pending','in_progress','completed'].forEach(status => {
        // Load tasks
        loadTasksByStatus(status);

        // Load maintenance records
        loadMaintenanceByStatus(status);
    });
}

function loadMaintenanceByStatus(status) {
    fetch('api/task_webhook.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'get_maintenance', user_id: currentUserId})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const records = data.data.filter(r => r.status === status);
            renderMaintenance(status, records);
            updateTaskCount(status, 
                parseInt(document.getElementById(`${status.replace('_','-')}-count`).textContent) 
                + records.length
            );
        }
    })
    .catch(err => console.error(err));
}

function renderMaintenance(status, records) {
    const container = document.getElementById(`${status.replace('_','-')}-tasks`);
    records.forEach(record => {
        const element = createMaintenanceElement(record);
        container.insertAdjacentHTML('beforeend', element);
    });
}

function createMaintenanceElement(record) {
    const startDate = new Date(record.start_date).toLocaleDateString();
    const endDate = new Date(record.end_date).toLocaleDateString();

    return `
    <div class="task-card ${record.status === 'completed' ? 'completed' : ''}" data-maintenance-id="${record.id}">
        <div class="task-header">
            <h6 class="task-title">🔧 ${escapeHtml(record.maintenance_type)}</h6>
            <span class="priority-badge priority-medium">Maintenance</span>
        </div>
        <p class="task-description">${escapeHtml(record.description || '')}</p>
        <div class="task-meta">
            <small class="text-muted">
                <i class="fas fa-user-cog"></i> ${escapeHtml(record.assigned_to_name)}<br>
                <i class="fas fa-calendar"></i> ${startDate} → ${endDate}<br>
                <i class="fas fa-coins"></i> ₱${record.cost || 0}
            </small>
        </div>
    </div>`;
}

function loadTasksByStatus(status) {
    fetch('api/task_webhook.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'get_tasks', status: status, user_id: currentUserId})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderTasks(status, data.data);
            updateTaskCount(status, data.data.length);
        }
    })
    .catch(err => console.error(err));
}

// Render Tasks
function renderTasks(status, tasks) {
    const container = document.getElementById(`${status.replace('_','-')}-tasks`);
    container.innerHTML = tasks.length === 0 
        ? ``
        : tasks.map(task => createTaskElement(task)).join('');
}

function createTaskElement(task) {
    const priorityClass = `priority-${task.priority}`;
    const dueDate = new Date(task.due_date).toLocaleDateString();
    const createdDate = new Date(task.created_at).toLocaleDateString();

    return `
    <div class="task-card ${task.status === 'completed' ? 'completed' : ''}" data-task-id="${task.id}">
        <div class="task-header">
            <h6 class="task-title">${escapeHtml(task.title)}</h6>
            <span class="priority-badge ${priorityClass}">
                ${task.priority.charAt(0).toUpperCase() + task.priority.slice(1)}
            </span>
        </div>
        <p class="task-description">${escapeHtml(task.description)}</p>
        <div class="task-meta">
            <small class="text-muted">
                <i class="fas fa-user"></i> ${escapeHtml(task.assigned_to_name)}<br>
                <i class="fas fa-calendar"></i> Due: ${dueDate}<br>
                <i class="fas fa-clock"></i> Created: ${createdDate}
            </small>
        </div>
        ${task.status === 'completed' && task.remarks ? `
            <div class="task-remarks">
                <strong>Remarks:</strong> ${escapeHtml(task.remarks)}
            </div>
        ` : ''}
        ${task.status !== 'completed' ? `
            <div class="task-actions">
                ${task.status === 'pending' 
                    ? `<button class="btn btn-sm btn-success" onclick="updateTaskStatus(${task.id}, 'in_progress')"><i class="fas fa-play"></i> Start</button>`
                    : `<button class="btn btn-sm btn-success" onclick="updateTaskStatus(${task.id}, 'completed')"><i class="fas fa-check"></i> Complete</button>`}
            </div>
        ` : ''}
    </div>`;
}

// Update Status
function updateTaskStatus(taskId, newStatus) {
    if (newStatus === 'completed') {
        document.getElementById('completeTaskId').value = taskId;
        document.getElementById('completeRemarks').value = '';
        new bootstrap.Modal(document.getElementById('completeTaskModal')).show();
    } else {
        if (confirm('Are you sure you want to update this task status?')) {
            sendTaskStatusUpdate(taskId, newStatus);
        }
    }
}

// Handle Complete Task form
document.getElementById('completeTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const taskId = document.getElementById('completeTaskId').value;
    const remarks = document.getElementById('completeRemarks').value.trim();
    if (!remarks) { alert('Please enter remarks.'); return; }
    sendTaskStatusUpdate(taskId, 'completed', remarks);
    bootstrap.Modal.getInstance(document.getElementById('completeTaskModal')).hide();
});

function sendTaskStatusUpdate(taskId, newStatus, remarks = '') {
    fetch('api/task_webhook.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'update_status', task_id: taskId, new_status: newStatus, remarks: remarks})
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Task status updated!', 'success');
            loadAllTasks();
        } else {
            showAlert('Failed: ' + data.message, 'danger');
        }
    })
    .catch(() => showAlert('Error updating task', 'danger'));
}

// Helpers
function updateTaskCount(status, count) {
    document.getElementById(`${status.replace('_','-')}-count`).textContent = count;
}
function refreshTasks() { loadAllTasks(); showAlert('Tasks refreshed!', 'info'); }
function showAlert(msg, type) {
    const id = 'alert-'+Date.now();
    document.getElementById('alert-container').innerHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" id="${id}" role="alert">
            ${msg}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>`;
    setTimeout(() => { const el=document.getElementById(id); if(el) el.remove(); }, 3000);
}
function escapeHtml(text) {
    const div=document.createElement('div'); div.textContent=text; return div.innerHTML;
}
</script>

<style>
.kanban-column { height: 70vh; overflow-y: auto; }
.task-list { min-height: 200px; }
.task-card {
    background: #fff; border: 1px solid #e9ecef; border-radius: 8px;
    padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: all 0.3s ease; cursor: pointer;
}
.task-card:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.15); transform: translateY(-2px); }
.task-card.completed { opacity: 0.85; background: #f8f9fa; }
.task-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
.task-title { margin: 0; font-weight: 600; color: #333; flex: 1; }
.priority-badge { padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
.priority-low { background: #d4edda; color: #155724; }
.priority-medium { background: #fff3cd; color: #856404; }
.priority-high { background: #f8d7da; color: #721c24; }
.priority-urgent { background: #f5c6cb; color: #721c24; }
.task-description { color: #666; font-size: 0.9rem; margin-bottom: 10px; }
.task-meta { margin-bottom: 10px; font-size: 0.85rem; }
.task-remarks { font-size: 0.85rem; color: #444; background: #eef; padding: 6px; border-radius: 5px; margin-top: 8px; }
.task-actions { display: flex; gap: 5px; }
.task-actions .btn { font-size: 0.8rem; padding: 4px 8px; }
#alert-container { position: fixed; top: 20px; right: 20px; z-index: 1050; max-width: 400px; }
</style>

<?php require_once 'footer.php'; ?> 
