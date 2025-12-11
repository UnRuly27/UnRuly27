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

if (!$active_election) {
    die('<div class="container"><div class="card"><h2>No Active Election</h2><p>There is no active election at the moment.</p><a href="index.php" class="btn">Go Back</a></div></div>');
}

// Check if already voted
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM votes WHERE voter_id = ? AND election_id = ?");
$stmt->execute([$_SESSION['user_id'], $active_election['election_id']]);
$has_voted = $stmt->fetch()['count'] > 0;

if ($has_voted) {
    die('<div class="container"><div class="card"><h2>Already Voted</h2><p>You have already cast your vote in this election.</p><a href="index.php" class="btn">Go Back</a></div></div>');
}

// Get positions and candidates
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.candidate_id,
           c.user_id as candidate_user_id,
           u.first_name,
           u.last_name,
           u.profile_pic,
           d.dept_name
    FROM positions p
    LEFT JOIN candidates c ON p.position_id = c.position_id AND c.is_approved = 1
    LEFT JOIN users u ON c.user_id = u.user_id
    LEFT JOIN departments d ON u.dept_id = d.dept_id
    WHERE p.election_id = ?
    ORDER BY p.position_id
");
$stmt->execute([$active_election['election_id']]);
$positions_data = $stmt->fetchAll();

// Group by position
$positions = [];
foreach ($positions_data as $data) {
    $positions[$data['position_name']][] = $data;
}

// Handle voting
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $votes = $_POST['votes'] ?? [];
    
    if (count($votes) != count($positions)) {
        $error = 'Please vote for all positions';
    } else {
        try {
            $pdo->beginTransaction();
            
            foreach ($votes as $position_id => $candidate_id) {
                // Validate candidate belongs to position
                $stmt = $pdo->prepare("
                    SELECT c.candidate_id 
                    FROM candidates c 
                    WHERE c.candidate_id = ? 
                    AND c.position_id = ? 
                    AND c.is_approved = 1
                ");
                $stmt->execute([$candidate_id, $position_id]);
                
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO votes (voter_id, candidate_id, position_id, election_id) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $_SESSION['user_id'],
                        $candidate_id,
                        $position_id,
                        $active_election['election_id']
                    ]);
                }
            }
            
            $pdo->commit();
            $success = 'Vote cast successfully! Thank you for voting.';
            logActivity('Cast Vote', 'Voted in election: ' . $active_election['election_title']);
            
            // Redirect after 3 seconds
            header('refresh:3;url=index.php');
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to cast vote: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Vote - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="user-info">
                <span><?php echo $_SESSION['full_name']; ?> (Voter)</span>
                <a href="index.php" class="btn">Dashboard</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <h1>Cast Your Vote</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2><?php echo $active_election['election_title']; ?></h2>
                <p><?php echo $active_election['description']; ?></p>
                
                <?php if (!$success): ?>
                <form method="POST" action="">
                    <?php foreach ($positions as $position_name => $candidates): ?>
                    <div style="margin-top: 30px; padding: 20px; border: 2px solid #e2e8f0; border-radius: 10px;">
                        <h3 style="color: #667eea; margin-bottom: 20px;"><?php echo $position_name; ?></h3>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px;">
                            <?php foreach ($candidates as $candidate): 
                                if (!$candidate['candidate_id']) continue;
                            ?>
                            <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; cursor: pointer;"
                                 onclick="document.getElementById('candidate_<?php echo $candidate['candidate_id']; ?>').checked = true;">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <input type="radio" 
                                           id="candidate_<?php echo $candidate['candidate_id']; ?>"
                                           name="votes[<?php echo $candidate['position_id']; ?>]" 
                                           value="<?php echo $candidate['candidate_id']; ?>"
                                           required>
                                    
                                    <?php if ($candidate['profile_pic']): ?>
                                    <img src="../<?php echo $candidate['profile_pic']; ?>" 
                                         alt="Profile" style="width: 60px; height: 60px; border-radius: 50%;">
                                    <?php else: ?>
                                    <div style="width: 60px; height: 60px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center;">
                                        <span style="font-size: 24px;">👤</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <h4 style="margin: 0;"><?php echo $candidate['first_name'] . ' ' . $candidate['last_name']; ?></h4>
                                        <p style="margin: 5px 0 0 0; color: #718096;"><?php echo $candidate['dept_name']; ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f7fafc; border-radius: 5px;">
                        <p style="color: #666;">
                            <strong>Important:</strong> Once you submit your vote, you cannot change it. 
                            Please review your selections carefully.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Submit Vote</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>