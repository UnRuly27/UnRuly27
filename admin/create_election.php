<?php
require_once '../includes/config.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    if (empty($title) || empty($start_date) || empty($end_date)) {
        $error = 'Please fill all required fields';
    } elseif ($start_date >= $end_date) {
        $error = 'End date must be after start date';
    } else {
        try {
            // Create election
            $stmt = $pdo->prepare("
                INSERT INTO elections (election_title, description, start_date, end_date, status, created_by) 
                VALUES (?, ?, ?, ?, 'upcoming', ?)
            ");
            $stmt->execute([$title, $description, $start_date, $end_date, $_SESSION['user_id']]);
            $election_id = $pdo->lastInsertId();
            
            // Create positions (President and Vice President)
            $positions = ['President', 'Vice President'];
            foreach ($positions as $position) {
                $stmt = $pdo->prepare("INSERT INTO positions (position_name, election_id) VALUES (?, ?)");
                $stmt->execute([$position, $election_id]);
            }
            
            $success = 'Election created successfully! Positions (President and Vice President) have been created.';
            logActivity('Create Election', 'Created election: ' . $title . ' with ID: ' . $election_id);
            
        } catch (Exception $e) {
            $error = 'Failed to create election: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Election - <?php echo SITE_NAME; ?></title>
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
        <div style="max-width: 800px; margin: 0 auto;">
            <h1>Create New Election</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Election Title *</label>
                        <input type="text" id="title" name="title" class="form-control" required 
                               placeholder="e.g., DMI Student Union Election 2024">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="4"
                                  placeholder="Describe the purpose and scope of this election"></textarea>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="start_date">Start Date & Time *</label>
                            <input type="datetime-local" id="start_date" name="start_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date & Time *</label>
                            <input type="datetime-local" id="end_date" name="end_date" class="form-control" required>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f7fafc; border-radius: 5px;">
                        <p style="color: #666; margin: 0;">
                            <strong>Note:</strong> This election will automatically include two positions: 
                            <strong>President</strong> and <strong>Vice President</strong>.
                        </p>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Create Election</button>
                        <a href="manage_elections.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Set default dates (tomorrow for start, day after for end)
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        tomorrow.setHours(9, 0, 0, 0);
        
        const dayAfter = new Date();
        dayAfter.setDate(dayAfter.getDate() + 2);
        dayAfter.setHours(17, 0, 0, 0);
        
        // Format for datetime-local input (YYYY-MM-DDTHH:MM)
        const formatDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        };
        
        // Set default values
        document.getElementById('start_date').value = formatDate(tomorrow);
        document.getElementById('end_date').value = formatDate(dayAfter);
    </script>
</body>
</html>