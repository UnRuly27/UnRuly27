<?php
require_once '../includes/config.php';

if (!isLoggedIn() || ($_SESSION['user_role'] != 'admin' && $_SESSION['user_role'] != 'super_admin')) {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';

// Get active elections
$stmt = $pdo->query("SELECT * FROM elections WHERE status IN ('upcoming', 'active') ORDER BY election_title");
$elections = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $student_id = sanitize($_POST['student_id']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $election_id = intval($_POST['election_id']);
    $position = sanitize($_POST['position']);
    $slogan = sanitize($_POST['slogan']);
    $agenda = sanitize($_POST['agenda']);
    $dept_id = intval($_POST['dept_id']);
    $password = 'DMI' . substr($student_id, 0, 6); // Default password
    
    if (empty($first_name) || empty($last_name) || empty($student_id) || empty($email) || empty($phone) || empty($election_id) || empty($position)) {
        $error = 'Please fill all required fields';
    } elseif (strlen($student_id) != 11) {
        $error = 'Student ID must be exactly 11 characters';
    } else {
        try {
            // Check if student ID exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE student_id = ?");
            $stmt->execute([$student_id]);
            
            if ($stmt->fetch()) {
                $error = 'Student ID already exists';
            } else {
                // Create user account
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $username = strtolower($first_name . '.' . $last_name . '.' . substr($student_id, -4));
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, first_name, last_name, phone, student_id, dept_id, user_role) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'candidate')
                ");
                $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $phone, $student_id, $dept_id]);
                $user_id = $pdo->lastInsertId();
                
                // Get position ID
                $stmt = $pdo->prepare("SELECT position_id FROM positions WHERE position_name = ? AND election_id = ?");
                $stmt->execute([$position, $election_id]);
                $position_data = $stmt->fetch();
                
                if ($position_data) {
                    // Create candidate (auto-approve since admin is creating)
                    $stmt = $pdo->prepare("
                        INSERT INTO candidates (user_id, position_id, election_id, slogan, agenda, is_approved, approved_by) 
                        VALUES (?, ?, ?, ?, ?, 1, ?)
                    ");
                    $stmt->execute([$user_id, $position_data['position_id'], $election_id, $slogan, $agenda, $_SESSION['user_id']]);
                    
                    $success = 'Candidate created successfully!<br>';
                    $success .= '<strong>Login Details:</strong><br>';
                    $success .= 'Username: ' . $username . '<br>';
                    $success .= 'Password: ' . $password . '<br>';
                    $success .= 'Please inform the candidate to change their password after first login.';
                    
                    logActivity('Create Candidate', 'Created candidate: ' . $first_name . ' ' . $last_name . ' for ' . $position);
                } else {
                    // Delete the user if position not found
                    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $error = 'Position not found in selected election';
                }
            }
        } catch (PDOException $e) {
            $error = 'Failed to create candidate: ' . $e->getMessage();
        }
    }
}

// Get departments for dropdown
$stmt = $pdo->query("SELECT * FROM departments ORDER BY dept_name");
$departments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Candidate - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="header">
        <div class="container header-content">
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            <div class="user-info">
                <span><?php echo $_SESSION['full_name']; ?></span>
                <a href="manage_candidates.php" class="btn">Back to Candidates</a>
                <a href="logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <h1>Create New Candidate</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST" action="">
                    <h3 style="color: #667eea; margin-bottom: 20px;">Candidate Information</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="last_name">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_id">Student ID * (11 characters)</label>
                        <input type="text" id="student_id" name="student_id" class="form-control" required
                               maxlength="11" minlength="11"
                               oninput="generateUsername()">
                        <small style="color: #666;">Must be exactly 11 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" class="form-control" required 
                               pattern="[0-9]{10,15}">
                    </div>
                    
                    <div class="form-group">
                        <label for="dept_id">Department *</label>
                        <select id="dept_id" name="dept_id" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['dept_id']; ?>">
                                <?php echo $dept['dept_name']; ?> (<?php echo $dept['dept_code']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="margin-top: 30px; border-top: 2px solid #e2e8f0; padding-top: 20px;">
                        <h3 style="color: #667eea; margin-bottom: 20px;">Election Information</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="election_id">Election *</label>
                                <select id="election_id" name="election_id" class="form-control" required>
                                    <option value="">Select Election</option>
                                    <?php foreach ($elections as $election): ?>
                                    <option value="<?php echo $election['election_id']; ?>">
                                        <?php echo $election['election_title']; ?> 
                                        (<?php echo ucfirst($election['status']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="position">Position *</label>
                                <select id="position" name="position" class="form-control" required>
                                    <option value="">Select Position</option>
                                    <option value="President">President</option>
                                    <option value="Vice President">Vice President</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; border-top: 2px solid #e2e8f0; padding-top: 20px;">
                        <h3 style="color: #667eea; margin-bottom: 20px;">Campaign Information (Optional)</h3>
                        
                        <div class="form-group">
                            <label for="slogan">Campaign Slogan</label>
                            <input type="text" id="slogan" name="slogan" class="form-control" 
                                   placeholder="Enter campaign slogan">
                        </div>
                        
                        <div class="form-group">
                            <label for="agenda">Campaign Agenda</label>
                            <textarea id="agenda" name="agenda" class="form-control" rows="4"
                                      placeholder="Describe the candidate's campaign plans"></textarea>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f7fafc; border-radius: 5px;">
                        <p style="color: #666; margin: 0;">
                            <strong>Note:</strong> A user account will be created automatically for this candidate. 
                            Login credentials will be generated and displayed after creation.
                        </p>
                    </div>
                    
                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Create Candidate</button>
                        <a href="manage_candidates.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function generateUsername() {
            const firstName = document.getElementById('first_name').value.toLowerCase().replace(/\s+/g, '');
            const lastName = document.getElementById('last_name').value.toLowerCase().replace(/\s+/g, '');
            const studentId = document.getElementById('student_id').value;
            
            if (firstName && lastName && studentId.length >= 4) {
                const lastFour = studentId.slice(-4);
                // Username will be auto-generated in PHP: first.last.last4
                // Just for display purposes
                const usernameDisplay = firstName + '.' + lastName + '.' + lastFour;
                // You could display it in a span if you want
            }
        }
    </script>
</body>
</html>