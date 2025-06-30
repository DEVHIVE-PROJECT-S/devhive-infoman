<?php
session_start();
if (!isset($_SESSION['clinic_id'])) {
    header('Location: login.php?type=clinic');
    exit();
}
require 'includes/db.php';

// Handle add/edit/delete actions
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_med'])) {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $qty = intval($_POST['quantity']);
        $unit = trim($_POST['unit']);
        $exp = $_POST['expiration_date'] ?: null;
        if ($name === '' || $qty < 0) {
            $error = 'Name and quantity are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO medications (name, description, quantity, unit, expiration_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('ssiss', $name, $desc, $qty, $unit, $exp);
            if ($stmt->execute()) $success = 'Medication added!';
            else $error = 'Failed to add.';
        }
    } elseif (isset($_POST['edit_med'])) {
        $id = intval($_POST['medication_id']);
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $qty = intval($_POST['quantity']);
        $unit = trim($_POST['unit']);
        $exp = $_POST['expiration_date'] ?: null;
        $stmt = $conn->prepare("UPDATE medications SET name=?, description=?, quantity=?, unit=?, expiration_date=? WHERE medication_id=?");
        $stmt->bind_param('ssissi', $name, $desc, $qty, $unit, $exp, $id);
        if ($stmt->execute()) $success = 'Medication updated!';
        else $error = 'Failed to update.';
    } elseif (isset($_POST['delete_med'])) {
        $id = intval($_POST['medication_id']);
        $stmt = $conn->prepare("DELETE FROM medications WHERE medication_id=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) $success = 'Medication deleted!';
        else $error = 'Failed to delete.';
    }
}
// Search
$search = $_GET['search'] ?? '';
$where = '';
if ($search !== '') {
    $search_sql = '%' . $conn->real_escape_string($search) . '%';
    $where = "WHERE name LIKE '$search_sql' OR description LIKE '$search_sql'";
}
// Fetch medications
$medications = [];
$sql = "SELECT * FROM medications $where ORDER BY expiration_date IS NULL, expiration_date ASC, name ASC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) $medications[] = $row;
// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="medications.csv"');
    echo "Name,Description,Quantity,Unit,Expiration Date\n";
    foreach ($medications as $m) {
        echo '"' . str_replace('"', '""', $m['name']) . '",';
        echo '"' . str_replace('"', '""', $m['description']) . '",';
        echo $m['quantity'] . ',';
        echo '"' . str_replace('"', '""', $m['unit']) . '",';
        echo '"' . $m['expiration_date'] . '"\n';
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medications - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .container { max-width: 900px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 40px; }
        h2 { font-size: 2rem; margin-bottom: 24px; }
        .search-bar { margin-bottom: 18px; }
        .search-bar input { width: 300px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem; }
        .export-btn { background: #fff; color: #764ba2; border: none; border-radius: 8px; padding: 10px 22px; font-weight: 600; margin-left: 18px; cursor: pointer; }
        .export-btn:hover { background: #e0e7ff; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; }
        th, td { padding: 14px 10px; text-align: left; }
        th { background: rgba(255,255,255,0.12); color: #fff; font-weight: 600; }
        tr:nth-child(even) { background: rgba(255,255,255,0.04); }
        tr:hover { background: rgba(255,255,255,0.15); }
        .action-btn { background: #5fc9c4; color: #fff; border: none; border-radius: 6px; padding: 7px 18px; font-weight: 600; cursor: pointer; text-decoration: none; margin-right: 6px; }
        .action-btn.edit { background: #764ba2; }
        .action-btn.delete { background: #ef4444; }
        .action-btn:hover { opacity: 0.85; }
        .no-data { color: #e0e7ff; text-align: center; padding: 30px 0; }
        .form-section { margin: 30px 0 0 0; background: rgba(255,255,255,0.10); border-radius: 14px; padding: 24px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #e0e7ff; }
        input, textarea, select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.12); color: #fff; margin-bottom: 18px; font-size: 1rem; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #764ba2; background: rgba(255,255,255,0.18); }
        .msg-success { background: #22c55e; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        .msg-error { background: #ef4444; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
    </style>
    <script>
    function fillEditForm(id, name, desc, qty, unit, exp) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_desc').value = desc;
        document.getElementById('edit_qty').value = qty;
        document.getElementById('edit_unit').value = unit;
        document.getElementById('edit_exp').value = exp;
        document.getElementById('editForm').style.display = 'block';
        document.getElementById('editForm').scrollIntoView({behavior:'smooth'});
    }
    function closeEditForm() {
        document.getElementById('editForm').style.display = 'none';
    }
    function toggleAddForm() {
        var f = document.getElementById('addForm');
        f.style.display = (f.style.display === 'none' || f.style.display === '') ? 'block' : 'none';
        if (f.style.display === 'block') f.scrollIntoView({behavior:'smooth'});
    }
    </script>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-pills"></i> Clinic Medications</h2>
        <?php if ($success) echo '<div class="msg-success">' . $success . '</div>'; ?>
        <?php if ($error) echo '<div class="msg-error">' . $error . '</div>'; ?>
        <div class="search-bar">
            <form method="get" style="display:inline;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search medication...">
                <button type="submit" class="export-btn"><i class="fa fa-search"></i> Search</button>
            </form>
            <a href="?export=csv" class="export-btn"><i class="fa fa-download"></i> Export CSV</a>
        </div>
        <button class="action-btn" style="margin-bottom:18px;" onclick="toggleAddForm()"><i class="fa fa-plus"></i> Add Medication</button>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Expiration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($medications) === 0) { ?>
                    <tr><td colspan="6" class="no-data">No medications found.</td></tr>
                <?php } else { foreach ($medications as $m) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($m['name']); ?></td>
                        <td><?php echo htmlspecialchars($m['description']); ?></td>
                        <td><?php echo htmlspecialchars($m['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($m['unit']); ?></td>
                        <td><?php echo htmlspecialchars($m['expiration_date']); ?></td>
                        <td>
                            <button class="action-btn edit" onclick="fillEditForm('<?php echo $m['medication_id']; ?>','<?php echo htmlspecialchars(addslashes($m['name'])); ?>','<?php echo htmlspecialchars(addslashes($m['description'])); ?>','<?php echo $m['quantity']; ?>','<?php echo htmlspecialchars(addslashes($m['unit'])); ?>','<?php echo $m['expiration_date']; ?>')"><i class="fa fa-edit"></i> Edit</button>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="medication_id" value="<?php echo $m['medication_id']; ?>">
                                <button type="submit" name="delete_med" class="action-btn delete" onclick="return confirm('Delete this medication?')"><i class="fa fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php }} ?>
            </tbody>
        </table>
        <div class="form-section" id="addForm" style="display:none;">
            <h3>Add Medication</h3>
            <form method="post">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" required>
                <label for="description">Description</label>
                <textarea name="description" id="description"></textarea>
                <label for="quantity">Quantity</label>
                <input type="number" name="quantity" id="quantity" min="0" required>
                <label for="unit">Unit</label>
                <input type="text" name="unit" id="unit">
                <label for="expiration_date">Expiration Date</label>
                <input type="date" name="expiration_date" id="expiration_date">
                <button type="submit" name="add_med" class="action-btn"><i class="fa fa-plus"></i> Add</button>
            </form>
        </div>
        <div class="form-section" id="editForm" style="display:none;">
            <h3>Edit Medication</h3>
            <form method="post">
                <input type="hidden" name="medication_id" id="edit_id">
                <label for="edit_name">Name</label>
                <input type="text" name="name" id="edit_name" required>
                <label for="edit_desc">Description</label>
                <textarea name="description" id="edit_desc"></textarea>
                <label for="edit_qty">Quantity</label>
                <input type="number" name="quantity" id="edit_qty" min="0" required>
                <label for="edit_unit">Unit</label>
                <input type="text" name="unit" id="edit_unit">
                <label for="edit_exp">Expiration Date</label>
                <input type="date" name="expiration_date" id="edit_exp">
                <button type="submit" name="edit_med" class="action-btn edit"><i class="fa fa-save"></i> Save Changes</button>
                <button type="button" class="action-btn delete" onclick="closeEditForm()"><i class="fa fa-times"></i> Close</button>
            </form>
        </div>
        <a href="clinic_dashboard.php" class="export-btn" style="margin-top:24px;"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html> 