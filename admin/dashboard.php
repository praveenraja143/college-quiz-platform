<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config.php';

// Fetch competitions
$comp_query = "SELECT * FROM competitions ORDER BY start_time DESC";
$competitions = $conn->query($comp_query);

// Analytics
$student_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$comp_count = $competitions->num_rows;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - JKKMCT</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; margin: 0; padding: 0; display: flex; }
        .sidebar { width: 250px; background-color: #1F2937; color: white; min-height: 100vh; padding: 1rem 0; }
        .sidebar h2 { text-align: center; margin-bottom: 2rem; color: #E5E7EB; border-bottom: 1px solid #374151; padding-bottom: 1rem; }
        .sidebar a { display: block; padding: 1rem 1.5rem; color: #D1D5DB; text-decoration: none; transition: background 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #374151; color: white; }
        .content { flex: 1; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .card-container { display: flex; gap: 1.5rem; margin-bottom: 2rem; }
        .card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); flex: 1; }
        .card h3 { margin: 0 0 0.5rem 0; color: #6B7280; font-size: 0.875rem; text-transform: uppercase; }
        .card div { font-size: 2rem; font-weight: bold; color: #1F2937; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #E5E7EB; }
        th { background-color: #F9FAFB; color: #374151; font-weight: 600; }
        .btn-action { padding: 0.375rem 0.75rem; border-radius: 4px; text-decoration: none; color: white; font-size: 0.875rem; }
        .btn-primary { background-color: #4F46E5; }
        .btn-danger { background-color: #DC2626; }
        .btn-sm { font-size: 0.75rem; padding: 0.25rem 0.5rem; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="manage_competitions.php">Competitions</a>
        <a href="view_candidates.php">Candidates</a>
        <a href="generate_report.php">Results & Reports</a>
        <a href="logout.php">Logout</a>
    </div>
    
    <div class="content">
        <div class="header">
            <h1>Welcome, Admin</h1>
            <a href="manage_competitions.php" class="btn-action btn-primary">+ Create Competition</a>
        </div>
        
        <div class="card-container">
            <div class="card">
                <h3>Total Students</h3>
                <div><?php echo $student_count; ?></div>
            </div>
            <div class="card">
                <h3>Total Competitions</h3>
                <div><?php echo $comp_count; ?></div>
            </div>
        </div>

        <h2>Recent Competitions</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($comp = $competitions->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $comp['id']; ?></td>
                    <td><?php echo htmlspecialchars($comp['name']); ?></td>
                    <td><?php echo $comp['start_time']; ?></td>
                    <td><?php echo $comp['end_time']; ?></td>
                    <td>
                        <span style="font-weight: bold; color: <?php echo $comp['status'] == 'active' ? 'green' : ($comp['status'] == 'completed' ? 'gray' : 'orange'); ?>;">
                            <?php echo ucfirst($comp['status']); ?>
                        </span>
                    </td>
                    <td>
                        <a href="manage_questions.php?id=<?php echo $comp['id']; ?>" class="btn-action btn-primary btn-sm">Questions</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if($competitions->num_rows == 0): ?>
                    <tr><td colspan="6" style="text-align:center;">No competitions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
