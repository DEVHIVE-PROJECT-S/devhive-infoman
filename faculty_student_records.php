<?php
session_start();
if (!isset($_SESSION['faculty_id'])) {
    header('Location: login.php?type=faculty');
    exit();
}
require 'includes/db.php';
$faculty_id = $_SESSION['faculty_id'];
// Get sections assigned to this faculty
$section_sql = "SELECT section_id FROM faculty WHERE faculty_id = ?";
$stmt = $conn->prepare($section_sql);
$stmt->bind_param('i', $faculty_id);
$stmt->execute();
$stmt->bind_result($section_id);
$stmt->fetch();
$stmt->close();
$students = [];
if ($section_id) {
    $sql = "SELECT s.student_id, s.lrn, s.first_name, s.middle_name, s.last_name, sec.section_name, gl.level_name
        FROM students s
        JOIN student_enrollments e ON s.student_id = e.student_id
        JOIN sections sec ON e.section_id = sec.section_id
        JOIN grade_levels gl ON sec.grade_level_id = gl.grade_level_id
        WHERE e.section_id = ? ORDER BY s.last_name, s.first_name";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $section_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
function csv_escape($v) {
    return '"' . str_replace('"', '""', $v) . '"';
}
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="student_records.csv"');
    echo "LRN,Full Name,Section\n";
    foreach ($students as $stu) {
        $name = trim($stu['last_name'] . ', ' . $stu['first_name'] . ' ' . $stu['middle_name']);
        echo csv_escape($stu['lrn']) . ',' . csv_escape($name) . ',' . csv_escape($stu['level_name'] . ' - ' . $stu['section_name']) . "\n";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Records - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, rgb(67, 78, 127) 0%, rgb(107, 92, 122) 100%);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            color: #fff;
            margin: 0;
            padding: 0;
        }
        .container { max-width: 1200px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 48px 56px; }
        h2 { font-size: 2rem; margin-bottom: 24px; }
        .search-bar { margin-bottom: 18px; }
        .search-bar input { width: 300px; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem; }
        .export-btn { background: #fff; color: #764ba2; border: none; border-radius: 8px; padding: 10px 22px; font-weight: 600; margin-left: 18px; cursor: pointer; }
        .export-btn:hover { background: #e0e7ff; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(255,255,255,0.08);
            border-radius: 12px;
            overflow: hidden;
            font-size: 1.08rem;
        }
        th, td {
            padding: 18px 16px;   /* Increased padding for more space */
            text-align: left;
            line-height: 1.6;     /* More vertical space */
        }
        th {
            background: rgba(255,255,255,0.12);
            color: #fff;
            font-weight: 600;
        }
        tr:nth-child(even) { background: rgba(255,255,255,0.04); }
        tr:hover { background: rgba(255,255,255,0.15); }
        .view-btn { background: #5fc9c4; color: #fff; border: none; border-radius: 6px; padding: 7px 18px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .view-btn:hover { background: #195b8b; }
        .no-data { color: #e0e7ff; text-align: center; padding: 30px 0; }
        a.btn { display: inline-block; margin-top: 24px; background: #fff; color: #764ba2; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
        a.btn:hover { background: #e0e7ff; }
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
        .main-content {
            margin-left: 280px; /* same as sidebar width */
            padding: 40px;
            min-height: 100vh;
            overflow-y: auto;
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
    </style>
    <script>
    function filterTable() {
        var input = document.getElementById('searchInput');
        var filter = input.value.toUpperCase();
        var table = document.getElementById('studentsTable');
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
        <h2><i class="fa fa-users"></i> Faculty Student Records</h2>
        <div class="search-bar">
            <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by LRN, name, or section...">
            <a href="?export=csv" class="export-btn"><i class="fa fa-download"></i> Export CSV</a>
        </div>
        <table id="studentsTable">
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Full Name</th>
                    <th>Section</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) === 0) { ?>
                    <tr><td colspan="4" class="no-data">No students found for your section.</td></tr>
                <?php } else { foreach ($students as $stu) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stu['lrn']); ?></td>
                        <td><?php echo htmlspecialchars(trim($stu['last_name'] . ', ' . $stu['first_name'] . ' ' . $stu['middle_name'])); ?></td>
                        <td><?php echo htmlspecialchars($stu['level_name'] . ' - ' . $stu['section_name']); ?></td>
                    </tr>
                <?php }} ?>
            </tbody>
        </table>
        <a href="faculty_dashboard.php" class="btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>