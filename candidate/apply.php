<?php
require_once '../includes/config.php';

// Only voters can apply
if (!isLoggedIn() || $_SESSION['user_role'] != 'voter') {
    header('Location: ../index.php');
    exit();
}

// Check if already a candidate
$stmt = $pdo->prepare("SELECT candidate_id FROM candidates WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
if ($stmt->fetch()) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

// Get active elections
$stmt = $pdo->query("SELECT * FROM elections WHERE status = 'upcoming' ORDER BY start_date");
$elections = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $election_id = intval($_POST['election_id']);
    $position_id = intval($_POST['position_id']);
    $slogan = sanitize($_POST['slogan']);
    $agenda = sanitize($_POST['agenda']);
    
    if (empty($election_id) || empty($position_id)) {
        $error = 'Please select an election and position';
    } else {
        try {
            // Check if already applied
            $stmt = $pdo->prepare("
                SELECT candidate_id 
                FROM candidates 
                WHERE user_id = ? AND position_id = ?
            ");
            $stmt->execute([$_SESSION['user_id'], $position_id]);
            
            if ($stmt->fetch()) {
                $error = 'You have already applied for this position';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO candidates (user_id, position_id, election_id, slogan, agenda) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$_SESSION['user_id'], $position_id, $election_id, $slogan, $agenda]);
                
                $success = 'Application submitted successfully! Waiting for admin approval.';
                logActivity('Apply as Candidate', 'Applied for position ID: ' . $position_id);
            }
        } catch (PDOException $e) {
            $error = 'Failed to submit application: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply as Candidate - <?php echo SITE_NAME; ?></title>
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
        <div style="max-width: 600px; margin: 0 auto;">
            <h1>Apply as Candidate</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (empty($elections)): ?>
                <div class="card">
                    <h2>No Elections Available</h2>
                    <p>There are no upcoming elections to apply for at the moment.</p>
                    <a href="index.php" class="btn">Back to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="card">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="election_id">Select Election *</label>
                            <select id="election_id" name="election_id" class="form-control" required onchange="loadPositions(this.value)">
                                <option value="">Select an election</option>
                                <?php foreach ($elections as $election): ?>
                                <option value="<?php echo $election['election_id']; ?>">
                                    <?php echo $election['election_title']; ?> 
                                    (Starts: <?php echo date('M d, Y', strtotime($election['start_date'])); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="position_id">Select Position *</label>
                            <select id="position_id" name="position_id" class="form-control" required>
                                <option value="">Select a position</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="slogan">Campaign Slogan (Optional)</label>
                            <input type="text" id="slogan" name="slogan" class="form-control" 
                                   placeholder="Enter your campaign slogan">
                        </div>
                        
                        <div class="form-group">
                            <label for="agenda">Campaign Agenda (Optional)</label>
                            <textarea id="agenda" name="agenda" class="form-control" rows="4"
                                      placeholder="Describe your campaign plans and promises"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function loadPositions(electionId) {
            const positionSelect = document.getElementById('position_id');
            positionSelect.innerHTML = '<option value="">Select a position</option>';
            
            if (!electionId) return;
            
            fetch(`../includes/get_positions.php?election_id=${electionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.positions.forEach(position => {
                            const option = document.createElement('option');
                            option.value = position.position_id;
                            option.textContent = position.position_name;
                            positionSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>