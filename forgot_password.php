<?php
require 'includes/db.php';
$type = isset($_GET['type']) ? $_GET['type'] : 'student';
$success = $error = '';
$step = 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $user = trim($_POST['user']);
    $birth_month = str_pad(trim($_POST['birth_month']), 2, '0', STR_PAD_LEFT);
    $birth_day = str_pad(trim($_POST['birth_day']), 2, '0', STR_PAD_LEFT);
    $birth_year = trim($_POST['birth_year']);
    $birthdate = "$birth_year-$birth_month-$birth_day";
    $table = $type === 'student' ? 'students' : ($type === 'faculty' ? 'faculty' : 'clinic_staff');
    $id_field = $type === 'student' ? 'student_id' : ($type === 'faculty' ? 'username' : 'username');
    $birth_field = $type === 'student' ? 'birthdate' : null;
    // Step 1: Verify identity
    if (!isset($_POST['new_password'])) {
        if ($type === 'student') {
            $sql = "SELECT * FROM students WHERE student_id = ? AND birthdate = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('is', $user, $birthdate);
        } else {
            $sql = "SELECT * FROM $table WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $user);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && ($type !== 'student' || $row['birthdate'] === $birthdate)) {
            $step = 2;
            $user_id = $type === 'student' ? $row['student_id'] : ($type === 'faculty' ? $row['faculty_id'] : $row['clinic_id']);
        } else {
            $error = 'No matching user found.';
        }
    } else {
        // Step 2: Set new password
        $user_id = $_POST['user_id'];
        $new_password = $_POST['new_password'];
        if (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters.';
            $step = 2;
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            if ($type === 'student') {
                $sql = "UPDATE students SET password=? WHERE student_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $password_hash, $user_id);
            } elseif ($type === 'faculty') {
                $sql = "UPDATE faculty SET password=? WHERE faculty_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $password_hash, $user_id);
            } else {
                $sql = "UPDATE clinic_staff SET password=? WHERE clinic_id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('si', $password_hash, $user_id);
            }
            if ($stmt->execute()) {
                $success = 'Password updated! You can now <a href=\'login.php?type=' . htmlspecialchars($type) . '\'>log in</a>.';
            } else {
                $error = 'Failed to update password.';
                $step = 2;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="overlay">
        <a href="index.php"><img src="assets/pdmhs_logo.png" alt="PDMHS Logo" class="logo"></a>
        <div class="school-title">Forgot Password</div>
        <div class="school-desc">Student Medical Record System</div>
        <?php if ($success) { echo '<div class="msg-success">'.$success.'</div>'; } ?>
        <?php if ($error) { echo '<div class="msg-error">'.$error.'</div>'; } ?>
        <?php if (!$success) { ?>
        <form method="post" style="width:100%;max-width:340px;">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <?php if ($step === 1) { ?>
            <div class="form-group">
                <label for="user" class="visually-hidden"><?php echo $type === 'student' ? 'Student LRN' : 'Username'; ?></label>
                <input type="text" name="user" id="user" placeholder="<?php echo $type === 'student' ? 'Student LRN' : 'Username'; ?>" required>
            </div>
            <?php if ($type === 'student') { ?>
            <div class="form-group form-row">
                <label for="birth_month" class="visually-hidden">Birth Month</label>
                <input type="text" name="birth_month" id="birth_month" class="birth" placeholder="Birth Month" maxlength="2" required>
                <label for="birth_day" class="visually-hidden">Birth Day</label>
                <input type="text" name="birth_day" id="birth_day" class="birth" placeholder="Birth Day" maxlength="2" required>
                <label for="birth_year" class="visually-hidden">Birth Year</label>
                <input type="text" name="birth_year" id="birth_year" class="birth" placeholder="Birth Year" maxlength="4" required>
            </div>
            <?php } ?>
            <button type="submit" class="signin-btn">Verify</button>
            <?php } else { ?>
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
            <div class="form-group">
                <label for="new_password" class="visually-hidden">New Password</label>
                <input type="password" name="new_password" id="new_password" placeholder="New Password" minlength="6" required>
            </div>
            <button type="submit" class="signin-btn">Set New Password</button>
            <?php } ?>
        </form>
        <?php } ?>
        <a href="login.php?type=<?php echo htmlspecialchars($type); ?>" class="forgot" style="display:block;text-align:center;margin-top:15px;">&larr; Back to Login</a>
    </div>
</body>
</html> 