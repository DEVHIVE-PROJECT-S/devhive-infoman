document.addEventListener('DOMContentLoaded', function() {
    const studentBtn = document.getElementById('studentBtn');
    const facultyBtn = document.getElementById('facultyBtn');
    const clinicBtn = document.getElementById('clinicBtn');

    if(studentBtn) studentBtn.onclick = () => window.location.href = 'login.php?type=student';
    if(facultyBtn) facultyBtn.onclick = () => window.location.href = 'login.php?type=faculty';
    if(clinicBtn) clinicBtn.onclick = () => window.location.href = 'login.php?type=clinic';
}); 