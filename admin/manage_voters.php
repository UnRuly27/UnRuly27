<?php
require_once '../includes/config.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header('Location: ../index.php');
    exit();
}

// Handle voter actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND user_role = 'voter'");
        $stmt->execute([$id]);
        logActivity('Delete Voter', 'Deleted voter ID: ' . $id);
    } elseif ($_GET['action'] == 'toggle') {
        $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ? AND user_role = 'voter'");
        $stmt->execute([$id]);
        logActivity('Toggle Voter', 'Toggled voter ID: ' . $id);
    }
    header('Location: manage_voters.php');
    exit();
}

// Get all voters
$stmt = $pdo->query("
    SELECT u.*, d.dept_name 
    FROM users u 
    JOIN departments d ON u.dept_id = d.dept_id 
    WHERE u.user_role = 'voter' 
    ORDER BY u.created_at DESC
");
$voters = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Voters - <?php echo SITE_NAME; ?></title>
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
        <h1>Manage Voters</h1>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($voters as $voter): ?>
                    <tr>
                        <td><?php echo $voter['user_id']; ?></td>
                        <td><?php echo $voter['student_id']; ?></td>
                        <td><?php echo $voter['first_name'] . ' ' . $voter['last_name']; ?></td>
                        <td><?php echo $voter['username']; ?></td>
                        <td><?php echo $voter['email']; ?></td>
                        <td><?php echo $voter['phone']; ?></td>
                        <td><?php echo $voter['dept_name']; ?></td>
                        <td>
                            <span style="color: <?php echo $voter['is_active'] ? '#48bb78' : '#f56565'; ?>">
                                <?php echo $voter['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($voter['created_at'])); ?></td>
                        <td>
                            <a href="?action=toggle&id=<?php echo $voter['user_id']; ?>" 
                               class="btn" style="padding: 5px 10px; font-size: 14px;">
                                <?php echo $voter['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="?action=delete&id=<?php echo $voter['user_id']; ?>" 
                               class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;"
                               onclick="return confirm('Delete this voter?')">
                                Delete
                            </a>
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