<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config.php';

$student_id = $_SESSION['student_id'];

// New Requirement: Check if this user has ALREADY participated in ANY competition
$check_global = $conn->prepare("SELECT id FROM results WHERE student_id = ?");
$check_global->bind_param("i", $student_id);
$check_global->execute();
if ($check_global->get_result()->num_rows > 0) {
    // Already used their one-time opportunity
    header("Location: logout.php");
    exit();
}
$check_global->close();

// Get Candidate Details
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get Competitions (Active or Upcoming)
$comp_query = "SELECT c.*, r.id as attempt_id FROM competitions c 
               LEFT JOIN results r ON c.id = r.competition_id AND r.student_id = $student_id
               WHERE c.status IN ('active', 'upcoming') 
               ORDER BY c.start_time ASC";
$competitions = $conn->query($comp_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Gateway - JKKMCT Quiz</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; color: #1F2937; margin: 0; padding: 2rem; display: flex; flex-direction: column; align-items: center; }
        .header { width: 100%; max-width: 800px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .navbar a { text-decoration: none; color: #EF4444; font-weight: bold; padding: 0.5rem 1rem; border: 1px solid #EF4444; border-radius: 4px; transition: 0.2s; }
        .navbar a:hover { background: #EF4444; color: white; }
        
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 800px; margin-bottom: 2rem; }
        .card h2 { margin-top: 0; border-bottom: 2px solid #E5E7EB; padding-bottom: 1rem; color: #4F46E5; }
        
        .profile-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .profile-item { margin-bottom: 0.5rem; }
        .profile-item strong { color: #6B7280; display: block; font-size: 0.875rem; }
        .profile-item span { font-size: 1.125rem; font-weight: 500; }
        
        .comp-list { list-style: none; padding: 0; margin: 0; }
        .comp-item { border: 1px solid #E5E7EB; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; background: #F9FAFB; }
        .comp-info h3 { margin: 0 0 0.5rem 0; color: #1F2937; }
        .comp-info p { margin: 0; color: #6B7280; font-size: 0.875rem; }
        
        .btn-start { background-color: #10B981; color: white; text-decoration: none; padding: 0.75rem 2rem; border-radius: 4px; font-weight: bold; font-size: 1.125rem; transition: 0.2s; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); }
        .btn-start:hover { background-color: #059669; transform: translateY(-2px); box-shadow: 0 6px 8px rgba(16, 185, 129, 0.3); }
        .btn-disabled { background-color: #D1D5DB; color: #6B7280; cursor: not-allowed; padding: 0.75rem 2rem; border-radius: 4px; font-weight: bold; }
        .badge { display: inline-block; padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.75rem; font-weight: bold; }
        .badge-active { background: #D1FAE5; color: #065F46; }
        .badge-upcoming { background: #FEF3C7; color: #92400E; }
        .badge-completed { background: #EEF2FF; color: #4F46E5; }
    </style>
</head>
<body>
    <div class="header">
        <h1>JKKMCT Quiz Portal</h1>
        <div class="navbar"><a href="logout.php">Logout</a></div>
    </div>

    <div class="card">
        <h2>Candidate Details</h2>
        <div class="profile-grid">
            <div class="profile-item"><strong>Name</strong><span><?php echo htmlspecialchars($student['name']); ?></span></div>
            <div class="profile-item"><strong>Register Number</strong><span><?php echo htmlspecialchars($student['reg_no']); ?></span></div>
            <div class="profile-item"><strong>Department</strong><span><?php echo htmlspecialchars($student['department']); ?></span></div>
            <div class="profile-item"><strong>Year of Study</strong><span><?php echo htmlspecialchars($student['year']); ?></span></div>
            <div class="profile-item"><strong>Email</strong><span><?php echo htmlspecialchars($student['email']); ?></span></div>
            <div class="profile-item"><strong>Unique ID</strong><span><?php echo htmlspecialchars($student['unique_id']); ?></span></div>
        </div>
    </div>

    <div class="card">
        <h2>Available Competitions</h2>
        <?php if($competitions->num_rows > 0): ?>
            <ul class="comp-list">
                <?php while($comp = $competitions->fetch_assoc()): 
                    $now = new DateTime();
                    $start = new DateTime($comp['start_time']);
                    $end = new DateTime($comp['end_time']);
                    
                    // Business logic to determine button state
                    $can_start = false;
                    $status_text = "Upcoming";
                    $badge_class = "badge-upcoming";
                    
                    if ($comp['attempt_id']) {
                        $status_text = "Already Attempted";
                        $badge_class = "badge-completed";
                    } else if ($comp['status'] == 'active' && $now >= $start && $now <= $end) {
                        $can_start = true;
                        $status_text = "Active Now";
                        $badge_class = "badge-active";
                    } else if ($comp['status'] == 'active' && $now < $start) {
                        $status_text = "Starts at " . $start->format('d M Y, h:i A');
                    } else if ($comp['status'] == 'active' && $now > $end) {
                        $status_text = "Time Expired";
                    }
                ?>
                <li class="comp-item">
                    <div class="comp-info">
                        <h3><?php echo htmlspecialchars($comp['name']); ?> <span class="badge <?php echo $badge_class; ?>"><?php echo $status_text; ?></span></h3>
                        <p>Window: <?php echo $start->format('d M, h:i A'); ?> - <?php echo $end->format('d M, h:i A'); ?></p>
                        <p style="color:red; font-size:0.8rem; margin-top:0.5rem;">&#9888; Warning: Once started, do not leave the page or press any keyboard keys. Doing so will auto-submit your exam.</p>
                    </div>
                    <div class="comp-action">
                        <?php if($can_start): ?>
                            <a href="take_quiz.php?comp_id=<?php echo $comp['id']; ?>" class="btn-start" onclick="return confirm('Ready to start? The timer will begin immediately and you cannot pause.');">Start Exam</a>
                        <?php else: ?>
                            <button class="btn-disabled" disabled><?php echo ($comp['attempt_id']) ? 'Completed' : 'Wait...'; ?></button>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p style="color:#6B7280; text-align:center;">No competitions are currently available.</p>
        <?php endif; ?>
    </div>
</body>
</html>
