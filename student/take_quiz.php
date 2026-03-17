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

if ($comp_id != $session_comp_id) {
    die("You are not registered for this competition.");
}

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

$attempt_stmt = $conn->prepare("SELECT id FROM results WHERE student_id = ? AND competition_id = ?");
$attempt_stmt->bind_param("ii", $student_id, $comp_id);
$attempt_stmt->execute();
if ($attempt_stmt->get_result()->num_rows > 0) {
    die("You have already completed this competition.");
}

$session_time_key = "start_time_" . $comp_id;
if (!isset($_SESSION[$session_time_key])) {
    $_SESSION[$session_time_key] = time();
}

$q_stmt = $conn->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d FROM questions WHERE competition_id = ?");
$q_stmt->bind_param("i", $comp_id);
$q_stmt->execute();
$questions_result = $q_stmt->get_result();

$questions = [];
while ($q = $questions_result->fetch_assoc()) {
    $questions[] = $q;
}
$total_questions = count($questions);

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
    <title><?php echo htmlspecialchars($competition['name']); ?> - Exam</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #F3F4F6; -webkit-user-select: none; user-select: none; }
        
        /* Header */
        .header { background: #1F2937; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header-info h2 { margin: 0; font-size: 1.25rem; }
        .header-info span { font-size: 0.8rem; color: #9CA3AF; }
        .timer { font-size: 1.5rem; font-weight: bold; font-family: monospace; background: #DC2626; padding: 0.5rem 1rem; border-radius: 4px; }
        
        /* Question Number Circles Panel */
        .nav-panel { background: white; padding: 1rem 2rem; border-bottom: 1px solid #E5E7EB; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; justify-content: center; }
        .q-circle { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.875rem; cursor: pointer; border: 2px solid #D1D5DB; background: white; color: #374151; transition: all 0.2s; }
        .q-circle:hover { transform: scale(1.1); box-shadow: 0 2px 4px rgba(0,0,0,0.15); }
        .q-circle.active { border-color: #4F46E5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.3); }
        .q-circle.answered { background: #10B981; color: white; border-color: #059669; }
        .q-circle.skipped { background: #F59E0B; color: white; border-color: #D97706; }
        .q-circle.unanswered { background: #EF4444; color: white; border-color: #DC2626; }
        
        /* Legend */
        .legend { display: flex; gap: 1.5rem; justify-content: center; padding: 0.75rem; background: #F9FAFB; border-bottom: 1px solid #E5E7EB; font-size: 0.8rem; color: #6B7280; }
        .legend-item { display: flex; align-items: center; gap: 0.35rem; }
        .legend-dot { width: 14px; height: 14px; border-radius: 50%; display: inline-block; }
        .legend-dot.green { background: #10B981; }
        .legend-dot.yellow { background: #F59E0B; }
        .legend-dot.red { background: #EF4444; }
        .legend-dot.gray { background: white; border: 2px solid #D1D5DB; }

        /* Question Area */
        .question-container { max-width: 700px; margin: 2rem auto; padding: 0 1rem; }
        .question-card { background: white; padding: 2.5rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.07); min-height: 350px; display: flex; flex-direction: column; }
        .q-number { color: #6B7280; font-size: 0.875rem; margin-bottom: 0.5rem; }
        .question-text { font-size: 1.2rem; font-weight: 600; color: #1F2937; margin-bottom: 2rem; line-height: 1.6; }
        
        .options-grid { display: grid; gap: 0.75rem; flex: 1; }
        .option-label { display: flex; align-items: center; background: #F9FAFB; border: 2px solid #E5E7EB; padding: 1rem 1.25rem; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
        .option-label:hover { border-color: #A78BFA; background: #F5F3FF; }
        .option-label input[type="radio"] { display: none; }
        .option-label.selected { border-color: #4F46E5; background: #EEF2FF; }
        .option-letter { width: 32px; height: 32px; border-radius: 50%; background: #E5E7EB; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.875rem; margin-right: 1rem; flex-shrink: 0; }
        .option-label.selected .option-letter { background: #4F46E5; color: white; }
        .option-text { font-size: 1rem; color: #374151; }

        /* Navigation Buttons */
        .nav-buttons { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; max-width: 700px; margin-left: auto; margin-right: auto; padding: 0 1rem; }
        .btn-nav { padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 1rem; transition: all 0.2s; }
        .btn-prev { background: #F3F4F6; color: #374151; border: 2px solid #D1D5DB; }
        .btn-prev:hover { background: #E5E7EB; }
        .btn-next { background: #4F46E5; color: white; }
        .btn-next:hover { background: #4338CA; }
        .btn-submit { background: #10B981; color: white; padding: 0.75rem 2rem; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; font-size: 1rem; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); }
        .btn-submit:hover { background: #059669; }
        .btn-nav:disabled { opacity: 0.4; cursor: not-allowed; }
        .q-counter { color: #6B7280; font-weight: 500; }

        /* Violation Overlay */
        #overlay-warning { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); color: white; z-index: 1000; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 2rem; }
        #overlay-warning h1 { color: #EF4444; font-size: 3rem; margin-bottom: 1rem; }
    </style>
</head>
<body oncontextmenu="return false;">

    <div id="overlay-warning">
        <h1>VIOLATION DETECTED</h1>
        <p style="font-size:1.5rem;">Unpermitted action detected. You must use only mouse/touch clicks.</p>
        <p>Your exam is being automatically submitted...</p>
    </div>

    <!-- Header -->
    <div class="header">
        <div class="header-info">
            <h2><?php echo htmlspecialchars($competition['name']); ?></h2>
            <span>Candidate: <?php echo htmlspecialchars($_SESSION['student_name']); ?></span>
        </div>
        <div class="timer" id="timerDisplay">--:--:--</div>
    </div>

    <!-- Question Number Circles -->
    <div class="nav-panel" id="navPanel">
        <?php for($i = 1; $i <= $total_questions; $i++): ?>
            <div class="q-circle <?php echo ($i == 1) ? 'active' : ''; ?>" id="circle_<?php echo $i; ?>" onclick="goToQuestion(<?php echo $i; ?>)">
                <?php echo $i; ?>
            </div>
        <?php endfor; ?>
    </div>
    
    <!-- Legend -->
    <div class="legend">
        <div class="legend-item"><span class="legend-dot green"></span> Answered</div>
        <div class="legend-item"><span class="legend-dot yellow"></span> Skipped</div>
        <div class="legend-item"><span class="legend-dot red"></span> Not Visited</div>
        <div class="legend-item"><span class="legend-dot gray"></span> Current</div>
    </div>

    <!-- Question Card -->
    <div class="question-container">
        <form id="quizForm" method="POST" action="submit_quiz.php">
            <input type="hidden" name="comp_id" value="<?php echo $comp_id; ?>">
            <input type="hidden" name="violation" id="violationInput" value="0">

            <?php foreach($questions as $index => $q): $qnum = $index + 1; ?>
            <div class="question-card" id="question_<?php echo $qnum; ?>" style="<?php echo ($qnum > 1) ? 'display:none;' : ''; ?>">
                <div class="q-number">Question <?php echo $qnum; ?> of <?php echo $total_questions; ?></div>
                <div class="question-text"><?php echo nl2br(htmlspecialchars($q['question_text'])); ?></div>
                <div class="options-grid">
                    <?php 
                    $options = ['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']];
                    foreach($options as $letter => $text): 
                    ?>
                    <label class="option-label" id="opt_<?php echo $q['id']; ?>_<?php echo $letter; ?>" onclick="selectOption(<?php echo $q['id']; ?>, '<?php echo $letter; ?>', <?php echo $qnum; ?>)">
                        <input type="radio" name="q_<?php echo $q['id']; ?>" value="<?php echo $letter; ?>">
                        <div class="option-letter"><?php echo $letter; ?></div>
                        <span class="option-text"><?php echo htmlspecialchars($text); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </form>
    </div>

    <!-- Navigation -->
    <div class="nav-buttons">
        <button class="btn-nav btn-prev" id="btnPrev" onclick="navigate(-1)" disabled>&larr; Previous</button>
        <span class="q-counter" id="qCounter">1 / <?php echo $total_questions; ?></span>
        <button class="btn-nav btn-next" id="btnNext" onclick="navigate(1)">Next &rarr;</button>
    </div>
    <div style="text-align:center; margin: 1.5rem 0 3rem;">
        <button class="btn-submit" onclick="confirmSubmit()">Submit Examination</button>
    </div>

    <script>
        const totalQ = <?php echo $total_questions; ?>;
        let currentQ = 1;
        let visited = new Set([1]); // Track visited questions
        let answered = new Set(); // Track answered questions

        const form = document.getElementById('quizForm');

        function goToQuestion(num) {
            // Hide current
            document.getElementById('question_' + currentQ).style.display = 'none';
            document.getElementById('circle_' + currentQ).classList.remove('active');
            
            // Mark previous question as skipped if visited but not answered
            updateCircleColor(currentQ);

            // Show target
            currentQ = num;
            visited.add(num);
            document.getElementById('question_' + currentQ).style.display = 'flex';
            document.getElementById('circle_' + currentQ).classList.add('active');
            
            // Update nav buttons
            document.getElementById('btnPrev').disabled = (currentQ === 1);
            document.getElementById('btnNext').style.display = (currentQ === totalQ) ? 'none' : 'inline-block';
            document.getElementById('qCounter').textContent = currentQ + ' / ' + totalQ;
            
            // Update all circle colors
            updateAllCircles();
        }

        function navigate(dir) {
            let next = currentQ + dir;
            if (next >= 1 && next <= totalQ) {
                goToQuestion(next);
            }
        }

        function selectOption(qId, letter, qNum) {
            // Mark as answered
            answered.add(qNum);
            
            // Select the radio
            const radio = document.querySelector('input[name="q_' + qId + '"][value="' + letter + '"]');
            radio.checked = true;
            
            // Visual selection: clear all, highlight selected
            const labels = document.querySelectorAll('label[id^="opt_' + qId + '_"]');
            labels.forEach(l => l.classList.remove('selected'));
            document.getElementById('opt_' + qId + '_' + letter).classList.add('selected');
            
            // Update circle color
            updateCircleColor(qNum);
        }

        function updateCircleColor(qNum) {
            const circle = document.getElementById('circle_' + qNum);
            circle.classList.remove('answered', 'skipped', 'unanswered');
            
            if (qNum === currentQ) return; // Current question keeps the active style only
            
            if (answered.has(qNum)) {
                circle.classList.add('answered'); // Green
            } else if (visited.has(qNum)) {
                circle.classList.add('skipped'); // Yellow
            } else {
                circle.classList.add('unanswered'); // Red
            }
        }

        function updateAllCircles() {
            for (let i = 1; i <= totalQ; i++) {
                const circle = document.getElementById('circle_' + i);
                circle.classList.remove('answered', 'skipped', 'unanswered');
                
                if (i === currentQ) continue; // current = active border only
                
                if (answered.has(i)) {
                    circle.classList.add('answered');
                } else if (visited.has(i)) {
                    circle.classList.add('skipped');
                } else {
                    circle.classList.add('unanswered');
                }
            }
        }

        // Initially mark unvisited questions as red
        updateAllCircles();

        // --- TIMER ---
        let timeRemaining = <?php echo $time_remaining; ?>;
        const timerDisplay = document.getElementById('timerDisplay');

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

        // --- ANTI-CHEAT ---
        let submitted = false;
        
        function forceSubmit(reason) {
            if(submitted) return;
            submitted = true;
            document.getElementById('overlay-warning').style.display = 'flex';
            document.getElementById('violationInput').value = "1";
            setTimeout(() => { form.submit(); }, 2000);
        }

        function confirmSubmit() {
            if(confirm('Are you sure you want to submit? You cannot undo this.')) {
                submitted = true;
                form.submit();
            }
        }

        document.addEventListener('visibilitychange', () => { if (document.hidden) forceSubmit("Tab switch"); });
        window.addEventListener('blur', () => { forceSubmit("Window lost focus"); });
        document.addEventListener('keydown', (e) => { e.preventDefault(); forceSubmit("Keyboard detected"); });
    </script>
</body>
</html>
