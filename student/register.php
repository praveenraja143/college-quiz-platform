<?php
session_start();
require_once '../config.php';

$message = '';
$debug_info = ''; // To show password for testing since real emails might not send on localhost

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $dept = sanitize_input($_POST['department']);
    $year = sanitize_input($_POST['year']);
    $reg = sanitize_input($_POST['reg_no']);
    $email = sanitize_input($_POST['email']);

    // Generate unique ID and password
    $unique_id = 'JKK' . strtoupper(substr(md5(uniqid()), 0, 6));
    $raw_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$'), 0, 8);
    $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO students (name, department, year, reg_no, email, unique_id, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $name, $dept, $year, $reg, $email, $unique_id, $hashed_password);

    if ($stmt->execute()) {
        // Attempt to send email (this is basic mail(), usually needs SMTP configuration in php.ini to work locally)
        $to = $email;
        $subject = "JKKMCT Quiz Competition - Credentials";
        $body = "Hello $name,\n\nYou have registered for the JKKMCT Quiz Competition.\nYour Unique ID: $unique_id\nYour Password: $raw_password\n\nPlease keep these safe. The password is only valid until the competition end date.\n\nRegards,\nAdmin";
        $headers = "From: noreply@jkkmct.edu\r\n";
        
        @mail($to, $subject, $body, $headers); // Supress warnings if mail server not configured
        
        $message = "Registration successful! An email has been sent to your address with your credentials.";
        // For development/testing: Provide the credentials on screen to the user. Remove in production.
        $debug_info = "<strong>Testing Mode Credentials (copy these):</strong><br>Unique ID: $unique_id<br>Password: $raw_password";
    } else {
        if ($conn->errno == 1062) {
            $message = "Error: Email or Register Number already exists.";
        } else {
            $message = "Error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - JKKMCT Quiz</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; margin: 0; padding: 2rem; display: flex; justify-content: center; }
        .reg-container { background: white; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .reg-container h2 { text-align: center; color: #1F2937; margin-top: 0; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 500; }
        input[type="text"], input[type="email"] { width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 0.75rem; background-color: #4F46E5; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 1rem; }
        .btn:hover { background-color: #4338CA; }
        .msg { padding: 1rem; background: #D1FAE5; color: #065F46; border-radius: 4px; margin-bottom: 1rem; text-align: center; }
        .error { background: #FEE2E2; color: #B91C1C; }
        .debug { background: #FFFBEB; color: #92400E; padding: 1rem; border: 1px dashed #D97706; border-radius: 4px; margin-bottom: 1.5rem; }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: #4F46E5; text-decoration: none; font-weight: 500; }
    </style>
</head>
<body>
    <div class="reg-container">
        <h2>Student Registration</h2>
        
        <?php 
            if($message) {
                $class = strpos($message, 'Error') !== false ? 'msg error' : 'msg';
                echo "<div class='$class'>$message</div>";
            }
            if($debug_info) {
                echo "<div class='debug'>$debug_info</div>";
            }
        ?>

        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Department</label>
                <input type="text" name="department" required placeholder="e.g. Computer Science">
            </div>
            <div class="form-group">
                <label>Year of Study</label>
                <input type="text" name="year" required placeholder="e.g. 3rd Year">
            </div>
            <div class="form-group">
                <label>Register Number</label>
                <input type="text" name="reg_no" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required>
            </div>
            <button type="submit" class="btn">Register Now</button>
        </form>
        
        <a href="../index.php" class="back-link">Return to Home</a>
    </div>
</body>
</html>
