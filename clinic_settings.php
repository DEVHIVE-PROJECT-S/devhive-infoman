<?php
session_start();
if (!isset($_SESSION['clinic_id'])) {
    header('Location: login.php?type=clinic');
    exit();
}
require 'includes/db.php';
$clinic_id = $_SESSION['clinic_id'];
// Fetch clinic staff info
$sql = "SELECT first_name, middle_name, last_name, username FROM clinic_staff WHERE clinic_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $clinic_id);
$stmt->execute();
$clinic = $stmt->get_result()->fetch_assoc();
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($first_name && $last_name && $username) {
        $update_sql = "UPDATE clinic_staff SET first_name=?, middle_name=?, last_name=?, username=? WHERE clinic_id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('ssssi', $first_name, $middle_name, $last_name, $username, $clinic_id);
        $stmt->execute();
        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE clinic_staff SET password=? WHERE clinic_id=?");
            $stmt2->bind_param('si', $password_hash, $clinic_id);
            $stmt2->execute();
        }
        $success = 'Profile updated.';
        // Refresh data
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $clinic_id);
        $stmt->execute();
        $clinic = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'First name, last name, and username are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .container { max-width: 600px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 40px; }
        h2 { font-size: 2rem; margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #e0e7ff; }
        input { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.12); color: #fff; margin-bottom: 18px; font-size: 1rem; }
        input:focus { outline: none; border-color: #764ba2; background: rgba(255,255,255,0.18); }
        .btn { background: #5fc9c4; color: #fff; border: none; border-radius: 8px; padding: 14px 32px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #195b8b; }
        .msg-success { background: #22c55e; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        .msg-error { background: #ef4444; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        .profile-info { margin-bottom: 24px; }
        .profile-info span { display: block; margin-bottom: 6px; color: #e0e7ff; }
        a.btn-back { display: inline-block; margin-top: 24px; background: #fff; color: #764ba2; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
        a.btn-back:hover { background: #e0e7ff; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-cog"></i> Clinic Settings</h2>
        <?php if ($success) echo '<div class="msg-success">' . $success . '</div>'; ?>
        <?php if ($error) echo '<div class="msg-error">' . $error . '</div>'; ?>
        <div class="profile-info">
            <span><b>Username:</b> <?php echo htmlspecialchars($clinic['username']); ?></span>
        </div>
        <div style="display:flex; gap:16px; margin-bottom:18px;">
            <button type="button" class="btn" onclick="enableEdit()"><i class="fa fa-edit"></i> Edit Information</button>
            <button type="button" class="btn" onclick="focusPassword()"><i class="fa fa-key"></i> Change Password</button>
        </div>
        <form method="post" id="settingsForm">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($clinic['first_name']); ?>" required readonly>
            <label for="middle_name">Middle Name</label>
            <input type="text" name="middle_name" id="middle_name" value="<?php echo htmlspecialchars($clinic['middle_name']); ?>" readonly>
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($clinic['last_name']); ?>" required readonly>
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($clinic['username']); ?>" required readonly>
            <label for="password">New Password (leave blank to keep current)</label>
            <input type="password" name="password" id="password" autocomplete="new-password" readonly>
            <button type="submit" class="btn"><i class="fa fa-save"></i> Save Changes</button>
        </form>
        <a href="clinic_dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    <script>
    function enableEdit() {
        document.querySelectorAll('#settingsForm input').forEach(function(input) {
            input.removeAttribute('readonly');
        });
        document.getElementById('first_name').focus();
    }
    function focusPassword() {
        document.getElementById('password').removeAttribute('readonly');
        document.getElementById('password').focus();
    }
    </script>
</body>
</html> 