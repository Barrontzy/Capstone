<?php
require_once 'includes/session.php';
require_once 'includes/db.php';

// Check if user is logged in
requireLogin();

// Handle search
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$searchQuery = $search ? "WHERE asset_tag LIKE '%$search%' OR assigned_person LIKE '%$search%' OR location LIKE '%$search%'" : "";
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
    .card { border-radius: 15px; }
    .sidebar { background: white; min-height: 100vh; box-shadow: 2px 0 10px rgba(0,0,0,0.1); }
    .sidebar .nav-link { color: #343a40; margin: 5px 0; border-radius: 8px; }
    .sidebar .nav-link.active,
    .sidebar .nav-link:hover { background: #dc3545; color: white; }
    .main-content { padding: 20px; }
    .clickable-row { cursor: pointer; }
  </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-danger">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">
      <i class="fas fa-university"></i> BSU Inventory System
    </a>
    <div class="navbar-nav ms-auto">
      <div class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
          <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
        </a>
        <ul class="dropdown-menu">
          <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
      </div>
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
        <div>
          <form class="d-inline" method="get">
            <input type="text" name="search" class="form-control d-inline-block" style="width:200px;" 
                   placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-search"></i></button>
          </form>
          <button class="btn btn-danger"><i class="fas fa-plus"></i> Add Equipment</button>
        </div>
      </div>

      <!-- Tabs -->
      <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#desktops">Desktops</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#laptops">Laptops</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#printers">Printers</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#accesspoints">Access Points</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#switches">Switches</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#telephones">Telephones</button></li>
      </ul>

      <div class="tab-content border bg-white p-3 rounded-bottom shadow-sm">
        <!-- Desktop -->
        <div class="tab-pane fade show active" id="desktops">
          <h5>🖥️ Desktop Inventory</h5>
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Processor</th><th>OS</th></tr></thead>
            <tbody>
            <?php
            $res = $conn->query("SELECT * FROM desktop $searchQuery");
            while ($row = $res->fetch_assoc()) {
              echo "<tr class='clickable-row'
                      data-bs-toggle='modal' data-bs-target='#desktopModal'
                      data-asset='{$row['asset_tag']}'
                      data-user='{$row['assigned_person']}'
                      data-location='{$row['location']}'
                      data-processor='{$row['processor']}'
                      data-ram='{$row['ram']}'
                      data-gpu='{$row['gpu']}'
                      data-hdd='{$row['hard_drive']}'
                      data-os='{$row['operating_system']}'
                      data-date='{$row['date_acquired']}'
                      data-itemno='{$row['inventory_item_no']}'
                      data-price='{$row['unit_price']}'
                      data-remarks='{$row['remarks']}'>
                <td>{$row['asset_tag']}</td>
                <td>{$row['assigned_person']}</td>
                <td>{$row['location']}</td>
                <td>{$row['processor']}</td>
                <td>{$row['operating_system']}</td>
              </tr>";
            }
            ?>
            </tbody>
          </table>
        </div>

        <!-- Laptops -->
        <div class="tab-pane fade" id="laptops">
          <h5>💻 Laptop Inventory</h5>
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Hardware</th><th>Software</th></tr></thead>
            <tbody>
            <?php
            $res = $conn->query("SELECT * FROM laptops $searchQuery");
            while ($row = $res->fetch_assoc()) {
              echo "<tr class='clickable-row'
                      data-bs-toggle='modal' data-bs-target='#equipModal'
                      data-equipment='Laptop'
                      data-asset='{$row['asset_tag']}'
                      data-user='{$row['assigned_person']}'
                      data-location='{$row['location']}'
                      data-specs='HW: {$row['hardware_specifications']} | SW: {$row['software_specifications']}'
                      data-date='{$row['date_acquired']}'
                      data-itemno='{$row['inventory_item_no']}'
                      data-price='{$row['unit_price']}'
                      data-remarks='{$row['remarks']}'>
                <td>{$row['asset_tag']}</td>
                <td>{$row['assigned_person']}</td>
                <td>{$row['location']}</td>
                <td>{$row['hardware_specifications']}</td>
                <td>{$row['software_specifications']}</td>
              </tr>";
            }
            ?>
            </tbody>
          </table>
        </div>

        <!-- Printers -->
        <div class="tab-pane fade" id="printers">
          <h5>🖨️ Printer Inventory</h5>
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php
            $res = $conn->query("SELECT * FROM printers $searchQuery");
            while ($row = $res->fetch_assoc()) {
              echo "<tr class='clickable-row'
                      data-bs-toggle='modal' data-bs-target='#equipModal'
                      data-equipment='Printer'
                      data-asset='{$row['asset_tag']}'
                      data-user='{$row['assigned_person']}'
                      data-location='{$row['location']}'
                      data-specs='HW: {$row['hardware_specifications']} | SW: {$row['software_specifications']}'
                      data-date='{$row['date_acquired']}'
                      data-itemno='{$row['inventory_item_no']}'
                      data-price='{$row['unit_price']}'
                      data-remarks='{$row['remarks']}'>
                <td>{$row['asset_tag']}</td>
                <td>{$row['assigned_person']}</td>
                <td>{$row['location']}</td>
                <td>{$row['remarks']}</td>
              </tr>";
            }
            ?>
            </tbody>
          </table>
        </div>

        <!-- Access Points -->
        <div class="tab-pane fade" id="accesspoints">
          <h5>📡 Access Point Inventory</h5>
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php
            $res = $conn->query("SELECT * FROM accesspoint $searchQuery");
            while ($row = $res->fetch_assoc()) {
              echo "<tr class='clickable-row'
                      data-bs-toggle='modal' data-bs-target='#equipModal'
                      data-equipment='Access Point'
                      data-asset='{$row['asset_tag']}'
                      data-user='{$row['assigned_person']}'
                      data-location='{$row['location']}'
                      data-specs='HW: {$row['hardware_specifications']} | SW: {$row['software_specifications']}'
                      data-date='{$row['date_acquired']}'
                      data-itemno='{$row['inventory_item_no']}'
                      data-price='{$row['unit_price']}'
                      data-remarks='{$row['remarks']}'>
                <td>{$row['asset_tag']}</td>
                <td>{$row['assigned_person']}</td>
                <td>{$row['location']}</td>
                <td>{$row['remarks']}</td>
              </tr>";
            }
            ?>
            </tbody>
          </table>
        </div>

        <!-- Switches -->
        <div class="tab-pane fade" id="switches">
          <h5>🔀 Switch Inventory</h5>
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php
            $res = $conn->query("SELECT * FROM switch $searchQuery");
            while ($row = $res->fetch_assoc()) {
              echo "<tr class='clickable-row'
                      data-bs-toggle='modal' data-bs-target='#equipModal'
                      data-equipment='Switch'
                      data-asset='{$row['asset_tag']}'
                      data-user='{$row['assigned_person']}'
                      data-location='{$row['location']}'
                      data-specs='HW: {$row['hardware_specifications']} | SW: {$row['software_specifications']}'
                      data-date='{$row['date_acquired']}'
                      data-itemno='{$row['inventory_item_no']}'
                      data-price='{$row['unit_price']}'
                      data-remarks='{$row['remarks']}'>
                <td>{$row['asset_tag']}</td>
                <td>{$row['assigned_person']}</td>
                <td>{$row['location']}</td>
                <td>{$row['remarks']}</td>
              </tr>";
            }
            ?>
            </tbody>
          </table>
        </div>

        <!-- Telephones -->
        <div class="tab-pane fade" id="telephones">
          <h5>☎️ Telephone Inventory</h5>
          <table class="table table-hover">
            <thead><tr><th>Asset Tag</th><th>User</th><th>Location</th><th>Remarks</th></tr></thead>
            <tbody>
            <?php
            $res = $conn->query("SELECT * FROM telephone $searchQuery");
            while ($row = $res->fetch_assoc()) {
              echo "<tr class='clickable-row'
                      data-bs-toggle='modal' data-bs-target='#equipModal'
                      data-equipment='Telephone'
                      data-asset='{$row['asset_tag']}'
                      data-user='{$row['assigned_person']}'
                      data-location='{$row['location']}'
                      data-specs='HW: {$row['hardware_specifications']} | SW: {$row['software_specifications']}'
                      data-date='{$row['date_acquired']}'
                      data-itemno='{$row['inventory_item_no']}'
                      data-price='{$row['unit_price']}'
                      data-remarks='{$row['remarks']}'>
                <td>{$row['asset_tag']}</td>
                <td>{$row['assigned_person']}</td>
                <td>{$row['location']}</td>
                <td>{$row['remarks']}</td>
              </tr>";
            }
            ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Desktop Modal -->
<div class="modal fade" id="desktopModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">🖥️ Desktop Details</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body"><ul class="list-group">
      <li class="list-group-item"><strong>Asset Tag:</strong> <span id="d_asset"></span></li>
      <li class="list-group-item"><strong>User:</strong> <span id="d_user"></span></li>
      <li class="list-group-item"><strong>Location:</strong> <span id="d_location"></span></li>
      <li class="list-group-item"><strong>Processor:</strong> <span id="d_processor"></span></li>
      <li class="list-group-item"><strong>RAM:</strong> <span id="d_ram"></span></li>
      <li class="list-group-item"><strong>GPU:</strong> <span id="d_gpu"></span></li>
      <li class="list-group-item"><strong>Hard Drive:</strong> <span id="d_hdd"></span></li>
      <li class="list-group-item"><strong>OS:</strong> <span id="d_os"></span></li>
      <li class="list-group-item"><strong>Date Acquired:</strong> <span id="d_date"></span></li>
      <li class="list-group-item"><strong>Inventory No:</strong> <span id="d_itemno"></span></li>
      <li class="list-group-item"><strong>Price:</strong> ₱<span id="d_price"></span></li>
      <li class="list-group-item"><strong>Remarks:</strong> <span id="d_remarks"></span></li>
    </ul></div>
  </div></div>
</div>

<!-- Generic Modal -->
<div class="modal fade" id="equipModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">📦 Equipment Details</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body"><ul class="list-group">
      <li class="list-group-item"><strong>Equipment:</strong> <span id="e_equipment"></span></li>
      <li class="list-group-item"><strong>Asset Tag:</strong> <span id="e_asset"></span></li>
      <li class="list-group-item"><strong>User:</strong> <span id="e_user"></span></li>
      <li class="list-group-item"><strong>Location:</strong> <span id="e_location"></span></li>
      <li class="list-group-item"><strong>Specs:</strong> <span id="e_specs"></span></li>
      <li class="list-group-item"><strong>Date Acquired:</strong> <span id="e_date"></span></li>
      <li class="list-group-item"><strong>Inventory No:</strong> <span id="e_itemno"></span></li>
      <li class="list-group-item"><strong>Price:</strong> ₱<span id="e_price"></span></li>
            <li class="list-group-item"><strong>Remarks:</strong> <span id="e_remarks"></span></li>
    </ul></div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".clickable-row").forEach(function (row) {
    row.addEventListener("click", function () {
      if (this.dataset.processor) {
        // Desktop
        document.getElementById("d_asset").textContent = this.dataset.asset;
        document.getElementById("d_user").textContent = this.dataset.user;
        document.getElementById("d_location").textContent = this.dataset.location;
        document.getElementById("d_processor").textContent = this.dataset.processor;
        document.getElementById("d_ram").textContent = this.dataset.ram;
        document.getElementById("d_gpu").textContent = this.dataset.gpu;
        document.getElementById("d_hdd").textContent = this.dataset.hdd;
        document.getElementById("d_os").textContent = this.dataset.os;
        document.getElementById("d_date").textContent = this.dataset.date;
        document.getElementById("d_itemno").textContent = this.dataset.itemno;
        document.getElementById("d_price").textContent = this.dataset.price;
        document.getElementById("d_remarks").textContent = this.dataset.remarks;
        new bootstrap.Modal(document.getElementById("desktopModal")).show();
      } else {
        // Generic Equipment
        document.getElementById("e_equipment").textContent = this.dataset.equipment;
        document.getElementById("e_asset").textContent = this.dataset.asset;
        document.getElementById("e_user").textContent = this.dataset.user;
        document.getElementById("e_location").textContent = this.dataset.location;
        document.getElementById("e_specs").textContent = this.dataset.specs;
        document.getElementById("e_date").textContent = this.dataset.date;
        document.getElementById("e_itemno").textContent = this.dataset.itemno;
        document.getElementById("e_price").textContent = this.dataset.price;
        document.getElementById("e_remarks").textContent = this.dataset.remarks;
        new bootstrap.Modal(document.getElementById("equipModal")).show();
      }
    });
  });
});
</script>
</body>
</html>

