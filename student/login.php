<?php
session_start();
if (isset($_SESSION['student_id'])) {
    header("Location: wait_room.php");
    exit();
}

require_once '../config.php';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $unique_id = sanitize_input($_POST['unique_id']);
    $password = $_POST['password'];

    // Fetch student along with their competition details
    $stmt = $conn->prepare("SELECT s.id, s.name, s.competition_id, c.name as comp_name, c.end_time, c.status 
                            FROM students s 
                            JOIN competitions c ON s.competition_id = c.id 
                            WHERE s.unique_id = ?");
    $stmt->bind_param("s", $unique_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            // Check if competition has ended
            $now = new DateTime();
            $end = new DateTime($row['end_time']);
            if ($row['status'] == 'completed' || $now > $end) {
                $error = "The competition <strong>" . htmlspecialchars($row['comp_name']) . "</strong> has ended. These credentials are no longer valid.";
            } else {
                // Check if already attempted THIS competition
                $check_used = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND competition_id = ?");
                $check_used->bind_param("ii", $row['id'], $row['competition_id']);
                $check_used->execute();
                if ($check_used->get_result()->num_rows > 0) {
                    $error = "You have already completed <strong>" . htmlspecialchars($row['comp_name']) . "</strong>. This ID is no longer valid.";
                } else {
                    $_SESSION['student_id'] = $row['id'];
                    $_SESSION['student_name'] = $row['name'];
                    $_SESSION['student_comp_id'] = $row['competition_id'];
                    $_SESSION['student_comp_name'] = $row['comp_name'];
                    header("Location: wait_room.php");
                    exit();
                }
            }
        } else {
            $error = "Invalid Unique ID or Password.";
        }
    } else {
        $error = "Invalid Unique ID or Password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - JKKMCT Quiz</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #F3F4F6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .login-box { background: white; padding: 2.5rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .login-box h2 { text-align: center; color: #1F2937; margin-bottom: 0.5rem; margin-top: 0; }
        .subtitle { text-align: center; color: #6B7280; margin-bottom: 2rem; font-size: 0.875rem; }
        .input-group { margin-bottom: 1.25rem; }
        .input-group label { display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 500; }
        .input-group input { width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 0.75rem; background-color: #4F46E5; color: white; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: background-color 0.2s; font-size: 1rem; }
        .btn:hover { background-color: #4338CA; }
        .error { color: #DC2626; text-align: center; margin-bottom: 1rem; font-size: 0.875rem; padding: 0.75rem; background: #FEE2E2; border-radius: 4px; }
        .links { margin-top: 1.5rem; text-align: center; font-size: 0.875rem; }
        .links a { color: #4F46E5; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Student Login</h2>
        <div class="subtitle">Enter the credentials sent to your email</div>
        
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        
        <form method="POST" action="">
            <div class="input-group">
                <label>Unique ID</label>
                <input type="text" name="unique_id" required placeholder="e.g. JKKAB12C3" autocomplete="off">
            </div>
            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login to Portal</button>
        </form>
        
        <div class="links">
            <a href="../index.php">Return to Home</a> | <a href="register.php">Register Here</a>
        </div>
    </div>
</body>
</html>
