<?php
session_start();
if (!isset($_SESSION['clinic_id'])) {
    header('Location: login.php?type=clinic');
    exit();
}
require 'includes/db.php';
$visits = [];
$sql = "SELECT v.visit_id, v.visit_date, v.symptoms, v.treatment, v.clinic_staff_id, s.first_name, s.middle_name, s.last_name, cs.first_name AS staff_first, cs.middle_name AS staff_middle, cs.last_name AS staff_last FROM visits v JOIN students s ON v.student_id = s.student_id LEFT JOIN clinic_staff cs ON v.clinic_staff_id = cs.clinic_id ORDER BY v.visit_date DESC";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $visits[] = $row;
}
function csv_escape($v) {
    return '"' . str_replace('"', '""', $v) . '"';
}
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="visit_logs.csv"');
    echo "Date,Student,Reason,Treatment,Staff\n";
    foreach ($visits as $v) {
        $name = trim($v['last_name'] . ', ' . $v['first_name'] . ' ' . $v['middle_name']);
        echo csv_escape($v['visit_date']) . ',' . csv_escape($name) . ',' . csv_escape($v['symptoms']) . ',' . csv_escape($v['treatment']) . ',' . csv_escape($v['staff_first'] ? htmlspecialchars(trim($v['staff_first'] . ' ' . $v['staff_middle'] . ' ' . $v['staff_last'])) : 'N/A') . "\n";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visit Logs - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .container { max-width: 1100px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 40px; }
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
        .view-btn { background: #5fc9c4; color: #fff; border: none; border-radius: 6px; padding: 7px 18px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .view-btn:hover { background: #195b8b; }
        .no-data { color: #e0e7ff; text-align: center; padding: 30px 0; }
        a.btn { display: inline-block; margin-top: 24px; background: #fff; color: #764ba2; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
        a.btn:hover { background: #e0e7ff; }
    </style>
    <script>
    function filterTable() {
        var input = document.getElementById('searchInput');
        var filter = input.value.toUpperCase();
        var table = document.getElementById('visitsTable');
        var trs = table.getElementsByTagName('tr');
        for (var i = 1; i < trs.length; i++) {
            var show = false;
            var tds = trs[i].getElementsByTagName('td');
            for (var j = 0; j < tds.length-1; j++) {
                if (tds[j].innerText.toUpperCase().indexOf(filter) > -1) show = true;
            }
            trs[i].style.display = show ? '' : 'none';
        }
    }
    </script>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-clipboard-list"></i> Clinic Visit Logs</h2>
        <div class="search-bar">
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by student, reason, or staff...">
            <a href="?export=csv" class="export-btn"><i class="fa fa-download"></i> Export CSV</a>
        </div>
        <table id="visitsTable">
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
                <?php if (count($visits) === 0) { ?>
                    <tr><td colspan="6" class="no-data">No visit logs found.</td></tr>
                <?php } else { foreach ($visits as $v) { ?>
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
        <a href="clinic_dashboard.php" class="btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html> 