<?php
require_once '../includes/config.php';

// Only voters can access
if (!isLoggedIn() || $_SESSION['user_role'] != 'voter') {
    header('Location: ../index.php');
    exit();
}

// Get active election
$stmt = $pdo->query("SELECT * FROM elections WHERE status = 'active' LIMIT 1");
$active_election = $stmt->fetch();

// Check if voter has already voted
$has_voted = false;
if ($active_election) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM votes WHERE voter_id = ? AND election_id = ?");
    $stmt->execute([$_SESSION['user_id'], $active_election['election_id']]);
    $has_voted = $stmt->fetch()['count'] > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Voter)</span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Voter Dashboard</h1>
        
        <?php if ($active_election): ?>
            <div class="card">
                <h2>Current Election: <?php echo $active_election['election_title']; ?></h2>
                <p><?php echo $active_election['description']; ?></p>
                
                <div style="display: flex; gap: 20px; margin-top: 20px;">
                    <div style="flex: 1;">
                        <h3>Election Timeline</h3>
                        <p><strong>Starts:</strong> <?php echo date('M d, Y H:i', strtotime($active_election['start_date'])); ?></p>
                        <p><strong>Ends:</strong> <?php echo date('M d, Y H:i', strtotime($active_election['end_date'])); ?></p>
                    </div>
                    
                    <div style="flex: 1;">
                        <h3>Your Voting Status</h3>
                        <?php if ($has_voted): ?>
                            <p style="color: #48bb78; font-weight: bold;">✓ You have already voted</p>
                            <a href="view_results.php" class="btn btn-success">View Results</a>
                        <?php else: ?>
                            <p style="color: #ed8936; font-weight: bold;">✗ You haven't voted yet</p>
                            <a href="cast_vote.php" class="btn btn-primary">Cast Your Vote Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>No Active Election</h2>
                <p>There is no active election at the moment. Please check back later.</p>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Cast Vote</h3>
                <p>Vote for your preferred candidates</p>
                <a href="cast_vote.php" class="btn">Vote Now</a>
            </div>
            
            <div class="dashboard-card">
                <h3>View Results</h3>
                <p>See live election results</p>
                <a href="view_results.php" class="btn">View Results</a>
            </div>
            
            <div class="dashboard-card">
                <h3>Manage Account</h3>
                <p>Update your profile</p>
                <a href="manage_account.php" class="btn">Manage Account</a>
            </div>
            
            <div class="dashboard-card">
                <h3>Election Info</h3>
                <p>View election details</p>
                <a href="election_info.php" class="btn">View Info</a>
            </div>
        </div>
    </div>
</body>
</html>