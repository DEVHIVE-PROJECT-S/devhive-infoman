<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}
require 'includes/db.php';
$student_id = $_SESSION['student_id'];

// Fetch student info
$stmt = $conn->prepare("SELECT s.first_name, s.middle_name, s.last_name, s.lrn, s.gender, s.birthdate, s.address, e.section_id, e.grade_level_id, e.school_year, sec.section_name, g.level_name
    FROM students s
    JOIN student_enrollments e ON s.student_id = e.student_id
    JOIN sections sec ON e.section_id = sec.section_id
    JOIN grade_levels g ON e.grade_level_id = g.grade_level_id
    WHERE s.student_id = ?
    ORDER BY e.school_year DESC LIMIT 1");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

$today = date('Y-m-d');

// Fetch existing 4Ps data if any
$fourps = [];
$q = $conn->prepare("SELECT * FROM fourps_beneficiaries fb JOIN fourps_households fh ON fb.household_id = fh.household_id WHERE fb.student_id=?");
$q->bind_param('i', $student_id);
$q->execute();
$fourps = $q->get_result()->fetch_assoc();

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $household_number = trim($_POST['household_id'] ?? '');
    $school_year = trim($_POST['school_year'] ?? '');
    $relationship = trim($_POST['relationship'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');
    $verified = isset($_POST['verified']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');
    $date_registered = $today;

    // 1. Ensure household exists in fourps_households
    $stmt = $conn->prepare("SELECT household_id FROM fourps_households WHERE household_number = ?");
    $stmt->bind_param('s', $household_number);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $household_id = $row['household_id'];
    } else {
        // Insert new household
        $ins = $conn->prepare("INSERT INTO fourps_households (household_number, date_registered) VALUES (?, ?)");
        $ins->bind_param('ss', $household_number, $date_registered);
        if ($ins->execute()) {
            $household_id = $conn->insert_id;
        } else {
            $error = "Failed to create household.";
            $household_id = null;
        }
    }

    if ($household_id) {
        if (isset($_POST['add'])) {
            // Add new record
            $insert = $conn->prepare("INSERT INTO fourps_beneficiaries (student_id, household_id, school_year, relationship, guardian_name, status, verified, notes, date_registered) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->bind_param('iissssiss', $student_id, $household_id, $school_year, $relationship, $guardian_name, $status, $verified, $notes, $date_registered);
            if ($insert->execute()) {
                $success = "4Ps record added.";
            } else {
                $error = "Add failed.";
            }
        } else {
            // Update existing record
            $update = $conn->prepare("UPDATE fourps_beneficiaries SET household_id=?, school_year=?, relationship=?, guardian_name=?, status=?, verified=?, notes=?, date_registered=? WHERE student_id=?");
            $update->bind_param('issssissi', $household_id, $school_year, $relationship, $guardian_name, $status, $verified, $notes, $date_registered, $student_id);
            if ($update->execute()) {
                $success = "4Ps record updated.";
            } else {
                $error = "Update failed.";
            }
        }
        // Refresh $fourps after add/update
        $q = $conn->prepare("SELECT * FROM fourps_beneficiaries fb JOIN fourps_households fh ON fb.household_id = fh.household_id WHERE fb.student_id=?");
        $q->bind_param('i', $student_id);
        $q->execute();
        $fourps = $q->get_result()->fetch_assoc();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>4Ps Beneficiary Registration</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .container { 
            max-width: 1200px;
            margin: 60px auto; 
            background: rgba(255,255,255,0.10); 
            border-radius: 22px; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.12); 
            padding: 44px 64px;
        }
        @media (max-width: 1100px) {
            .container { max-width: 98vw; padding: 18px 2vw; }
        }
        @media (max-width: 900px) {
            .form-columns { flex-direction: column; gap: 0; }
            .container { padding: 18px 4vw; }
        }
        @media (max-width: 700px) {
            .container { padding: 10px 2vw; }
        }
        h2 { font-size: 2.1rem; margin-bottom: 28px; letter-spacing: 0.01em; }
        .form-section {
            background: rgba(255,255,255,0.13);
            border-radius: 16px;
            padding: 24px 22px 18px 22px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(95,201,196,0.06);
        }
        .form-section-title {
            font-size: 1.18rem;
            font-weight: 600;
            color: #e0e7ff;
            margin-bottom: 16px;
            letter-spacing: 0.01em;
        }
        label { display: block; margin-bottom: 7px; font-weight: 500; color: #e0e7ff; font-size: 1.04rem; }
        input, select {
            width: 100%;
            padding: 13px 14px;
            border-radius: 9px;
            border: 1.5px solid rgba(255,255,255,0.22);
            background: rgba(255,255,255,0.15);
            color: #23213a;
            margin-bottom: 18px;
            font-size: 1.01rem;
            transition: border 0.2s, box-shadow 0.2s;
        }
        input:focus, select:focus {
            outline: none;
            border-color: #764ba2;
            background: rgba(255,255,255,0.22);
            box-shadow: 0 0 0 2px #e0e7ff;
        }
        textarea{
            width: 95%;
            padding: 13px 14px;
            border-radius: 9px;
            border: 1.5px solid rgba(255,255,255,0.22);
            background: rgba(255,255,255,0.15);
            color: #23213a;
            margin-bottom: 18px;
            font-size: 1.01rem;
            transition: border 0.2s, box-shadow 0.2s;
        }
        textarea:focus{
            outline: none;
            border-color: #764ba2;
            background: rgba(255,255,255,0.22);
            box-shadow: 0 0 0 2px #e0e7ff;
        }


        .checkbox-row {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 18px 0 0 0;
            gap: 10px;
        }
        .checkbox-row input[type="checkbox"] {
            width: 28px;
            height: 28px;
            accent-color: #764ba2;
            margin-bottom: 6px;
            cursor: pointer;
        }
        .checkbox-row label {
            font-size: 1.13rem;
            color: #e0e7ff;
            font-weight: 600;
            margin-bottom: 0;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 15px 36px;
            font-size: 1.13rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
            margin-top: 10px;
        }
        .btn:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            box-shadow: 0 2px 8px rgba(95,201,196,0.10);
        }
        .btn-secondary {
            background: #fff;
            color: #764ba2;
            margin-left: 12px;
        }
        .msg-success { background: #22c55e; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        .msg-error { background: #ef4444; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        .form-columns {
            display: flex;
            gap: 32px;
            margin-bottom: 28px;
        }
        .form-col {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .student-info-columns {
            display: flex;
            gap: 32px;
        }
        .student-info-col {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0;
        }
        .student-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px 32px;
        }
        @media (max-width: 1000px) {
            .student-info-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
        @media (max-width: 900px) {
            .student-info-columns { flex-direction: column; gap: 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-users"></i> 4Ps Beneficiary Registration</h2>
        <?php if ($success) echo '<div class="msg-success">' . $success . '</div>'; ?>
        <?php if ($error) echo '<div class="msg-error">' . $error . '</div>'; ?>
        <form method="post">
            <!-- Household Information: full width -->
            <div class="form-section">
                <div class="form-section-title">Household Information</div>
                <label for="household_id">4Ps Household ID Number <span style="color:#facc15;">*</span></label>
                <input type="text" name="household_id" id="household_id" required
                    value="<?php echo htmlspecialchars($fourps['household_number'] ?? ''); ?>"
                    style="background:#fffbe6; border:1.5px solid #ffe066; color:#b08900; font-weight:600;">
            </div>

            <!-- Student Information: two columns -->
            <div class="form-section">
                <div class="form-section-title">Student Information</div>
                <div class="student-info-grid">
                    <div>
                        <label>First Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['first_name']); ?>" readonly>
                    </div>
                    <div>
                        <label>Middle Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['middle_name']); ?>" readonly>
                    </div>
                    <div>
                        <label>Last Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['last_name']); ?>" readonly>
                    </div>
                    <div>
                        <label>Grade Level</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['level_name']); ?>" readonly>
                    </div>
                    <div>
                        <label>Section</label>
                        <input type="text" value="<?php echo htmlspecialchars($student['section_name']); ?>" readonly>
                    </div>
                    <div>
                        <label>School Year <span style="color:#facc15;">*</span></label>
                        <input type="text" name="school_year" required placeholder="e.g., 2024-2025"
                            value="<?php echo htmlspecialchars($fourps['school_year'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Family & Status: full width -->
            <div class="form-section">
                <div class="form-section-title">Family & Status</div>
                <label>Relationship <span style="color:#facc15;">*</span></label>
                <select name="relationship" required>
                    <option value="">Select Relationship</option>
                    <option value="Son" <?php if(($fourps['relationship'] ?? '')=='Son') echo 'selected'; ?>>Son</option>
                    <option value="Daughter" <?php if(($fourps['relationship'] ?? '')=='Daughter') echo 'selected'; ?>>Daughter</option>
                    <option value="Other" <?php if(($fourps['relationship'] ?? '')=='Other') echo 'selected'; ?>>Other</option>
                </select>
                <label>Parent/Guardian Name</label>
                <input type="text" name="guardian_name" placeholder="Enter Parent/Guardian Name"
                    value="<?php echo htmlspecialchars($fourps['guardian_name'] ?? ''); ?>">
                <label>Status</label>
                <select name="status">
                    <option value="Active" <?php if(($fourps['status'] ?? '')=='Active') echo 'selected'; ?>>Active</option>
                    <option value="Inactive" <?php if(($fourps['status'] ?? '')=='Inactive') echo 'selected'; ?>>Inactive</option>
                </select>
                <div class="checkbox-row">
                    <input type="checkbox" name="verified" id="verified" <?php if(($fourps['verified'] ?? 0)==1) echo 'checked'; ?>>
                    <label for="verified">4Ps Beneficiary Status Verified</label>
                </div>
            </div>

            <!-- Additional Notes: full width -->
            <div class="form-section">
                <div class="form-section-title">Additional Notes</div>
                <textarea name="notes" placeholder="Any additional information or remarks"><?php echo htmlspecialchars($fourps['notes'] ?? ''); ?></textarea>
            </div>

            <?php if (!$fourps): ?>
                <button type="submit" name="add" class="btn"><i class="fa fa-plus"></i> Add</button>
            <?php else: ?>
                <button type="submit" class="btn"><i class="fa fa-save"></i> Update</button>
            <?php endif; ?>
            <a href="student_dashboard.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Go Back to Dashboard</a>
        </form>
    </div>
</body>
</html>