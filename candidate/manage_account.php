<?php
require_once '../includes/config.php';

// Only candidates can access
if (!isLoggedIn() || $_SESSION['user_role'] != 'candidate') {
    header('Location: ../index.php');
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get candidate info
$stmt = $pdo->prepare("
    SELECT c.*, p.position_name, e.election_title
    FROM candidates c
    JOIN positions p ON c.position_id = p.position_id
    JOIN elections e ON c.election_id = e.election_id
    WHERE c.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$candidate_info = $stmt->fetch();

$error = '';
$success = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $slogan = sanitize($_POST['slogan']);
    $agenda = sanitize($_POST['agenda']);
    
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error = 'Please fill all required fields';
    } else {
        try {
            // Check if email exists for another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            
            if ($stmt->fetch()) {
                $error = 'Email already exists for another user';
            } else {
                // Update user info
                $stmt = $pdo->prepare("
                    UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?
                ");
                $stmt->execute([$first_name, $last_name, $email, $phone, $_SESSION['user_id']]);
                
                // Update candidate info
                if ($candidate_info) {
                    $stmt = $pdo->prepare("
                        UPDATE candidates SET slogan = ?, agenda = ? WHERE candidate_id = ?
                    ");
                    $stmt->execute([$slogan, $agenda, $candidate_info['candidate_id']]);
                }
                
                // Update session
                $_SESSION['full_name'] = $first_name . ' ' . $last_name;
                
                $success = 'Profile updated successfully!';
                logActivity('Update Candidate Profile', 'Updated profile and campaign info');
                
                // Refresh data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                $stmt = $pdo->prepare("
                    SELECT c.*, p.position_name, e.election_title
                    FROM candidates c
                    JOIN positions p ON c.position_id = p.position_id
                    JOIN elections e ON c.election_id = e.election_id
                    WHERE c.user_id = ?
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $candidate_info = $stmt->fetch();
            }
        } catch (PDOException $e) {
            $error = 'Failed to update profile: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Account - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="user-info">
                <span><?php echo $_SESSION['full_name']; ?> (Candidate)</span>
                <a href="index.php" class="btn">Dashboard</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <h1>Manage Your Account</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Personal Information</h2>
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" 
                                   value="<?php echo $user['first_name']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" 
                                   value="<?php echo $user['last_name']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo $user['email']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo $user['phone']; ?>" required pattern="[0-9]{10,15}">
                    </div>
                    
                    <?php if ($candidate_info): ?>
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                        <h2>Campaign Information</h2>
                        
                        <div class="form-group">
                            <label>Position</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo $candidate_info['position_name']; ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Election</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo $candidate_info['election_title']; ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label for="slogan">Campaign Slogan</label>
                            <input type="text" id="slogan" name="slogan" class="form-control" 
                                   value="<?php echo $candidate_info['slogan'] ?? ''; ?>"
                                   placeholder="Enter your campaign slogan">
                        </div>
                        
                        <div class="form-group">
                            <label for="agenda">Campaign Agenda</label>
                            <textarea id="agenda" name="agenda" class="form-control" rows="4"
                                      placeholder="Describe your campaign plans and promises"><?php echo $candidate_info['agenda'] ?? ''; ?></textarea>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>