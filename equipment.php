<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

$message = '';
$error = '';

// Handle Add Equipment (form POST handled here)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_equipment') {
    $type = $conn->real_escape_string($_POST['type'] ?? '');
    // Generic fields (present in most tables)
    $asset_tag = $conn->real_escape_string($_POST['asset_tag'] ?? '');
    $property_equipment = $conn->real_escape_string($_POST['property_equipment'] ?? '');
    $department = $conn->real_escape_string($_POST['department'] ?? '');
    $assigned_person = $conn->real_escape_string($_POST['assigned_person'] ?? '');
    $location = $conn->real_escape_string($_POST['location'] ?? '');
    $unit_price = is_numeric($_POST['unit_price'] ?? null) ? floatval($_POST['unit_price']) : null;
    $date_acquired = !empty($_POST['date_acquired']) ? $conn->real_escape_string($_POST['date_acquired']) : null;
    $useful_life = $conn->real_escape_string($_POST['useful_life'] ?? '');
    $hardware_specifications = $conn->real_escape_string($_POST['hardware_specifications'] ?? '');
    $software_specifications = $conn->real_escape_string($_POST['software_specifications'] ?? '');
    $high_value_ics_no = $conn->real_escape_string($_POST['high_value_ics_no'] ?? '');
    $inventory_item_no = $conn->real_escape_string($_POST['inventory_item_no'] ?? '');
    $remarks = $conn->real_escape_string($_POST['remarks'] ?? '');

    // Desktop extra fields
    $processor = $conn->real_escape_string($_POST['processor'] ?? '');
    $ram = $conn->real_escape_string($_POST['ram'] ?? '');
    $gpu = $conn->real_escape_string($_POST['gpu'] ?? '');
    $hard_drive = $conn->real_escape_string($_POST['hard_drive'] ?? '');
    $operating_system = $conn->real_escape_string($_POST['operating_system'] ?? '');

    // Insert based on type
    if ($type === 'desktop') {
        // Adjust columns if your desktop table has different column names
        $stmt = $conn->prepare("INSERT INTO desktop (asset_tag, property_equipment, assigned_person, location, processor, ram, gpu, hard_drive, operating_system, date_acquired, inventory_item_no, unit_price, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssdss",
            $asset_tag, $property_equipment, $assigned_person, $location,
            $processor, $ram, $gpu, $hard_drive, $operating_system,
            $date_acquired, $inventory_item_no, $unit_price, $remarks
        );
    } elseif (in_array($type, ['laptop','printer','accesspoint','switch','telephone'])) {
        // Map type to table name
        $map = [
            'laptop' => 'laptops',
            'printer' => 'printers',
            'accesspoint' => 'accesspoint',
            'switch' => 'switch',
            'telephone' => 'telephone'
        ];
        $table = $map[$type];

        $stmt = $conn->prepare("INSERT INTO {$table} (asset_tag, property_equipment, department, assigned_person, location, unit_price, date_acquired, useful_life, hardware_specifications, software_specifications, high_value_ics_no, inventory_item_no, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssdsisssss",
            $asset_tag, $property_equipment, $department, $assigned_person, $location,
            $unit_price, $date_acquired, $useful_life, $hardware_specifications, $software_specifications,
            $high_value_ics_no, $inventory_item_no, $remarks
        );
    } else {
        $error = "Unknown equipment type.";
    }

    if (empty($error)) {
        if ($stmt->execute()) {
            $stmt->close();
            // redirect to avoid resubmission
            header("Location: equipment.php?added=1");
            exit;
        } else {
            $error = "Insert failed: " . $stmt->error;
            $stmt->close();
        }
    }
}

// Search and status filter setup
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Helper to return WHERE clause for search + status for a given table and remarks column (remarks exists)
function buildWhere($search, $status, $remarksColumn = 'remarks') {
    $clauses = [];
    if (!empty($search)) {
        $s = $search;
        $clauses[] = "(asset_tag LIKE '%$s%' OR assigned_person LIKE '%$s%' OR location LIKE '%$s%')";
    }
    if ($status === 'working') {
        $clauses[] = "$remarksColumn LIKE '%Working%'";
    } elseif ($status === 'notworking') {
        $clauses[] = "$remarksColumn NOT LIKE '%Working%'";
    }
    return (count($clauses) > 0) ? ' WHERE ' . implode(' AND ', $clauses) : '';
}

