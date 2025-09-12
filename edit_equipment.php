<?php
require_once 'includes/session.php';
require_once 'includes/db.php';
requireLogin();

if (!isset($_GET['asset_tag'], $_GET['type'])) {
    die("❌ Invalid request.");
}

$asset_tag = $conn->real_escape_string($_GET['asset_tag']);
$type = strtolower($_GET['type']);

// Map to actual table names
$map = [
    'desktop' => 'desktop',
    'laptop' => 'laptops',
    'printer' => 'printers',
    'access point' => 'accesspoint',
    'switch' => 'switch',
    'telephone' => 'telephone'
];

if (!array_key_exists($type, $map)) {
    die("Invalid equipment type.");
}

$table = $map[$type];

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [];
    $params = [];
    $typestr = '';

    foreach ($_POST as $key => $value) {
        if ($key === 'asset_tag') continue; // don't update PK
        $fields[] = "`$key`=?";
        $params[] = $value;
        $typestr .= 's';
    }
    $params[] = $asset_tag;
    $typestr .= 's';

    $sql = "UPDATE `$table` SET " . implode(", ", $fields) . " WHERE asset_tag=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($typestr, ...$params);

    if ($stmt->execute()) {
        header("Location: equipment.php?updated=1");
        exit;
    } else {
        echo "❌ Update failed: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch current record
$res = $conn->query("SELECT * FROM `$table` WHERE asset_tag = '$asset_tag' LIMIT 1");
if ($res->num_rows === 0) {
    die("❌ Equipment not found.");
}
$data = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Equipment</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
  <h2>✏️ Edit Equipment (<?php echo ucfirst($type); ?>)</h2>
  <form method="POST">
    <?php foreach ($data as $field => $value): ?>
      <div class="mb-3">
        <label class="form-label"><?php echo ucfirst(str_replace("_", " ", $field)); ?></label>
        <?php if ($field === 'asset_tag'): ?>
          <input type="text" class="form-control" name="asset_tag" value="<?php echo htmlspecialchars($value); ?>" readonly>
        <?php elseif (in_array($field, ['remarks','hardware_specifications','software_specifications'])): ?>
          <textarea name="<?php echo $field; ?>" class="form-control"><?php echo htmlspecialchars($value); ?></textarea>
        <?php elseif ($field === 'date_acquired'): ?>
          <input type="date" class="form-control" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php elseif ($field === 'unit_price'): ?>
          <input type="number" step="0.01" class="form-control" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php else: ?>
          <input type="text" class="form-control" name="<?php echo $field; ?>" value="<?php echo htmlspecialchars($value); ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-danger">💾 Save Changes</button>
    <a href="equipment.php" class="btn btn-secondary">Cancel</a>
  </form>
</body>
</html>
