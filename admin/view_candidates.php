<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config.php';

$students = $conn->query("SELECT * FROM students ORDER BY registered_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Candidates - Admin</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #F3F4F6; margin: 0; padding: 0; display: flex; }
        .sidebar { width: 250px; background-color: #1F2937; color: white; min-height: 100vh; padding: 1rem 0; }
        .sidebar h2 { text-align: center; margin-bottom: 2rem; color: #E5E7EB; border-bottom: 1px solid #374151; padding-bottom: 1rem; }
        .sidebar a { display: block; padding: 1rem 1.5rem; color: #D1D5DB; text-decoration: none; }
        .sidebar a:hover, .sidebar a.active { background-color: #374151; color: white; }
        .content { flex: 1; padding: 2rem; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #E5E7EB; }
        th { background-color: #F9FAFB; font-weight: 600; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_competitions.php">Competitions</a>
        <a href="view_candidates.php" class="active">Candidates</a>
        <a href="generate_report.php">Results & Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <h1>Registered Candidates</h1>
        <div class="container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th><th>Reg No</th><th>Name</th><th>Department</th><th>Year</th><th>Email</th><th>Reg. Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['reg_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td><?php echo htmlspecialchars($row['year']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo date('Y-m-d H:i', strtotime($row['registered_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($students->num_rows == 0) echo "<tr><td colspan='7' style='text-align:center'>No candidates registered yet.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
