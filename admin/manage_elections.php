<?php
require_once '../includes/config.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header('Location: ../index.php');
    exit();
}

// Handle election actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] == 'start') {
        // End any currently active election first
        $pdo->query("UPDATE elections SET status = 'ended' WHERE status = 'active'");
        // Start the new election
        $stmt = $pdo->prepare("UPDATE elections SET status = 'active' WHERE election_id = ?");
        $stmt->execute([$id]);
        logActivity('Start Election', 'Started election ID: ' . $id);
    } elseif ($_GET['action'] == 'end') {
        $stmt = $pdo->prepare("UPDATE elections SET status = 'ended' WHERE election_id = ?");
        $stmt->execute([$id]);
        logActivity('End Election', 'Ended election ID: ' . $id);
    } elseif ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM elections WHERE election_id = ?");
        $stmt->execute([$id]);
        logActivity('Delete Election', 'Deleted election ID: ' . $id);
    }
    header('Location: manage_elections.php');
    exit();
}

// Get all elections
$stmt = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC");
$elections = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elections - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="user-info">
                <span><?php echo $_SESSION['full_name']; ?></span>
                <a href="index.php" class="btn">Dashboard</a>
                <a href="create_election.php" class="btn btn-primary">Create Election</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Manage Elections</h1>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Candidates</th>
                        <th>Votes</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($elections as $election): 
                        // Get candidate count
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM candidates WHERE election_id = ?");
                        $stmt->execute([$election['election_id']]);
                        $candidate_count = $stmt->fetch()['count'];
                        
                        // Get vote count
                        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM votes WHERE election_id = ?");
                        $stmt->execute([$election['election_id']]);
                        $vote_count = $stmt->fetch()['count'];
                    ?>
                    <tr>
                        <td><?php echo $election['election_id']; ?></td>
                        <td><?php echo $election['election_title']; ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?></td>
                        <td>
                            <?php 
                            $status_color = [
                                'upcoming' => '#ed8936',
                                'active' => '#48bb78',
                                'ended' => '#718096'
                            ];
                            ?>
                            <span style="color: <?php echo $status_color[$election['status']]; ?>; font-weight: bold;">
                                <?php echo ucfirst($election['status']); ?>
                            </span>
                        </td>
                        <td><?php echo $candidate_count; ?></td>
                        <td><?php echo $vote_count; ?></td>
                        <td><?php echo date('M d, Y', strtotime($election['created_at'])); ?></td>
                        <td>
                            <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                <a href="view_results.php?election=<?php echo $election['election_id']; ?>" 
                                   class="btn" style="padding: 5px 10px; font-size: 14px;">View</a>
                                
                                <?php if ($election['status'] == 'upcoming'): ?>
                                    <a href="?action=start&id=<?php echo $election['election_id']; ?>" 
                                       class="btn btn-success" style="padding: 5px 10px; font-size: 14px;"
                                       onclick="return confirm('Start this election?')">Start</a>
                                <?php elseif ($election['status'] == 'active'): ?>
                                    <a href="?action=end&id=<?php echo $election['election_id']; ?>" 
                                       class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;"
                                       onclick="return confirm('End this election?')">End</a>
                                <?php endif; ?>
                                
                                <?php if ($election['status'] != 'active'): ?>
                                    <a href="?action=delete&id=<?php echo $election['election_id']; ?>" 
                                       class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;"
                                       onclick="return confirm('Delete this election?')">Delete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="create_election.php" class="btn btn-primary">Create New Election</a>
            <a href="index.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>