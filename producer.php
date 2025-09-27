<?php
session_start();
require __DIR__ . '/config/db.php';
if (empty($_SESSION['producer_logged'])) { header('Location: login.php'); exit; }
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Producer Control</title>
<link rel="stylesheet" href="assets/style.css">
<style>
  ul { list-style: none; padding: 0; }
  li { padding: 6px; border: 1px solid #ccc; margin: 4px 0; display: flex; justify-content: space-between; align-items: center; }
  li.active { background: #d0ffd0; }
  button { margin-left: 4px; }
</style>
</head>
<body>
<div class="container">
  <h1>Producer Control Panel</h1>
  <p><a href="logout.php">Logout</a></p>

  <!-- Add message -->
  <section>
    <h2>Add Message</h2>
    <form id="messageForm">
      <textarea id="messageInput" name="content" placeholder="Type new message"></textarea>
      <button type="submit">Add Message</button>
    </form>
  </section>

  <!-- Messages list -->
  <section>
    <h2>Messages</h2>
    <ul id="messagesList"></ul>
  </section>

  <!-- Settings -->
  <section>
    <h2>Settings</h2>
    <form id="settingsForm">
      <label>Font size (px)
        <input type="number" name="font_size" value="48" min="10" max="200">
      </label>
      <label>Text color
        <input type="text" name="color" value="#ffffff">
      </label>
      <button type="submit">Save Settings</button>
    </form>
  </section>
</div>

<script src="assets/app.js"></script>
</body>
</html>

