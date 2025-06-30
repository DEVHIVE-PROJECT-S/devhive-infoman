<?php
session_start();
if (!isset($_SESSION['clinic_id'])) {
    header('Location: login.php?type=clinic');
    exit();
}
require 'includes/db.php';
// Fetch medications for dropdowns (with details)
$medications = [];
$med_result = $conn->query("SELECT name, unit, quantity, expiration_date FROM medications ORDER BY name ASC");
while ($row = $med_result->fetch_assoc()) {
    $medications[] = $row;
}
// Static list of common treatments
$treatments = [
    'Rest',
    'Wound cleaning',
    'Ice pack',
    'Hot compress',
    'Blood pressure check',
    'First aid',
    'Observation',
    'Referral to hospital',
    'Counseling',
    'Isolation',
    'Return to class',
];
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lrn = trim($_POST['lrn'] ?? '');
    $symptoms = trim($_POST['symptoms'] ?? '');
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    // Treatment: use dropdown or custom
    $treatment = isset($_POST['treatment']) ? trim($_POST['treatment']) : '';
    if (isset($_POST['treatment_other']) && $_POST['treatment_other'] !== '') {
        $treatment = trim($_POST['treatment_other']);
    }
    // Medications: allow multiple
    $medications_selected = isset($_POST['medications']) ? $_POST['medications'] : [];
    $medications_str = is_array($medications_selected) ? implode(", ", $medications_selected) : trim($medications_selected);
    $notes = trim($_POST['notes'] ?? '');
    $clinic_staff_id = $_SESSION['clinic_id'];
    if ($lrn === '' || $symptoms === '') {
        $error = 'LRN and symptoms are required.';
    } else {
        // Find student_id by LRN
        $stmt = $conn->prepare('SELECT student_id FROM students WHERE lrn = ?');
        $stmt->bind_param('s', $lrn);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $student_id = $row['student_id'];
            $visit_date = date('Y-m-d H:i:s');
            $stmt2 = $conn->prepare('INSERT INTO visits (student_id, visit_date, symptoms, diagnosis, treatment, medications, notes, clinic_staff_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt2->bind_param('issssssi', $student_id, $visit_date, $symptoms, $diagnosis, $treatment, $medications_str, $notes, $clinic_staff_id);
            if ($stmt2->execute()) {
                $success = 'Visit recorded successfully!';
            } else {
                $error = 'Failed to record visit.';
            }
        } else {
            $error = 'Student with this LRN not found.';
        }
    }
}
// Group medications: available first, expired last
$available_meds = [];
$expired_meds = [];
$today = date('Y-m-d');
foreach ($medications as $med) {
    if ($med['expiration_date'] && $med['expiration_date'] < $today) {
        $expired_meds[] = $med;
    } else {
        $available_meds[] = $med;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Clinic Visit - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .container { max-width: 600px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 40px; }
        h2 { font-size: 2rem; margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #e0e7ff; }
        input, textarea { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.12); color: #fff; margin-bottom: 18px; font-size: 1rem; }
        input:focus, textarea:focus { outline: none; border-color: #764ba2; background: rgba(255,255,255,0.18); }
        .btn { background: #5fc9c4; color: #fff; border: none; border-radius: 8px; padding: 14px 32px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #195b8b; }
        a.btn-back { display: inline-block; margin-top: 24px; background: #fff; color: #764ba2; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
        a.btn-back:hover { background: #e0e7ff; }
        .message { padding: 12px; border-radius: 8px; margin-bottom: 18px; font-weight: 600; }
        .success { background: #d1fae5; color: #065f46; }
        .error { background: #fee2e2; color: #991b1b; }
        /* ENHANCED DROPDOWNS */
        select, .custom-select {
            width: 100%;
            padding: 14px 16px;
            border-radius: 12px;
            border: 1.5px solid #a5b4fc;
            background: rgba(255,255,255,0.18);
            color: #3b3763;
            font-size: 1.08rem;
            margin-bottom: 18px;
            transition: border 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(95,201,196,0.08);
            appearance: none;
            cursor: pointer;
        }
        select:focus, .custom-select:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 3px #e0e7ff;
            outline: none;
            background: rgba(255,255,255,0.25);
        }
        select option {
            background: #f3f4fa;
            color: #3b3763;
            font-size: 1rem;
            border-radius: 8px;
            padding: 8px 12px;
        }
        /* ENHANCED SEARCH BOXES */
        #treatmentSearch, #medicationsSearch {
            width: 100%;
            padding: 12px 16px;
            border-radius: 10px;
            border: 1.5px solid #a5b4fc;
            background: rgba(255,255,255,0.18);
            color: #3b3763;
            font-size: 1.05rem;
            margin-bottom: 10px;
            margin-top: 2px;
            transition: border 0.2s, box-shadow 0.2s;
        }
        #treatmentSearch:focus, #medicationsSearch:focus {
            border-color: #5fc9c4;
            box-shadow: 0 0 0 2px #e0e7ff;
            outline: none;
            background: rgba(255,255,255,0.25);
        }
        /* ENHANCED MULTISELECT */
        select[multiple] {
            min-height: 120px;
            background: rgba(255,255,255,0.22);
            border: 1.5px solid #a5b4fc;
            color: #3b3763;
            font-size: 1.05rem;
            border-radius: 12px;
            margin-bottom: 18px;
        }
        select[multiple]:focus {
            border-color: #5fc9c4;
            box-shadow: 0 0 0 2px #e0e7ff;
            background: rgba(255,255,255,0.28);
        }
        /* Option hover/focus (not supported in all browsers, but helps in some) */
        select option:hover, select option:focus {
            background: #a5b4fc !important;
            color: #fff !important;
        }
        /* Custom scrollbar for dropdowns */
        select, select[multiple] {
            scrollbar-width: thin;
            scrollbar-color: #764ba2 #e0e7ff;
        }
        select::-webkit-scrollbar, select[multiple]::-webkit-scrollbar {
            width: 8px;
            background: #e0e7ff;
        }
        select::-webkit-scrollbar-thumb, select[multiple]::-webkit-scrollbar-thumb {
            background: #764ba2;
            border-radius: 8px;
        }
        /* ENHANCED MEDICATIONS DROPDOWN */
        #medications {
            width: 100%;
            min-height: 44px;
            background: #fff;
            border: 2px solid #a5b4fc;
            border-radius: 16px;
            color: #3b3763;
            font-size: 1rem;
            font-weight: 400;
            box-shadow: 0 4px 18px rgba(118,75,162,0.10), 0 1.5px 6px rgba(95,201,196,0.10);
            margin-bottom: 18px;
            padding: 6px 0;
            transition: border 0.2s, box-shadow 0.2s;
        }
        #medications:focus {
            border-color: #764ba2;
            box-shadow: 0 0 0 3px #e0e7ff, 0 4px 18px rgba(118,75,162,0.10);
            outline: none;
            background: #f3f4fa;
        }
        #medications option {
            background: #f3f4fa;
            color: #3b3763;
            font-size: 0.98rem;
            font-weight: 400;
            border-radius: 8px;
            padding: 7px 12px;
            margin: 3px 0;
            transition: background 0.18s, color 0.18s;
        }
        #medications option:hover, #medications option:focus {
            background: #a5b4fc !important;
            color: #fff !important;
        }
        /* Custom scrollbar for dropdowns */
        #medications {
            scrollbar-width: thin;
            scrollbar-color: #764ba2 #e0e7ff;
        }
        #medications::-webkit-scrollbar {
            width: 8px;
            background: #e0e7ff;
        }
        #medications::-webkit-scrollbar-thumb {
            background: #764ba2;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-plus"></i> New Clinic Visit</h2>
        <?php if ($success) { echo '<div class="message success">' . $success . '</div>'; } ?>
        <?php if ($error) { echo '<div class="message error">' . $error . '</div>'; } ?>
        <form method="post">
            <label for="lrn">Student LRN</label>
            <input type="text" id="lrn" name="lrn" placeholder="Enter LRN" required>
            <label for="symptoms">Symptoms</label>
            <input type="text" id="symptoms" name="symptoms" placeholder="e.g. Headache, Fever" required>
            <label for="diagnosis">Diagnosis</label>
            <input type="text" id="diagnosis" name="diagnosis" placeholder="e.g. Migraine, Viral Infection">
            <label for="treatment">Treatment</label>
            <select id="treatment" name="treatment">
                <option value="">-- Select Treatment --</option>
