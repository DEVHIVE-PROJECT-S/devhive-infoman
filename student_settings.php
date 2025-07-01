<?php
session_start();
if (!isset($_SESSION['student_id'])) { header('Location: login.php'); exit(); }
require 'includes/db.php';
$student_id = $_SESSION['student_id'];
// Fetch student info
$sql = "SELECT s.student_id, s.lrn, s.first_name, s.middle_name, s.last_name, s.gender, s.birthdate, s.address, sec.section_name, gl.level_name
        FROM students s
        JOIN student_enrollments e ON s.student_id = e.student_id
        JOIN sections sec ON e.section_id = sec.section_id
        JOIN grade_levels gl ON sec.grade_level_id = gl.grade_level_id
        WHERE s.student_id = ?
        ORDER BY e.school_year DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) $student = [];
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_name'])) {
        $first_name = $_POST['first_name'] ?? '';
        $middle_name = $_POST['middle_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        if ($first_name && $last_name) {
            $update_sql = "UPDATE students SET first_name=?, middle_name=?, last_name=? WHERE student_id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param('sssi', $first_name, $middle_name, $last_name, $student_id);
            $stmt->execute();
            $success = 'Name updated.';
            // Refresh data
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
        } else {
            $error = 'First and last name are required.';
        }
    } elseif (isset($_POST['update_password'])) {
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if ($password && strlen($password) >= 6) {
            if ($password === $password2) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("UPDATE students SET password=? WHERE student_id=?");
                $stmt2->bind_param('si', $password_hash, $student_id);
                $stmt2->execute();
                $success = 'Password updated.';
            } else {
                $error = 'Passwords do not match.';
            }
        } else {
            $error = 'Password must be at least 6 characters.';
        }
    }
}
// Fetch from emergency_contacts via student_emergency_contacts
$sql_contacts = "SELECT ec.contact_name, ec.contact_number, ec.relationship, ec.address, sec.is_primary
        FROM student_emergency_contacts sec
        JOIN emergency_contacts ec ON sec.contact_id = ec.contact_id
        WHERE sec.student_id = ?";
