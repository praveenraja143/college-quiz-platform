<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid Competition ID.");
}

$comp_id = $_GET['id'];
$message = '';

// Check if competition exists
$comp_check = $conn->prepare("SELECT name FROM competitions WHERE id = ?");
$comp_check->bind_param("i", $comp_id);
$comp_check->execute();
$comp_result = $comp_check->get_result();
if ($comp_result->num_rows === 0) {
    die("Competition not found.");
}
$comp_name = $comp_result->fetch_assoc()['name'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question_text = sanitize_input($_POST['question_text']);
    $option_a = sanitize_input($_POST['option_a']);
    $option_b = sanitize_input($_POST['option_b']);
    $option_c = sanitize_input($_POST['option_c']);
    $option_d = sanitize_input($_POST['option_d']);
    $correct = $_POST['correct_option'];

    $stmt = $conn->prepare("INSERT INTO questions (competition_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $comp_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct);
    
    if ($stmt->execute()) {
        $message = "Question added successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
}

$questions = $conn->query("SELECT * FROM questions WHERE competition_id = $comp_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Questions - <?php echo htmlspecialchars($comp_name); ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; margin: 0; padding: 0; display: flex; }
        .sidebar { width: 250px; background-color: #1F2937; color: white; min-height: 100vh; padding: 1rem 0; }
        .sidebar h2 { text-align: center; margin-bottom: 2rem; color: #E5E7EB; border-bottom: 1px solid #374151; padding-bottom: 1rem; }
        .sidebar a { display: block; padding: 1rem 1.5rem; color: #D1D5DB; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background-color: #374151; color: white; }
        .content { flex: 1; padding: 2rem; }
        .form-container, .list-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input[type="text"], textarea, select { width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.75rem 1.5rem; background-color: #4F46E5; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        .btn:hover { background-color: #4338CA; }
        .q-card { border: 1px solid #E5E7EB; padding: 1rem; margin-bottom: 1rem; border-radius: 4px; background: #F9FAFB; }
        .correct { color: #059669; font-weight: bold; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_competitions.php" class="active">Competitions</a>
        <a href="view_candidates.php">Candidates</a>
        <a href="generate_report.php">Results & Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <h1>Questions for: <?php echo htmlspecialchars($comp_name); ?></h1>
        <a href="manage_competitions.php" style="color:#4F46E5; text-decoration:none; display:inline-block; margin-bottom:1rem;">&larr; Back to Competitions</a>
        
        <?php if($message) echo "<div style='padding:1rem; background:#D1FAE5; color:#065F46; border-radius:4px; margin-bottom:1rem;'>$message</div>"; ?>

        <div class="form-container">
            <h2>Add New Question</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="question_text" rows="3" required></textarea>
                </div>
                <div style="display:flex; gap:1rem;">
                    <div class="form-group" style="flex:1;">
                        <label>Option A</label>
                        <input type="text" name="option_a" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Option B</label>
                        <input type="text" name="option_b" required>
                    </div>
                </div>
                <div style="display:flex; gap:1rem;">
                    <div class="form-group" style="flex:1;">
                        <label>Option C</label>
                        <input type="text" name="option_c" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Option D</label>
                        <input type="text" name="option_d" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Correct Option</label>
                    <select name="correct_option" required style="width:200px;">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                    </select>
                </div>
                <button type="submit" class="btn">Add Question</button>
            </form>
        </div>

        <div class="list-container">
            <h2>Added Questions (<?php echo $questions->num_rows; ?>)</h2>
            <?php while($q = $questions->fetch_assoc()): ?>
                <div class="q-card">
                    <p><strong>Q: <?php echo nl2br(htmlspecialchars($q['question_text'])); ?></strong></p>
                    <ul style="list-style-type:none; padding-left:0; margin-bottom:0.5rem;">
                        <li>A) <?php echo htmlspecialchars($q['option_a']); ?> <?php if($q['correct_option'] == 'A') echo "<span class='correct'>(Correct)</span>"; ?></li>
                        <li>B) <?php echo htmlspecialchars($q['option_b']); ?> <?php if($q['correct_option'] == 'B') echo "<span class='correct'>(Correct)</span>"; ?></li>
                        <li>C) <?php echo htmlspecialchars($q['option_c']); ?> <?php if($q['correct_option'] == 'C') echo "<span class='correct'>(Correct)</span>"; ?></li>
                        <li>D) <?php echo htmlspecialchars($q['option_d']); ?> <?php if($q['correct_option'] == 'D') echo "<span class='correct'>(Correct)</span>"; ?></li>
                    </ul>
                </div>
            <?php endwhile; ?>
            <?php if($questions->num_rows == 0) echo "<p>No questions added yet.</p>"; ?>
        </div>
    </div>
</body>
</html>
