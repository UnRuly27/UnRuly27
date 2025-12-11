<?php
session_start();
ob_start();

// 000webhost Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'id_your_database_username'); // You'll get this in Step 6
define('DB_PASS', 'your_database_password'); // You'll create this in Step 6
define('DB_NAME', 'id_your_database_name'); // You'll create this in Step 6

// Website configuration
define('SITE_NAME', 'DMI Blantyre Voting System');
define('SITE_URL', 'https://dmivoting.000webhostapp.com/'); // CHANGE TO YOUR URL

// File upload configuration
define('UPLOAD_DIR', 'assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Rest of the file remains the same...
// Create database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function getDepartmentName($dept_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT dept_name FROM departments WHERE dept_id = ?");
    $stmt->execute([$dept_id]);
    $result = $stmt->fetch();
    return $result ? $result['dept_name'] : 'Unknown';
}

function logActivity($action, $details = '') {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return;
    
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
}
?>