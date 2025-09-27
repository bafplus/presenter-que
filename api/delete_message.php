<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['producer_logged'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    echo json_encode(['ok' => false, 'error' => 'Invalid ID']);
    exit;
}

// Remove the message
$stmt = $pdo->prepare("DELETE FROM messages WHERE id=?");
$stmt->execute([$id]);

// If it was the active message, clear state
$pdo->prepare("UPDATE state SET active_message_id = NULL WHERE active_message_id=?")
    ->execute([$id]);

echo json_encode(['ok' => true]);
