<?php
// ==========================================
// JKKMCT Quiz Platform - Configuration
// ==========================================
// For production: Update these with your hosting credentials
$host = 'localhost';
$username = 'root';
$password = ''; // Update for production
$database = 'jkkmct_quiz';

// Use persistent connection for better performance under load
// The 'p:' prefix tells PHP to reuse existing connections
$conn = new mysqli('p:' . $host, $username, $password);

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

// Set timezone (India Standard Time)
date_default_timezone_set('Asia/Kolkata');

// Performance: Set charset for proper encoding
$conn->set_charset("utf8mb4");

// Utility function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Auto-update competition statuses based on current time
// Uses prepared-style queries for safety under concurrent load
function auto_update_competition_status() {
    global $conn;
    $now = date('Y-m-d H:i:s');
    
    // Upcoming → Active (start_time has been reached)
    $stmt1 = $conn->prepare("UPDATE competitions SET status = 'active' WHERE status = 'upcoming' AND start_time <= ?");
    $stmt1->bind_param("s", $now);
    $stmt1->execute();
    $stmt1->close();
    
    // Active → Completed (end_time has passed)
    $stmt2 = $conn->prepare("UPDATE competitions SET status = 'completed' WHERE status = 'active' AND end_time <= ?");
    $stmt2->bind_param("s", $now);
    $stmt2->execute();
    $stmt2->close();
}
auto_update_competition_status();
?>
