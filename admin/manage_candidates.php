<?php
require_once '../includes/config.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header('Location: ../index.php');
    exit();
}

// Handle candidate actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] == 'approve') {
        $stmt = $pdo->prepare("UPDATE candidates SET is_approved = 1, approved_by = ?, approved_at = NOW() WHERE candidate_id = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);
        logActivity('Approve Candidate', 'Approved candidate ID: ' . $id);
    } elseif ($_GET['action'] == 'reject') {
        $stmt = $pdo->prepare("DELETE FROM candidates WHERE candidate_id = ?");
        $stmt->execute([$id]);
        logActivity('Reject Candidate', 'Rejected candidate ID: ' . $id);
    }
    header('Location: manage_candidates.php');
    exit();
}

// Get all candidates
$stmt = $pdo->query("
    SELECT c.*, u.first_name, u.last_name, u.username, u.student_id, d.dept_name, p.position_name, e.election_title
    FROM candidates c
    JOIN users u ON c.user_id = u.user_id
    JOIN departments d ON u.dept_id = d.dept_id
    JOIN positions p ON c.position_id = p.position_id
    JOIN elections e ON c.election_id = e.election_id
    ORDER BY c.created_at DESC
");
$candidates = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - <?php echo SITE_NAME; ?></title>
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

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>Manage Candidates</h1>
    <div>
        <a href="create_candidate.php" class="btn btn-primary">Create New Candidate</a>
        <a href="index.php" class="btn">Dashboard</a>
    </div>
</div>

    <div class="container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1>Manage Candidates</h1>
        <div>
            <a href="create_candidate.php" class="btn btn-primary">Create New Candidate</a>
            <a href="index.php" class="btn">Dashboard</a>
        </div>
    </div>
    
    <div class="table-container">
        <!-- Rest of the table code remains the same -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Candidate</th>
                    <th>Student ID</th>
                    <th>Position</th>
                    <th>Election</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Applied</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidates as $candidate): ?>
                <!-- Rest of the table rows remain the same -->
                    <tr>
                        <td><?php echo $candidate['candidate_id']; ?></td>
                        <td><?php echo $candidate['first_name'] . ' ' . $candidate['last_name']; ?></td>
                        <td><?php echo $candidate['student_id']; ?></td>
                        <td><?php echo $candidate['position_name']; ?></td>
                        <td><?php echo $candidate['election_title']; ?></td>
                        <td><?php echo $candidate['dept_name']; ?></td>
                        <td>
                            <?php if ($candidate['is_approved']): ?>
                                <span style="color: #48bb78;">Approved</span>
                            <?php else: ?>
                                <span style="color: #ed8936;">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($candidate['created_at'])); ?></td>
                        <td>
                            <?php if (!$candidate['is_approved']): ?>
                                <a href="?action=approve&id=<?php echo $candidate['candidate_id']; ?>" 
                                   class="btn btn-success" style="padding: 5px 10px; font-size: 14px;">
                                    Approve
                                </a>
                                <a href="?action=reject&id=<?php echo $candidate['candidate_id']; ?>" 
                                   class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;"
                                   onclick="return confirm('Reject this candidate?')">
                                    Reject
                                </a>
                            <?php else: ?>
                                <span style="color: #48bb78;">Approved</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="index.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>