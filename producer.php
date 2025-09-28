<?php
session_start();
require __DIR__ . '/config/db.php';

if (empty($_SESSION['producer_logged'])) {
    header('Location: login.php');
    exit;
}

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
$theme         = $settings['theme'] ?? 'light';
$color         = $settings['color'] ?? '#ffffff';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
<meta charset="UTF-8">
<title>Producer Control</title>
<link rel="stylesheet" href="assets/style.css">
<style>
body { margin:0; font-family: Arial, sans-serif; background-color: var(--bg); color: var(--text); }
:root[data-theme="light"] { --bg:#f0f0f0; --text:#000; --card-bg:#fff; --border-color:#ccc; }
:root[data-theme="dark"]  { --bg:#121212; --text:#f4f4f4; --card-bg:#222; --border-color:#444; }

header { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; background-color: var(--card-bg); border-bottom: 1px solid #888; }
header h1 { margin:0; font-size:1.5em; }
header button, header a { background:transparent; border:none; font-size:22px; cursor:pointer; color:inherit; text-decoration:none; margin-left:8px; }

/* Grid layout with resizers */
.main-container { display:grid; grid-template-columns: 20% 5px 1fr 5px 30%; grid-template-rows:1fr; height:calc(100vh - 60px); }
.column { display:flex; flex-direction:column; overflow:hidden; padding:0 8px; }
.resizer { background: var(--border-color); cursor: col-resize; width:5px; }

/* Messages */
#messagesContainer { flex:1; overflow-y:auto; margin-bottom:10px; background-color: var(--card-bg); border-radius:5px; padding:5px; }
#messages { list-style:none; padding:0; margin:0; }
#messages li { display:flex; justify-content:space-between; align-items:center; background-color: var(--card-bg); padding:5px 10px; border-bottom:1px solid var(--border-color); }
#messages li:last-child { border-bottom:none; }
#messages li.active { border:2px solid #4CAF50; }
#messages li span { flex:1; margin-right:10px; }
#messages li button { font-size:18px; margin-left:5px; cursor:pointer; }

/* Inputs dark theme */
input, textarea, select { width:100%; padding:6px; border-radius:4px; border:1px solid var(--border-color); background-color: var(--card-bg); color: var(--text); box-sizing:border-box; }
textarea { resize:vertical; }

/* Create / Edit Message */
#newMessageBox { margin-top:10px; }
#newMessageBox input, #newMessageBox textarea, #newMessageBox button { margin-bottom:6px; }
#newMessageBox button { padding:6px 12px; font-size:16px; border-radius:4px; cursor:pointer; }

/* Settings box */
.settings-box { background-color: var(--card-bg); padding:10px; border-radius:5px; margin-bottom:10px; }

/* Previews */
.preview-container { background-color: var(--card-bg); border-radius:5px; padding:5px; margin-bottom:10px; }
.preview-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:5px; }
.presenter-frame-wrapper { width:100%; overflow:hidden; position:relative; aspect-ratio: <?= $screenWidth ?>/<?= $screenHeight ?>; }
.presenter-frame-inner { width:<?= $screenWidth ?>px; height:<?= $screenHeight ?>px; transform-origin: top left; }
.presenter-frame-inner iframe { border:none; width:100%; height:100%; }
.youtube-frame-wrapper { width:100%; aspect-ratio:16/9; }
.youtube-frame-wrapper iframe { width:100%; height:100%; border:none; }

/* Popups */
.popup { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; }
.popup-content { background:var(--card-bg); padding:20px; border-radius:8px; max-width:500px; width:90%; }
.popup-content h2 { margin-top:0; }

/* Chat */
#youtubeChatMessages { list-style:none; margin:0; padding:0; }
#youtubeChatMessages li { display:flex; justify-content:space-between; align-items:center; padding:4px 0; border-bottom:1px solid var(--border-color); }
#youtubeChatMessages li span { flex:1; }
#youtubeChatMessages li button { margin-left:8px; font-size:16px; cursor:pointer; }
</style>
</head>
<body>

<header>
    <h1>Producer Control</h1>
    <div>
        <button id="fullscreenToggle">⛶</button>
        <button id="themeToggle"><?= $theme==='dark'?'☀️':'🌙' ?></button>
        <a href="logout.php">🔒</a>
    </div>
</header>

<div class="main-container">
    <div class="column left-column">
        <h2>YouTube Live Chat</h2>
        <div style="flex:1;overflow-y:auto;background:var(--card-bg);border-radius:5px;padding:5px;">
            <ul id="youtubeChatMessages"></ul>
        </div>
        <button id="openYoutubeApiSettings">⚙️ API Key</button>
    </div>

    <div class="resizer" id="resizerLeft"></div>

    <div class="column middle-column">
        <h2>Messages Queue</h2>
        <div id="messagesContainer">
            <ul id="messages"></ul>
        </div>
        <div id="newMessageBox">
            <h2>Create / Edit Message</h2>
            <input type="hidden" id="editId" value="">
            <input type="text" id="newTitle" placeholder="Optional title">
            <textarea id="newMessage" rows="3" placeholder="Enter message"></textarea>
            <button id="saveMessage">💾 Save</button>
        </div>
    </div>

    <div class="resizer" id="resizerRight"></div>

    <div class="column right-column">
        <div class="settings-box">
            <h2>Presenter Settings</h2>
        </div>

        <div class="preview-container">
            <div class="preview-header">
                <span>Presenter view</span>
                <div>
                    <button id="refreshPresenter">🔄</button>
                    <button id="openSettingsPopup">⚙️</button>
                </div>
            </div>
            <div class="presenter-frame-wrapper">
                <div class="presenter-frame-inner">
                    <iframe id="presenterFrame" src="presenter.php"></iframe>
                </div>
            </div>
        </div>

        <div class="preview-container">
            <div class="preview-header">
                <span>YouTube Live</span>
                <div>
                    <button id="refreshYoutube">🔄</button>
                    <button id="openYoutubeSettings">⚙️</button>
                </div>
            </div>
            <div class="youtube-frame-wrapper"></div>
        </div>
    </div>
</div>

<!-- Popups -->
<div id="settingsPopup" class="popup">
    <div class="popup-content">
        <h2>Presenter Settings</h2>
        <label>Screen Width: <input type="number" id="screenWidth" value="<?= htmlspecialchars($screenWidth) ?>" min="100" max="3840"></label><br>
        <label>Screen Height: <input type="number" id="screenHeight" value="<?= htmlspecialchars($screenHeight) ?>" min="100" max="2160"></label><br>
        <label>Header Height: <input type="number" id="headerHeight" value="<?= htmlspecialchars($headerHeight) ?>" min="10" max="500"></label><br>
        <label>Clock Enabled: <input type="checkbox" id="clockEnabled" <?= $clockEnabled==='1'?'checked':'' ?>></label><br>
        <label>24h Clock: <input type="checkbox" id="clock24h" <?= $clock24h==='1'?'checked':'' ?>></label><br>
        <label>Text Color: <input type="color" id="color" value="<?= htmlspecialchars($color) ?>"></label><br>
        <button id="closeSettingsPopup">Close</button>
    </div>
</div>

<div id="youtubeSettingsPopup" class="popup">
    <div class="popup-content">
        <h2>YouTube Settings</h2>
        <label>Video URL: <input type="text" id="youtubeUrl" placeholder="Enter YouTube Live URL"></label><br>
        <button id="saveYoutubeSettings">Save</button>
        <button id="closeYoutubeSettings">Close</button>
    </div>
</div>

<div id="youtubeApiSettingsPopup" class="popup">
    <div class="popup-content">
        <h2>YouTube API Settings</h2>
        <label>API Key: <input type="text" id="youtubeApiKey" placeholder="Enter API Key"></label><br>
        <button id="saveYoutubeApiKey">Save</button>
        <button id="closeYoutubeApiSettings">Close</button>
    </div>
</div>

<script>
const listEl = document.getElementById('messages');
const newTitleEl = document.getElementById('newTitle');
const newMsgEl = document.getElementById('newMessage');
const editIdEl = document.getElementById('editId');
const saveBtn = document.getElementById('saveMessage');

const screenWidthEl  = document.getElementById('screenWidth');
const screenHeightEl = document.getElementById('screenHeight');
const headerHeightEl = document.getElementById('headerHeight');
const clockEnabledEl = document.getElementById('clockEnabled');
const clock24hEl     = document.getElementById('clock24h');
const colorEl        = document.getElementById('color');
const themeToggleBtn = document.getElementById('themeToggle');
const fullscreenBtn  = document.getElementById('fullscreenToggle');

const presenterFrame = document.getElementById('presenterFrame');
const presenterWrapper = document.querySelector('.presenter-frame-wrapper');
const presenterInner = document.querySelector('.presenter-frame-inner');
const youtubeFrameWrapper = document.querySelector('.youtube-frame-wrapper');

// === Popups handlers ===
function openPopup(id){ document.getElementById(id).style.display='flex'; }
function closePopup(id){ document.getElementById(id).style.display='none'; }

document.getElementById('openSettingsPopup').onclick = ()=>openPopup('settingsPopup');
document.getElementById('closeSettingsPopup').onclick = ()=>closePopup('settingsPopup');
document.getElementById('openYoutubeSettings').onclick = ()=>openPopup('youtubeSettingsPopup');
document.getElementById('closeYoutubeSettings').onclick = ()=>closePopup('youtubeSettingsPopup');
document.getElementById('openYoutubeApiSettings').onclick = ()=>openPopup('youtubeApiSettingsPopup');
document.getElementById('closeYoutubeApiSettings').onclick = ()=>closePopup('youtubeApiSettingsPopup');

// === Fullscreen toggle ===
fullscreenBtn.onclick = ()=>{
    if(!document.fullscreenElement) document.documentElement.requestFullscreen();
    else document.exitFullscreen();
};

// === Save new message ===
saveBtn.onclick = () => {
    const id = editIdEl.value.trim();
    const title = newTitleEl.value.trim();
    const content = newMsgEl.value.trim();
    if (!content) return alert('Message cannot be empty');
    const data = new URLSearchParams({content, title});
    if (id) data.append('id', id);

    fetch('api/save_message.php', { method:'POST', body:data })
        .then(r=>r.json())
        .then(res=>{
            if(res.ok){ newTitleEl.value=''; newMsgEl.value=''; editIdEl.value=''; loadMessages(); }
            else alert(res.error || 'Error saving message');
        }).catch(()=>alert("Network error"));
};

// === Load messages ===
function loadMessages(){
    fetch('api/get_messages.php')
        .then(r=>r.json())
        .then(res=>{
            if(!res.ok) return console.error(res.error);
            listEl.innerHTML='';
            res.messages.forEach(m=>{
                const li=document.createElement('li');
                li.className=m.is_active?'active':'';
                const span=document.createElement('span');
                span.textContent=m.title?m.title+': '+m.content:m.content;
                li.appendChild(span);

                const btnShow=document.createElement('button');
                btnShow.textContent=m.is_active?'❌':'▶️';
                btnShow.onclick=()=>setActive(m.is_active?null:m.id);
                li.appendChild(btnShow);

                const btnEdit=document.createElement('button');
                btnEdit.textContent='✏️';
                btnEdit.onclick=()=>{ newTitleEl.value=m.title||''; newMsgEl.value=m.content; editIdEl.value=m.id; };
                li.appendChild(btnEdit);

                const btnDel=document.createElement('button');
                btnDel.textContent='🗑️';
                btnDel.onclick=()=>{ if(confirm('Delete this message?')){ fetch('api/delete_message.php',{ method:'POST', body:new URLSearchParams({id:m.id}) }).then(()=>loadMessages()); }};
                li.appendChild(btnDel);

                listEl.appendChild(li);
            });
        }).catch(console.error);
}

// === Set active message ===
function setActive(id){
    const data = new URLSearchParams();
    if(id!==null) data.append('active_id',id);
    fetch('api/set_active.php',{ method:'POST', body:data }).then(()=>loadMessages());
}

// === Save settings ===
function saveSettings(){
    const data = new URLSearchParams({
        screen_width: screenWidthEl.value,
        screen_height: screenHeightEl.value,
        header_height: headerHeightEl.value,
        clock_enabled: clockEnabledEl.checked?1:0,
        clock_24h: clock24hEl.checked?1:0,
        color: colorEl.value,
        theme: document.documentElement.dataset.theme
    });
    fetch('api/save_settings.php',{ method:'POST', body:data })
        .then(r=>r.json()).then(res=>{ if(!res.ok) console.error(res.error); })
        .catch(console.error);
}
[screenWidthEl, screenHeightEl, headerHeightEl, clockEnabledEl, clock24hEl, colorEl].forEach(el=>{ el.onchange = saveSettings; });

// === Theme toggle ===
themeToggleBtn.onclick = ()=>{
    const current=document.documentElement.dataset.theme;
    const next=current==='dark'?'light':'dark';
    document.documentElement.dataset.theme=next;
    themeToggleBtn.textContent=next==='dark'?'☀️':'🌙';
    saveSettings();
};

// === Presenter refresh ===
document.getElementById('refreshPresenter').onclick = ()=> presenterFrame.contentWindow.location.reload();

// === Scale presenter dynamically ===
function scalePresenter(){
    const wrapperW = presenterWrapper.clientWidth;
    const wrapperH = presenterWrapper.clientHeight;
    const originalW = <?= $screenWidth ?>;
    const originalH = <?= $screenHeight ?>;
    const scale = Math.min(wrapperW/originalW, wrapperH/originalH);
    presenterInner.style.transform = `scale(${scale})`;
}
window.addEventListener('resize', scalePresenter);
scalePresenter();

// === Resizable columns ===
function makeResizable(resizer,leftIdx,rightIdx){
    const container=document.querySelector('.main-container');
    let startX,startCols;
    resizer.addEventListener('mousedown', e=>{
        startX=e.clientX;
        startCols=window.getComputedStyle(container).gridTemplateColumns.split(' ');
        document.addEventListener('mousemove', mouseMove);
        document.addEventListener('mouseup', mouseUp);
    });
    function mouseMove(e){
        const dx=e.clientX-startX;
        const totalWidth=container.offsetWidth;
        let left=parseFloat(startCols[leftIdx]);
        let right=parseFloat(startCols[rightIdx]);
        const newLeft=Math.max(150,left+dx);
        const newRight=Math.max(200,right-dx);
        const mid = totalWidth-newLeft-newRight-10;
        container.style.gridTemplateColumns = `${newLeft}px 5px ${mid}px 5px ${newRight}px`;
        scalePresenter();
    }
    function mouseUp(){
        document.removeEventListener('mousemove', mouseMove);
        document.removeEventListener('mouseup', mouseUp);
    }
}
makeResizable(document.getElementById('resizerLeft'),0,4);
makeResizable(document.getElementById('resizerRight'),4,0);

// === YouTube video ===
function getYoutubeVideoId(url){ const m=url.match(/(?:youtu\.be\/|v=)([^&]+)/); return m?m[1]:''; }
function loadYoutubeVideo(){
    const url=localStorage.getItem('youtubeUrl');
    if(!url) return;
    const vid=getYoutubeVideoId(url);
    youtubeFrameWrapper.innerHTML = vid ? `<iframe src="https://www.youtube.com/embed/${vid}?autoplay=1&mute=1&playsinline=1&enablejsapi=1&vq=hd720&speed=2" allow="autoplay"></iframe>`:'';
}
document.getElementById('saveYoutubeSettings').onclick = ()=>{
    const url=document.getElementById('youtubeUrl').value.trim();
    if(url){ localStorage.setItem('youtubeUrl',url); loadYoutubeVideo(); }
    closePopup('youtubeSettingsPopup');
};
document.getElementById('refreshYoutube').onclick=loadYoutubeVideo;

// === YouTube Chat ===
let nextPageToken='';
function loadYoutubeChat(){
    const apiKey=localStorage.getItem('youtubeApiKey');
    const url=localStorage.getItem('youtubeUrl');
    if(!apiKey || !url) return;
    const vid=getYoutubeVideoId(url);
    if(!vid) return;

    fetch(`https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id=${vid}&key=${apiKey}`)
    .then(r=>r.json())
    .then(data=>{
        const chatId=data.items?.[0]?.liveStreamingDetails?.activeLiveChatId;
        if(chatId) pollChat(chatId,apiKey);
    }).catch(console.error);
}
function pollChat(chatId,apiKey){
    fetch(`https://www.googleapis.com/youtube/v3/liveChat/messages?liveChatId=${chatId}&part=snippet,authorDetails&key=${apiKey}${nextPageToken?`&pageToken=${nextPageToken}`:''}`)
    .then(r=>r.json())
    .then(data=>{
        nextPageToken=data.nextPageToken||'';
        const ul=document.getElementById('youtubeChatMessages');
        data.items.forEach(item=>{
            const li=document.createElement('li');
            const msg=document.createElement('span');
            msg.textContent=`${item.authorDetails.displayName}: ${item.snippet.displayMessage}`;
            li.appendChild(msg);
            const btn=document.createElement('button');
            btn.textContent='➕';
            btn.onclick=()=>{
                const params=new URLSearchParams({ title:item.authorDetails.displayName, content:item.snippet.displayMessage });
                fetch('api/save_message.php',{ method:'POST', body:params }).then(()=>loadMessages());
            };
            li.appendChild(btn);
            ul.appendChild(li);
        });
        const timeout=data.pollingIntervalMillis||5000;
        setTimeout(()=>pollChat(chatId,apiKey), timeout);
    }).catch(console.error);
}
document.getElementById('saveYoutubeApiKey').onclick = ()=>{
    const key=document.getElementById('youtubeApiKey').value.trim();
    if(key){ localStorage.setItem('youtubeApiKey',key); loadYoutubeChat(); }
    closePopup('youtubeApiSettingsPopup');
};

// === Initial load ===
loadMessages();
loadYoutubeVideo();
loadYoutubeChat();
</script>
</body>
</html>
