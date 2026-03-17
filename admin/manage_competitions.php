<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config.php';

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize_input($_POST['name']);
    $start_time = $_POST['start_time']; // Format: YYYY-MM-DDTHH:MM
    $end_time = $_POST['end_time'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("INSERT INTO competitions (name, start_time, end_time, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $start_time, $end_time, $status);
    
    if ($stmt->execute()) {
        $message = "Competition created successfully!";
    } else {
        $message = "Error creating competition: " . $stmt->error;
    }
    $stmt->close();
}

$competitions = $conn->query("SELECT * FROM competitions ORDER BY start_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Competitions - Admin</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; margin: 0; padding: 0; display: flex; }
        .sidebar { width: 250px; background-color: #1F2937; color: white; min-height: 100vh; padding: 1rem 0; }
        .sidebar h2 { text-align: center; margin-bottom: 2rem; color: #E5E7EB; border-bottom: 1px solid #374151; padding-bottom: 1rem; }
        .sidebar a { display: block; padding: 1rem 1.5rem; color: #D1D5DB; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background-color: #374151; color: white; }
        .content { flex: 1; padding: 2rem; }
        .form-container, .table-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; color: #374151; font-weight: 500; }
        input[type="text"], input[type="datetime-local"], select { width: 100%; padding: 0.75rem; border: 1px solid #D1D5DB; border-radius: 4px; box-sizing: border-box; }
        .btn { padding: 0.75rem 1.5rem; background-color: #4F46E5; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600; }
        .btn:hover { background-color: #4338CA; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; border-bottom: 1px solid #E5E7EB; text-align: left; }
        .msg { padding: 1rem; background: #D1FAE5; color: #065F46; border-radius: 4px; margin-bottom: 1rem; }
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
        <h1>Manage Competitions</h1>
        
        <?php if($message) echo "<div class='msg'>$message</div>"; ?>

        <div class="form-container">
            <h2>Create New Competition</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Competition Name</label>
                    <input type="text" name="name" required placeholder="e.g., Spring Coding Quiz 2026">
                </div>
                <div style="display:flex; gap:1rem;">
                    <div class="form-group" style="flex:1;">
                        <label>Start Time</label>
                        <input type="datetime-local" name="start_time" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>End Time</label>
                        <input type="datetime-local" name="end_time" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Status</label>
                        <select name="status">
                            <option value="upcoming">Upcoming</option>
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn">Create Competition</button>
            </form>
        </div>

        <div class="table-container">
            <h2>All Competitions</h2>
            <table>
                <tr>
                    <th>ID</th><th>Name</th><th>Start</th><th>End</th><th>Status</th><th>Manage</th>
                </tr>
                <?php while($row = $competitions->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['start_time'])); ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['end_time'])); ?></td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                    <td>
                        <a href="manage_questions.php?id=<?php echo $row['id']; ?>" style="color:#4F46E5; text-decoration:none; font-weight:bold;">Questions</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>
</body>
</html>
