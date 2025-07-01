<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}
require 'includes/db.php';
$student_id = $_SESSION['student_id'];
// Fetch student info
$student_sql = "SELECT s.lrn, s.first_name, s.middle_name, s.last_name, s.gender, s.birthdate, s.address, sec.section_name, gl.level_name
    FROM students s
    JOIN student_enrollments e ON s.student_id = e.student_id
    JOIN sections sec ON e.section_id = sec.section_id
    JOIN grade_levels gl ON sec.grade_level_id = gl.grade_level_id
    WHERE s.student_id = ?
    ORDER BY e.school_year DESC LIMIT 1";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) {
    $student = [];
}

// After fetching enrollment info
$enroll_sql = "SELECT s.section_name, g.level_name, e.school_year
FROM student_enrollments e
JOIN sections s ON e.section_id = s.section_id
JOIN grade_levels g ON s.grade_level_id = g.grade_level_id
WHERE e.student_id = ? ORDER BY e.school_year DESC LIMIT 1";
$stmt = $conn->prepare($enroll_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();
if (!$enrollment) {
    $enrollment = [];
}

// Fetch medical profile
$med_sql = "SELECT * FROM medical_profiles WHERE student_id = ?";
$stmt = $conn->prepare($med_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$medical = $stmt->get_result()->fetch_assoc();
// Fetch allergies
$allergy_sql = "SELECT a.allergy_name FROM student_allergies sa JOIN allergies a ON sa.allergy_id = a.allergy_id WHERE sa.student_id = ?";
$stmt = $conn->prepare($allergy_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$allergies = $stmt->get_result();
// Fetch fallback allergies selected (if any)
$fallback_allergies = ['Peanuts', 'Pollen', 'Shellfish', 'Eggs', 'Milk', 'Wheat', 'Soy', 'Tree nuts', 'Dust mites', 'Insect stings', 'Latex', 'Pet dander', 'Mold'];
$fallback_selected = [];
if (isset($medical['student_id'])) {
    $res = $conn->query("SELECT allergy_id FROM student_allergies WHERE student_id = $student_id");
    while ($row = $res->fetch_assoc()) {
        if (strpos($row['allergy_id'], 'f_') === 0) {
            $idx = intval(substr($row['allergy_id'], 2));
            if (isset($fallback_allergies[$idx])) $fallback_selected[] = $fallback_allergies[$idx];
        }
    }
}
// Fetch recent visits for this student
$visits = [];
$visit_sql = "SELECT v.visit_date, v.symptoms, v.treatment, cs.first_name AS staff_first, cs.middle_name AS staff_middle, cs.last_name AS staff_last FROM visits v LEFT JOIN clinic_staff cs ON v.clinic_staff_id = cs.clinic_id WHERE v.student_id = ? ORDER BY v.visit_date DESC LIMIT 5";
$stmt = $conn->prepare($visit_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$visit_result = $stmt->get_result();
while ($row = $visit_result->fetch_assoc()) $visits[] = $row;
// Notifications: add a notification for the most recent visit if exists
if (count($visits) > 0) {
    $latest = $visits[0];
    $notifications[] = [
        "Clinic visit recorded: " . htmlspecialchars($latest['symptoms']),
        date('M d, Y h:i A', strtotime($latest['visit_date']))
    ];
}
// Fetch 4P's household number (if applicable)
$fourps_household = null;
$stmt = $conn->prepare("SELECT fh.household_number FROM fourps_beneficiaries fb JOIN fourps_households fh ON fb.household_id = fh.household_id WHERE fb.student_id = ?");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $fourps_household = $row['household_number'];
}
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - PDMHS</title>
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.2);
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            background: rgba(255,255,255,0.15);
        }
        .stat-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .stat-label {
            font-size: 16px;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
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
        .badges { display: flex; flex-wrap: wrap; gap: 6px; }
        .badge { background: #3b82f6; color: #fff; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-allergy { background: #ef4444; }
        .badge-condition { background: #f59e0b; }
        .notification-list { display: flex; flex-direction: column; gap: 12px; }
        .notification-item { display: flex; align-items: flex-start; gap: 12px; padding: 16px; background: rgba(255,255,255,0.08); border-radius: 8px; border-left: 4px solid #3b82f6; }
        .notification-dot { width: 8px; height: 8px; border-radius: 50%; background: #3b82f6; margin-top: 6px; flex-shrink: 0; }
        .notification-content { flex: 1; }
        .notification-text { font-weight: 500; margin-bottom: 2px; }
        .notification-time { font-size: 12px; color: #e0e7ff; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .data-table th { background: rgba(255,255,255,0.08); padding: 12px; text-align: left; font-weight: 600; color: #fff; border-bottom: 1px solid rgba(255,255,255,0.15); font-size: 14px; }
        .data-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.08); font-size: 14px; color: #fff; }
        .data-table tr:hover { background: rgba(255,255,255,0.05); }
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
                    <div class="welcome-title">Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</div>
                    <div class="welcome-subtitle">Manage your medical records and view your health information</div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar"><i class="fa fa-user"></i></div>
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                        <p>Student</p>
                    </div>
                </div>
            </div>
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header"><i class="fa fa-id-card card-icon"></i> <span class="card-title">Profile</span></div>
                    <div class="card-body">
                        <div style="display: flex; flex-wrap: wrap; gap: 18px 32px; align-items: center; font-size: 1.08rem;">
                            <span style="font-size: 1.18rem; font-weight: bold; color: #fff; margin-right: 18px;"> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']); ?> </span>
                            <span><b>Section:</b> <span class="badge"><?php echo isset($enrollment['level_name']) ? htmlspecialchars($enrollment['level_name'] . ' - ' . $enrollment['section_name']) : 'N/A'; ?></span></span>
                            <span><b>LRN:</b> <span class="badge"><?php echo htmlspecialchars($student['lrn']); ?></span></span>
                            <span><b>Birthdate:</b> <span class="badge"><?php echo htmlspecialchars($student['birthdate']); ?></span></span>
                            <span><b>Gender:</b> <span class="badge"><?php echo htmlspecialchars($student['gender']); ?></span></span>
                            <span><b>Address:</b> <span class="badge"><?php echo htmlspecialchars($student['address']); ?></span></span>
                            <?php if ($fourps_household): ?>
    <span><b>4P's Household Number:</b> <span class="badge"><?php echo htmlspecialchars($fourps_household); ?></span></span>
<?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><i class="fa fa-notes-medical card-icon"></i> <span class="card-title">Medical Information</span></div>
                    <div class="card-body">
                        <div style="display: grid; gap: 16px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.08);">
                                <span style="font-weight: 500; color: #e0e7ff;">Blood Type</span>
                                <span style="font-weight: 500; color: #fff;"> <?php echo htmlspecialchars($medical['blood_type'] ?? 'N/A'); ?> </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.08);">
                                <span style="font-weight: 500; color: #e0e7ff;">Disability Status</span>
                                <span style="font-weight: 500; color: #fff;"> <?php echo htmlspecialchars($medical['disability_status'] ?? 'N/A'); ?> </span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.08);">
                                <span style="font-weight: 500; color: #e0e7ff;">Allergies</span>
                                <div class="badges">
                                    <?php if ($allergies->num_rows > 0) { while($a = $allergies->fetch_assoc()) { ?>
                                        <span class="badge badge-allergy"><?php echo htmlspecialchars($a['allergy_name']); ?></span>
                                    <?php }} ?>
                                    <?php foreach ($fallback_selected as $fa) { ?>
                                        <span class="badge badge-allergy"><?php echo htmlspecialchars($fa); ?></span>
                                    <?php } ?>
                                    <?php if ($allergies->num_rows === 0 && count($fallback_selected) === 0) { echo '<span style="color:#fff;">None</span>'; } ?>
                                </div>
                            </div>
                            <?php if (!empty($medical['notes'])) { ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.08);">
                                <span style="font-weight: 500; color: #e0e7ff;">Notes</span>
                                <span style="font-weight: 500; color: #fff;"> <?php echo htmlspecialchars($medical['notes']); ?> </span>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><i class="fa fa-history card-icon"></i> <span class="card-title">Recent Visit History</span></div>
                    <div class="card-body">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th>Treatment</th>
                                    <th>Staff</th>
                                </tr>
                            </thead>
                            <tbody>
<?php if (count($visits) === 0) { ?>
                                <tr>
                                    <td colspan="4" style="text-align:center; color:#e0e7ff;">No visit history yet.</td>
                                </tr>
<?php } else { foreach ($visits as $v) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v['visit_date']); ?></td>
                                    <td><?php echo htmlspecialchars($v['symptoms']); ?></td>
                                    <td><?php echo htmlspecialchars($v['treatment']); ?></td>
                                    <td><?php echo ($v['staff_first'] ? htmlspecialchars(trim($v['staff_first'] . ' ' . $v['staff_middle'] . ' ' . $v['staff_last'])) : 'N/A'); ?></td>
                                </tr>
<?php }} ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (!empty($notifications)) { ?>
                <div class="card">
                    <div class="card-header"><i class="fa fa-bell card-icon"></i> <span class="card-title">Recent Notifications</span></div>
                    <div class="card-body">
                        <div class="notification-list">
                            <?php foreach ($notifications as $note) { ?>
                            <div class="notification-item">
                                <div class="notification-dot"></div>
                                <div class="notification-content">
                                    <div class="notification-text"><?php echo htmlspecialchars($note[0]); ?></div>
                                    <div class="notification-time"><?php echo htmlspecialchars($note[1]); ?></div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </main>
    </div>
</body>
</html>