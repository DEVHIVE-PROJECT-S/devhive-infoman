<?php
session_start();
require 'includes/db.php';
$error = '';
function clean($str) {
    return htmlspecialchars(trim($str));
}
$type = isset($_GET['type']) ? $_GET['type'] : (isset($_POST['type']) ? $_POST['type'] : 'student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($type === 'student') {
        $lrn = clean($_POST['lrn']);
        $birth_month = str_pad(clean($_POST['birth_month']), 2, '0', STR_PAD_LEFT);
        $birth_day = str_pad(clean($_POST['birth_day']), 2, '0', STR_PAD_LEFT);
        $birth_year = clean($_POST['birth_year']);
        $password = $_POST['password'];
        if (!$lrn || !$birth_month || !$birth_day || !$birth_year || strlen($password) < 6) {
            $error = 'Please fill in all required fields and use a password of at least 6 characters.';
        } else {
            $birthdate = "$birth_year-$birth_month-$birth_day";
            $sql = "SELECT * FROM students WHERE lrn = ? AND birthdate = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ss', $lrn, $birthdate);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['student_id'] = $row['student_id'];
                    header('Location: student_dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'Invalid LRN or birthdate.';
            }
        }
    } elseif ($type === 'faculty') {
        $username = clean($_POST['lrn']);
        $password = $_POST['password'];
        if (!$username || strlen($password) < 6) {
            $error = 'Please fill in all required fields and use a password of at least 6 characters.';
        } else {
            $sql = "SELECT * FROM faculty WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['faculty_id'] = $row['faculty_id'];
                    header('Location: faculty_dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'Invalid username.';
            }
        }
    } elseif ($type === 'clinic') {
        $username = clean($_POST['lrn']);
        $password = $_POST['password'];
        if (!$username || strlen($password) < 6) {
            $error = 'Please fill in all required fields and use a password of at least 6 characters.';
        } else {
            $sql = "SELECT * FROM clinic_staff WHERE username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    $_SESSION['clinic_id'] = $row['clinic_id'];
                    header('Location: clinic_dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'Invalid username.';
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
    <title>Sign In - PDMHS Student Medical Record System</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
    function updateForm() {
        var type = '<?php echo $type; ?>';
        document.getElementById('birth-row').style.display = (type === 'student') ? 'flex' : 'none';
        document.getElementById('lrn').placeholder = (type === 'student') ? 'Student LRN' : 'Username';
    }
    window.onload = updateForm;
    </script>
</head>
<body>
    <div class="overlay">
        <a href="index.php"><img src="assets/pdmhs_logo.png" alt="PDMHS Logo" class="logo"></a>
        <div class="school-title">President Diosdado Macapagal High School</div>
        <div class="school-desc">Student Medical Record System</div>
        <div class="welcome">Welcome, Dadonians!</div>
        <div class="subtext">Sign in to start your session</div>
        <?php if ($error) echo '<div class="error-message">'.$error.'</div>'; ?>
        <form method="post" action="login.php?type=<?php echo htmlspecialchars($type); ?>" autocomplete="off" style="width:100%;max-width:340px;">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <div class="form-group" style="margin-bottom: 10px;">
                <label for="lrn" class="visually-hidden"><?php echo ($type==='student') ? 'Student LRN' : 'Username'; ?></label>
                <input type="text" name="lrn" id="lrn" placeholder="<?php echo ($type==='student') ? 'Student LRN' : 'Username'; ?>" required>
            </div>
            <div class="form-group form-row" id="birth-row" style="<?php echo ($type==='student') ? '' : 'display:none;'; ?>">
                <label for="birth_month" class="visually-hidden">Birth Month</label>
                <input type="text" name="birth_month" id="birth_month" class="birth" placeholder="Birth Month" maxlength="2" <?php echo ($type==='student') ? 'required' : ''; ?>>
                <label for="birth_day" class="visually-hidden">Birth Day</label>
                <input type="text" name="birth_day" id="birth_day" class="birth" placeholder="Birth Day" maxlength="2" <?php echo ($type==='student') ? 'required' : ''; ?>>
                <label for="birth_year" class="visually-hidden">Birth Year</label>
                <input type="text" name="birth_year" id="birth_year" class="birth" placeholder="Birth Year" maxlength="4" <?php echo ($type==='student') ? 'required' : ''; ?>>
            </div>
            <div class="form-group">
                <label for="password" class="visually-hidden">Password</label>
                <input type="password" name="password" id="password" placeholder="Password" required>
            </div>
            <button type="submit" class="signin-btn">Sign in</button>
            <a href="forgot_password.php?type=<?php echo htmlspecialchars($type); ?>" class="forgot">I forgot my password</a>
        </form>
        <a href="register.php" class="btn btn-student" style="width:100%;margin-top:20px;">Register</a>
    </div>
</body>
</html> 