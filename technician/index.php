<?php
session_start();
require_once '../includes/session.php';
require_once '../includes/db.php';

// Check if user is logged in and is a technician
if (!isset($_SESSION['user_id'])) {
    header('Location: ../landing.php');
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
                <h2><i class="fas fa-tasks"></i> Task & Maintenance Board</h2>
                <div class="d-flex gap-2">
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
                <!-- Pending -->
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

                <!-- In Progress -->
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

                <!-- Completed -->
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

<!-- Single Complete Modal for both Tasks & Maintenance -->
<div class="modal fade" id="completeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-comment"></i> Complete Item - Add Remarks</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="completeForm">
          <input type="hidden" name="item_id" id="completeItemId">
          <input type="hidden" name="item_type" id="completeItemType"> <!-- task / maintenance -->
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
    loadAllItems();
    startAutoRefresh();

    document.getElementById('autoRefresh').addEventListener('change', function() {
        this.checked ? startAutoRefresh() : stopAutoRefresh();
    });

    // Handle Complete Modal submit
    document.getElementById('completeForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('completeItemId').value;
        const type = document.getElementById('completeItemType').value;
        const remarks = document.getElementById('completeRemarks').value.trim();
        if (!remarks) { alert("Please enter remarks."); return; }
        if (type === 'task') {
            sendTaskStatusUpdate(id, 'completed', remarks);
        } else {
            sendMaintenanceStatusUpdate(id, 'completed', remarks);
        }
        bootstrap.Modal.getInstance(document.getElementById('completeModal')).hide();
    });
});

// Refreshing
function startAutoRefresh() { autoRefreshInterval = setInterval(loadAllItems, 10000); }
function stopAutoRefresh() { clearInterval(autoRefreshInterval); }
function loadAllItems() {
    ['pending','in_progress','completed'].forEach(status => {
        loadTasksByStatus(status);
        loadMaintenanceByStatus(status);
    });
}

// ---------------- TASKS ----------------
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
    });
}

function renderTasks(status, tasks) {
    const container = document.getElementById(`${status.replace('_','-')}-tasks`);
    container.innerHTML = tasks.map(task => createTaskElement(task)).join('');
}

function createTaskElement(task) {
    const dueDate = new Date(task.due_date).toLocaleDateString();
    const createdDate = new Date(task.created_at).toLocaleDateString();

    return `
    <div class="task-card ${task.status === 'completed' ? 'completed' : ''}">
        <div class="task-header">
            <h6 class="task-title">${escapeHtml(task.title)}</h6>
            <span class="priority-badge priority-${task.priority}">${task.priority}</span>
        </div>
        <p class="task-description">${escapeHtml(task.description)}</p>
        <div class="task-meta">
            <small class="text-muted">
                <i class="fas fa-user"></i> ${escapeHtml(task.assigned_to_name)}<br>
                <i class="fas fa-calendar"></i> Due: ${dueDate}<br>
                <i class="fas fa-clock"></i> Created: ${createdDate}
            </small>
        </div>
        ${task.status === 'completed' && task.remarks ? `<div><strong>Remarks:</strong> ${escapeHtml(task.remarks)}</div>` : ''}
        ${task.status !== 'completed' ? `
            <div class="task-actions">
                ${task.status === 'pending'
                    ? `<button class="btn btn-sm btn-success" onclick="updateTaskStatus(${task.id}, 'in_progress')">Start</button>`
                    : `<button class="btn btn-sm btn-success" onclick="openCompleteModal(${task.id}, 'task')">Complete</button>`}
            </div>` : ''}
    </div>`;
}

// ---------------- MAINTENANCE ----------------
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
                parseInt(document.getElementById(`${status.replace('_','-')}-count`).textContent) + records.length
            );
        }
    });
}

