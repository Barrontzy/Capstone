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
$equipment_info = null;
$error = '';
$success = '';

// Handle QR code processing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'scan_qr':
                $qr_data = trim($_POST['qr_data']);
                if (!empty($qr_data)) {
                    // Look for equipment with this QR code
                    $stmt = $conn->prepare("SELECT * FROM equipment WHERE qr_code = ?");
                    $stmt->bind_param("s", $qr_data);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $equipment_info = $result->fetch_assoc();
                        $success = 'Equipment found!';
                    } else {
                        $error = 'No equipment found with this QR code.';
                    }
                    $stmt->close();
                } else {
                    $error = 'Please enter QR code data.';
                }
                break;
                
            case 'upload_qr':
                if (isset($_FILES['qr_file']) && $_FILES['qr_file']['error'] == 0) {
                    $file_content = file_get_contents($_FILES['qr_file']['tmp_name']);
                    // For demo purposes, we'll assume the file contains QR data
                    $qr_data = trim($file_content);
                    
                    if (!empty($qr_data)) {
                        $stmt = $conn->prepare("SELECT * FROM equipment WHERE qr_code = ?");
                        $stmt->bind_param("s", $qr_data);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        if ($result->num_rows > 0) {
                            $equipment_info = $result->fetch_assoc();
                            $success = 'Equipment found from uploaded file!';
                        } else {
                            $error = 'No equipment found with this QR code.';
                        }
                        $stmt->close();
                    } else {
                        $error = 'Could not read QR code from file.';
                    }
                } else {
                    $error = 'Please select a valid file.';
                }
                break;
        }
    }
}

// Get equipment list for QR generation
$equipment_list = $conn->query("SELECT id, name, serial_number, qr_code FROM equipment ORDER BY name");

