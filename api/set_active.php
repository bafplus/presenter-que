<?php
require __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

<?php
session_start();
if(empty($_SESSION['producer_logged'])){ http_response_code(401); exit; }

$active_id = isset($_POST['active_id']) && $_POST['active_id'] !== '' ? (int)$_POST['active_id'] : null;
try {
    $stmt = $pdo->prepare('UPDATE state SET active_message_id = ? WHERE id = 1');
    $stmt->execute([$active_id]);
    echo json_encode(['ok'=>true, 'active'=>$active_id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
