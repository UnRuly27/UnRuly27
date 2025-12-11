<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$role = $_SESSION['user_role'];

switch ($role) {
    case 'super_admin':
    case 'admin':
        header('Location: admin/');
        break;
    case 'candidate':
        header('Location: candidate/');
        break;
    case 'voter':
        header('Location: voter/');
        break;
    default:
        session_destroy();
        header('Location: index.php');
}
exit();