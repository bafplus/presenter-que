<?php
session_start();
require __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

// Check if producer is logged in
if (empty($_SESSION['producer_logged'])) {
    echo json_encode(['ok'=>false, 'error'=>'Unauthorized']);
    exit;
}

// Get POST data
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$id = $_POST['id'] ?? null;

// Validate content
if (!$content) {
    echo json_encode(['ok'=>false, 'error'=>'Content cannot be empty']);
    exit;
}

try {
    if ($id) {
        // Update existing message
        $stmt = $pdo->prepare("UPDATE messages SET title=?, content=? WHERE id=?");
        $stmt->execute([$title, $content, $id]);
    } else {
        // Insert new message
        $stmt = $pdo->prepare("INSERT INTO messages (title, content) VALUES (?, ?)");
        $stmt->execute([$title, $content]);
    }

    echo json_encode(['ok'=>true]);
} catch (PDOException $e) {
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
