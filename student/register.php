<?php
session_start();
require_once '../config.php';

$message = '';
$debug_info = '';

// Fetch active/upcoming competitions for the dropdown
$comp_result = $conn->query("SELECT id, name, status, start_time FROM competitions WHERE status IN ('upcoming', 'active') ORDER BY start_time ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $dept = sanitize_input($_POST['department']);
    $year = sanitize_input($_POST['year']);
    $reg = sanitize_input($_POST['reg_no']);
    $email = sanitize_input($_POST['email']);
    $comp_id = intval($_POST['competition_id']);

    // Validate competition exists
    $comp_check = $conn->prepare("SELECT name FROM competitions WHERE id = ?");
    $comp_check->bind_param("i", $comp_id);
    $comp_check->execute();
    $comp_check_res = $comp_check->get_result();
    if ($comp_check_res->num_rows === 0) {
        $message = "Error: Invalid competition selected.";
    } else {
        $comp_name_val = $comp_check_res->fetch_assoc()['name'];

        // Generate unique ID and password
        $unique_id = 'JKK' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $raw_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$'), 0, 8);
        $hashed_password = password_hash($raw_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO students (competition_id, name, department, year, reg_no, email, unique_id, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssss", $comp_id, $name, $dept, $year, $reg, $email, $unique_id, $hashed_password);

        if ($stmt->execute()) {
            $to = $email;
            $subject = "JKKMCT Quiz Competition - Credentials for $comp_name_val";
            $body = "Hello $name,\n\nYou have registered for the JKKMCT Quiz Competition: $comp_name_val.\nYour Unique ID: $unique_id\nYour Password: $raw_password\n\nPlease keep these safe. This ID and password are valid ONLY for this competition.\n\nRegards,\nAdmin";
            $headers = "From: noreply@jkkmct.edu\r\n";
            @mail($to, $subject, $body, $headers);

            $message = "Registration successful for <strong>$comp_name_val</strong>! Credentials have been sent to your email.";
            $debug_info = "<strong>Testing Mode Credentials (copy these):</strong><br>Competition: $comp_name_val<br>Unique ID: $unique_id<br>Password: $raw_password";
        } else {
            if ($conn->errno == 1062) {
                $message = "Error: You are already registered for this competition with the same Register Number.";
            } else {
                $message = "Error: " . $stmt->error;
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
    <title>Student Registration - JKKMCT Quiz</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; margin: 0; padding: 2rem; display: flex; justify-content: center; }
        .reg-container { background: white; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        .reg-container h2 { text-align: center; color: #1F2937; margin-top: 0; margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.25rem; }
        label { display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 500; }
        input[type="text"], input[type="email"], select { width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 4px; box-sizing: border-box; }
        select { background: white; }
        .btn { width: 100%; padding: 0.75rem; background-color: #4F46E5; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.2s; font-size: 1rem; }
        .btn:hover { background-color: #4338CA; }
        .msg { padding: 1rem; background: #D1FAE5; color: #065F46; border-radius: 4px; margin-bottom: 1rem; text-align: center; }
        .error { background: #FEE2E2; color: #B91C1C; }
        .debug { background: #FFFBEB; color: #92400E; padding: 1rem; border: 1px dashed #D97706; border-radius: 4px; margin-bottom: 1.5rem; }
        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: #4F46E5; text-decoration: none; font-weight: 500; }
        .comp-info { background: #EEF2FF; padding: 0.75rem; border-radius: 4px; margin-top: 0.25rem; font-size: 0.85rem; color: #4338CA; }
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
                <label>Select Competition</label>
                <select name="competition_id" required id="compSelect">
                    <option value="">-- Choose a Competition --</option>
                    <?php 
                    if ($comp_result->num_rows > 0) {
                        while($c = $comp_result->fetch_assoc()) {
                            $label = htmlspecialchars($c['name']) . ' (' . ucfirst($c['status']) . ')';
                            echo "<option value='{$c['id']}'>$label</option>";
                        }
                    }
                    ?>
                </select>
                <?php if($comp_result->num_rows == 0): ?>
                    <div class="comp-info">No competitions are currently available for registration.</div>
                <?php endif; ?>
            </div>
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