function renderMaintenance(status, records) {
    const container = document.getElementById(`${status.replace('_','-')}-tasks`);
    records.forEach(r => container.insertAdjacentHTML('beforeend', createMaintenanceElement(r)));
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
        ${record.status !== 'completed' ? `
            <div class="task-actions">
                ${record.status === 'pending'
                    ? `<button class="btn btn-sm btn-success" onclick="updateMaintenanceStatus(${record.id}, 'in_progress')"><i class="fas fa-play"></i> Start</button>`
                    : `<button class="btn btn-sm btn-success" onclick="openCompleteModal(${record.id}, 'maintenance')"><i class="fas fa-check"></i> Complete</button>`}
            </div>
        ` : ''}
    </div>`;
}

// ---------------- TASKS ----------------
function updateTaskStatus(id, newStatus) {
    if (newStatus === 'completed') {
        openCompleteModal(id, 'task');
    } else {
        sendTaskStatusUpdate(id, newStatus);
    }
}

function sendTaskStatusUpdate(id, status, remarks = '') {
    fetch('api/task_webhook.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'update_status',
            task_id: id,
            new_status: status,
            remarks: remarks
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Task updated!', 'success');
            loadAllItems();
        } else {
            showAlert('Failed: ' + data.message, 'danger');
        }
    })
    .catch(() => showAlert('Error updating task', 'danger'));
}

// ---------------- MAINTENANCE ----------------
function updateMaintenanceStatus(maintenanceId, newStatus) {
    if (newStatus === 'completed') {
        document.getElementById('completeMaintenanceId').value = maintenanceId;
        document.getElementById('maintenanceRemarks').value = '';
        new bootstrap.Modal(document.getElementById('completeMaintenanceModal')).show();
    } else {
        if (confirm('Are you sure you want to update this maintenance status?')) {
            sendMaintenanceStatusUpdate(maintenanceId, newStatus);
        }
    }
}

function sendMaintenanceStatusUpdate(id, status, remarks = '') {
    fetch('api/task_webhook.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'update_maintenance_status',
            maintenance_id: id,
            new_status: status,
            remarks: remarks
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert('Maintenance updated!', 'success');
            loadAllItems();
        } else {
            showAlert('Failed: ' + data.message, 'danger');
        }
    })
    .catch(() => showAlert('Error updating maintenance', 'danger'));
}

// ---------------- Helpers ----------------
function openCompleteModal(id, type) {
    document.getElementById('completeItemId').value = id;
    document.getElementById('completeItemType').value = type;
    document.getElementById('completeRemarks').value = '';
    new bootstrap.Modal(document.getElementById('completeModal')).show();
}
function updateTaskCount(status,count){ document.getElementById(`${status.replace('_','-')}-count`).textContent=count; }
function refreshTasks(){ loadAllItems(); showAlert("Refreshed","info"); }
function showAlert(msg,type){
    const id='alert-'+Date.now();
    document.getElementById('alert-container').innerHTML=`
        <div class="alert alert-${type} alert-dismissible fade show" id="${id}">
        ${msg}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;
    setTimeout(()=>{const el=document.getElementById(id);if(el)el.remove();},3000);
}
function escapeHtml(txt){const div=document.createElement('div');div.textContent=txt;return div.innerHTML;}
</script>

<style>
.kanban-column { height: 70vh; overflow-y:auto; }
.task-card { background:#fff; border:1px solid #e9ecef; border-radius:8px;
    padding:15px; margin-bottom:15px; box-shadow:0 2px 4px rgba(0,0,0,.1); }
.task-card.completed { opacity:.8; background:#f8f9fa; }
.task-header { display:flex; justify-content:space-between; }
.priority-badge { padding:2px 8px; border-radius:12px; font-size:0.7rem; font-weight:600; }
.priority-low{background:#d4edda;color:#155724;} .priority-medium{background:#fff3cd;color:#856404;}
.priority-high{background:#f8d7da;color:#721c24;} .priority-urgent{background:#f5c6cb;color:#721c24;}
.task-actions .btn{font-size:0.8rem;padding:4px 8px;}
</style>

<?php require_once 'footer.php'; ?>
