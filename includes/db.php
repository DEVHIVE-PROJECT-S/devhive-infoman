<?php
$host = "localhost";
$user = "pdmhs";
$pass = "pdmhs_2026@";
$db   = "pdmhs";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?> 