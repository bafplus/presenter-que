<?php
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

<?php
session_start();
if(empty($_SESSION['producer_logged'])){ http_response_code(401); exit; }

$key = $_POST['k'] ?? null;
$val = $_POST['v'] ?? null;
if (!$key) {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'error'=>'missing key']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)');
    $stmt->execute([$key, $val]);
    echo json_encode(['ok'=>true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
