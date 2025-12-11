<?php
require_once '../includes/config.php';

// Only voters can access
if (!isLoggedIn() || $_SESSION['user_role'] != 'voter') {
    header('Location: ../index.php');
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error = '';
$success = '';

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    
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
                // Handle password change
                $password_update = '';
                $params = [$first_name, $last_name, $email, $phone, $_SESSION['user_id']];
                
                if (!empty($_POST['password'])) {
                    if ($_POST['password'] !== $_POST['confirm_password']) {
                        $error = 'Passwords do not match';
                    } elseif (strlen($_POST['password']) < 6) {
                        $error = 'Password must be at least 6 characters';
                    } else {
                        $password_update = ', password = ?';
                        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        array_splice($params, 4, 0, [$hashed_password]);
                    }
                }
                
                if (!$error) {
                    // Handle profile picture upload
                    $profile_pic = $user['profile_pic'];
                    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                        $file = $_FILES['profile_pic'];
                        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        
                        if (in_array($file_ext, ALLOWED_TYPES)) {
                            if ($file['size'] <= MAX_FILE_SIZE) {
                                $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\.]/', '_', $file['name']);
                                $target_path = UPLOAD_DIR . $filename;
                                
                                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                                    // Delete old picture if exists
                                    if ($profile_pic && file_exists($profile_pic)) {
                                        unlink($profile_pic);
                                    }
                                    $profile_pic = $target_path;
                                    $password_update .= ', profile_pic = ?';
                                    $params[] = $profile_pic;
                                }
                            }
                        }
                    }
                    
                    $sql = "UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?" . $password_update . " WHERE user_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    // Update session
                    $_SESSION['full_name'] = $first_name . ' ' . $last_name;
                    
                    $success = 'Profile updated successfully!';
                    logActivity('Update Profile', 'Updated profile information');
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                }
            }
        } catch (PDOException $e) {
            $error = 'Failed to update profile: ' . $e->getMessage();
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
    <title>Manage Account - <?php echo SITE_NAME; ?></title>
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
            <h1>Manage Your Account</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <form method="POST" action="" enctype="multipart/form-data">
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
                    
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input type="text" id="student_id" class="form-control" 
                               value="<?php echo $user['student_id']; ?>" disabled>
                        <small style="color: #666;">Student ID cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="dept_id">Department</label>
                        <select id="dept_id" class="form-control" disabled>
                            <option><?php echo getDepartmentName($user['dept_id']); ?></option>
                        </select>
                        <small style="color: #666;">Department cannot be changed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="profile_pic">Profile Picture</label>
                        <input type="file" id="profile_pic" name="profile_pic" class="form-control" 
                               accept="image/*" onchange="previewImage(this)">
                        <div class="image-preview" id="imagePreview">
                            <?php if ($user['profile_pic']): ?>
                                <img src="../<?php echo $user['profile_pic']; ?>" alt="Current Profile Picture">
                            <?php else: ?>
                                <span style="color: #999;">No image selected</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 30px; padding: 20px; background: #f7fafc; border-radius: 5px;">
                        <h3>Change Password (Optional)</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="password">New Password</label>
                                <input type="password" id="password" name="password" class="form-control">
                                <small>Leave empty to keep current password</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 15px; margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const currentImage = '<?php echo $user['profile_pic'] ? addslashes($user['profile_pic']) : ''; ?>';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                // Reset to current image
                if (currentImage) {
                    preview.innerHTML = '<img src="../' + currentImage + '" alt="Current Profile Picture">';
                } else {
                    preview.innerHTML = '<span style="color: #999;">No image selected</span>';
                }
            }
        }
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password && confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#f56565';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });
    </script>
</body>
</html>