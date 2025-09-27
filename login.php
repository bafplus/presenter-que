<?php
session_start();
require __DIR__ . '/config/db.php';

// Already logged in? go to producer
if (!empty($_SESSION['producer_logged'])) {
    header('Location: producer.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? '';
    if ($pw === $CONFIG['producer_password']) {
        $_SESSION['producer_logged'] = true;
        header('Location: producer.php');
        exit;
    } else {
        $error = 'Invalid password';
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Producer Login</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
  <h1>Producer Login</h1>
  <?php if($error): ?><p style="color:red"><?=htmlspecialchars($error)?></p><?php endif; ?>
  <form method="post">
    <label>Password</label>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
  </form>
</div>
</body>
</html>
