<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit();
}
require 'includes/db.php';
$student_id = $_SESSION['student_id'];
// Fetch medical profile
$med_sql = "SELECT * FROM medical_profiles WHERE student_id = ?";
$stmt = $conn->prepare($med_sql);
$stmt->bind_param('i', $student_id);
$stmt->execute();
$medical = $stmt->get_result()->fetch_assoc();
// Fetch allergies and conditions for multi-selects
$allergies_list = [];
$res = $conn->query("SELECT allergy_id, allergy_name FROM allergies ORDER BY allergy_name");
while ($row = $res->fetch_assoc()) $allergies_list[] = $row;
// Fallback: add common allergies if none in DB
$fallback_allergies = ['Peanuts', 'Pollen', 'Shellfish', 'Eggs', 'Milk', 'Wheat', 'Soy', 'Tree nuts', 'Dust mites', 'Insect stings', 'Latex', 'Pet dander', 'Mold'];
if (count($allergies_list) === 0) {
    foreach ($fallback_allergies as $i => $name) {
        $allergies_list[] = ['allergy_id' => 'f_' . $i, 'allergy_name' => $name];
    }
} else {
    // Add fallback only if not already in DB
    $existing = array_map(function($a){return strtolower($a['allergy_name']);}, $allergies_list);
    foreach ($fallback_allergies as $i => $name) {
        if (!in_array(strtolower($name), $existing)) {
            $allergies_list[] = ['allergy_id' => 'f_' . $i, 'allergy_name' => $name];
        }
    }
}
// Fetch selected allergies
$student_allergies = [];
$res = $conn->query("SELECT allergy_id FROM student_allergies WHERE student_id = $student_id");
while ($row = $res->fetch_assoc()) $student_allergies[] = $row['allergy_id'];
// Immunization options
$immunizations = ['MMR', 'Polio', 'Tetanus', 'COVID-19', 'Hepatitis B', 'Varicella', 'HPV'];
$selected_immunizations = [];
if (!empty($medical['immunization_status'])) {
    $selected_immunizations = explode(',', $medical['immunization_status']);
}
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $blood_type = $_POST['blood_type'] ?? '';
    $disability_status = $_POST['disability_status'] ?? '';
    $current_medications = $_POST['current_medications'] ?? '';
    $emergency_contact_name = $_POST['emergency_contact_name'] ?? '';
    $emergency_contact_number = $_POST['emergency_contact_number'] ?? '';
    $immunization_status = isset($_POST['immunization_status']) ? implode(',', $_POST['immunization_status']) : '';
    $notes = $_POST['notes'] ?? '';
    // Allergies
    $selected_allergies = isset($_POST['allergies']) ? $_POST['allergies'] : [];
    $other_allergy = trim($_POST['other_allergy'] ?? '');
    if ($other_allergy !== '') $selected_allergies[] = $other_allergy;
    $conn->query("DELETE FROM student_allergies WHERE student_id = $student_id");
    foreach ($selected_allergies as $aid) {
        if (is_numeric($aid)) {
            $conn->query("INSERT INTO student_allergies (student_id, allergy_id) VALUES ($student_id, $aid)");
        } else if ($aid !== '') {
            if (strpos($aid, 'f_') === 0) {
                // Fallback allergy selected, get name
                $fallback_allergies = ['Peanuts', 'Pollen', 'Shellfish', 'Eggs', 'Milk', 'Wheat', 'Soy', 'Tree nuts', 'Dust mites', 'Insect stings', 'Latex', 'Pet dander', 'Mold'];
                $idx = intval(substr($aid, 2));
                $name = isset($fallback_allergies[$idx]) ? $fallback_allergies[$idx] : '';
                if ($name) {
                    // Insert into allergies if not exists
                    $conn->query("INSERT IGNORE INTO allergies (allergy_name) VALUES ('" . $conn->real_escape_string($name) . "')");
                    $res = $conn->query("SELECT allergy_id FROM allergies WHERE allergy_name = '" . $conn->real_escape_string($name) . "'");
                    if ($row = $res->fetch_assoc()) {
                        $conn->query("INSERT INTO student_allergies (student_id, allergy_id) VALUES ($student_id, " . $row['allergy_id'] . ")");
                    }
                }
            } else {
                // Insert new allergy if not exists
                $conn->query("INSERT IGNORE INTO allergies (allergy_name) VALUES ('" . $conn->real_escape_string($aid) . "')");
                $new_id = $conn->insert_id;
                if ($new_id) $conn->query("INSERT INTO student_allergies (student_id, allergy_id) VALUES ($student_id, $new_id)");
            }
        }
    }
    if ($medical) {
        $sql = "UPDATE medical_profiles SET blood_type=?, disability_status=?, current_medications=?, immunization_status=?, emergency_contact_name=?, emergency_contact_number=?, notes=? WHERE student_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssssi', $blood_type, $disability_status, $current_medications, $immunization_status, $emergency_contact_name, $emergency_contact_number, $notes, $student_id);
        if ($stmt->execute()) {
            $success = 'Medical info updated.';
        } else {
            $error = 'Update failed.';
        }
    } else {
        $sql = "INSERT INTO medical_profiles (student_id, blood_type, disability_status, current_medications, immunization_status, emergency_contact_name, emergency_contact_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssssss', $student_id, $blood_type, $disability_status, $current_medications, $immunization_status, $emergency_contact_name, $emergency_contact_number, $notes);
        if ($stmt->execute()) {
            $success = 'Medical info saved.';
        } else {
            $error = 'Save failed.';
        }
    }
    // Refresh data
    $stmt = $conn->prepare($med_sql);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $medical = $stmt->get_result()->fetch_assoc();
    // Refresh allergies
    $student_allergies = [];
    $res = $conn->query("SELECT allergy_id FROM student_allergies WHERE student_id = $student_id");
    while ($row = $res->fetch_assoc()) $student_allergies[] = $row['allergy_id'];
    $selected_immunizations = $immunization_status ? explode(',', $immunization_status) : [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Info - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .container { max-width: 650px; margin: 60px auto; background: rgba(255,255,255,0.10); border-radius: 22px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 44px 32px; }
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
        input, textarea, select {
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
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #764ba2;
            background: rgba(255,255,255,0.22);
            box-shadow: 0 0 0 2px #e0e7ff;
        }
        select[multiple], .checkbox-group {
            min-height: 48px;
            background: rgba(255,255,255,0.18);
            border-radius: 9px;
            margin-bottom: 18px;
            padding: 10px 8px;
            font-size: 1.01rem;
        }
        .checkbox-group label {
            display: inline-block;
            margin-right: 18px;
            margin-bottom: 8px;
            font-weight: 400;
            color: #23213a;
            font-size: 1.01rem;
        }
        .checkbox-group input[type=checkbox] {
            margin-right: 7px;
            accent-color: #764ba2;
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
        .msg-success { background: #22c55e; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        .msg-error { background: #ef4444; color: #fff; padding: 10px 18px; border-radius: 8px; margin-bottom: 18px; }
        @media (max-width: 700px) {
            .container { padding: 18px 4vw; }
            .form-section { padding: 14px 6vw 10px 6vw; }
        }
        .custom-multiselect select[multiple] {
            width: 100%;
            min-height: 48px;
            background: rgba(255,255,255,0.18);
            border-radius: 9px;
            border: 1.5px solid rgba(255,255,255,0.22);
            color: #23213a;
            font-size: 1.01rem;
            padding: 10px 8px;
            margin-bottom: 18px;
            box-shadow: 0 2px 8px rgba(95,201,196,0.06);
            outline: none;
            transition: border 0.2s, box-shadow 0.2s;
        }
        .custom-multiselect select[multiple]:focus {
            border-color: #764ba2;
            background: rgba(255,255,255,0.22);
            box-shadow: 0 0 0 2px #e0e7ff;
        }
        .custom-multiselect option {
            padding: 8px 12px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-notes-medical"></i> My Medical Information</h2>
        <?php if ($success) echo '<div class="msg-success">' . $success . '</div>'; ?>
        <?php if ($error) echo '<div class="msg-error">' . $error . '</div>'; ?>
        <form method="post">
            <div class="form-section">
                <div class="form-section-title">Basic Medical Information</div>
                <label for="blood_type">Blood Type</label>
                <select name="blood_type" id="blood_type" required>
                    <option value="">-- Select Blood Type --</option>
                    <option value="A+" <?php if(($medical['blood_type'] ?? '')=='A+') echo 'selected'; ?>>A+</option>
                    <option value="A-" <?php if(($medical['blood_type'] ?? '')=='A-') echo 'selected'; ?>>A-</option>
                    <option value="B+" <?php if(($medical['blood_type'] ?? '')=='B+') echo 'selected'; ?>>B+</option>
                    <option value="B-" <?php if(($medical['blood_type'] ?? '')=='B-') echo 'selected'; ?>>B-</option>
                    <option value="AB+" <?php if(($medical['blood_type'] ?? '')=='AB+') echo 'selected'; ?>>AB+</option>
                    <option value="AB-" <?php if(($medical['blood_type'] ?? '')=='AB-') echo 'selected'; ?>>AB-</option>
                    <option value="O+" <?php if(($medical['blood_type'] ?? '')=='O+') echo 'selected'; ?>>O+</option>
                    <option value="O-" <?php if(($medical['blood_type'] ?? '')=='O-') echo 'selected'; ?>>O-</option>
                </select>
                <label for="disability_status">Disability Status</label>
                <select name="disability_status" id="disability_status">
                    <option value="">-- Select Disability Status --</option>
                    <option value="None" <?php if(($medical['disability_status'] ?? '')=='None') echo 'selected'; ?>>None</option>
                    <option value="Physical" <?php if(($medical['disability_status'] ?? '')=='Physical') echo 'selected'; ?>>Physical</option>
                    <option value="Visual" <?php if(($medical['disability_status'] ?? '')=='Visual') echo 'selected'; ?>>Visual</option>
                    <option value="Hearing" <?php if(($medical['disability_status'] ?? '')=='Hearing') echo 'selected'; ?>>Hearing</option>
                    <option value="Learning" <?php if(($medical['disability_status'] ?? '')=='Learning') echo 'selected'; ?>>Learning</option>
                    <option value="Other" <?php if(($medical['disability_status'] ?? '')=='Other') echo 'selected'; ?>>Other</option>
                </select>
            </div>
            <div class="form-section">
                <div class="form-section-title">Allergies &amp; Medications</div>
                <label for="allergies">Allergies</label>
                <div class="custom-multiselect">
                    <select name="allergies[]" id="allergies" multiple>
<?php foreach ($allergies_list as $a) { ?>
                        <option value="<?php echo $a['allergy_id']; ?>" <?php if(in_array($a['allergy_id'], $student_allergies)) echo 'selected'; ?>><?php echo htmlspecialchars($a['allergy_name']); ?></option>
<?php } ?>
                    </select>
                </div>
                <input type="text" name="other_allergy" placeholder="Other allergy (specify)">
            </div>
            <div class="form-section">
                <div class="form-section-title">Immunization Status</div>
                <div class="checkbox-group">
<?php foreach ($immunizations as $imm) { ?>
                    <label><input type="checkbox" name="immunization_status[]" value="<?php echo $imm; ?>" <?php if(in_array($imm, $selected_immunizations)) echo 'checked'; ?>> <?php echo $imm; ?></label>
<?php } ?>
                </div>
            </div>
            <div class="form-section">
                <div class="form-section-title">Emergency Contact</div>
                <label for="emergency_contact_name">Emergency Contact Name</label>
                <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="<?php echo htmlspecialchars($medical['emergency_contact_name'] ?? ''); ?>">
                <label for="emergency_contact_number">Emergency Contact Number</label>
                <input type="text" name="emergency_contact_number" id="emergency_contact_number" value="<?php echo htmlspecialchars($medical['emergency_contact_number'] ?? ''); ?>">
            </div>
            <div class="form-section">
                <div class="form-section-title">Other Notes</div>
                <textarea name="notes" id="notes" rows="4"><?php echo htmlspecialchars($medical['notes'] ?? ''); ?></textarea>
            </div>
            <button type="submit" class="btn"><i class="fa fa-save"></i> Save</button>
            <a href="student_dashboard.php" class="btn" style="background:#fff;color:#764ba2;margin-left:12px;"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        </form>
    </div>
</body>
</html> 