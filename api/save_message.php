<?php
session_start();
require __DIR__ . '/../config/db.php';
if (empty($_SESSION['producer_logged'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$content = trim($_POST['content'] ?? '');
$id = isset($_POST['id']) ? (int)$_POST['id'] : null;

if ($content === '') {
    echo json_encode(['ok' => false, 'error' => 'Empty content']);
    exit;
}

if ($id) {
    $stmt = $pdo->prepare("UPDATE messages SET content=? WHERE id=?");
    $stmt->execute([$content, $id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO messages (content) VALUES (?)");
    $stmt->execute([$content]);
}

echo json_encode(['ok' => true]);

