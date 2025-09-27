<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['producer_logged'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$fontSize = isset($_POST['font_size']) ? (int)$_POST['font_size'] : 48;
$color = trim($_POST['color'] ?? '#ffffff');
$theme = ($_POST['theme'] ?? 'light') === 'dark' ? 'dark' : 'light';

$pdo->prepare("REPLACE INTO settings (k,v) VALUES ('font_size', ?)")->execute([$fontSize]);
$pdo->prepare("REPLACE INTO settings (k,v) VALUES ('color', ?)")->execute([$color]);
$pdo->prepare("REPLACE INTO settings (k,v) VALUES ('theme', ?)")->execute([$theme]);

echo json_encode(['ok' => true]);
