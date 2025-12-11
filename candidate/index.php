<?php
require_once '../includes/config.php';

// Only candidates can access
if (!isLoggedIn() || $_SESSION['user_role'] != 'candidate') {
    header('Location: ../index.php');
    exit();
}

// Get candidate info
$stmt = $pdo->prepare("
    SELECT c.*, p.position_name, e.election_title, e.status as election_status
    FROM candidates c
    JOIN positions p ON c.position_id = p.position_id
    JOIN elections e ON c.election_id = e.election_id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$candidate_info = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['full_name']; ?> (Candidate)</span>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Candidate Dashboard</h1>
        
        <?php if ($candidate_info): ?>
            <div class="card">
                <h2>Your Candidacy Information</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                    <div>
                        <h3>Position</h3>
                        <p style="font-size: 24px; color: #667eea;"><?php echo $candidate_info['position_name']; ?></p>
                    </div>
                    
                    <div>
                        <h3>Election</h3>
                        <p><?php echo $candidate_info['election_title']; ?></p>
                        <p>Status: <span style="color: <?php echo $candidate_info['election_status'] == 'active' ? '#48bb78' : '#ed8936'; ?>">
                            <?php echo ucfirst($candidate_info['election_status']); ?>
                        </span></p>
                    </div>
                    
                    <div>
                        <h3>Approval Status</h3>
                        <?php if ($candidate_info['is_approved']): ?>
                            <p style="color: #48bb78; font-weight: bold;">✓ Approved</p>
                        <?php else: ?>
                            <p style="color: #ed8936; font-weight: bold;">⏳ Pending Approval</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($candidate_info['slogan']): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #f7fafc; border-radius: 5px;">
                        <h3>Your Slogan</h3>
                        <p><?php echo $candidate_info['slogan']; ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
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
                <h3>Campaign</h3>
                <p>Update your campaign info</p>
                <a href="campaign.php" class="btn">Campaign</a>
            </div>
            
            <div class="dashboard-card">
                <h3>Statistics</h3>
                <p>View voting statistics</p>
                <a href="statistics.php" class="btn">Statistics</a>
            </div>
        </div>
    </div>
</body>
</html>