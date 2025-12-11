<?php
require_once '../includes/config.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header('Location: ../index.php');
    exit();
}

$election_id = isset($_GET['election']) ? intval($_GET['election']) : null;

// Get active election if none specified
if (!$election_id) {
    $stmt = $pdo->query("SELECT * FROM elections WHERE status = 'active' LIMIT 1");
    $active_election = $stmt->fetch();
    if ($active_election) {
        $election_id = $active_election['election_id'];
    }
}

// Get election details
if ($election_id) {
    $stmt = $pdo->prepare("SELECT * FROM elections WHERE election_id = ?");
    $stmt->execute([$election_id]);
    $election = $stmt->fetch();
    
    // Get results by position
    $stmt = $pdo->prepare("
        SELECT 
            p.position_id,
            p.position_name,
            c.candidate_id,
            CONCAT(u.first_name, ' ', u.last_name) as candidate_name,
            u.profile_pic,
            d.dept_name,
            COUNT(v.vote_id) as vote_count
        FROM positions p
        LEFT JOIN candidates c ON p.position_id = c.position_id AND c.is_approved = 1
        LEFT JOIN users u ON c.user_id = u.user_id
        LEFT JOIN departments d ON u.dept_id = d.dept_id
        LEFT JOIN votes v ON c.candidate_id = v.candidate_id
        WHERE p.election_id = ?
        GROUP BY p.position_id, c.candidate_id
        ORDER BY p.position_id, vote_count DESC
    ");
    $stmt->execute([$election_id]);
    $results = $stmt->fetchAll();
    
    // Group by position
    $positions = [];
    foreach ($results as $result) {
        $positions[$result['position_name']][] = $result;
    }
    
    // Get voter statistics by department
    $stmt = $pdo->prepare("
        SELECT 
            d.dept_name,
            d.dept_code,
            COUNT(DISTINCT u.user_id) as total_voters,
            COUNT(DISTINCT v.voter_id) as voted_count
        FROM departments d
        LEFT JOIN users u ON d.dept_id = u.dept_id AND u.user_role IN ('voter', 'candidate')
        LEFT JOIN votes v ON v.election_id = ? AND v.voter_id = u.user_id
        GROUP BY d.dept_id
    ");
    $stmt->execute([$election_id]);
    $department_stats = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="user-info">
                <span><?php echo $_SESSION['full_name']; ?></span>
                <a href="index.php" class="btn">Dashboard</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>Election Results</h1>
        
        <?php if (!$election): ?>
            <div class="card">
                <h2>No Election Selected</h2>
                <p>Please select an election to view results.</p>
                <a href="manage_elections.php" class="btn">View Elections</a>
            </div>
        <?php else: ?>
            <div class="card">
                <h2><?php echo $election['election_title']; ?></h2>
                <p><?php echo $election['description']; ?></p>
                <p><strong>Status:</strong> 
                    <span style="color: <?php echo $election['status'] == 'active' ? '#48bb78' : '#718096'; ?>">
                        <?php echo ucfirst($election['status']); ?>
                    </span>
                </p>
                <p><strong>Period:</strong> 
                    <?php echo date('M d, Y H:i', strtotime($election['start_date'])); ?> 
                    to 
                    <?php echo date('M d, Y H:i', strtotime($election['end_date'])); ?>
                </p>
            </div>
            
            <!-- Department Statistics -->
            <div class="card" style="margin-top: 20px;">
                <h2>Voter Turnout by Department</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Total Voters</th>
                                <th>Voted</th>
                                <th>Turnout</th>
                                <th>Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($department_stats as $stat): 
                                $percentage = $stat['total_voters'] > 0 ? 
                                    round(($stat['voted_count'] / $stat['total_voters']) * 100, 2) : 0;
                            ?>
                            <tr>
                                <td><?php echo $stat['dept_name']; ?> (<?php echo $stat['dept_code']; ?>)</td>
                                <td><?php echo $stat['total_voters']; ?></td>
                                <td><?php echo $stat['voted_count']; ?></td>
                                <td>
                                    <div style="background: #e2e8f0; height: 20px; border-radius: 10px; overflow: hidden;">
                                        <div style="background: #48bb78; height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                    </div>
                                </td>
                                <td><?php echo $percentage; ?>%</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Results by Position -->
            <?php foreach ($positions as $position_name => $candidates): ?>
            <div class="card" style="margin-top: 20px;">
                <h2><?php echo $position_name; ?></h2>
                
                <?php if (empty($candidates[0]['candidate_id'])): ?>
                    <p>No candidates for this position.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Candidate</th>
                                    <th>Department</th>
                                    <th>Votes</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_votes = 0;
                                foreach ($candidates as $candidate) {
                                    $total_votes += $candidate['vote_count'];
                                }
                                
                                $rank = 1;
                                foreach ($candidates as $candidate): 
                                    $percentage = $total_votes > 0 ? 
                                        round(($candidate['vote_count'] / $total_votes) * 100, 2) : 0;
                                ?>
                                <tr>
                                    <td><?php echo $rank++; ?></td>
                                    <td>
                                        <?php if ($candidate['profile_pic']): ?>
                                            <img src="../<?php echo $candidate['profile_pic']; ?>" 
                                                 alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; vertical-align: middle; margin-right: 10px;">
                                        <?php endif; ?>
                                        <?php echo $candidate['candidate_name']; ?>
                                    </td>
                                    <td><?php echo $candidate['dept_name']; ?></td>
                                    <td><?php echo $candidate['vote_count']; ?></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div style="background: #e2e8f0; height: 20px; width: 200px; border-radius: 10px; overflow: hidden;">
                                                <div style="background: #667eea; height: 100%; width: <?php echo $percentage; ?>%;"></div>
                                            </div>
                                            <span><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="index.php" class="btn">Back to Dashboard</a>
            <a href="manage_elections.php" class="btn">View All Elections</a>
        </div>
    </div>
</body>
</html>