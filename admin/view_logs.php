<?php
require_once '../includes/config.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header('Location: ../index.php');
    exit();
}

// Handle log deletion
if (isset($_GET['action']) && $_GET['action'] == 'clear' && $_SESSION['user_role'] == 'super_admin') {
    $pdo->query("TRUNCATE TABLE logs");
    logActivity('Clear Logs', 'Cleared all activity logs');
    header('Location: view_logs.php');
    exit();
}

// Get filter parameters
$filter_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
$filter_action = isset($_GET['action_filter']) ? sanitize($_GET['action_filter']) : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$sql = "SELECT l.*, u.username, u.first_name, u.last_name FROM logs l JOIN users u ON l.user_id = u.user_id WHERE 1=1";
$params = [];

if ($filter_user) {
    $sql .= " AND l.user_id = ?";
    $params[] = $filter_user;
}

if ($filter_action) {
    $sql .= " AND l.action LIKE ?";
    $params[] = "%$filter_action%";
}

if ($filter_date) {
    $sql .= " AND DATE(l.created_at) = ?";
    $params[] = $filter_date;
}

$sql .= " ORDER BY l.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get all users for filter dropdown
$stmt = $pdo->query("SELECT user_id, username, first_name, last_name FROM users ORDER BY username");
$users = $stmt->fetchAll();

// Get unique actions for filter
$stmt = $pdo->query("SELECT DISTINCT action FROM logs ORDER BY action");
$actions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - <?php echo SITE_NAME; ?></title>
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
        <h1>Activity Logs</h1>
        
        <!-- Filters -->
        <div class="card" style="margin-bottom: 20px;">
            <h3>Filter Logs</h3>
            <form method="GET" action="">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                    <div class="form-group">
                        <label for="user_id">User</label>
                        <select id="user_id" name="user_id" class="form-control">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['user_id']; ?>" 
                                <?php echo $filter_user == $user['user_id'] ? 'selected' : ''; ?>>
                                <?php echo $user['username']; ?> (<?php echo $user['first_name']; ?> <?php echo $user['last_name']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="action_filter">Action</label>
                        <select id="action_filter" name="action_filter" class="form-control">
                            <option value="">All Actions</option>
                            <?php foreach ($actions as $action): ?>
                            <option value="<?php echo $action['action']; ?>" 
                                <?php echo $filter_action == $action['action'] ? 'selected' : ''; ?>>
                                <?php echo $action['action']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" class="form-control" 
                               value="<?php echo $filter_date; ?>">
                    </div>
                    
                    <div class="form-group" style="align-self: end;">
                        <button type="submit" class="btn">Filter</button>
                        <a href="view_logs.php" class="btn btn-secondary">Clear Filters</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Logs Table -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Recent Activities (Last 100 entries)</h3>
                <?php if ($_SESSION['user_role'] == 'super_admin'): ?>
                <a href="?action=clear" class="btn btn-danger" 
                   onclick="return confirm('Clear ALL activity logs? This cannot be undone!')">
                    Clear All Logs
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($logs)): ?>
                <p>No activity logs found.</p>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo $log['log_id']; ?></td>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <?php echo $log['username']; ?><br>
                                    <small style="color: #666;"><?php echo $log['first_name']; ?> <?php echo $log['last_name']; ?></small>
                                </td>
                                <td>
                                    <span style="
                                        background: <?php echo getActionColor($log['action']); ?>;
                                        color: white;
                                        padding: 3px 8px;
                                        border-radius: 4px;
                                        font-size: 12px;
                                        font-weight: bold;
                                    ">
                                        <?php echo $log['action']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['details']): ?>
                                        <div style="max-width: 300px; word-wrap: break-word;">
                                            <?php echo htmlspecialchars($log['details']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $log['ip_address']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <p style="color: #666;">
                        Showing <?php echo count($logs); ?> log entries
                        <?php if ($filter_user || $filter_action || $filter_date): ?>
                            (filtered)
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="index.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

<?php
// Helper function to get color based on action type
function getActionColor($action) {
    $colors = [
        'Login' => '#48bb78',
        'Logout' => '#718096',
        'Registration' => '#4299e1',
        'Create' => '#38a169',
        'Update' => '#d69e2e',
        'Delete' => '#e53e3e',
        'Approve' => '#38a169',
        'Reject' => '#e53e3e',
        'Vote' => '#805ad5',
        'Start' => '#38a169',
        'End' => '#e53e3e',
        'Clear' => '#e53e3e',
    ];
    
    foreach ($colors as $key => $color) {
        if (stripos($action, $key) !== false) {
            return $color;
        }
    }
    
    return '#718096'; // Default color
}
?>