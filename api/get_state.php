<?php
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query('SELECT active_message_id FROM state WHERE id=1');
    $state = $stmt->fetch();
    $active_id = $state ? $state['active_message_id'] : null;

    $message = null;
    if ($active_id) {
        $stmt = $pdo->prepare('SELECT id, title, content, updated_at FROM messages WHERE id = ?');
        $stmt->execute([$active_id]);
        $message = $stmt->fetch();
    }

    $stmt = $pdo->query('SELECT k, v FROM settings');
    $settings = [];
    while ($r = $stmt->fetch()) {
        $settings[$r['k']] = $r['v'];
    }

    echo json_encode(['ok'=>true, 'active' => $active_id, 'message' => $message, 'settings' => $settings]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
