<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../config.php';

// Handle XML Download
if (isset($_GET['download']) && is_numeric($_GET['download'])) {
    $comp_id = $_GET['download'];
    
    // Get Competition Details
    $comp_stmt = $conn->prepare("SELECT name FROM competitions WHERE id = ?");
    $comp_stmt->bind_param("i", $comp_id);
    $comp_stmt->execute();
    $comp_res = $comp_stmt->get_result();
    if ($comp_res->num_rows === 0) die("Competition not found.");
    $comp_name = $comp_res->fetch_assoc()['name'];

    // Get Results
    $sql = "SELECT s.name, s.reg_no, s.department, s.year, r.score, r.time_taken_seconds 
            FROM results r 
            JOIN students s ON r.student_id = s.id 
            WHERE r.competition_id = ? 
            ORDER BY r.score DESC, r.time_taken_seconds ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $comp_id);
    $stmt->execute();
    $results = $stmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_comp_' . $comp_id . '.csv"');
    $output = fopen('php://output', 'w');

    // Add metadata/info at top
    fputcsv($output, array('Competition Name', $comp_name));
    fputcsv($output, array('Generated At', date('Y-m-d H:i:s')));
    fputcsv($output, array('')); // Empty spacer row

    // Table Headers
    fputcsv($output, array('Rank', 'Name', 'Register Number', 'Department', 'Year', 'Marks', 'Time Taken (Seconds)'));

    $rank = 1;
    $prev_score = -1;
    $prev_time = -1;
    $actual_position = 1;

    while ($row = $results->fetch_assoc()) {
        if ($prev_score !== -1) {
            if ($row['score'] != $prev_score || $row['time_taken_seconds'] != $prev_time) {
                $rank = $actual_position;
            }
        }
        
        // Output row directly to csv
        fputcsv($output, array(
            $rank,
            $row['name'],
            $row['reg_no'],
            $row['department'],
            $row['year'],
            $row['score'],
            $row['time_taken_seconds']
        ));

        $prev_score = $row['score'];
        $prev_time = $row['time_taken_seconds'];
        $actual_position++;
    }

    fclose($output);
    exit();
}

$competitions = $conn->query("SELECT * FROM competitions ORDER BY start_time DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Reports - Admin</title>
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
        .btn-action { padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; color: white; font-weight: bold; background-color: #10B981; }
        .btn-action:hover { background-color: #059669; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="manage_competitions.php">Competitions</a>
        <a href="view_candidates.php">Candidates</a>
        <a href="generate_report.php" class="active">Results & Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="content">
        <h1>Competition Results Report</h1>
        <p>Download the ranked Excel (CSV) report for any competition. The system automatically sorts candidates based on the highest marks, followed by the lowest time taken.</p>
        
        <div class="container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Competition Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $competitions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo ucfirst($row['status']); ?></td>
                        <td>
                            <a href="generate_report.php?download=<?php echo $row['id']; ?>" class="btn-action">Download Excel (CSV)</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($competitions->num_rows == 0) echo "<tr><td colspan='4' style='text-align:center;'>No competitions available.</td></tr>"; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
