<?php
session_start();
if (!isset($_SESSION['clinic_id'])) {
    header('Location: login.php?type=clinic');
    exit();
}
require 'includes/db.php';
$tab = $_GET['tab'] ?? 'visits';
$success = $error = '';
// Visit Logs Report
$visit_logs = [];
if ($tab === 'visits') {
    $from = $_GET['from'] ?? '';
    $to = $_GET['to'] ?? '';
    $where = '';
    if ($from && $to) {
        $where = "WHERE v.visit_date BETWEEN '" . $conn->real_escape_string($from) . "' AND '" . $conn->real_escape_string($to) . " 23:59:59'";
    }
    $sql = "SELECT v.visit_id, v.visit_date, v.symptoms, v.treatment, v.clinic_staff_id, s.first_name, s.middle_name, s.last_name, cs.first_name AS staff_first, cs.middle_name AS staff_middle, cs.last_name AS staff_last FROM visits v JOIN students s ON v.student_id = s.student_id LEFT JOIN clinic_staff cs ON v.clinic_staff_id = cs.clinic_id $where ORDER BY v.visit_date DESC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) $visit_logs[] = $row;
    // Export CSV
    if (isset($_GET['export']) && $_GET['export'] === 'visits') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="visit_logs_report.csv"');
        echo "Date,Student,Reason,Treatment,Staff\n";
        foreach ($visit_logs as $v) {
            $name = trim($v['last_name'] . ', ' . $v['first_name'] . ' ' . $v['middle_name']);
            echo '"' . $v['visit_date'] . '","' . $name . '","' . $v['symptoms'] . '","' . $v['treatment'] . '","' . ($v['staff_first'] ? htmlspecialchars(trim($v['staff_first'] . ' ' . $v['staff_middle'] . ' ' . $v['staff_last'])) : 'N/A') . "\n";
        }
        exit();
    }
}
// Medication Usage Report (placeholder, as no logs table yet)
$med_usage = [];
if ($tab === 'medications') {
    // If you add a medication usage log table, fetch and filter here
    // For now, just show all medications
    $sql = "SELECT * FROM medications ORDER BY expiration_date IS NULL, expiration_date ASC, name ASC";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) $med_usage[] = $row;
    if (isset($_GET['export']) && $_GET['export'] === 'medications') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="medication_usage_report.csv"');
        echo "Name,Description,Quantity,Unit,Expiration Date\n";
        foreach ($med_usage as $m) {
            echo '"' . $m['name'] . '","' . $m['description'] . '","' . $m['quantity'] . '","' . $m['unit'] . '","' . $m['expiration_date'] . "\n";
        }
        exit();
    }
}
// Summary Statistics
$summary = [];
if ($tab === 'summary') {
    $sql = "SELECT COUNT(*) AS total_visits FROM visits";
    $summary['total_visits'] = $conn->query($sql)->fetch_assoc()['total_visits'] ?? 0;
    $sql = "SELECT COUNT(*) AS total_students FROM students";
    $summary['total_students'] = $conn->query($sql)->fetch_assoc()['total_students'] ?? 0;
    $sql = "SELECT COUNT(*) AS total_medications FROM medications";
    $summary['total_medications'] = $conn->query($sql)->fetch_assoc()['total_medications'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .container { max-width: 1000px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 40px; }
        h2 { font-size: 2rem; margin-bottom: 24px; }
        .tabs { display: flex; gap: 18px; margin-bottom: 24px; }
        .tab-btn { background: #fff; color: #764ba2; border: none; border-radius: 8px; padding: 10px 22px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .tab-btn.active, .tab-btn:hover { background: #764ba2; color: #fff; }
        .export-btn { background: #fff; color: #764ba2; border: none; border-radius: 8px; padding: 10px 22px; font-weight: 600; margin-left: 18px; cursor: pointer; }
        .export-btn:hover { background: #e0e7ff; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; }
        th, td { padding: 14px 10px; text-align: left; }
        th { background: rgba(255,255,255,0.12); color: #fff; font-weight: 600; }
        tr:nth-child(even) { background: rgba(255,255,255,0.04); }
        tr:hover { background: rgba(255,255,255,0.15); }
        .no-data { color: #e0e7ff; text-align: center; padding: 30px 0; }
        .summary-cards { display: flex; gap: 32px; margin-top: 32px; }
        .summary-card { background: rgba(255,255,255,0.12); border-radius: 14px; padding: 32px; flex: 1; text-align: center; }
        .summary-title { font-size: 1.2rem; color: #e0e7ff; margin-bottom: 12px; }
        .summary-value { font-size: 2.2rem; font-weight: 700; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-file-medical"></i> Clinic Reports</h2>
        <div class="tabs">
            <a href="?tab=visits" class="tab-btn<?php if($tab==='visits') echo ' active'; ?>">Visit Logs</a>
            <a href="?tab=medications" class="tab-btn<?php if($tab==='medications') echo ' active'; ?>">Medication Usage</a>
            <a href="?tab=summary" class="tab-btn<?php if($tab==='summary') echo ' active'; ?>">Summary</a>
        </div>
        <?php if ($tab === 'visits') { ?>
            <form method="get" style="margin-bottom:18px;">
                <input type="hidden" name="tab" value="visits">
                <label>From: <input type="date" name="from" value="<?php echo htmlspecialchars($_GET['from'] ?? ''); ?>"></label>
                <label>To: <input type="date" name="to" value="<?php echo htmlspecialchars($_GET['to'] ?? ''); ?>"></label>
                <button type="submit" class="tab-btn"><i class="fa fa-filter"></i> Filter</button>
                <a href="?tab=visits&export=visits<?php if(isset($_GET['from'])) echo '&from=' . urlencode($_GET['from']); if(isset($_GET['to'])) echo '&to=' . urlencode($_GET['to']); ?>" class="export-btn"><i class="fa fa-download"></i> Export CSV</a>
            </form>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Reason</th>
                        <th>Treatment</th>
                        <th>Staff</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($visit_logs) === 0) { ?>
                        <tr><td colspan="5" class="no-data">No visit logs found.</td></tr>
                    <?php } else { foreach ($visit_logs as $v) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($v['visit_date']); ?></td>
                            <td><?php echo htmlspecialchars(trim($v['last_name'] . ', ' . $v['first_name'] . ' ' . $v['middle_name'])); ?></td>
                            <td><?php echo htmlspecialchars($v['symptoms']); ?></td>
                            <td><?php echo htmlspecialchars($v['treatment']); ?></td>
                            <td><?php echo ($v['staff_first'] ? htmlspecialchars(trim($v['staff_first'] . ' ' . $v['staff_middle'] . ' ' . $v['staff_last'])) : 'N/A'); ?></td>
                        </tr>
                    <?php }} ?>
                </tbody>
            </table>
        <?php } elseif ($tab === 'medications') { ?>
            <a href="?tab=medications&export=medications" class="export-btn"><i class="fa fa-download"></i> Export CSV</a>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Expiration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($med_usage) === 0) { ?>
                        <tr><td colspan="5" class="no-data">No medication usage data found.</td></tr>
                    <?php } else { foreach ($med_usage as $m) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['name']); ?></td>
                            <td><?php echo htmlspecialchars($m['description']); ?></td>
                            <td><?php echo htmlspecialchars($m['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($m['unit']); ?></td>
                            <td><?php echo htmlspecialchars($m['expiration_date']); ?></td>
                        </tr>
                    <?php }} ?>
                </tbody>
            </table>
        <?php } elseif ($tab === 'summary') { ?>
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-title">Total Clinic Visits</div>
                    <div class="summary-value"><?php echo $summary['total_visits']; ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-title">Total Students</div>
                    <div class="summary-value"><?php echo $summary['total_students']; ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-title">Total Medications</div>
                    <div class="summary-value"><?php echo $summary['total_medications']; ?></div>
                </div>
            </div>
        <?php } ?>
        <a href="clinic_dashboard.php" class="export-btn" style="margin-top:24px;"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html> 