$page_title = 'QR Code Scanner';
require_once 'header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2><i class="fas fa-qrcode"></i> QR Code Scanner & Generator</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- QR Code Input Methods -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-camera"></i> Scan QR Code</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto;"></div>
                            </div>
                            <p class="text-muted small">Position the QR code within the scanner frame</p>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-upload"></i> Upload QR File</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="upload_qr">
                                <div class="mb-3">
                                    <label class="form-label">Select QR Code File</label>
                                    <input type="file" class="form-control" name="qr_file" accept=".txt,.json,.xml">
                                    <small class="text-muted">Supported formats: TXT, JSON, XML</small>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Upload and Scan
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-keyboard"></i> Manual Entry</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="scan_qr">
                                <div class="mb-3">
                                    <label class="form-label">Enter QR Code Data</label>
                                    <textarea class="form-control" name="qr_data" rows="3" 
                                              placeholder="Paste or type QR code data here..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Search Equipment
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Equipment Information -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Equipment Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($equipment_info): ?>
                                <div class="equipment-details">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Equipment Details</h6>
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($equipment_info['name']); ?></p>
                                            <p><strong>Serial Number:</strong> <?php echo htmlspecialchars($equipment_info['serial_number']); ?></p>
                                            <p><strong>Model:</strong> <?php echo htmlspecialchars($equipment_info['model']); ?></p>
                                            <p><strong>Brand:</strong> <?php echo htmlspecialchars($equipment_info['brand']); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Status & Location</h6>
                                            <p><strong>Status:</strong> 
                                                <span class="badge bg-<?php 
                                                    echo $equipment_info['status'] == 'active' ? 'success' : 
                                                        ($equipment_info['status'] == 'maintenance' ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo ucfirst($equipment_info['status']); ?>
                                                </span>
                                            </p>
                                            <p><strong>Location:</strong> <?php echo htmlspecialchars($equipment_info['location']); ?></p>
                                            <p><strong>Acquisition Date:</strong> <?php echo date('M d, Y', strtotime($equipment_info['acquisition_date'])); ?></p>
                                            <p><strong>Cost:</strong> ₱<?php echo number_format($equipment_info['acquisition_cost'], 2); ?></p>
                                        </div>
                                    </div>
                                    
                                    <?php if ($equipment_info['notes']): ?>
                                        <div class="mt-3">
                                            <h6>Notes</h6>
                                            <p class="text-muted"><?php echo htmlspecialchars($equipment_info['notes']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <div class="mt-3">
                                        <a href="../view_equipment.php?id=<?php echo $equipment_info['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i> View Full Details
                                        </a>
                                        <button class="btn btn-outline-success" onclick="addToHistory(<?php echo $equipment_info['id']; ?>)">
                                            <i class="fas fa-history"></i> Add to History
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Equipment Selected</h5>
                                    <p class="text-muted">Scan a QR code or enter QR data to view equipment information.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- QR Code Generator Section -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-download"></i> Generate & Download QR Codes</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Select Equipment</h6>
                                    <div class="mb-3">
                                        <select class="form-select" id="equipment-select">
                                            <option value="">Choose equipment...</option>
                                            <?php while ($equipment = $equipment_list->fetch_assoc()): ?>
                                                <option value="<?php echo $equipment['id']; ?>" 
                                                        data-qr="<?php echo htmlspecialchars($equipment['qr_code']); ?>"
                                                        data-name="<?php echo htmlspecialchars($equipment['name']); ?>">
                                                    <?php echo htmlspecialchars($equipment['name']); ?> 
                                                    (<?php echo htmlspecialchars($equipment['serial_number']); ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">QR Code Size</label>
                                        <select class="form-select" id="qr-size">
                                            <option value="200">Small (200x200)</option>
                                            <option value="300" selected>Medium (300x300)</option>
                                            <option value="400">Large (400x400)</option>
                                            <option value="500">Extra Large (500x500)</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">File Format</label>
                                        <select class="form-select" id="qr-format">
                                            <option value="png" selected>PNG</option>
                                            <option value="jpg">JPG</option>
                                            <option value="svg">SVG</option>
                                        </select>
                                    </div>
                                    
                                    <button class="btn btn-primary" onclick="generateQR()">
                                        <i class="fas fa-qrcode"></i> Generate QR Code
                                    </button>
                                </div>
                                
                                <div class="col-md-6">
                                    <div id="qr-preview" class="text-center" style="display: none;">
                                        <h6>QR Code Preview</h6>
                                        <div id="qr-image-container" class="mb-3">
                                            <!-- QR code will be generated here -->
                                        </div>
                                        <div id="qr-download-buttons" style="display: none;">
                                            <button class="btn btn-success me-2" onclick="downloadQR('png')">
                                                <i class="fas fa-download"></i> Download PNG
                                            </button>
                                            <button class="btn btn-info me-2" onclick="downloadQR('jpg')">
                                                <i class="fas fa-download"></i> Download JPG
                                            </button>
                                            <button class="btn btn-warning" onclick="downloadQR('svg')">
                                                <i class="fas fa-download"></i> Download SVG
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div id="qr-placeholder" class="text-center py-4">
                                        <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                                        <h6 class="text-muted">Select equipment to generate QR code</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
// QR Code Scanner
function onScanSuccess(decodedText, decodedResult) {
    // Handle the scanned code
    document.querySelector('textarea[name="qr_data"]').value = decodedText;
    
    // Auto-submit the form
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="scan_qr">
        <input type="hidden" name="qr_data" value="${decodedText}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function onScanFailure(error) {
    // Handle scan failure, usually better to ignore and keep scanning
    console.warn(`QR scan error = ${error}`);
}

let html5QrcodeScanner = new Html5QrcodeScanner(
    "qr-reader",
    { fps: 10, qrbox: {width: 250, height: 250} },
    /* verbose= */ false);
html5QrcodeScanner.render(onScanSuccess, onScanFailure);

function addToHistory(equipmentId) {
    // Add equipment to technician's history
    fetch('add_to_history.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            equipment_id: equipmentId,
            action: 'qr_scan'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Equipment added to history!');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// QR Code Generator Functions
function generateQR() {
    const equipmentSelect = document.getElementById('equipment-select');
    const qrSize = document.getElementById('qr-size').value;
    const qrFormat = document.getElementById('qr-format').value;
    
    if (!equipmentSelect.value) {
        alert('Please select an equipment first.');
        return;
    }
    
    const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex];
    const qrData = selectedOption.getAttribute('data-qr');
    const equipmentName = selectedOption.getAttribute('data-name');
    
    if (!qrData) {
        alert('This equipment does not have a QR code assigned.');
        return;
    }
    
    // Generate QR code using external API
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${qrSize}x${qrSize}&data=${encodeURIComponent(qrData)}&format=${qrFormat}`;
    
    // Show preview
    const preview = document.getElementById('qr-preview');
    const placeholder = document.getElementById('qr-placeholder');
    const imageContainer = document.getElementById('qr-image-container');
    const downloadButtons = document.getElementById('qr-download-buttons');
    
    imageContainer.innerHTML = `
        <img src="${qrUrl}" alt="QR Code for ${equipmentName}" 
             style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 8px;">
        <p class="mt-2 text-muted small">${equipmentName}</p>
    `;
    
    // Store data for download
    imageContainer.setAttribute('data-qr-url', qrUrl);
    imageContainer.setAttribute('data-equipment-name', equipmentName);
    
    preview.style.display = 'block';
    placeholder.style.display = 'none';
    downloadButtons.style.display = 'block';
}

function downloadQR(format) {
    const imageContainer = document.getElementById('qr-image-container');
    const qrUrl = imageContainer.getAttribute('data-qr-url');
    const equipmentName = imageContainer.getAttribute('data-equipment-name');
    
    if (!qrUrl) {
        alert('Please generate a QR code first.');
        return;
    }
    
    // Create download URL with proper format
    const downloadUrl = qrUrl.replace(/format=[^&]*/, `format=${format}`);
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = `${equipmentName.replace(/[^a-zA-Z0-9]/g, '_')}_QR.${format}`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<style>
.equipment-details {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}

.equipment-details h6 {
    color: #dc3545;
    font-weight: 600;
    margin-bottom: 10px;
}

.equipment-details p {
    margin-bottom: 8px;
}

#qr-reader {
    border: 2px solid #dc3545;
    border-radius: 10px;
    overflow: hidden;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

#qr-preview {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}

#qr-placeholder {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
}
</style>

<?php require_once 'footer.php'; ?> 