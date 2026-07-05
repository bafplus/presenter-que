<?php
require __DIR__ . '/config/db.php';

// Load settings
$stmt = $pdo->query("SELECT k,v FROM settings");
$settings = [];
foreach ($stmt as $row) {
    $settings[$row['k']] = $row['v'];
}

// Defaults
$screenWidth   = $settings['screen_width'] ?? 1920;
$screenHeight  = $settings['screen_height'] ?? 1080;
$headerHeight  = $settings['header_height'] ?? 60;
$clockEnabled  = $settings['clock_enabled'] ?? '1';
$clock24h      = $settings['clock_24h'] ?? '1';
$color         = $settings['color'] ?? '#ffffff';
$theme         = $settings['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
<meta charset="UTF-8">
<title>Presenter Screen</title>
<style>
body { margin:0; background:#000; color:<?= htmlspecialchars($color) ?>; font-family:Arial, sans-serif; }
#container { width:<?= (int)$screenWidth ?>px; height:<?= (int)$screenHeight ?>px; margin:auto; display:flex; flex-direction:column; }
#header { 
    height: <?= (int)$headerHeight ?>px; 
    line-height: <?= (int)$headerHeight ?>px; 
    font-weight:bold; 
    font-size: <?= ((int)$headerHeight - 20) ?>px; 
    padding: 0 10px; 
    box-sizing:border-box; 
    display:flex; 
    align-items:center; 
    justify-content:space-between; 
    overflow:hidden; 
}
#fsBtn {
    cursor: pointer;
    opacity: 0.4;
    transition: opacity 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    border-radius: 4px;
    flex-shrink: 0;
    line-height: 1;
}
#fsBtn:hover { opacity: 1; background: rgba(255,255,255,0.1); }
#fsBtn svg { width: 24px; height: 24px; fill: currentColor; }
#messageContainer { 
    flex:1; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    padding:20px; 
    box-sizing:border-box; 
    text-align:center; 
    overflow:hidden; 
}

/* Responsive scaling: container scales down to fit viewport */
html, body { width:100%; height:100%; overflow:hidden; }
body { display:flex; align-items:center; justify-content:center; }
#container { transform-origin: center center; }
#messageText { display:inline-block; word-wrap:break-word; transition: opacity 0.3s ease; white-space: pre-wrap; }
#messageTitle { font-weight:bold; display:block; margin-bottom:10px; }
</style>
</head>
<body>

<div id="container">
    <div id="header">
        <?php if($clockEnabled==='1'): ?>
            <span id="clock">--:--:--</span>
        <?php endif; ?>
        <span id="fsBtn" title="Toggle fullscreen">
            <svg viewBox="0 0 24 24"><path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/></svg>
        </span>
    </div>
    <div id="messageContainer">
        <span id="messageText"><span id="messageTitle"></span></span>
    </div>
</div>

<script>
const messageEl = document.getElementById('messageText');
const messageTitleEl = document.getElementById('messageTitle');
const containerEl = document.getElementById('messageContainer');
const headerEl = document.getElementById('header');
const clockEl = document.getElementById('clock');

// Use 24h or 12h based on settings
let use24h = <?= $clock24h==='1' ? 'true' : 'false' ?>;

// Clock update
function updateClock(){
    if(!clockEl) return;
    const d = new Date();
    let hh = d.getHours();
    const mm = d.getMinutes().toString().padStart(2,'0');
    const ss = d.getSeconds().toString().padStart(2,'0');
    let ampm = '';

    if(!use24h){
        ampm = hh >= 12 ? ' PM' : ' AM';
        hh = hh % 12;
        if(hh === 0) hh = 12;
    }

    hh = hh.toString().padStart(2,'0');
    clockEl.textContent = hh + ":" + mm + ":" + ss + ampm;
}
setInterval(updateClock,1000);
updateClock();

// ── Screen Wake Lock: keep iPad screen on ──
let wakeLock = null;

async function requestWakeLock() {
    try {
        wakeLock = await navigator.wakeLock.request('screen');
    } catch (err) {
        // Wake Lock not supported or denied — silently ignore
    }
}

// Re-acquire when page becomes visible again (user switches back)
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && !wakeLock) {
        requestWakeLock();
    }
});

