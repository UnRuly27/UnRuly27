<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    if ($role == 'super_admin' || $role == 'admin') {
        header('Location: admin/');
        exit();
    } elseif ($role == 'candidate') {
        header('Location: candidate/');
        exit();
    } else {
        header('Location: voter/');
        exit();
    }
}

$error = '';
$success = '';
$form_data = [];

// Handle registration
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_data = [
        'first_name' => sanitize($_POST['first_name']),
        'last_name' => sanitize($_POST['last_name']),
        'username' => sanitize($_POST['username']),
        'email' => sanitize($_POST['email']),
        'phone' => sanitize($_POST['phone']),
        'student_id' => sanitize($_POST['student_id']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'dept_id' => intval($_POST['dept_id']),
        'user_role' => 'voter'
    ];
    
    // Validate student ID
    if (strlen($form_data['student_id']) != 11) {
        $error = 'Student ID must be exactly 11 characters';
    }
    // Validate password
    elseif ($form_data['password'] !== $form_data['confirm_password']) {
        $error = 'Passwords do not match';
    }
    elseif (strlen($form_data['password']) < 6) {
        $error = 'Password must be at least 6 characters';
    }
    else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ? OR student_id = ?");
            $stmt->execute([$form_data['username'], $form_data['email'], $form_data['student_id']]);
            
            if ($stmt->fetch()) {
                $error = 'Username, email or Student ID already exists';
            } else {
                // Handle file upload
                $profile_pic = null;
                if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                    $file = $_FILES['profile_pic'];
                    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if (in_array($file_ext, ALLOWED_TYPES)) {
                        if ($file['size'] <= MAX_FILE_SIZE) {
                            $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $file['name']);
                            $target_path = UPLOAD_DIR . $filename;
                            
                            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                $profile_pic = $target_path;
                            }
                        }
                    }
                }
                
                // Insert user
                $stmt = $pdo->prepare("
                    INSERT INTO users 
                    (username, email, password, first_name, last_name, phone, student_id, profile_pic, dept_id, user_role) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $hashed_password = password_hash($form_data['password'], PASSWORD_DEFAULT);
                
                $stmt->execute([
                    $form_data['username'],
                    $form_data['email'],
                    $hashed_password,
                    $form_data['first_name'],
                    $form_data['last_name'],
                    $form_data['phone'],
                    $form_data['student_id'],
                    $profile_pic,
                    $form_data['dept_id'],
                    $form_data['user_role']
                ]);
                
                $success = 'Registration successful! You can now login.';
                $form_data = [];
                
                logActivity('Registration', 'New user registered');
            }
        } catch (PDOException $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}

// Get departments
$stmt = $pdo->query("SELECT * FROM departments ORDER BY dept_name");
$departments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>


    <div class="container">
        <div style="max-width: 1200px; margin: 50px auto;">
            <div class="card">
                <h2 style="text-align: center; color: #667eea; margin-bottom: 30px;">Create Your Account</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data" class="split-view">
                    <!-- Left Panel -->
                    <div class="split-panel left">
                        <h3 class="panel-title">Personal Information</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" required 
                                       value="<?php echo isset($form_data['first_name']) ? $form_data['first_name'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" required 
                                       value="<?php echo isset($form_data['last_name']) ? $form_data['last_name'] : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="username">Username *</label>
                            <input type="text" id="username" name="username" class="form-control" required 
                                   value="<?php echo isset($form_data['username']) ? $form_data['username'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" class="form-control" required 
                                   value="<?php echo isset($form_data['email']) ? $form_data['email'] : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" class="form-control" required 
                                   value="<?php echo isset($form_data['phone']) ? $form_data['phone'] : ''; ?>" 
                                   pattern="[0-9]{10,15}">
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_pic">Profile Picture</label>
                            <input type="file" id="profile_pic" name="profile_pic" class="form-control" 
                                   accept="image/*" onchange="previewImage(this)">
                            <div class="image-preview" id="imagePreview">
                                <span style="color: #999;">No image selected</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Panel -->
                    <div class="split-panel">
                        <h3 class="panel-title">Academic & Security</h3>
                        
                        <div class="form-group">
                            <label for="student_id">Student ID * (11 characters)</label>
                            <input type="text" id="student_id" name="student_id" class="form-control" required 
                                   value="<?php echo isset($form_data['student_id']) ? $form_data['student_id'] : ''; ?>"
                                   maxlength="11" minlength="11">
                            <small style="color: #666;">Must be exactly 11 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="dept_id">Department *</label>
                            <select id="dept_id" name="dept_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['dept_id']; ?>" 
                                        <?php echo (isset($form_data['dept_id']) && $form_data['dept_id'] == $dept['dept_id']) ? 'selected' : ''; ?>>
                                        <?php echo $dept['dept_name']; ?> (<?php echo $dept['dept_code']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <small style="color: #666;">Minimum 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <div style="margin-top: 30px; padding: 20px; background: #f7fafc; border-radius: 5px;">
                            <p style="color: #666;">
                                <strong>Note:</strong> All registered users are voters. Contact administration to become a candidate.
                            </p>
                        </div>
                        
                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">Register</button>
                            <a href="index.php" class="btn btn-secondary">Back to Login</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.innerHTML = '<span style="color: #999;">No image selected</span>';
            }
        }
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            if (password !== confirmPassword) {
                this.style.borderColor = '#f56565';
            } else {
                this.style.borderColor = '#48bb78';
            }
        });
    </script>
</body>
</html>