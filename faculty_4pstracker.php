<?php
session_start();
if (!isset($_SESSION['faculty_id'])) {
    header('Location: login.php?type=faculty');
    exit();
}
require 'includes/db.php';
$faculty_id = $_SESSION['faculty_id'];

// Get faculty's section and grade level
$faculty_sql = "SELECT f.honorific, f.first_name, f.middle_name, f.last_name, f.section_id, s.grade_level_id, gl.level_name
    FROM faculty f
    JOIN sections s ON f.section_id = s.section_id
    JOIN grade_levels gl ON s.grade_level_id = gl.grade_level_id
    WHERE f.faculty_id = ?";
$stmt = $conn->prepare($faculty_sql);
$stmt->bind_param('i', $faculty_id);
$stmt->execute();
$faculty = $stmt->get_result()->fetch_assoc();
$grade_level_id = $faculty['grade_level_id'];
$section_id = $faculty['section_id'];

// Fetch 4Ps students in this section
$students = [];
$sql = "SELECT s.lrn, s.first_name, s.middle_name, s.last_name, g.level_name, sec.section_name, fb.school_year, fh.household_number
        FROM students s
        JOIN student_enrollments e ON s.student_id = e.student_id
        JOIN sections sec ON e.section_id = sec.section_id
        JOIN grade_levels g ON sec.grade_level_id = g.grade_level_id
        JOIN fourps_beneficiaries fb ON s.student_id = fb.student_id
        JOIN fourps_households fh ON fb.household_id = fh.household_id
        WHERE e.section_id = ?";
$stmt2 = $conn->prepare($sql);
$stmt2->bind_param('i', $section_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
while ($row = $result2->fetch_assoc()) $students[] = $row;

// Fetch from emergency_contacts via student_emergency_contacts
$sql = "SELECT ec.contact_name, ec.contact_number, ec.relationship, ec.address, sec.is_primary
        FROM student_emergency_contacts sec
        JOIN emergency_contacts ec ON sec.contact_id = ec.contact_id
        WHERE sec.student_id = ?";
$stmt3 = $conn->prepare($sql);
$stmt3->bind_param('i', $student_id);
$stmt3->execute();
$result3 = $stmt3->get_result();
$emergency_contacts = $result3->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Faculty 4Ps Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .center-card {
            max-width: 1200px;
            margin: 80px auto 0 auto;
            background: rgba(255,255,255,0.13);
            border-radius: 22px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            padding: 48px 56px 36px 56px;
        }
        .card-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 18px;
            text-align: center;
        }
        .table-wrap { overflow-x: auto; margin-bottom: 18px; }
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
        .btn-back {
            display: inline-block;
            margin-top: 18px;
            background: #fff;
            color: #764ba2;
            border: none;
            border-radius: 10px;
            padding: 12px 28px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-back:hover { background: #e0e7ff; }
    </style>
</head>
<body>
    <div class="center-card">
        <div class="card-title"><i class="fa fa-id-card"></i> Faculty 4Ps Student Tracker</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>LRN</th>
                        <th>Full Name</th>
                        <th>Grade & Section</th>
                        <th>School Year</th>
                        <th>4Ps Household #</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stu['lrn']); ?></td>
                            <td><?php echo htmlspecialchars($stu['last_name'] . ', ' . $stu['first_name'] . ' ' . $stu['middle_name']); ?></td>
                            <td><?php echo htmlspecialchars($stu['level_name'] . ' - ' . $stu['section_name']); ?></td>
                            <td><?php echo htmlspecialchars($stu['school_year']); ?></td>
                            <td><?php echo htmlspecialchars($stu['household_number']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;opacity:0.8;">No 4Ps students in your section.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="faculty_dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>