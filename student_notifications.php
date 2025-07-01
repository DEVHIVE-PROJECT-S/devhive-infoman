<?php
session_start();
if (!isset($_SESSION['student_id'])) { header('Location: login.php'); exit(); }
require 'includes/db.php';
$student_id = $_SESSION['student_id'];
$notifications = [];
// Fetch student name
$student_name = '';
$sql = "SELECT first_name, middle_name, last_name FROM students WHERE student_id = ?";
$stmt_name = $conn->prepare($sql);
$stmt_name->bind_param('i', $student_id);
$stmt_name->execute();
$stmt_name->bind_result($first, $middle, $last);
if ($stmt_name->fetch()) {
    $student_name = trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last);
}
$stmt_name->close();
// Fetch recent clinic visits as notifications
$visit_sql = "SELECT visit_date, symptoms FROM visits WHERE student_id = ? ORDER BY visit_date DESC LIMIT 5";
$stmt = $conn->prepare($visit_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $notifications[] = [
        'text' => 'Clinic visit recorded: ' . htmlspecialchars($row['symptoms']),
        'time' => date('M d, Y h:i A', strtotime($row['visit_date']))
    ];
}
// Optionally, add more notifications for profile/password updates if you store them
// Fetch from emergency_contacts via student_emergency_contacts
$sql = "SELECT ec.contact_name, ec.contact_number, ec.relationship, ec.address, sec.is_primary
        FROM student_emergency_contacts sec
        JOIN emergency_contacts ec ON sec.contact_id = ec.contact_id
        WHERE sec.student_id = ?";
// Use $is_primary to highlight the main contact.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, rgb(67, 78, 127) 0%, rgb(107, 92, 122) 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        .dashboard-container {
            min-height: 100vh;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255,255,255,0.2);
            padding: 30px 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
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
            margin-left: 280px; /* same as sidebar width */
            padding: 40px;
            min-height: 100vh;
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
            .sidebar {
                width: 70vw;
                min-width: 200px;
                max-width: 320px;
            }
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }
        @media (max-width: 600px) {
            .main-content { padding: 10px; }
            .card-header, .card-body { padding: 15px; }
        }
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: rgba(255,255,255,0.08);
            border-radius: 14px;
            overflow: hidden;
            margin-top: 0;
        }
        .data-table th, .data-table td {
            padding: 14px 18px;
            text-align: left;
        }
        .data-table th {
            background: rgba(255,255,255,0.13);
            color: #e0e7ff;
            font-weight: 600;
            font-size: 1.08rem;
        }
        .data-table td {
            color: #fff;
            font-size: 1.04rem;
            background: rgba(255,255,255,0.04);
        }
        .data-table tr:not(:last-child) td {
            border-bottom: 1px solid rgba(255,255,255,0.10);
        }
        .data-table tr:last-child td {
            border-bottom: none;
        }
        .data-table tr:hover td {
            background: rgba(118,75,162,0.10);
        }
    </style>
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
                    <div class="welcome-title">Notifications</div>
                    <div class="welcome-subtitle">See your recent activity and clinic updates</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fa fa-user-graduate"></i></div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($student_name); ?></h4>
                        <p>Student</p>
                    </div>
                </div>
            </div>
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header"><i class="fa fa-bell card-icon"></i> <span class="card-title">Recent Notifications</span></div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Notification</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($notifications) === 0) { ?>
                                    <tr><td colspan="2" style="color:#e0e7ff;text-align:center;">No notifications yet.</td></tr>
                                <?php } else { foreach ($notifications as $note) { ?>
                                    <tr>
                                        <td><?php echo $note['time']; ?></td>
                                        <td><?php echo $note['text']; ?></td>
                                    </tr>
                                <?php }} ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>