<?php
require __DIR__ . '/../config/db.php';

// Load active message
$stmt = $pdo->query("SELECT s.active_message_id, m.title, m.content 
                     FROM state s 
                     LEFT JOIN messages m ON s.active_message_id = m.id 
                     WHERE s.id = 1");
$row = $stmt->fetch();

$message = null;
if($row && $row['active_message_id']){
    $message = [
        'id' => $row['active_message_id'],
        'title' => $row['title'],
        'content' => $row['content']
    ];
}

// Load settings
$stmt = $pdo->query("SELECT k,v FROM settings");
$settings = [];
foreach($stmt as $row){
    $settings[$row['k']] = $row['v'];
}

header('Content-Type: application/json');
echo json_encode([
    'ok' => true,
    'message' => $message,
    'settings' => $settings
]);
