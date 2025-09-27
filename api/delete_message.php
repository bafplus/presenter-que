<?php
session_start();
if (empty($_SESSION['producer_logged'])) {
    http_response_code(401);
    echo json_encode(['error'=>'unauthorized']);
    exit;
}

require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'missing id']);
    exit;
}

try {
    // If deleting active message, clear active
    $stmt = $pdo->prepare('SELECT active_message_id FROM state WHERE id=1');
    $active_id = $stmt->execute() ? $stmt->fetchColumn() : null;

    if ($active_id == $id) {
        $stmt = $pdo->prepare('UPDATE state SET active_message_id = NULL WHERE id=1');
        $stmt->execute();
    }

    // Delete message
    $stmt = $pdo->prepare('DELETE FROM messages WHERE id=?');
    $stmt->execute([$id]);

    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}
