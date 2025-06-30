<?php
session_start();
if (!isset($_SESSION['clinic_id'])) {
    header('Location: login.php?type=clinic');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Inter', sans-serif; color: #fff; }
        .container { max-width: 600px; margin: 60px auto; background: rgba(255,255,255,0.08); border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); padding: 40px; }
        h2 { font-size: 2rem; margin-bottom: 24px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; color: #e0e7ff; }
        input, select { width: 100%; padding: 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.12); color: #fff; margin-bottom: 18px; font-size: 1rem; }
        input:focus, select:focus { outline: none; border-color: #764ba2; background: rgba(255,255,255,0.18); }
        .btn { background: #5fc9c4; color: #fff; border: none; border-radius: 8px; padding: 14px 32px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background: #195b8b; }
        a.btn-back { display: inline-block; margin-top: 24px; background: #fff; color: #764ba2; padding: 12px 28px; border-radius: 8px; font-weight: 600; text-decoration: none; transition: background 0.2s; }
        a.btn-back:hover { background: #e0e7ff; }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fa fa-user-plus"></i> Add Student</h2>
        <form>
            <label for="lrn">LRN</label>
            <input type="text" id="lrn" name="lrn" placeholder="Enter LRN" required>
            <label for="first_name">First Name</label>
            <input type="text" id="first_name" name="first_name" required>
            <label for="middle_name">Middle Name</label>
            <input type="text" id="middle_name" name="middle_name">
            <label for="last_name">Last Name</label>
            <input type="text" id="last_name" name="last_name" required>
            <label for="birthdate">Birthdate</label>
            <input type="date" id="birthdate" name="birthdate" required>
            <label for="gender">Gender</label>
            <select id="gender" name="gender" required>
                <option value="">Select Gender</option>
                <option value="M">Male</option>
                <option value="F">Female</option>
            </select>
            <label for="address">Address</label>
            <input type="text" id="address" name="address">
            <button type="submit" class="btn"><i class="fa fa-save"></i> Add Student</button>
        </form>
        <a href="clinic_dashboard.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html> 