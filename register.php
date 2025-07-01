<?php
require 'includes/db.php';
session_start();
$type = $_GET['type'] ?? 'student';
$success = $error = '';
function clean($str) {
    return htmlspecialchars(trim($str));
}
// Fetch grade levels and sections into arrays
$grade_levels_res = $conn->query("SELECT grade_level_id, level_name FROM grade_levels ORDER BY level_name");
$grade_levels = [];
while ($row = $grade_levels_res->fetch_assoc()) $grade_levels[] = $row;
$sections_res = $conn->query("SELECT section_id, section_name, grade_level_id FROM sections ORDER BY grade_level_id, section_name");
$sections = [];
while ($row = $sections_res->fetch_assoc()) $sections[] = $row;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    if ($type === 'student') {
        $lrn = clean($_POST['lrn'] ?? '');
        $first_name = clean($_POST['first_name'] ?? '');
        $middle_name = clean($_POST['middle_name'] ?? '');
        $last_name = clean($_POST['last_name'] ?? '');
        $birthdate = clean($_POST['birthdate'] ?? '');
        $gender = clean($_POST['gender'] ?? '');
        $address = clean($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($lrn === '') {
            $error = 'LRN is required.';
        } elseif ($first_name === '') {
            $error = 'First name is required.';
        } elseif ($last_name === '') {
            $error = 'Last name is required.';
        } elseif ($birthdate === '') {
            $error = 'Birthdate is required.';
        } elseif ($gender === '') {
            $error = 'Gender is required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Check LRN uniqueness
            $check = $conn->prepare('SELECT lrn FROM students WHERE lrn = ?');
            $check->bind_param('s', $lrn);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = 'This LRN is already registered.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $grade_level_id = clean($_POST['grade_level_id'] ?? '');
                $section_id = clean($_POST['section_id'] ?? '');
                
                // Start transaction
                $conn->begin_transaction();
                try {
                    // Insert student
                    $sql = "INSERT INTO students (lrn, first_name, middle_name, last_name, birthdate, gender, address, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('ssssssss', $lrn, $first_name, $middle_name, $last_name, $birthdate, $gender, $address, $password_hash);
                    $stmt->execute();
                    
                    $student_id = $conn->insert_id;
                    
                    // Insert enrollment
                    if ($grade_level_id && $section_id) {
                        $current_year = date('Y');
                        $school_year = $current_year . '-' . ($current_year + 1);
                        $enrollment_sql = "INSERT INTO student_enrollments (student_id, grade_level_id, section_id, school_year) VALUES (?, ?, ?, ?)";
                        $enrollment_stmt = $conn->prepare($enrollment_sql);
                        $enrollment_stmt->bind_param('iiis', $student_id, $grade_level_id, $section_id, $school_year);
                        $enrollment_stmt->execute();
                    }
                    
                    $conn->commit();
                    $_SESSION['student_id'] = $student_id;
                    header('Location: student_dashboard.php');
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = 'Registration failed: ' . $e->getMessage();
                }
            }
        }
    } elseif ($type === 'faculty') {
        $first_name = clean($_POST['first_name'] ?? '');
        $middle_name = clean($_POST['middle_name'] ?? '');
        $last_name = clean($_POST['last_name'] ?? '');
        $username = clean($_POST['username'] ?? '');
        $subject = clean($_POST['subject'] ?? '');
        $grade_level_id = clean($_POST['grade_level_id'] ?? '');
        $section_id = clean($_POST['section_id'] ?? '');
        $honorific = clean($_POST['honorific'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($first_name === '') {
            $error = 'First name is required.';
        } elseif ($last_name === '') {
            $error = 'Last name is required.';
        } elseif ($username === '') {
            $error = 'Username is required.';
        } elseif ($subject === '') {
            $error = 'Subject is required.';
        } elseif ($grade_level_id === '') {
            $error = 'Grade level is required.';
        } elseif ($section_id === '') {
            $error = 'Section is required.';
        } elseif ($honorific === '') {
            $error = 'Honorific is required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Check username uniqueness
            $check = $conn->prepare('SELECT username FROM faculty WHERE username = ?');
            $check->bind_param('s', $username);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = 'This username is already registered.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO faculty (first_name, middle_name, last_name, honorific, username, password, subject, grade_level_id, section_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssssssii', $first_name, $middle_name, $last_name, $honorific, $username, $password_hash, $subject, $grade_level_id, $section_id);
                if ($stmt->execute()) {
                    $faculty_id = $conn->insert_id;
                    $_SESSION['faculty_id'] = $faculty_id;
                    header('Location: faculty_dashboard.php');
                    exit();
                } else {
                    $error = 'Registration failed: ' . $conn->error;
                }
            }
        }
    } elseif ($type === 'clinic') {
        $first_name = clean($_POST['first_name'] ?? '');
        $middle_name = clean($_POST['middle_name'] ?? '');
        $last_name = clean($_POST['last_name'] ?? '');
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($first_name === '') {
            $error = 'First name is required.';
        } elseif ($last_name === '') {
            $error = 'Last name is required.';
        } elseif ($username === '') {
            $error = 'Username is required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            // Check username uniqueness
            $check = $conn->prepare('SELECT username FROM clinic_staff WHERE username = ?');
            $check->bind_param('s', $username);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $error = 'This username is already registered.';
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO clinic_staff (first_name, middle_name, last_name, username, password) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssss', $first_name, $middle_name, $last_name, $username, $password_hash);
                if ($stmt->execute()) {
                    $clinic_id = $conn->insert_id;
                    $_SESSION['clinic_id'] = $clinic_id;
                    header('Location: clinic_dashboard.php');
                    exit();
                } else {
                    $error = 'Registration failed: ' . $conn->error;
                }
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
    <title>Register - PDMHS</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="overlay">
        <a href="index.php"><img src="assets/pdmhs_logo.png" alt="PDMHS Logo" class="logo"></a>
        <div class="school-title">President Diosdado Macapagal High School</div>
        <div class="school-desc">Student Medical Record System</div>
        <div style="font-size: 0.98rem; color: #444; margin-bottom: 10px;">Sign up to create your account</div>
        <?php if ($error) echo '<div class="msg-error">'.$error.'</div>'; ?>
        <div class="form-wrapper">
        <form method="post" autocomplete="off" class="form-wrapper" style="width:100%;max-width:540px;margin:0 auto;">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
            <?php if ($type === 'student') { ?>
            <div class="form-group"><input type="text" name="lrn" placeholder="Student LRN" required></div>
            <div class="form-group"><input type="text" name="first_name" placeholder="First Name" required></div>
            <div class="form-group"><input type="text" name="middle_name" placeholder="Middle Name"></div>
            <div class="form-group"><input type="text" name="last_name" placeholder="Last Name" required></div>
            <div class="form-group"><input type="date" name="birthdate" placeholder="Birthdate" required></div>
            <div class="form-group">
                <select name="gender" required style="width:100%;padding:10px;border-radius:5px;border:1px solid #ccc;">
                    <option value="">Select Gender</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select>
            </div>
            <div class="form-group"><input type="text" name="address" placeholder="Address"></div>
            <div class="form-group"><input type="password" name="password" placeholder="Password" required minlength="6"></div>
            <!-- Grade Level Radios -->
            <div class="selection-group">
                <span class="selection-label">Grade Level:</span>
                <div class="radio-btn-group" style="margin-bottom: 16px;">
                <?php foreach($grade_levels as $gl): ?>
                    <div class="radio-btn">
                        <input type="radio" id="grade_<?php echo $gl['grade_level_id']; ?>" name="grade_level_id" value="<?php echo $gl['grade_level_id']; ?>" required onclick="loadSections(this)">
                        <label for="grade_<?php echo $gl['grade_level_id']; ?>"><?php echo htmlspecialchars($gl['level_name']); ?></label>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

            <!-- Section Selection (will be populated dynamically) -->
            <div class="selection-group">
                <span class="selection-label">Section:</span>
                <div id="section-container" class="radio-btn-group" style="margin-bottom: 0;"></div>
            </div>

            <!-- Strand Selection (for Senior High only) -->
            <div class="selection-group" id="strand-group" style="display:none;">
                <span class="selection-label">Strand:</span>
                <div class="radio-btn-group" id="strand-container" style="margin-bottom: 0;">
                    <div class="radio-btn"><input type="radio" id="strand_stem" name="strand" value="STEM"><label for="strand_stem">STEM</label></div>
                    <div class="radio-btn"><input type="radio" id="strand_abm" name="strand" value="ABM"><label for="strand_abm">ABM</label></div>
                    <div class="radio-btn"><input type="radio" id="strand_humss" name="strand" value="HUMSS"><label for="strand_humss">HUMSS</label></div>
                    <div class="radio-btn"><input type="radio" id="strand_tvlhe" name="strand" value="TVL-HE"><label for="strand_tvlhe">TVL-HE</label></div>
                </div>
            </div>
            <?php } elseif ($type === 'faculty') { ?>
            <div class="form-group"><input type="text" name="first_name" placeholder="First Name" required></div>
            <div class="form-group"><input type="text" name="middle_name" placeholder="Middle Name"></div>
            <div class="form-group"><input type="text" name="last_name" placeholder="Last Name" required></div>
            <div class="form-group"><input type="text" name="username" placeholder="Username" required></div>
            <div class="form-group"><input type="text" name="subject" placeholder="Subject" required></div>
            <div class="form-group">
                <select name="grade_level_id" required style="width:100%;padding:10px;border-radius:5px;border:1px solid #ccc;" onchange="loadFacultySections(this.value)">
                    <option value="">Select Grade Level</option>
                    <?php foreach($grade_levels as $gl) { ?>
                        <option value="<?php echo $gl['grade_level_id']; ?>"><?php echo htmlspecialchars($gl['level_name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <select name="section_id" required style="width:100%;padding:10px;border-radius:5px;border:1px solid #ccc;" id="faculty-section-select">
                    <option value="">Select Section</option>
                </select>
            </div>
            <div class="form-group"><input type="password" name="password" placeholder="Password" required minlength="6"></div>
            <div class="form-group">
                <select name="honorific" required style="width:100%;padding:10px;border-radius:5px;border:1.5px solid #ccc;">
                    <option value="">Select Honorific</option>
                    <option value="Sir">Sir</option>
                    <option value="Ma'am">Ma'am</option>
                </select>
            </div>
            <?php } elseif ($type === 'clinic') { ?>
            <div class="form-group"><input type="text" name="first_name" placeholder="First Name" required></div>
            <div class="form-group"><input type="text" name="middle_name" placeholder="Middle Name"></div>
            <div class="form-group"><input type="text" name="last_name" placeholder="Last Name" required></div>
            <div class="form-group"><input type="text" name="username" placeholder="Username" required></div>
            <div class="form-group"><input type="password" name="password" placeholder="Password" required minlength="6"></div>
            <?php } ?>
            <button type="submit" class="signin-btn" style="margin-top:10px;">Register</button>
        </form>
        </div>
        <a href="register_landing.php" class="forgot" style="display:block;text-align:center;margin-top:15px;">&larr; Back to Register Options</a>
        <a href="login_landing.php" class="forgot" style="display:block;text-align:center;margin-top:5px;">&larr; Back to Login</a>
    </div>
    <script>
    // Store sections data from PHP
    var sectionsData = <?php echo json_encode($sections); ?>;
    var gradeLevelsData = <?php echo json_encode($grade_levels); ?>;
    
    function setupStrandListeners() {
        var strandInputs = document.querySelectorAll('input[name="strand"]');
        strandInputs.forEach(function(input) {
            input.addEventListener('change', function() {
                if (this.checked) {
                    loadSeniorHighSections(this.value);
                }
            });
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        setupStrandListeners();
    });

    // Also call setupStrandListeners after grade level change (in case DOM is re-rendered)
    function loadSections(radio) {
        var gradeId = radio.value;
        var gradeText = radio.parentNode.textContent.trim();
        var sectionContainer = document.getElementById('section-container');
        var strandGroup = document.getElementById('strand-group');
        document.querySelectorAll('input[name="section_id"]').forEach(input => input.checked = false);
        document.querySelectorAll('input[name="strand"]').forEach(input => input.checked = false);
        if (gradeText === 'Grade 11' || gradeText === 'Grade 12') {
            strandGroup.style.display = 'block';
            sectionContainer.innerHTML = '';
            Array.from(strandGroup.querySelectorAll('input')).forEach(i => i.required = true);
            setupStrandListeners(); // ensure listeners are set
        } else {
            strandGroup.style.display = 'none';
            Array.from(strandGroup.querySelectorAll('input')).forEach(i => i.required = false);
            loadJuniorHighSections(gradeId);
        }
    }

    function loadSeniorHighSections(strand) {
        var sectionContainer = document.getElementById('section-container');
        var selectedGrade = document.querySelector('input[name="grade_level_id"]:checked');
        if (!selectedGrade) return;
        sectionContainer.innerHTML = '';
        sectionContainer.className = 'radio-btn-group';
        // Always show only two options: 1 and 2
        for (let i = 1; i <= 2; i++) {
            var label = document.createElement('label');
            label.className = 'radio-btn';
            label.style.marginTop = '4px';
            var input = document.createElement('input');
            input.type = 'radio';
            input.name = 'section_id';
            // Find the correct section_id from sectionsData
            var sectionObj = sectionsData.find(function(section) {
                return section.grade_level_id == selectedGrade.value && section.section_name === (strand + '-' + i);
            });
            if (sectionObj) {
                input.value = sectionObj.section_id;
            } else {
                input.value = '';
            }
            input.required = true;
            label.appendChild(input);
            var span = document.createElement('span');
            span.textContent = ' ' + i;
            label.appendChild(span);
            sectionContainer.appendChild(label);
        }
    }
    
    function loadJuniorHighSections(gradeId) {
        var sectionContainer = document.getElementById('section-container');
        sectionContainer.innerHTML = '';
        sectionContainer.className = 'radio-btn-group';
        // Filter sections for the selected grade level (A, B, C only)
        var gradeSections = sectionsData.filter(function(section) {
            return section.grade_level_id == gradeId && ['A', 'B', 'C'].includes(section.section_name);
        });
        // Always show A, B, C as button-like radios
        ['A', 'B', 'C'].forEach(function(letter) {
            var sectionObj = gradeSections.find(s => s.section_name === letter);
            if (sectionObj) {
                var label = document.createElement('label');
                label.className = 'radio-btn';
                label.style.marginTop = '4px';
                var input = document.createElement('input');
                input.type = 'radio';
                input.name = 'section_id';
                input.value = sectionObj.section_id;
                input.required = true;
                label.appendChild(input);
                var span = document.createElement('span');
                span.textContent = ' ' + letter;
                label.appendChild(span);
                sectionContainer.appendChild(label);
            }
        });
    }
    
    function loadFacultySections(gradeId) {
        var sectionSelect = document.getElementById('faculty-section-select');
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        
        if (!gradeId) return;
        
        // Filter sections for the selected grade level
        var gradeSections = sectionsData.filter(function(section) {
            return section.grade_level_id == gradeId;
        });
        
        gradeSections.forEach(function(section) {
            var option = document.createElement('option');
            option.value = section.section_id;
            option.textContent = section.section_name;
            sectionSelect.appendChild(option);
        });
    }
    </script>
</body>
</html>