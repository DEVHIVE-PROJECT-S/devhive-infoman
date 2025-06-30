<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - PDMHS Student Medical Record System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="overlay">
        <a href="index.php"><img src="assets/pdmhs_logo.png" alt="PDMHS Logo" class="logo"></a>
        <div class="school-title">President Diosdado Macapagal High School</div>
        <div class="school-desc">Student Medical Record System</div>
        <div class="welcome" style="font-size:1.3rem;margin-top:18px;margin-bottom:8px;">Log In as:</div>
        <div class="subtext" style="margin-bottom:32px;">Please select your role to log in</div>
        <div class="btn-group" style="display:flex; flex-direction:column; align-items:center; gap:14px; width:100%;max-width:260px;margin:0 auto 0 auto;">
            <a href="login.php?type=student" class="btn btn-student" style="width:100%;font-size:1.1rem;text-align:center;text-decoration:none;">Student</a>
            <a href="login.php?type=faculty" class="btn btn-faculty" style="width:100%;font-size:1.1rem;text-align:center;text-decoration:none;">Faculty</a>
            <a href="login.php?type=clinic" class="btn btn-clinic" style="width:100%;font-size:1.1rem;text-align:center;text-decoration:none;">Clinic Staff</a>
        </div>
        <a href="index.php" class="forgot" style="display:block;text-align:center;margin-top:22px;">&larr; Back to Welcome</a>
    </div>
</body>
</html> 