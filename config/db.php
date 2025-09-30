<?php
require_once __DIR__ . '/../vendor/autoload.php'; // adjust path if needed

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

$CONFIG = [
    'db_host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'db_name' => $_ENV['DB_NAME'] ?? 'live_presenter',
    'db_user' => $_ENV['DB_USER'] ?? 'your_db_user',
    'db_pass' => $_ENV['DB_PASS'] ?? 'your_db_password',
    'producer_password' => $_ENV['PRODUCER_PASSWORD'] ?? 'changeme',
];

try {
    $dsn = "mysql:host={$CONFIG['db_host']};dbname={$CONFIG['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $CONFIG['db_user'], $CONFIG['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode(['error' => 'DB connection failed', 'message' => $e->getMessage()]);
    exit;
}

