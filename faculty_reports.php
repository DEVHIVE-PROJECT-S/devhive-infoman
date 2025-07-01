<?php
session_start();
if (!isset($_SESSION['faculty_id'])) {
    header('Location: login.php?type=faculty');
    exit();
}
require 'includes/db.php';
$faculty_id = $_SESSION['faculty_id'];
// Get section and grade assigned to this faculty
$section_sql = "SELECT f.honorific, f.first_name, f.middle_name, f.last_name, f.section_id, s.grade_level_id, gl.level_name
    FROM faculty f
    JOIN sections s ON f.section_id = s.section_id
    JOIN grade_levels gl ON s.grade_level_id = gl.grade_level_id
    WHERE f.faculty_id = ?";
$stmt = $conn->prepare($section_sql);
$stmt->bind_param('i', $faculty_id);
$stmt->execute();
$stmt->bind_result($honorific, $first_name, $middle_name, $last_name, $section_id, $grade_level_id, $level_name);
$stmt->fetch();
$stmt->close();

$reports = [];
$date_from = $_POST['date_from'] ?? '';
$date_to = $_POST['date_to'] ?? '';
$condition = $_POST['condition'] ?? '';
$where = "";
$params = [];
$types = '';

if ($date_from && $date_to) {
    $where .= " AND v.visit_date BETWEEN ? AND ?";
    $params[] = $date_from . " 00:00:00";
    $params[] = $date_to . " 23:59:59";
    $types .= 'ss';
}
if ($condition) {
    $where .= " AND v.diagnosis LIKE ?";
    $params[] = "%$condition%";
    $types .= 's';
}

// Get all students in this section
$student_ids = [];
$stu_sql = "SELECT student_id FROM student_enrollments WHERE section_id = ?";
$stmt = $conn->prepare($stu_sql);
$stmt->bind_param('i', $section_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $student_ids[] = $row['student_id'];
$stmt->close();

if (count($student_ids) > 0) {
    $in = implode(',', array_fill(0, count($student_ids), '?'));
    $types2 = str_repeat('i', count($student_ids));
    $sql = "SELECT v.visit_date, v.diagnosis, v.treatment, s.lrn, s.first_name, s.middle_name, s.last_name FROM visits v JOIN students s ON v.student_id = s.student_id WHERE v.student_id IN ($in) $where ORDER BY v.visit_date DESC";
    $all_types = $types2 . $types;
    $stmt = $conn->prepare($sql);
    $bind_params = array_merge($student_ids, $params);
    $stmt->bind_param($all_types, ...$bind_params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $reports[] = $row;
    $stmt->close();
}

// CSV Export
if (isset($_POST['export_csv']) && count($reports) > 0) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="faculty_report.csv"');
    echo "Date,Student,LRN,Diagnosis,Treatment\n";
    foreach ($reports as $r) {
        $name = trim($r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name']);
        echo '"' . $r['visit_date'] . '","' . $name . '","' . $r['lrn'] . '","' . $r['diagnosis'] . '","' . $r['treatment'] . "\n";
    }
    exit();
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
        .container { max-width: 900px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 40px; }
        h2 { font-size: 2rem; margin-bottom: 24px; }
        form { display: flex; gap: 18px; flex-wrap: wrap; align-items: flex-end; margin-bottom: 28px; }
        label { display: block; margin-bottom: 6px; font-weight: 500; color: #e0e7ff; }
        input, select { padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem; margin-bottom: 0; }
        .btn { background: #5fc9c4; color: #fff; border: none; border-radius: 8px; padding: 12px 28px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #195b8b; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; background: rgba(255,255,255,0.08); border-radius: 12px; overflow: hidden; }
        th, td { padding: 14px 10px; text-align: left; }
        th { background: rgba(255,255,255,0.12); color: #fff; font-weight: 600; }
        tr:nth-child(even) { background: rgba(255,255,255,0.04); }
        tr:hover { background: rgba(255,255,255,0.15); }
        .download-btn { background: #fff; color: #764ba2; border: none; border-radius: 6px; padding: 7px 18px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .download-btn:hover { background: #e0e7ff; }
        .no-data { color: #e0e7ff; text-align: center; padding: 30px 0; }
        a.btn-back { display: inline-block; margin-top: 24px; background: #fff; color: #764ba2; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
        a.btn-back:hover { background: #e0e7ff; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-file-medical"></i> Faculty Reports</h2>
        <form method="post">
            <div>
                <label for="date_from">From</label>
                <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>" required>
            </div>
            <div>
                <label for="date_to">To</label>
                <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>" required>
            </div>
            <div>
                <label for="condition">Health Condition</label>
                <select name="condition" id="condition">
                    <option value="">All</option>
                    <option value="Asthma" <?php if($condition==='Asthma') echo 'selected'; ?>>Asthma</option>
                    <option value="Fever" <?php if($condition==='Fever') echo 'selected'; ?>>Fever</option>
                    <option value="Allergy" <?php if($condition==='Allergy') echo 'selected'; ?>>Allergy</option>
                </select>
            </div>
            <button type="submit" class="btn"><i class="fa fa-search"></i> Generate Report</button>
            <?php if (count($reports) > 0) { ?>
            <button type="submit" name="export_csv" value="1" class="btn" style="background:#fff;color:#764ba2;"><i class="fa fa-download"></i> Download CSV</button>
            <button type="button" onclick="window.print()" class="btn" style="background:#fff;color:#764ba2;"><i class="fa fa-print"></i> Print</button>
            <?php } ?>
        </form>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student</th>
                    <th>LRN</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reports) === 0) { ?>
                    <tr><td colspan="5" class="no-data">No records found for the selected filters.</td></tr>
                <?php } else { foreach ($reports as $r) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['visit_date']); ?></td>
                        <td><?php echo htmlspecialchars(trim($r['last_name'] . ', ' . $r['first_name'] . ' ' . $r['middle_name'])); ?></td>
                        <td><?php echo htmlspecialchars($r['lrn']); ?></td>
                        <td><?php echo htmlspecialchars($r['diagnosis']); ?></td>
                        <td><?php echo htmlspecialchars($r['treatment']); ?></td>
                    </tr>
                <?php }} ?>
            </tbody>
        </table>
        <a href="faculty_dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>