$stmt_contacts = $conn->prepare($sql_contacts);
$stmt_contacts->bind_param('i', $student_id);
$stmt_contacts->execute();
$emergency_contacts = $stmt_contacts->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            color: #fff;
        }
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 280px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255,255,255,0.2);
            padding: 30px 0;
            display: flex;
            flex-direction: column;
        }
        .logo-section {
            display: flex;
            align-items: center;
            padding: 0 30px;
            margin-bottom: 40px;
        }
        .logo-section img {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            margin-right: 15px;
        }
        .logo-text {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
        }
        .nav-menu {
            flex: 1;
            padding: 0 20px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            margin-bottom: 8px;
            border-radius: 16px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.15);
            color: #fff;
            transform: translateX(5px);
        }
        .nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: #fff;
            border-radius: 2px;
        }
        .nav-icon {
            font-size: 20px;
            margin-right: 15px;
            width: 24px;
            text-align: center;
        }
        .main-content {
            flex: 1;
            padding: 40px 40px 40px 40px;
            overflow-y: auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            padding: 25px 30px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .welcome-section {
            flex: 1;
        }
        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #fff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .welcome-subtitle {
            font-size: 16px;
            color: rgba(255,255,255,0.8);
            font-weight: 400;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .user-info h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .user-info p {
            font-size: 14px;
            color: rgba(255,255,255,0.7);
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
        }
        .card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 30px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 100%);
            padding: 25px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #fff;
        }
        .card-body {
            padding: 0 30px 30px 30px;
        }
        .notification-list { display: flex; flex-direction: column; gap: 12px; }
        .notification-item { display: flex; align-items: flex-start; gap: 12px; padding: 16px; background: rgba(255,255,255,0.08); border-radius: 8px; border-left: 4px solid #3b82f6; }
        .notification-dot { width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; margin-top: 6px; flex-shrink: 0; }
        .notification-content { flex: 1; }
        .notification-text { font-weight: 500; margin-bottom: 2px; }
        .notification-time { font-size: 12px; color: #e0e7ff; }
        @media (max-width: 1100px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .main-content { padding: 20px; }
        }
        @media (max-width: 900px) {
            .dashboard-container { flex-direction: column; }
            .sidebar { width: 100%; border-right: none; border-bottom: 1px solid rgba(255,255,255,0.2); flex-direction: row; padding: 10px 0; }
            .logo-section { margin-bottom: 0; }
            .nav-menu { flex-direction: row; padding: 0 10px; }
            .nav-item { margin-bottom: 0; margin-right: 8px; }
        }
        @media (max-width: 600px) {
            .main-content { padding: 10px; }
            .card-header, .card-body { padding: 15px; }
        }
        .btn-back { display: inline-block; margin-top: 24px; background: #fff; color: #764ba2; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
        .btn-back:hover { background: #e0e7ff; }
        .msg-success { background: #22c55e; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        .msg-error { background: #ef4444; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        .settings-actions { display: flex; gap: 18px; margin-bottom: 24px; }
        .settings-actions button { background: #fff; color: #764ba2; border: none; border-radius: 8px; padding: 12px 28px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .settings-actions button.active, .settings-actions button:hover { background: #e0e7ff; }
        .settings-form { display: none; margin-top: 10px; }
        .settings-form.active { display: block; }
    </style>
    <script>
    function showForm(form) {
        document.getElementById('form-name').classList.remove('active');
        document.getElementById('form-password').classList.remove('active');
        document.getElementById('btn-name').classList.remove('active');
        document.getElementById('btn-password').classList.remove('active');
        document.getElementById('form-' + form).classList.add('active');
        document.getElementById('btn-' + form).classList.add('active');
    }
    window.onload = function() {
        showForm('name');
    };
    </script>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo-section">
                <a href="index.php"><img src="assets/pdmhs_logo.png" alt="PDMHS Logo"></a>
                <span class="logo-text">PDMHS</span>
            </div>
            <div class="nav-menu">
                <a href="student_dashboard.php" class="nav-item active"><span class="nav-icon"><i class="fa fa-home"></i></span> Dashboard</a>
                <a href="student_medical_info.php" class="nav-item"><span class="nav-icon"><i class="fa fa-notes-medical"></i></span> Medical Info</a>
                <a href="student_visit_history.php" class="nav-item"><span class="nav-icon"><i class="fa fa-history"></i></span> Visit History</a>
                <a href="student_notifications.php" class="nav-item"><span class="nav-icon"><i class="fa fa-bell"></i></span> Notifications</a>
                <a href="student_4pstracker.php" class="nav-item"><span class="nav-icon"><i class="fa fa-users"></i></span> 4P's</a>
                <a href="student_settings.php" class="nav-item"><span class="nav-icon"><i class="fa fa-cog"></i></span> Settings</a>
            </div>
            <div style="margin-top:auto; padding: 0 20px;">
                <hr style="border: 1px solid rgba(255,255,255,0.15); margin: 20px 0;">
                <a href="logout.php" class="nav-item" style="color:#ef4444;"><span class="nav-icon"><i class="fa fa-sign-out-alt"></i></span> Logout</a>
            </div>
        </aside>
        <main class="main-content">
            <div class="header">
                <div class="welcome-section">
                    <div class="welcome-title">Settings</div>
                    <div class="welcome-subtitle">Manage your profile and preferences</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fa fa-user-graduate"></i></div>
                    <div class="user-info">
                        <h4>Student</h4>
                        <p>Student Portal</p>
                    </div>
                </div>
            </div>
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header"><i class="fa fa-cog card-icon"></i> <span class="card-title">Settings</span></div>
                    <div class="card-body">
                        <?php if ($success) echo '<div class="msg-success">' . $success . '</div>'; ?>
                        <?php if ($error) echo '<div class="msg-error">' . $error . '</div>'; ?>
                        <div class="settings-actions">
                            <button type="button" id="btn-name" onclick="showForm('name')"><i class="fa fa-user-edit"></i> Update Name</button>
                            <button type="button" id="btn-password" onclick="showForm('password')"><i class="fa fa-key"></i> Change Password</button>
                        </div>
                        <form method="post" id="form-name" class="settings-form">
                            <label for="lrn">LRN (Username)</label>
                            <input type="text" id="lrn" value="<?php echo htmlspecialchars($student['lrn']); ?>" readonly>
                            <label for="first_name">First Name</label>
                            <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                            <label for="middle_name">Middle Name</label>
                            <input type="text" name="middle_name" id="middle_name" value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                            <label for="last_name">Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                            <button type="submit" name="update_name" class="btn"><i class="fa fa-save"></i> Save Name</button>
                        </form>
                        <form method="post" id="form-password" class="settings-form">
                            <label for="password">New Password</label>
                            <input type="password" name="password" id="password" autocomplete="new-password" minlength="6" required>
                            <label for="password2">Confirm New Password</label>
                            <input type="password" name="password2" id="password2" autocomplete="new-password" minlength="6" required>
                            <button type="submit" name="update_password" class="btn"><i class="fa fa-save"></i> Save Password</button>
                        </form>
                        <a href="student_dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>