<?php
session_start();
if (!isset($_SESSION['clinic_id'])) {
    header('Location: login.php?type=clinic');
    exit();
}
require 'includes/db.php';
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if (!$student_id) { echo 'Invalid student.'; exit(); }
// Fetch student info
$student_sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = $conn->prepare($student_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
if (!$student) { echo 'Student not found.'; exit(); }
// Fetch medical profile
$med_sql = "SELECT * FROM medical_profiles WHERE student_id = ?";
$stmt = $conn->prepare($med_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$medical = $stmt->get_result()->fetch_assoc();
// Fetch all allergies
$allergies = $conn->query("SELECT * FROM allergies ORDER BY allergy_name");
// Fetch student's allergies
$student_allergies = [];
$res = $conn->query("SELECT allergy_id FROM student_allergies WHERE student_id = $student_id");
while($row = $res->fetch_assoc()) $student_allergies[] = $row['allergy_id'];
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update medical profile
    $blood_type = $_POST['blood_type'];
    $disability_status = $_POST['disability_status'];
    $notes = $_POST['notes'];
    $check_sql = "SELECT * FROM medical_profiles WHERE student_id = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $update_sql = "UPDATE medical_profiles SET blood_type=?, disability_status=?, notes=? WHERE student_id=?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('sssi', $blood_type, $disability_status, $notes, $student_id);
        $stmt->execute();
    } else {
        $insert_sql = "INSERT INTO medical_profiles (student_id, blood_type, disability_status, notes) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param('isss', $student_id, $blood_type, $disability_status, $notes);
        $stmt->execute();
    }
    // Update allergies
    $selected_allergies = isset($_POST['allergies']) ? $_POST['allergies'] : [];
    $conn->query("DELETE FROM student_allergies WHERE student_id = $student_id");
    foreach($selected_allergies as $aid) {
        $aid = intval($aid);
        $conn->query("INSERT INTO student_allergies (student_id, allergy_id) VALUES ($student_id, $aid)");
    }
    $msg = '<div class="msg-success">Medical record updated!</div>';
    // Refresh data
    $stmt = $conn->prepare($med_sql);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $medical = $stmt->get_result()->fetch_assoc();
    $student_allergies = [];
    $res = $conn->query("SELECT allergy_id FROM student_allergies WHERE student_id = $student_id");
    while($row = $res->fetch_assoc()) $student_allergies[] = $row['allergy_id'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student Medical Record - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .edit-form label { font-weight: 500; margin-bottom: 4px; display: block; }
        .edit-form input, .edit-form textarea, .edit-form select { margin-bottom: 14px; }
        .edit-form .form-group { margin-bottom: 18px; }
        .edit-form .checkbox-list { display: flex; flex-wrap: wrap; gap: 10px 18px; }
        .edit-form .checkbox-list label { font-weight: 400; display: inline; }
    </style>
</head>
<body>
    <div class="overlay" style="align-items: flex-start; padding: 40px; max-width: 600px;">
        <a href="index.php"><img src="assets/pdmhs_logo.png" alt="PDMHS Logo" class="logo"></a>
        <div class="school-title">Edit Student Medical Record</div>
        <div style="margin-bottom: 18px; color: #444;">
            <b><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></b> (LRN: <?php echo htmlspecialchars($student['student_id']); ?>)
        </div>
        <?php echo $msg; ?>
        <form method="post" class="edit-form">
            <div class="form-group">
                <label for="blood_type">Blood Type</label>
                <input type="text" name="blood_type" id="blood_type" value="<?php echo htmlspecialchars($medical['blood_type'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="disability_status">Disability Status</label>
                <input type="text" name="disability_status" id="disability_status" value="<?php echo htmlspecialchars($medical['disability_status'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea name="notes" id="notes" style="width:100%;padding:10px;border-radius:6px;border:1.5px solid #b5cfd2;min-height:60px;"><?php echo htmlspecialchars($medical['notes'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Allergies</label>
                <div class="checkbox-list">
                <?php while($a = $allergies->fetch_assoc()) { ?>
                    <label><input type="checkbox" name="allergies[]" value="<?php echo $a['allergy_id']; ?>" <?php if(in_array($a['allergy_id'], $student_allergies)) echo 'checked'; ?>> <?php echo htmlspecialchars($a['allergy_name']); ?></label>
                <?php } ?>
                </div>
            </div>
            <button type="submit" class="signin-btn">Save Changes</button>
            <a href="clinic_dashboard.php" class="btn btn-faculty" style="width:100%;margin-top:16px;">Back to Dashboard</a>
        </form>
    </div>
</body>
</html> 