# PDMHS Student Medical Record System

`devhive-infoman` is a web-based application designed to manage the medical records of students at President Diosdado Macapagal High School (PDMHS). The system provides a secure and efficient way for students, faculty, and clinic staff to access and manage health-related information.

## Features

The system is tailored to three distinct user roles:

### Student
- Securely log in using their Learner Reference Number (LRN) and birthdate.
- View their personal and medical information.
- Check their history of clinic visits.
- Receive notifications from the clinic.

### Faculty
- Log in with their dedicated credentials.
- Access medical records of students in their classes.
- Generate reports related to student health.

### Clinic Staff
- Manage the entire system.
- View and update student medical records.
- Record new student visits, including symptoms, diagnosis, and treatments.
- Manage the inventory of clinic medications.
- Generate comprehensive health reports.
- Manage system settings.

## Technology Stack

- **Backend:** PHP
- **Database:** MySQL / MariaDB
- **Frontend:** HTML, CSS, JavaScript (Vanilla)

## Database Schema

The `pdmhs.sql` file contains the complete database structure. Key tables include:

- `students`: Core information about each student.
- `faculty`: Information about faculty members.
- `clinic_staff`: Credentials and details for clinic personnel.
- `visits`: Detailed logs of each clinic visit.
- `medications`: Inventory of available medicines.
- `medical_profiles`: Specific health details for each student (blood type, allergies, etc.).
- `grade_levels`, `sections`, `student_enrollments`: Tables to manage the school's academic structure.

## Setup

1.  **Database:**
    - Create a new database named `pdmhs` in your MySQL/MariaDB server.
    - Import the `pdmhs.sql` file to set up the required tables and initial data.

2.  **Configuration:**
    - Edit the `includes/db.php` file.
    - Update the database credentials (`$host`, `$user`, `$pass`, `$db`) to match your local environment.

3.  **Running the Application:**
    - Place the project files in your web server's root directory (e.g., `htdocs` for XAMPP, `www` for WAMP).
    - Open your web browser and navigate to the project's URL (e.g., `http://localhost/devhive-infoman`).