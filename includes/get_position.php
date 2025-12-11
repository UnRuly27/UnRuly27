<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['election_id'])) {
    echo json_encode(['success' => false, 'message' => 'Election ID required']);
    exit();
}

$election_id = intval($_GET['election_id']);

try {
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE election_id = ? ORDER BY position_id");
    $stmt->execute([$election_id]);
    $positions = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'positions' => $positions]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>