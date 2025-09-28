<?php
session_start();
require_once '../includes/session.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];
$equipment_info = null;
$error = '';
$success = '';

/**
 * Search all equipment tables by asset_tag or id
 */
function findEquipment($conn, $qr_data) {
    $tables = ['desktop', 'laptops', 'printers', 'accesspoint', 'switch', 'telephone'];
    foreach ($tables as $table) {
        // try by asset_tag
        $stmt = $conn->prepare("SELECT *, ? AS table_name FROM $table WHERE asset_tag = ?");
        $stmt->bind_param("ss", $table, $qr_data);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row;
        }
        $stmt->close();

        // try by id if qr_data is numeric
        if (ctype_digit($qr_data)) {
            $id = (int)$qr_data;
            $stmt = $conn->prepare("SELECT *, ? AS table_name FROM $table WHERE id = ?");
            $stmt->bind_param("si", $table, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $stmt->close();
                return $row;
            }
            $stmt->close();
        }
    }
    return null;
}

// Handle QR code processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'scan_qr':
            $qr_data = trim($_POST['qr_data'] ?? '');

            if (!empty($qr_data)) {
                // If QR data was Base64-encoded
                $decodedText = base64_decode($qr_data, true);
                if ($decodedText !== false && !empty($decodedText)) {
                    $qr_data = $decodedText;
                }

                // If QR contains JSON (from generated QR)
                $decoded = json_decode($qr_data, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($decoded['asset_tag'])) {
                    $equipment_info = $decoded;
                    $success = '✅ Equipment loaded from QR code (JSON data)';
                } else {
                    // Otherwise, search across all tables
                    $equipment_info = findEquipment($conn, $qr_data);
                    if ($equipment_info) {
                        $success = '✅ Equipment found in ' . htmlspecialchars($equipment_info['table_name']) . ' table';
                    } else {
                        $error = '❌ No equipment found with this QR code.';
                    }
                }
            } else {
                $error = '⚠️ Please enter QR code data.';
            }
            break;

        case 'upload_qr':
            if (isset($_FILES['qr_file']) && $_FILES['qr_file']['error'] === 0) {
                $file_content = file_get_contents($_FILES['qr_file']['tmp_name']);
                $qr_data = trim($file_content);

                if (!empty($qr_data)) {
                    $decoded = json_decode($qr_data, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($decoded['asset_tag'])) {
                        $equipment_info = $decoded;
                        $success = '✅ Equipment loaded from uploaded QR file (JSON data)';
                    } else {
                        $equipment_info = findEquipment($conn, $qr_data);
                        if ($equipment_info) {
                            $success = '✅ Equipment found in ' . htmlspecialchars($equipment_info['table_name']) . ' table';
                        } else {
                            $error = '❌ No equipment found with this QR code.';
                        }
                    }
                } else {
                    $error = '⚠️ Could not read QR code from file.';
                }
            } else {
                $error = '⚠️ Please select a valid QR file.';
            }
            break;
    }
}

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
                    <!-- Scanner -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-camera"></i> Scan QR Code</h5>
                        </div>
                        <div class="card-body text-center">
                            <div id="qr-reader" style="width: 100%; max-width: 400px; margin: 0 auto; display: none;"></div>
                            
                            <button id="start-scan-btn" class="btn btn-danger mt-3">
                                <i class="fas fa-qrcode"></i> Start QR Scan
                            </button>
                            
                            <button id="stop-scan-btn" class="btn btn-secondary mt-3" style="display: none;">
                                <i class="fas fa-stop"></i> Stop Scan
                            </button>
                            
                            <p class="text-muted small mt-2">Position the QR code within the scanner frame</p>
                        </div>
                    </div>

                    <!-- Upload QR -->
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

                    <!-- Manual Entry -->
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
                                    <?php foreach ($equipment_info as $key => $val): ?>
                                        <?php if ($key !== 'table_name'): ?>
                                            <p><strong><?php echo ucfirst($key); ?>:</strong> <?php echo htmlspecialchars($val); ?></p>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <div class="mt-3">
                                        <button class="btn btn-outline-success" onclick="addToHistory(<?php echo $equipment_info['id']; ?>, '<?php echo $equipment_info['table_name']; ?>')">
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
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode"></script>
<script>
let html5QrcodeScanner;
let isScanning = false;

function onScanSuccess(decodedText, decodedResult) {
    if (!decodedText || decodedText.trim() === "") {
        console.warn("⚠️ Empty QR detected, ignoring.");
        return;
    }
    console.log("✅ QR scanned:", decodedText);

    const safeValue = btoa(decodedText);

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="scan_qr">
        <input type="hidden" name="qr_data" value="${safeValue}">
    `;
    document.body.appendChild(form);

    if (isScanning && html5QrcodeScanner) {
        html5QrcodeScanner.stop();
        isScanning = false;
        document.getElementById("qr-reader").style.display = "none";
        document.getElementById("start-scan-btn").style.display = "inline-block";
        document.getElementById("stop-scan-btn").style.display = "none";
    }

    form.submit();
}

function onScanFailure(error) {
    console.warn(`⚠️ QR scan error: ${error}`);
}

document.getElementById("start-scan-btn").addEventListener("click", () => {
    if (!isScanning) {
        html5QrcodeScanner = new Html5Qrcode("qr-reader");
        document.getElementById("qr-reader").style.display = "block";
        document.getElementById("start-scan-btn").style.display = "none";
        document.getElementById("stop-scan-btn").style.display = "inline-block";

        html5QrcodeScanner.start(
            { facingMode: "environment" }, 
            { fps: 10, qrbox: { width: 250, height: 250 } },
            onScanSuccess,
            onScanFailure
        ).then(() => {
            isScanning = true;
        }).catch(err => {
            alert("❌ Unable to start scanner: " + err);
        });
    }
});

document.getElementById("stop-scan-btn").addEventListener("click", () => {
    if (isScanning && html5QrcodeScanner) {
        html5QrcodeScanner.stop().then(() => {
            isScanning = false;
            document.getElementById("qr-reader").style.display = "none";
            document.getElementById("start-scan-btn").style.display = "inline-block";
            document.getElementById("stop-scan-btn").style.display = "none";
        }).catch(err => {
            console.error("❌ Failed to stop scanner:", err);
        });
    }
});

function addToHistory(equipmentId, tableName) {
    fetch('add_to_history.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ equipment_id: equipmentId, table_name: tableName, action: 'qr_scan' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Equipment added to history!');
        } else {
            alert('❌ Failed to add to history.');
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
    });
}
</script>

<style>
.equipment-details {
    background-color: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
}
.equipment-details p { margin-bottom: 6px; }
#qr-reader { border: 2px solid #dc3545; border-radius: 10px; }
</style>

<?php require_once 'footer.php'; ?>