// We'll fetch each table's rows separately (for the tabs)
$desktop_where = buildWhere($search, $status, 'remarks');
$laptops_where = buildWhere($search, $status, 'remarks');
$printers_where = buildWhere($search, $status, 'remarks');
$accesspoint_where = buildWhere($search, $status, 'remarks');
$switch_where = buildWhere($search, $status, 'remarks');
$telephone_where = buildWhere($search, $status, 'remarks');

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Equipment Management - BSU Inventory System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .sidebar { background: white; min-height: 100vh; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
    .sidebar .nav-link { color: #343a40; margin: 5px 0; border-radius: 8px; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #dc3545; color: white; }
    .main-content { padding: 20px; }
    .clickable-row { cursor: pointer; }
    .modal-lg { max-width: 900px; }

    #categoryChart { max-height: 250px; }

        .navbar-brand { display: flex; align-items: center; gap: 8px; }

        .logo-icon {
            height: 24px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
}


        .navbar { height: 56px; padding-top: 0; padding-bottom: 0; }
        .navbar .container-fluid { height: 56px; align-items: center; }

        .navbar-brand { display: flex; align-items: center; gap: 8px; padding: 0; }


        .logo-icon {
            height: 40px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
}
  </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php"><i class="fas fa-un"></i> 
      <img src="Ict logs.png" alt="BSU Logo" class="logo-icon"> BSU Inventory System
    <div class="navbar-nav ms-auto">
      <a href="profile.php" class="btn btn-light me-2"><i class="fas fa-user-circle"></i> Profile</a>
      <a href="logout.php" class="btn btn-outline-light"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</nav>

<div class="container-fluid">
  <div class="row">
    <!-- Sidebar -->
    <div class="col-md-3 col-lg-2 sidebar p-3">
      <ul class="nav flex-column">
        <li class="nav-item"><a href="dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li class="nav-item"><a href="equipment.php" class="nav-link active"><i class="fas fa-laptop"></i> Equipment</a></li>
        <li class="nav-item"><a href="departments.php" class="nav-link"><i class="fas fa-building"></i> Departments</a></li>
        <li class="nav-item"><a href="maintenance.php" class="nav-link"><i class="fas fa-tools"></i> Maintenance</a></li>
        <li class="nav-item"><a href="tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a></li>
        <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports</a></li>
        <li class="nav-item"><a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="col-md-9 col-lg-10 main-content">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-laptop"></i> Equipment</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEquipmentModal"><i class="fas fa-plus"></i> Add Equipment</button>
      </div>

      <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
        <div class="alert alert-success">✅ Equipment added successfully!</div>
      <?php endif; ?>
      <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <!-- Search -->
      <form method="GET" class="mb-3 d-flex">
        <input type="text" name="search" class="form-control me-2" placeholder="Search asset tag, user or location..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="status" class="form-select me-2" style="max-width:200px;">
          <option value="">All status</option>
          <option value="working" <?php echo ($status=='working') ? 'selected':''; ?>>Working</option>
          <option value="notworking" <?php echo ($status=='notworking') ? 'selected':''; ?>>Not Working</option>
        </select>
        <button type="submit" class="btn btn-danger"><i class="fas fa-search"></i></button>
      </form>

      <!-- Tabs -->
      <ul class="nav nav-tabs mb-2" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#desktops" type="button">Desktops</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#laptops" type="button">Laptops</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#printers" type="button">Printers</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#accesspoints" type="button">Access Points</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#switches" type="button">Switches</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#telephones" type="button">Telephones</button></li>
      </ul>

      <div class="tab-content border bg-white p-3 rounded-bottom shadow-sm">
        <!-- Desktops -->
        <div class="tab-pane fade show active" id="desktops">
          <h5>🖥️ Desktop Inventory</h5>
          <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Processor</th><th>OS</th></tr></thead>
            <tbody>
            <?php
            $q = "SELECT * FROM desktop" . $desktop_where . " ORDER BY date_acquired DESC";
            $res = $conn->query($q);
            while ($row = $res->fetch_assoc()):
            ?>
              <tr class="clickable-row"
                  data-type="desktop"
                  data-asset="<?php echo htmlspecialchars($row['asset_tag']); ?>"
                  data-user="<?php echo htmlspecialchars($row['assigned_person']); ?>"
                  data-location="<?php echo htmlspecialchars($row['location']); ?>"
                  data-processor="<?php echo htmlspecialchars($row['processor'] ?? ''); ?>"
                  data-ram="<?php echo htmlspecialchars($row['ram'] ?? ''); ?>"
                  data-gpu="<?php echo htmlspecialchars($row['gpu'] ?? ''); ?>"
                  data-hdd="<?php echo htmlspecialchars($row['hard_drive'] ?? ''); ?>"
                  data-os="<?php echo htmlspecialchars($row['operating_system'] ?? ''); ?>"
                  data-date="<?php echo htmlspecialchars($row['date_acquired'] ?? ''); ?>"
                  data-itemno="<?php echo htmlspecialchars($row['inventory_item_no'] ?? ''); ?>"
                  data-price="<?php echo htmlspecialchars($row['unit_price'] ?? ''); ?>"
                  data-remarks="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                <td><?php echo htmlspecialchars($row['asset_tag']); ?></td>
                <td><?php echo htmlspecialchars($row['assigned_person']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td><?php echo htmlspecialchars($row['processor'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['operating_system'] ?? ''); ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Laptops -->
        <div class="tab-pane fade" id="laptops">
          <h5>💻 Laptop Inventory</h5>
          <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Hardware</th><th>Software</th></tr></thead>
            <tbody>
            <?php
            $q = "SELECT * FROM laptops" . $laptops_where . " ORDER BY date_acquired DESC";
            $res = $conn->query($q);
            while ($row = $res->fetch_assoc()):
            ?>
              <tr class="clickable-row"
                  data-type="generic"
                  data-equipment="Laptop"
                  data-asset="<?php echo htmlspecialchars($row['asset_tag']); ?>"
                  data-user="<?php echo htmlspecialchars($row['assigned_person']); ?>"
                  data-location="<?php echo htmlspecialchars($row['location']); ?>"
                  data-specs="<?php echo 'HW: '.htmlspecialchars($row['hardware_specifications']).' | SW: '.htmlspecialchars($row['software_specifications']); ?>"
                  data-date="<?php echo htmlspecialchars($row['date_acquired'] ?? ''); ?>"
                  data-itemno="<?php echo htmlspecialchars($row['inventory_item_no'] ?? ''); ?>"
                  data-price="<?php echo htmlspecialchars($row['unit_price'] ?? ''); ?>"
                  data-remarks="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                <td><?php echo htmlspecialchars($row['asset_tag']); ?></td>
                <td><?php echo htmlspecialchars($row['assigned_person']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td><?php echo htmlspecialchars($row['hardware_specifications']); ?></td>
                <td><?php echo htmlspecialchars($row['software_specifications']); ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Printers -->
        <div class="tab-pane fade" id="printers">
          <h5>🖨️ Printer Inventory</h5>
          <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php
            $q = "SELECT * FROM printers" . $printers_where . " ORDER BY date_acquired DESC";
            $res = $conn->query($q);
            while ($row = $res->fetch_assoc()):
            ?>
              <tr class="clickable-row"
                  data-type="generic"
                  data-equipment="Printer"
                  data-asset="<?php echo htmlspecialchars($row['asset_tag']); ?>"
                  data-user="<?php echo htmlspecialchars($row['assigned_person']); ?>"
                  data-location="<?php echo htmlspecialchars($row['location']); ?>"
                  data-specs="<?php echo htmlspecialchars($row['hardware_specifications']); ?>"
                  data-date="<?php echo htmlspecialchars($row['date_acquired'] ?? ''); ?>"
                  data-itemno="<?php echo htmlspecialchars($row['inventory_item_no'] ?? ''); ?>"
                  data-price="<?php echo htmlspecialchars($row['unit_price'] ?? ''); ?>"
                  data-remarks="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                <td><?php echo htmlspecialchars($row['asset_tag']); ?></td>
                <td><?php echo htmlspecialchars($row['assigned_person']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td><?php echo htmlspecialchars($row['remarks']); ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Access Points -->
        <div class="tab-pane fade" id="accesspoints">
          <h5>📡 Access Point Inventory</h5>
          <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php
            $q = "SELECT * FROM accesspoint" . $accesspoint_where . " ORDER BY date_acquired DESC";
            $res = $conn->query($q);
            while ($row = $res->fetch_assoc()):
            ?>
              <tr class="clickable-row"
                  data-type="generic"
                  data-equipment="Access Point"
                  data-asset="<?php echo htmlspecialchars($row['asset_tag']); ?>"
                  data-user="<?php echo htmlspecialchars($row['assigned_person']); ?>"
                  data-location="<?php echo htmlspecialchars($row['location']); ?>"
                  data-specs="<?php echo htmlspecialchars($row['hardware_specifications']); ?>"
                  data-date="<?php echo htmlspecialchars($row['date_acquired'] ?? ''); ?>"
                  data-itemno="<?php echo htmlspecialchars($row['inventory_item_no'] ?? ''); ?>"
                  data-price="<?php echo htmlspecialchars($row['unit_price'] ?? ''); ?>"
                  data-remarks="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                <td><?php echo htmlspecialchars($row['asset_tag']); ?></td>
                <td><?php echo htmlspecialchars($row['assigned_person']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td><?php echo htmlspecialchars($row['remarks']); ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Switches -->
        <div class="tab-pane fade" id="switches">
          <h5>🔀 Switch Inventory</h5>
          <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php
            $q = "SELECT * FROM `switch`" . $switch_where . " ORDER BY date_acquired DESC";
            $res = $conn->query($q);
            while ($row = $res->fetch_assoc()):
            ?>
              <tr class="clickable-row"
                  data-type="generic"
                  data-equipment="Switch"
                  data-asset="<?php echo htmlspecialchars($row['asset_tag']); ?>"
                  data-user="<?php echo htmlspecialchars($row['assigned_person']); ?>"
                  data-location="<?php echo htmlspecialchars($row['location']); ?>"
                  data-specs="<?php echo htmlspecialchars($row['hardware_specifications']); ?>"
                  data-date="<?php echo htmlspecialchars($row['date_acquired'] ?? ''); ?>"
                  data-itemno="<?php echo htmlspecialchars($row['inventory_item_no'] ?? ''); ?>"
                  data-price="<?php echo htmlspecialchars($row['unit_price'] ?? ''); ?>"
                  data-remarks="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                <td><?php echo htmlspecialchars($row['asset_tag']); ?></td>
                <td><?php echo htmlspecialchars($row['assigned_person']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td><?php echo htmlspecialchars($row['remarks']); ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          </div>
        </div>

        <!-- Telephones -->
        <div class="tab-pane fade" id="telephones">
          <h5>☎️ Telephone Inventory</h5>
          <div class="table-responsive">
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php
            $q = "SELECT * FROM telephone" . $telephone_where . " ORDER BY date_acquired DESC";
            $res = $conn->query($q);
            while ($row = $res->fetch_assoc()):
            ?>
              <tr class="clickable-row"
                  data-type="generic"
                  data-equipment="Telephone"
                  data-asset="<?php echo htmlspecialchars($row['asset_tag']); ?>"
                  data-user="<?php echo htmlspecialchars($row['assigned_person']); ?>"
                  data-location="<?php echo htmlspecialchars($row['location']); ?>"
                  data-specs="<?php echo htmlspecialchars($row['hardware_specifications']); ?>"
                  data-date="<?php echo htmlspecialchars($row['date_acquired'] ?? ''); ?>"
                  data-itemno="<?php echo htmlspecialchars($row['inventory_item_no'] ?? ''); ?>"
                  data-price="<?php echo htmlspecialchars($row['unit_price'] ?? ''); ?>"
                  data-remarks="<?php echo htmlspecialchars($row['remarks'] ?? ''); ?>">
                <td><?php echo htmlspecialchars($row['asset_tag']); ?></td>
                <td><?php echo htmlspecialchars($row['assigned_person']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td><?php echo htmlspecialchars($row['remarks']); ?></td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- Desktop Modal -->
<div class="modal fade" id="desktopModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">🖥️ Desktop Details</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <ul class="list-group">
        <li class="list-group-item"><strong>Asset Tag:</strong> <span id="d_asset"></span></li>
        <li class="list-group-item"><strong>Assigned Person:</strong> <span id="d_user"></span></li>
        <li class="list-group-item"><strong>Location:</strong> <span id="d_location"></span></li>
        <li class="list-group-item"><strong>Processor:</strong> <span id="d_processor"></span></li>
        <li class="list-group-item"><strong>RAM:</strong> <span id="d_ram"></span></li>
        <li class="list-group-item"><strong>GPU:</strong> <span id="d_gpu"></span></li>
        <li class="list-group-item"><strong>Hard Drive:</strong> <span id="d_hdd"></span></li>
        <li class="list-group-item"><strong>OS:</strong> <span id="d_os"></span></li>
        <li class="list-group-item"><strong>Date Acquired:</strong> <span id="d_date"></span></li>
        <li class="list-group-item"><strong>Inventory Item No:</strong> <span id="d_itemno"></span></li>
        <li class="list-group-item"><strong>Unit Price:</strong> ₱<span id="d_price"></span></li>
        <li class="list-group-item"><strong>Remarks:</strong> <span id="d_remarks"></span></li>
      </ul>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" onclick="editFromModal()">Edit</button>
      <button class="btn btn-danger" onclick="deleteFromModal()">Delete</button>
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

<!-- Generic Equipment Modal -->
<div class="modal fade" id="equipModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">📦 Equipment Details</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <ul class="list-group">
        <li class="list-group-item"><strong>Equipment:</strong> <span id="e_equipment"></span></li>
        <li class="list-group-item"><strong>Asset Tag:</strong> <span id="e_asset"></span></li>
        <li class="list-group-item"><strong>Assigned Person:</strong> <span id="e_user"></span></li>
        <li class="list-group-item"><strong>Location:</strong> <span id="e_location"></span></li>
        <li class="list-group-item"><strong>Specifications:</strong> <span id="e_specs"></span></li>
        <li class="list-group-item"><strong>Date Acquired:</strong> <span id="e_date"></span></li>
        <li class="list-group-item"><strong>Inventory Item No:</strong> <span id="e_itemno"></span></li>
        <li class="list-group-item"><strong>Unit Price:</strong> ₱<span id="e_price"></span></li>
        <li class="list-group-item"><strong>Remarks:</strong> <span id="e_remarks"></span></li>
      </ul>
    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" onclick="editFromModal()">Edit</button>
      <button class="btn btn-danger" onclick="deleteFromModal()">Delete</button>
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

<!-- Add Equipment Modal (dynamic fields) -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST">
      <input type="hidden" name="action" value="add_equipment">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-plus"></i> Add Equipment</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Equipment Type</label>
            <select name="type" id="add-type" class="form-select" required>
              <option value="">-- Select Type --</option>
              <option value="desktop">Desktop</option>
              <option value="laptop">Laptop</option>
              <option value="printer">Printer</option>
              <option value="accesspoint">Access Point</option>
              <option value="switch">Switch</option>
              <option value="telephone">Telephone</option>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label">Asset Tag</label>
            <input type="text" name="asset_tag" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Property / Equipment</label>
            <input type="text" name="property_equipment" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label">Department</label>
            <input type="text" name="department" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label">Assigned Person</label>
            <input type="text" name="assigned_person" class="form-control">
          </div>

          <div class="col-md-6">
            <label class="form-label">Location</label>
            <input type="text" name="location" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Date Acquired</label>
            <input type="date" name="date_acquired" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Unit Price</label>
            <input type="number" step="0.01" name="unit_price" class="form-control">
          </div>

          <div class="col-md-4">
            <label class="form-label">Inventory Item No</label>
            <input type="text" name="inventory_item_no" class="form-control">
          </div>

          <!-- Generic fields -->
          <div class="col-12" id="generic-fields">
            <label class="form-label">Hardware Specifications</label>
            <textarea name="hardware_specifications" class="form-control"></textarea>
            <label class="form-label mt-2">Software Specifications</label>
            <textarea name="software_specifications" class="form-control"></textarea>
            <label class="form-label mt-2">Useful Life</label>
            <input type="text" name="useful_life" class="form-control">
            <label class="form-label mt-2">High Value ICS No</label>
            <input type="text" name="high_value_ics_no" class="form-control">
          </div>

          <!-- Desktop specific -->
          <div class="col-12 d-none" id="desktop-fields">
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Processor</label>
                <input type="text" name="processor" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">RAM</label>
                <input type="text" name="ram" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">GPU</label>
                <input type="text" name="gpu" class="form-control">
              </div>
              <div class="col-md-6 mt-2">
                <label class="form-label">Hard Drive</label>
                <input type="text" name="hard_drive" class="form-control">
              </div>
              <div class="col-md-6 mt-2">
                <label class="form-label">Operating System</label>
                <input type="text" name="operating_system" class="form-control">
              </div>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label mt-2">Remarks</label>
            <textarea name="remarks" class="form-control"></textarea>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-danger">Save</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentAssetTag = '';
let currentType = '';

document.addEventListener('DOMContentLoaded', function() {
  // When a row is clicked → open correct modal and store asset_tag/type
  document.querySelectorAll('.clickable-row').forEach(function(row) {
    row.addEventListener('click', function() {
      currentAssetTag = row.dataset.asset;
      currentType = row.dataset.type || row.dataset.equipment;

      if (row.dataset.type === 'desktop') {
        // Desktop modal
        document.getElementById('d_asset').textContent = row.dataset.asset;
        document.getElementById('d_user').textContent = row.dataset.user;
        document.getElementById('d_location').textContent = row.dataset.location;
        document.getElementById('d_processor').textContent = row.dataset.processor;
        document.getElementById('d_ram').textContent = row.dataset.ram;
        document.getElementById('d_gpu').textContent = row.dataset.gpu;
        document.getElementById('d_hdd').textContent = row.dataset.hdd;
        document.getElementById('d_os').textContent = row.dataset.os;
        document.getElementById('d_date').textContent = row.dataset.date;
        document.getElementById('d_itemno').textContent = row.dataset.itemno;
        document.getElementById('d_price').textContent = row.dataset.price;
        document.getElementById('d_remarks').textContent = row.dataset.remarks;

        new bootstrap.Modal(document.getElementById('desktopModal')).show();
      } else {
        // Generic modal
        document.getElementById('e_equipment').textContent = row.dataset.equipment;
        document.getElementById('e_asset').textContent = row.dataset.asset;
        document.getElementById('e_user').textContent = row.dataset.user;
        document.getElementById('e_location').textContent = row.dataset.location;
        document.getElementById('e_specs').textContent = row.dataset.specs;
        document.getElementById('e_date').textContent = row.dataset.date;
        document.getElementById('e_itemno').textContent = row.dataset.itemno;
        document.getElementById('e_price').textContent = row.dataset.price;
        document.getElementById('e_remarks').textContent = row.dataset.remarks;

        new bootstrap.Modal(document.getElementById('equipModal')).show();
      }
    });
  });
});

// ✅ Real Edit
function editFromModal() {
  if (!currentAssetTag || !currentType) {
    alert("No equipment selected.");
    return;
  }
  window.location.href = "edit_equipment.php?asset_tag=" + encodeURIComponent(currentAssetTag) + "&type=" + encodeURIComponent(currentType);
}

// ✅ Real Delete
function deleteFromModal() {
  if (!currentAssetTag || !currentType) {
    alert("No equipment selected.");
    return;
  }
  if (confirm("Are you sure you want to delete this equipment?")) {
    // Create a form and submit via POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'delete_equipment.php';

    const inputAsset = document.createElement('input');
    inputAsset.type = 'hidden';
    inputAsset.name = 'asset_tag';
    inputAsset.value = currentAssetTag;
    form.appendChild(inputAsset);

    const inputType = document.createElement('input');
    inputType.type = 'hidden';
    inputType.name = 'type';
    inputType.value = currentType;
    form.appendChild(inputType);

    document.body.appendChild(form);
    form.submit();
  }
}
</script>

</body>
</html>
