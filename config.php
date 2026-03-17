<?php
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP password is empty
$database = 'jkkmct_quiz';

$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql_db = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql_db) === TRUE) {
    $conn->select_db($database);
} else {
    die("Error creating database: " . $conn->error);
}

// Set timezone (adjust as needed for India)
date_default_timezone_set('Asia/Kolkata');

// Utility function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}
?>
