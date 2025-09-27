<?php
$CONFIG = [
    'db_host' => '127.0.0.1',
    'db_name' => 'live_presenter',
    'db_user' => 'your_db_user',
    'db_pass' => 'your_db_password',
    'producer_password' => 'changeme'
];

try {
    $dsn = "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $CONFIG['db_user'], $CONFIG['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => 'DB connection failed', 'message'=>$e->getMessage()]);
    exit;
}