// Re-acquire when released (e.g. by OS) or page visibility changes
if (navigator.wakeLock) {
    requestWakeLock();

    // Safety net: check every 30s in case wake lock gets released
    setInterval(() => {
        if (!wakeLock) requestWakeLock();
    }, 30000);
}
// ── End Wake Lock ──

// ── Fullscreen toggle ──
const fsBtn = document.getElementById('fsBtn');
const fsIcon = fsBtn.querySelector('svg path');

function isFullscreen() {
    return !!(document.fullscreenElement || document.webkitFullscreenElement);
}

function getFsIconPath(fs) {
    return fs
        ? 'M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z'  // compress (exit)
        : 'M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z';  // expand (enter)
}

fsBtn.addEventListener('click', async () => {
    if (isFullscreen()) {
        if (document.exitFullscreen) document.exitFullscreen();
        else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
    } else {
        const el = document.documentElement;
        if (el.requestFullscreen) await el.requestFullscreen();
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    }
});

document.addEventListener('fullscreenchange', () => {
    fsIcon.setAttribute('d', getFsIconPath(isFullscreen()));
});
document.addEventListener('webkitfullscreenchange', () => {
    fsIcon.setAttribute('d', getFsIconPath(isFullscreen()));
});
// ── End Fullscreen ──

// ── Responsive scaling: fit container within viewport ──
const container = document.getElementById('container');

function fitToViewport() {
    const cw = container.offsetWidth;
    const ch = container.offsetHeight;
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const scale = Math.min(vw / cw, vh / ch, 1); // never scale UP beyond 1×
    container.style.transform = `scale(${scale})`;
}

window.addEventListener('resize', fitToViewport);
fitToViewport();
// ── End Scaling ──

// Fit message text inside container
function fitText(el){
    const containerHeight = containerEl.clientHeight;
    const containerWidth = containerEl.clientWidth;
    let fontSize = 10;
    el.style.fontSize = fontSize + 'px';

    while(el.scrollHeight <= containerHeight && el.scrollWidth <= containerWidth && fontSize < 1000){
        fontSize += 2;
        el.style.fontSize = fontSize + 'px';
    }
    el.style.fontSize = (fontSize - 2) + 'px';
}

// Store current displayed message
let currentMessageId = null;

// Fetch active message + settings
function updateState() {
    fetch('api/get_state.php')
        .then(r => r.json())
        .then(res => {
            if (!res.ok) return;

            const msg = res.message;
            const s = res.settings || {};

            // Update header height
            if (s.header_height) {
                const newHeaderHeight = parseInt(s.header_height);
                headerEl.style.height = newHeaderHeight + 'px';
                headerEl.style.lineHeight = newHeaderHeight + 'px';
                headerEl.style.fontSize = (newHeaderHeight - 20) + 'px';
                containerEl.style.height = (<?= (int)$screenHeight ?> - newHeaderHeight) + 'px';
            }

            // Update clock visibility and 24h setting dynamically
            if (clockEl && s.clock_enabled !== undefined) {
                clockEl.style.display = s.clock_enabled === '1' ? 'inline' : 'none';
            }
            if (s.clock_24h !== undefined) {
                use24h = s.clock_24h === '1';
            }

            // CLEAR if no active message
            if (!msg) {
                currentMessageId = null;
                messageTitleEl.textContent = '';
                while(messageEl.childNodes.length > 1) messageEl.removeChild(messageEl.lastChild);
                return;
            }

            // Only update if message changed
            if (msg.id === currentMessageId) return;
            currentMessageId = msg.id;

            const title = msg.title || '';
            const content = msg.content || '';

            messageEl.style.opacity = 0;
            setTimeout(() => {
                messageTitleEl.textContent = title;
                while(messageEl.childNodes.length > 1) messageEl.removeChild(messageEl.lastChild);
                const contentNode = document.createTextNode(content);
                messageEl.appendChild(contentNode);
                fitText(messageEl);
                messageEl.style.opacity = 1;
            }, 300);
        })
        .catch(console.error);
}

// Initial load + polling every 2 seconds
updateState();
setInterval(updateState, 2000);
</script>

</body>
</html>
