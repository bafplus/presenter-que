<?php
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

try {
    // Get settings
    $stmt = $pdo->query("SELECT k, v FROM settings");
    $settings = [];
    foreach ($stmt as $row) {
        $settings[$row['k']] = $row['v'];
    }

    // Get active message
    $stmt = $pdo->query("SELECT active_message_id FROM state WHERE id=1");
    $activeRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $activeId = $activeRow['active_message_id'] ?? null;

    $message = null;
    if ($activeId) {
        $stmt = $pdo->prepare("SELECT id, title, content FROM messages WHERE id=?");
        $stmt->execute([$activeId]);
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'ok' => true,
        'message' => $message,
        'settings' => [
            'font_size' => $settings['font_size'] ?? '48',
            'color' => $settings['color'] ?? '#ffffff',
            'theme' => $settings['theme'] ?? 'light'
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}

