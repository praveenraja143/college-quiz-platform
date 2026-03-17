<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['comp_id'])) {
    header("Location: wait_room.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$comp_id = intval($_POST['comp_id']);
$violation = intval($_POST['violation'] ?? 0);

// Check if already submitted
$check = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND competition_id = ?");
$check->bind_param("ii", $student_id, $comp_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    die("Result already recorded.");
}
$check->close();

// Calculate Time Taken
$session_time_key = "start_time_" . $comp_id;
$start_time = $_SESSION[$session_time_key] ?? time();
$time_taken = time() - $start_time;

// Calculate Score
$score = 0;

$q_stmt = $conn->prepare("SELECT id, correct_option FROM questions WHERE competition_id = ?");
$q_stmt->bind_param("i", $comp_id);
$q_stmt->execute();
$questions = $q_stmt->get_result();

while ($q = $questions->fetch_assoc()) {
    $q_key = 'q_' . $q['id'];
    if (isset($_POST[$q_key]) && $_POST[$q_key] === $q['correct_option']) {
        // Scoring: 1 mark given per correct answer
        $score += 1; 
    }
}
$q_stmt->close();

// Penalize if violation occurred: force score to 0
if ($violation) {
    $score = 0;
}

$insert = $conn->prepare("INSERT INTO results (student_id, competition_id, score, time_taken_seconds, violation) VALUES (?, ?, ?, ?, ?)");
$insert->bind_param("iiiii", $student_id, $comp_id, $score, $time_taken, $violation);
$insert->execute();

// Clear the session start time
unset($_SESSION[$session_time_key]);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quiz Submitted</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: white; padding: 3rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
        h1 { color: #10B981; margin-top: 0; }
        .violation { color: #DC2626; font-weight: bold; background: #FEE2E2; padding: 1rem; border-radius: 6px; margin: 1rem 0; }
        .btn { display: inline-block; margin-top: 2rem; padding: 0.75rem 2rem; background: #4F46E5; color: white; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn:hover { background: #4338CA; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Examination Complete</h1>
        <p>Your responses have been securely submitted to the server and your time has been recorded.</p>
        
        <?php if($violation): ?>
            <div class="violation">
                Warning: Application was auto-submitted due to a detected environment violation (Keyboard usage, Tab Switch, or Window Minimized).
            </div>
        <?php endif; ?>
        
        <p style="color: #6B7280; font-size: 0.875rem;">Your results are under review by the administrator.</p>
        
        <a href="wait_room.php" class="btn">Return to Dashboard</a>
    </div>
</body>
</html>
