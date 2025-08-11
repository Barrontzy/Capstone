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

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query for equipment history
$where_conditions = ["e.assigned_to = $user_id"];
$params = [];

if ($status_filter) {
    $where_conditions[] = "e.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "e.acquisition_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "e.acquisition_date <= ?";
    $params[] = $date_to;
}

$where_clause = implode(' AND ', $where_conditions);

// Get equipment history
$query = "
    SELECT e.*, d.name as department_name, ec.name as category_name,
           COUNT(mr.id) as maintenance_count,
           MAX(mr.end_date) as last_maintenance
    FROM equipment e
    LEFT JOIN departments d ON e.department_id = d.id
    LEFT JOIN equipment_categories ec ON e.category_id = ec.id
    LEFT JOIN maintenance_records mr ON e.id = mr.equipment_id
    WHERE $where_clause
    GROUP BY e.id
    ORDER BY e.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$equipment_history = $stmt->get_result();

$page_title = 'Equipment History';
require_once 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-history"></i> Equipment History</h2>
                <button class="btn btn-outline-primary" onclick="exportHistory()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-filter"></i> Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="maintenance" <?php echo $status_filter == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                <option value="disposed" <?php echo $status_filter == 'disposed' ? 'selected' : ''; ?>>Disposed</option>
                                <option value="lost" <?php echo $status_filter == 'lost' ? 'selected' : ''; ?>>Lost</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="history.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Equipment History Table -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Equipment List</h5>
                </div>
                <div class="card-body">
                    <?php if ($equipment_history->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Equipment</th>
                                        <th>Category</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Location</th>
                                        <th>Maintenance</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($equipment = $equipment_history->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($equipment['name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        SN: <?php echo htmlspecialchars($equipment['serial_number']); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($equipment['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($equipment['department_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $equipment['status'] == 'active' ? 'success' : 
                                                        ($equipment['status'] == 'maintenance' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($equipment['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($equipment['location']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    Count: <?php echo $equipment['maintenance_count']; ?><br>
                                                    <?php if ($equipment['last_maintenance']): ?>
                                                        Last: <?php echo date('M d, Y', strtotime($equipment['last_maintenance'])); ?>
                                                    <?php else: ?>
                                                        Never
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="../view_equipment.php?id=<?php echo $equipment['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-info" 
                                                            onclick="showMaintenanceHistory(<?php echo $equipment['id']; ?>)" 
                                                            title="Maintenance History">
                                                        <i class="fas fa-tools"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Equipment History</h4>
                            <p class="text-muted">No equipment has been assigned to you yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Maintenance History Modal -->
<div class="modal fade" id="maintenanceHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-tools"></i> Maintenance History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="maintenanceHistoryContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function showMaintenanceHistory(equipmentId) {
    // Load maintenance history via AJAX
    fetch(`get_maintenance_history.php?equipment_id=${equipmentId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('maintenanceHistoryContent').innerHTML = html;
            new bootstrap.Modal(document.getElementById('maintenanceHistoryModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load maintenance history.');
        });
}

function exportHistory() {
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);
    params.append('export', '1');
    
    // Create download link
    const link = document.createElement('a');
    link.href = `export_history.php?${params.toString()}`;
    link.download = 'equipment_history.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<style>
.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
}

.btn-group .btn {
    margin-right: 2px;
}

.badge {
    font-size: 0.75rem;
}
</style>

<?php require_once 'footer.php'; ?> 