<?php
session_start();
if (empty($_SESSION['producer_logged'])) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

try {
    // Get active message ID
    $stmt = $pdo->query('SELECT active_message_id FROM state WHERE id=1');
    $active_id = $stmt->fetchColumn();

    // Get all messages
    $stmt = $pdo->query('SELECT id, content FROM messages ORDER BY id DESC');
    $messages = [];
    while ($row = $stmt->fetch()) {
        $row['is_active'] = ($row['id'] == $active_id) ? true : false;
        $messages[] = $row;
    }

    echo json_encode(['messages' => $messages]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

