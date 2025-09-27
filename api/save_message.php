<?php
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

<?php
session_start();
if(empty($_SESSION['producer_logged'])){ http_response_code(401); exit; }

$id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
$title = $_POST['title'] ?? null;
$content = $_POST['content'] ?? '';

try {
    if ($id) {
        $stmt = $pdo->prepare('UPDATE messages SET title = ?, content = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$title, $content, $id]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO messages (title, content) VALUES (?, ?)');
        $stmt->execute([$title, $content]);
        $id = $pdo->lastInsertId();
    }
    echo json_encode(['ok'=>true, 'id'=>$id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