<?php foreach ($treatments as $treat) { ?>
                <option value="<?php echo htmlspecialchars($treat); ?>"><?php echo htmlspecialchars($treat); ?></option>
<?php } ?>
                <option value="other">Other (specify below)</option>
            </select>
            <input type="text" id="treatment_other" name="treatment_other" placeholder="Other treatment" style="display:none; margin-top:8px;">
            <label for="medications">Medications</label>
            <select id="medications" name="medications">
                <option value="" disabled selected>-- Select Medication --</option>
<?php foreach ($available_meds as $med) { 
    $label = $med['name'] . ' (' . $med['quantity'] . ' ' . $med['unit'] . ', exp: ' . ($med['expiration_date'] ?: 'N/A') . ')';
?>
                <option value="<?php echo htmlspecialchars($med['name']); ?>" title="<?php echo htmlspecialchars($label); ?>"><?php echo htmlspecialchars($label); ?></option>
<?php } ?>
<?php if (count($expired_meds) > 0) { ?>
                <optgroup label="Expired Medications">
<?php foreach ($expired_meds as $med) { 
    $label = $med['name'] . ' (' . $med['quantity'] . ' ' . $med['unit'] . ', exp: ' . ($med['expiration_date'] ?: 'N/A') . ')';
?>
                    <option value="<?php echo htmlspecialchars($med['name']); ?>" title="<?php echo htmlspecialchars($label); ?>" style="color:#b91c1c;">&#9888; <?php echo htmlspecialchars($label); ?></option>
<?php } ?>
                </optgroup>
<?php } ?>
            </select>
            <div style="color:#a1a1aa; font-size:0.97rem; margin-top:-10px; margin-bottom:18px;">Only available (non-expired) medications are shown first. Expired medications are marked in red.</div>
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="2" placeholder="Additional notes"></textarea>
            <button type="submit" class="btn"><i class="fa fa-save"></i> Record Visit</button>
        </form>
        <a href="clinic_dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
    <script>
    // Show/hide custom treatment input
    document.addEventListener('DOMContentLoaded', function() {
        var treatmentSelect = document.getElementById('treatment');
        var treatmentOther = document.getElementById('treatment_other');
        treatmentSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                treatmentOther.style.display = 'block';
            } else {
                treatmentOther.style.display = 'none';
            }
        });
    });
    </script>
</body>
</html> 