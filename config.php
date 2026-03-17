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

// Auto-update competition statuses based on current time
// Runs on every page load to keep statuses in sync
function auto_update_competition_status() {
    global $conn;
    $now = date('Y-m-d H:i:s');
    
    // Upcoming → Active (start_time has been reached)
    $conn->query("UPDATE competitions SET status = 'active' WHERE status = 'upcoming' AND start_time <= '$now'");
    
    // Active → Completed (end_time has passed)
    $conn->query("UPDATE competitions SET status = 'completed' WHERE status = 'active' AND end_time <= '$now'");
}
auto_update_competition_status();
?>
