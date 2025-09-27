<?php
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check if producer is logged in
if (empty($_SESSION['producer_logged'])) {
    echo json_encode(['ok'=>false, 'error'=>'Unauthorized']);
    exit;
}

try {
    // Get all messages
    $stmt = $pdo->query("SELECT id, title, content FROM messages ORDER BY id DESC");
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get active message
    $activeStmt = $pdo->query("SELECT active_message_id FROM state WHERE id=1");
    $activeRow = $activeStmt->fetch(PDO::FETCH_ASSOC);
    $activeId = $activeRow['active_message_id'] ?? null;

    // Add is_active field
    foreach ($messages as &$msg) {
        $msg['is_active'] = ($msg['id'] == $activeId) ? 1 : 0;
    }

    echo json_encode(['ok'=>true, 'messages'=>$messages]);
} catch (PDOException $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
