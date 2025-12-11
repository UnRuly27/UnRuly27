<?php
require_once '../includes/config.php';

// Only admin and super_admin can access
if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header('Location: ../index.php');
    exit();
}

$user_role = $_SESSION['user_role'];

// Get stats
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_role = 'voter'");
$total_voters = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE user_role = 'candidate'");
$total_candidates = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM elections");
$total_elections = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM votes");
$total_votes = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (<?php echo $user_role; ?>)</span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Admin Dashboard</h1>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Total Voters</h3>
                <div class="count"><?php echo $total_voters; ?></div>
                <a href="manage_voters.php" class="btn" style="margin-top: 15px;">Manage Voters</a>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Candidates</h3>
                <div class="count"><?php echo $total_candidates; ?></div>
                <a href="manage_candidates.php" class="btn" style="margin-top: 15px;">Manage Candidates</a>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Elections</h3>
                <div class="count"><?php echo $total_elections; ?></div>
                <a href="manage_elections.php" class="btn" style="margin-top: 15px;">Manage Elections</a>
            </div>
            
            <div class="dashboard-card">
                <h3>Total Votes</h3>
                <div class="count"><?php echo $total_votes; ?></div>
                <a href="view_results.php" class="btn" style="margin-top: 15px;">View Results</a>
            </div>
        </div>

        <div class="card" style="margin-top: 40px;">
            <h2>Quick Actions</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <?php if ($user_role == 'super_admin'): ?>
                    <a href="create_admin.php" class="btn btn-primary">Create New Admin</a>
                <?php endif; ?>
                <a href="create_election.php" class="btn btn-primary">Create New Election</a>
                <a href="live_results.php" class="btn btn-success">View Live Results</a>
                <a href="manage_voters.php" class="btn">Manage Voters</a>
                <a href="manage_candidates.php" class="btn">Manage Candidates</a>
                <a href="view_logs.php" class="btn">View Activity Logs</a>
            </div>
        </div>
    </div>
</body>
</html>