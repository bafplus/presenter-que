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
    justify-content:flex-start; 
    overflow:hidden; 
}
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
