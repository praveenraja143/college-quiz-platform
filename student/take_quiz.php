<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config.php';

if (!isset($_GET['comp_id']) || !is_numeric($_GET['comp_id'])) {
    die("Invalid Competition ID.");
}
$comp_id = $_GET['comp_id'];
$student_id = $_SESSION['student_id'];
$session_comp_id = $_SESSION['student_comp_id'] ?? 0;

// Ensure student can only access the competition they registered for
if ($comp_id != $session_comp_id) {
    die("You are not registered for this competition.");
}

// Check Competition Status
$comp_stmt = $conn->prepare("SELECT * FROM competitions WHERE id = ?");
$comp_stmt->bind_param("i", $comp_id);
$comp_stmt->execute();
$comp_res = $comp_stmt->get_result();
if ($comp_res->num_rows === 0) die("Competition not found.");
$competition = $comp_res->fetch_assoc();

$now = new DateTime();
$start = new DateTime($competition['start_time']);
$end = new DateTime($competition['end_time']);

if ($competition['status'] != 'active' || $now < $start || $now > $end) {
    die("This competition is not currently active.");
}

// Check if already attempted THIS competition
$attempt_stmt = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND competition_id = ?");
$attempt_stmt->bind_param("ii", $student_id, $comp_id);
$attempt_stmt->execute();
if ($attempt_stmt->get_result()->num_rows > 0) {
    die("You have already completed this competition.");
}

// Track Start Time
$session_time_key = "start_time_" . $comp_id;
if (!isset($_SESSION[$session_time_key])) {
    $_SESSION[$session_time_key] = time();
}

// Fetch Questions
$q_stmt = $conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d FROM questions WHERE competition_id = ?");
$q_stmt->bind_param("i", $comp_id);
$q_stmt->execute();
$questions = $q_stmt->get_result();

