<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Presenter View</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/style.css">
  <style>
    /* Presenter-specific overrides */
    body {
      background: black;
      color: white;
      margin: 0;
    }
    .presenter-wrap {
      height: 100vh;
      width: 100vw;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .message {
      text-align: center;
      max-width: 95%;
      word-wrap: break-word;
      font-size: 48px;   /* default size, overridden by settings */
      line-height: 1.1;
    }
    #fsBtn {
      position: fixed;
      right: 10px;
      top: 10px;
      z-index: 999;
      padding: 8px 12px;
      background: #333;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="presenter-wrap">
    <div class="message" id="messageArea">(no active message)</div>
  </div>
  <button id="fsBtn">Go Fullscreen</button>

  <script>
  // How often to poll the API (milliseconds)
  const POLL_INTERVAL = 800;

  async function fetchState() {
    try {
      const res = await fetch('api/get_state.php', { cache: 'no-store' });
      return await res.json();
    } catch (err) {
      console.error('Error fetching state:', err);
      return null;
    }
  }

  let lastMessageId = null;

  async function poll() {
    const state = await fetchState();
    if (!state) return;

    const msg = state.message;
    const settings = state.settings || {};

    const messageArea = document.getElementById('messageArea');

    if (msg && msg.id !== lastMessageId) {
      messageArea.innerHTML = msg.content.replace(/\\n/g, '<br>');
      lastMessageId = msg.id;
    } else if (!msg && lastMessageId !== null) {
      messageArea.textContent = '(no active message)';
      lastMessageId = null;
    }

    // Apply settings
    if (settings.font_size) {
      messageArea.style.fontSize = parseInt(settings.font_size, 10) + 'px';
    }
    if (settings.color) {
      messageArea.style.color = settings.color;
    }
  }

  // Start polling
  setInterval(poll, POLL_INTERVAL);
  poll();

  // Fullscreen toggle
  document.getElementById('fsBtn').addEventListener('click', () => {
    const el = document.documentElement;
    if (el.requestFullscreen) el.requestFullscreen();
    else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
  });
  </script>
</body>
</html>
