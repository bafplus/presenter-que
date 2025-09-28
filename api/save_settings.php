<?php
session_start();
require __DIR__ . '/../config/db.php';

if (empty($_SESSION['producer_logged'])) {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

// Collect all possible settings
$settings = [
    'screen_width'   => isset($_POST['screen_width']) ? (int)$_POST['screen_width'] : null,
    'screen_height'  => isset($_POST['screen_height']) ? (int)$_POST['screen_height'] : null,
    'header_height'  => isset($_POST['header_height']) ? (int)$_POST['header_height'] : null,
    'clock_enabled'  => isset($_POST['clock_enabled']) ? ($_POST['clock_enabled'] ? '1' : '0') : null,
    'clock_24h'      => isset($_POST['clock_24h']) ? ($_POST['clock_24h'] ? '1' : '0') : null,
    'color'          => isset($_POST['color']) ? trim($_POST['color']) : null,
    'theme'          => isset($_POST['theme']) && $_POST['theme']==='dark' ? 'dark' : 'light',
];

// Save each non-null setting
foreach ($settings as $k => $v) {
    if ($v !== null) {
        $pdo->prepare("REPLACE INTO settings (k,v) VALUES (?,?)")->execute([$k,$v]);
    }
}

echo json_encode(['ok' => true]);