$time_remaining = $end->getTimestamp() - time();
if ($time_remaining <= 0) {
    header("Location: wait_room.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($competition['name']); ?> - Exam Running</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #F3F4F6; margin: 0; padding: 0; -webkit-user-select: none; user-select: none; }
        .header { background: #1F2937; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .timer { font-size: 1.5rem; font-weight: bold; font-family: monospace; background: #DC2626; padding: 0.5rem 1rem; border-radius: 4px; }
        
        .container { max-width: 800px; margin: 2rem auto; padding: 0 1rem; }
        .question-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1.5rem; }
        .question-text { font-size: 1.125rem; font-weight: 600; color: #1F2937; margin-bottom: 1.5rem; line-height: 1.5; }
        
        .options-grid { display: grid; gap: 0.75rem; }
        .option-label { display: block; background: #F9FAFB; border: 2px solid #E5E7EB; padding: 1rem; border-radius: 6px; cursor: pointer; transition: all 0.2s; position: relative; }
        .option-label:hover { border-color: #A78BFA; background: #F5F3FF; }
        .option-label input[type="radio"] { position: absolute; opacity: 0; }
        .option-label input[type="radio"]:checked + .option-text { font-weight: bold; color: #4F46E5; }
        .option-label:has(input[type="radio"]:checked) { border-color: #4F46E5; background: #EEF2FF; }

        .btn-submit { display: block; width: 100%; max-width: 300px; margin: 2rem auto; padding: 1rem; background: #10B981; color: white; border: none; border-radius: 8px; font-size: 1.25rem; font-weight: bold; cursor: pointer; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); transition: 0.2s; }
        .btn-submit:hover { background: #059669; transform: translateY(-2px); }
        
        #overlay-warning { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); color: white; z-index: 1000; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 2rem; }
        #overlay-warning h1 { color: #EF4444; font-size: 3rem; margin-bottom: 1rem; }
    </style>
</head>
<body oncontextmenu="return false;">

    <div id="overlay-warning">
        <h1>VIOLATION DETECTED</h1>
        <p style="font-size:1.5rem;">Unpermitted action disabled. You must use only mouse/touch clicks.</p>
        <p>Your competition is being automatically submitted...</p>
    </div>

    <div class="header">
        <div>
            <h2 style="margin:0;"><?php echo htmlspecialchars($competition['name']); ?></h2>
            <div style="font-size:0.875rem; color:#9CA3AF;">Candidate: <?php echo htmlspecialchars($_SESSION['student_name']); ?></div>
        </div>
        <div class="timer" id="timerDisplay">--:--</div>
    </div>

    <div class="container">
        <form id="quizForm" method="POST" action="submit_quiz.php">
            <input type="hidden" name="comp_id" value="<?php echo $comp_id; ?>">
            <input type="hidden" name="violation" id="violationInput" value="0">
            
            <?php 
            $q_num = 1;
            while($q = $questions->fetch_assoc()): 
            ?>
            <div class="question-card">
                <div class="question-text"><?php echo $q_num . ". " . nl2br(htmlspecialchars($q['question_text'])); ?></div>
                <div class="options-grid">
                    <label class="option-label">
                        <input type="radio" name="q_<?php echo $q['id']; ?>" value="A">
                        <span class="option-text"><?php echo htmlspecialchars($q['option_a']); ?></span>
                    </label>
                    <label class="option-label">
                        <input type="radio" name="q_<?php echo $q['id']; ?>" value="B">
                        <span class="option-text"><?php echo htmlspecialchars($q['option_b']); ?></span>
                    </label>
                    <label class="option-label">
                        <input type="radio" name="q_<?php echo $q['id']; ?>" value="C">
                        <span class="option-text"><?php echo htmlspecialchars($q['option_c']); ?></span>
                    </label>
                    <label class="option-label">
                        <input type="radio" name="q_<?php echo $q['id']; ?>" value="D">
                        <span class="option-text"><?php echo htmlspecialchars($q['option_d']); ?></span>
                    </label>
                </div>
            </div>
            <?php 
            $q_num++;
            endwhile; 
            if($questions->num_rows == 0) echo "<p style='text-align:center;'>No questions assigned for this competition yet.</p>";
            ?>
            
            <button type="button" class="btn-submit" onclick="confirmSubmit()">Submit Examination</button>
        </form>
    </div>

    <script>
        // --- TIMER LOGIC ---
        let timeRemaining = <?php echo $time_remaining; ?>;
        const timerDisplay = document.getElementById('timerDisplay');
        const form = document.getElementById('quizForm');

        function updateTimer() {
            if (timeRemaining <= 0) {
                timerDisplay.textContent = "00:00:00";
                form.submit();
                return;
            }
            let h = Math.floor(timeRemaining / 3600);
            let m = Math.floor((timeRemaining % 3600) / 60);
            let s = timeRemaining % 60;
            timerDisplay.textContent = 
                (h < 10 ? "0"+h : h) + ":" + 
                (m < 10 ? "0"+m : m) + ":" + 
                (s < 10 ? "0"+s : s);
            timeRemaining--;
        }
        setInterval(updateTimer, 1000);
        updateTimer();

        // --- ANTI-CHEAT LOGIC ---
        let submitted = false;
        
        function forceSubmit(reason) {
            if(submitted) return;
            submitted = true;
            document.getElementById('overlay-warning').style.display = 'flex';
            document.getElementById('violationInput').value = "1";
            setTimeout(() => { form.submit(); }, 2000);
        }

        function confirmSubmit() {
            if(confirm('Are you sure you want to manually submit? You cannot undo this.')) {
                submitted = true;
                form.submit();
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) { forceSubmit("Tab switch detected"); }
        });
        window.addEventListener('blur', () => { forceSubmit("Window lost focus"); });
        document.addEventListener('keydown', (e) => { e.preventDefault(); forceSubmit("Keyboard usage detected"); });
    </script>
</body>
</html>
