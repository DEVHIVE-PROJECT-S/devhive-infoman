<?php
session_start();
if (!isset($_SESSION['clinic_id'])) {
    header('Location: login.php?type=clinic');
    exit();
}
require 'includes/db.php';
// Fetch clinic staff info
$clinic_id = $_SESSION['clinic_id'];
$clinic_sql = "SELECT first_name, middle_name, last_name FROM clinic_staff WHERE clinic_id = ?";
$stmt = $conn->prepare($clinic_sql);
$stmt->bind_param('i', $clinic_id);
$stmt->execute();
$clinic = $stmt->get_result()->fetch_assoc();
$clinic_full_name = htmlspecialchars(trim($clinic['first_name'] . ' ' . $clinic['middle_name'] . ' ' . $clinic['last_name']));
// Fetch real stats from the database if available, otherwise set to 0
$active_visits = 0; // TODO: Query for today's active visits
$total_students = 0; // TODO: Query for total student records
$pending_treatments = 0; // TODO: Query for pending treatments
$recent_activity = []; // TODO: Query for recent activity

// Fetch counts for dashboard cards
// Student Records
$student_count = $conn->query("SELECT COUNT(*) FROM students")->fetch_row()[0];
// Visit Logs
$visit_count = $conn->query("SELECT COUNT(*) FROM visits")->fetch_row()[0];
// Medications
$med_count = $conn->query("SELECT COUNT(*) FROM medications")->fetch_row()[0];
// Reports (just show total visits for now)
$report_count = $visit_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Staff Dashboard - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            color: #ffffff;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-right: 1px solid rgba(255, 255, 255, 0.2);
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
            color: #ffffff;
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
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-item:hover, .nav-item.active {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
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
            background: #ffffff;
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
            padding: 30px;
            overflow-y: auto;
        }
        
        .header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 40px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 25px 30px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .welcome-section {
            flex: 1;
        }
        
        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
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
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .user-info h4 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        
        .user-info p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        .search-section {
            margin-bottom: 30px;
            position: relative;
        }
        
        .search-bar {
            display: flex;
            gap: 15px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 20px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .search-bar input {
            flex: 1;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 16px;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
        }
        
        .search-bar input::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .search-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            padding: 15px 30px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            background: rgba(255, 255, 255, 0.15);
        }
        
        .stat-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 100%);
            padding: 25px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;
        }
        
        .card-body {
            padding: 0;
        }
        
        .action-item {
            display: flex;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            color: #ffffff;
            font-family: 'Inter', sans-serif;
        }
        
        .action-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(10px);
        }
        
        .action-item:last-child {
            border-bottom: none;
        }
        
        .action-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 20px;
        }
        
        .action-text {
            font-size: 16px;
            font-weight: 500;
        }
        
        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin-right: 20px;
            margin-top: 8px;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-size: 15px;
            color: #ffffff;
            margin-bottom: 5px;
            line-height: 1.5;
        }
        
        .activity-time {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
        }
        
        @media (max-width: 1200px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 20px 0;
            }
            
            .nav-menu {
                display: flex;
                overflow-x: auto;
                padding: 0 20px;
            }
            
            .nav-item {
                white-space: nowrap;
                margin-right: 10px;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="logo-section">
                <a href="index.php"><img src="assets/pdmhs_logo.png" alt="PDMHS Logo"></a>
                <span class="logo-text">PDMHS</span>
            </div>
            <nav class="nav-menu">
                <a href="clinic_dashboard.php" class="nav-item active"><span class="nav-icon">🏠</span> Dashboard</a>
                <a href="clinic_new_visit.php" class="nav-item"><span class="nav-icon">➕</span> Add New Visit</a>
                <a href="clinic_visit_logs.php" class="nav-item"><span class="nav-icon">📋</span> Visit Logs</a>
                <a href="clinic_medications.php" class="nav-item"><span class="nav-icon">💊</span> Medications</a>
                <a href="clinic_reports.php" class="nav-item"><span class="nav-icon">📊</span> Reports</a>
                <a href="clinic_settings.php" class="nav-item"><span class="nav-icon">⚙️</span> Settings</a>
            </nav>
            <div style="margin-top:auto; padding: 0 20px;">
                <hr style="border: 1px solid rgba(255,255,255,0.15); margin: 20px 0;">
                <a href="logout.php" class="nav-item" style="color:#ef4444;"><span class="nav-icon"><i class="fa fa-sign-out-alt"></i></span> Logout</a>
            </div>
        </div>
        
        <div class="main-content">
            <div class="header">
                <div class="welcome-section">
                    <h1 class="welcome-title">Welcome, <?php echo $clinic_full_name; ?></h1>
                    <p class="welcome-subtitle">Have a nice day at work - PDMHS Medical System</p>
                </div>
                <div class="user-profile">
                    <div class="user-info">
                        <h4><?php echo $clinic_full_name; ?></h4>
                        <p>School Clinic Staff</p>
                    </div>
                    <div class="user-avatar">👩‍⚕️</div>
                </div>
            </div>
            
            <div class="search-section">
                <form class="search-bar" method="get" action="#">
                    <input type="text" name="search" placeholder="Search student by ID or name…">
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
            
            <div class="stats-grid">
                <?php if ($active_visits > 0 || $total_students > 0 || $pending_treatments > 0) { ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $active_visits; ?></div>
                        <div class="stat-label">Active Visits Today</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($total_students); ?></div>
                        <div class="stat-label">Total Student Records</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $pending_treatments; ?></div>
                        <div class="stat-label">Pending Treatments</div>
                    </div>
                <?php } else { ?>
                    <div class="stat-card" style="grid-column: span 3; text-align:center;">
                        <div class="stat-label">No activity yet.</div>
                    </div>
                <?php } ?>
            </div>
            
            <div class="dashboard-grid">
                <!-- Student Records Card -->
                <div class="card">
                    <div class="card-header"><span class="card-title">👥 Student Records</span></div>
                    <div class="card-body" style="padding: 30px;">
                        <div style="font-size:2.5rem;font-weight:700;"> <?php echo $student_count; ?> </div>
                        <div style="color:#e0e7ff;margin-bottom:18px;">Total Students</div>
                        <a href="clinic_student_records.php" class="action-item" style="text-decoration:none;justify-content:center;"><div class="action-icon">👁️</div><div class="action-text">View Records</div></a>
                    </div>
                </div>
                <!-- Visit Logs Card -->
                <div class="card">
                    <div class="card-header"><span class="card-title">📋 Visit Logs</span></div>
                    <div class="card-body" style="padding: 30px;">
                        <div style="font-size:2.5rem;font-weight:700;"> <?php echo $visit_count; ?> </div>
                        <div style="color:#e0e7ff;margin-bottom:18px;">Total Visits</div>
                        <a href="clinic_visit_logs.php" class="action-item" style="text-decoration:none;justify-content:center;"><div class="action-icon">👁️</div><div class="action-text">View Logs</div></a>
                    </div>
                </div>
                <!-- Medications Card -->
                <div class="card">
                    <div class="card-header"><span class="card-title">💊 Medications</span></div>
                    <div class="card-body" style="padding: 30px;">
                        <div style="font-size:2.5rem;font-weight:700;"> <?php echo $med_count; ?> </div>
                        <div style="color:#e0e7ff;margin-bottom:18px;">Medications in Stock</div>
                        <a href="clinic_medications.php" class="action-item" style="text-decoration:none;justify-content:center;"><div class="action-icon">👁️</div><div class="action-text">View Medications</div></a>
                    </div>
                </div>
                <!-- Reports Card -->
                <div class="card">
                    <div class="card-header"><span class="card-title">📊 Reports</span></div>
                    <div class="card-body" style="padding: 30px;">
                        <div style="font-size:2.5rem;font-weight:700;"> <?php echo $report_count; ?> </div>
                        <div style="color:#e0e7ff;margin-bottom:18px;">Total Reports (Visits)</div>
                        <a href="clinic_reports.php" class="action-item" style="text-decoration:none;justify-content:center;"><div class="action-icon">👁️</div><div class="action-text">View Reports</div></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 