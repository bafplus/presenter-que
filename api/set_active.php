<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['producer_logged'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$activeId = isset($_POST['active_id']) && $_POST['active_id'] !== ''
    ? (int)$_POST['active_id']
    : null;

$stmt = $pdo->prepare("UPDATE state SET active_message_id=?, updated_at=NOW() WHERE id=1");
$stmt->execute([$activeId]);


echo json_encode(['ok' => true]);
