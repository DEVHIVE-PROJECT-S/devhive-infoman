<?php
session_start();
if (!isset($_SESSION['faculty_id'])) {
    header('Location: login.php?type=faculty');
    exit();
}
require 'includes/db.php';
$faculty_id = $_SESSION['faculty_id'];
// Fetch faculty info
$sql = "SELECT first_name, middle_name, last_name, username, subject FROM faculty WHERE faculty_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($first_name && $last_name) {
        $update_sql = "UPDATE faculty SET first_name=?, middle_name=?, last_name=? WHERE faculty_id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('sssi', $first_name, $middle_name, $last_name, $faculty_id);
        $stmt->execute();
        if ($password) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE faculty SET password=? WHERE faculty_id=?");
            $stmt2->bind_param('si', $password_hash, $faculty_id);
            $stmt2->execute();
        }
        $success = 'Profile updated.';
        // Refresh data
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $faculty_id);
        $stmt->execute();
        $faculty = $stmt->get_result()->fetch_assoc();
    } else {
        $error = 'First and last name are required.';
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
        <h2><i class="fa fa-cog"></i> Faculty Settings</h2>
        <?php if ($success) echo '<div class="msg-success">' . $success . '</div>'; ?>
        <?php if ($error) echo '<div class="msg-error">' . $error . '</div>'; ?>
        <div class="profile-info">
            <span><b>Username:</b> <?php echo htmlspecialchars($faculty['username']); ?></span>
            <span><b>Subject:</b> <?php echo htmlspecialchars($faculty['subject']); ?></span>
        </div>
        <form method="post">
            <label for="first_name">First Name</label>
            <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($faculty['first_name']); ?>" required>
            <label for="middle_name">Middle Name</label>
            <input type="text" name="middle_name" id="middle_name" value="<?php echo htmlspecialchars($faculty['middle_name']); ?>">
            <label for="last_name">Last Name</label>
            <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($faculty['last_name']); ?>" required>
            <label for="password">New Password (leave blank to keep current)</label>
            <input type="password" name="password" id="password" autocomplete="new-password">
            <button type="submit" class="btn"><i class="fa fa-save"></i> Save Changes</button>
        </form>
        <a href="faculty_dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html> 