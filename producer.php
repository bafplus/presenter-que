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
body { margin:0; font-family: Arial, sans-serif; background-color: var(--bg); color: var(--text);}
:root[data-theme="light"] { --bg:#f0f0f0; --text:#000; --card-bg:#fff; --border-color: rgba(0,0,0,0.1);}
:root[data-theme="dark"]  { --bg:#121212; --text:#f4f4f4; --card-bg:#222; --border-color: rgba(255,255,255,0.1); }

header { display:flex; justify-content:space-between; align-items:center; padding:10px 20px; background-color: var(--card-bg); border-bottom: 1px solid #888;}
header h1 { margin:0; font-size:1.5em; }
header button, header a { background:transparent; border:none; font-size:24px; cursor:pointer; color:inherit; text-decoration:none;}

.main-container { display:flex; height: calc(100vh - 60px);}
.column { flex:1; display:flex; flex-direction:column; padding:10px; }
.middle-column { flex:2; display:flex; flex-direction:column; }
.right-column { flex:1; }

#messagesContainer { flex:1; overflow-y:auto; margin-bottom:10px; background-color: var(--card-bg); border-radius:5px; padding:5px; }
#messages { list-style:none; padding:0; margin:0; }
#messages li { display:flex; justify-content: space-between; align-items: center; background-color: var(--card-bg); margin-bottom:0; padding:10px; border-radius: 6px; border-bottom: 1px solid var(--border-color);}
#messages li:last-child { border-bottom:none; }
#messages li.active { border:2px solid #4CAF50; }
#messages li span { flex:1; margin-right:10px; }
#messages li:nth-child(odd) { background-color: rgba(76, 175, 80, 0.05);}
:root[data-theme="dark"] #messages li:nth-child(odd) { background-color: rgba(255,255,255,0.05);}
#messages li button { font-size: 20px; padding: 6px 10px; background-color: var(--card-bg); color: var(--text); border: 1px solid var(--border-color); border-radius: 4px; cursor: pointer; margin-left:5px; transition: background 0.2s, color 0.2s;}
#messages li button:hover { background-color:#4CAF50; color:#fff; }

#newMessageBox input,#newMessageBox textarea,#newMessageBox button { width: calc(100% - 10px); box-sizing: border-box; margin-bottom:10px; padding:5px 10px; font-size:14px; background-color: var(--card-bg); color: var(--text); border:1px solid var(--border-color); border-radius:4px;}
#newMessageBox input,#newMessageBox textarea { padding-right:10px;}
#newMessageBox button { font-size:16px; cursor:pointer;}

.settings-box { background-color: var(--card-bg); padding:10px; border-radius:5px; height:auto; overflow:auto; }

.presenter-view-box, .youtube-view-box { margin-top:20px; background-color: var(--card-bg); border-radius:5px; padding:10px; flex:none; display:flex; flex-direction:column;}
.presenter-view-box h3, .youtube-view-box h3 { margin:0 0 10px 0; font-size:1em; font-weight:bold; display:flex; justify-content:space-between; align-items:center;}
.presenter-view-box h3 span button, .youtube-view-box h3 span button { font-size:14px; padding:2px 6px; cursor:pointer; margin-left:5px; }

.presenter-frame-wrapper, .youtube-frame-wrapper { position: relative; width:100%; border:1px solid var(--border-color); border-radius:4px; overflow:hidden; }
.presenter-frame-wrapper { padding-top: calc( (<?= $screenHeight ?> / <?= $screenWidth ?>) * 100% ); }
.presenter-frame-inner { position:absolute; top:0; left:0; width:100%; height:100%; transform-origin: top left; overflow:hidden; }
.presenter-frame-inner iframe { position:absolute; top:0; left:0; width:100%; height:100%; border:0; }

.youtube-frame-wrapper { padding-top:0; } /* removed extra height */
.youtube-frame-wrapper iframe { position:absolute; top:0; left:0; width:100%; height:100%; border:0; }

.settings-popup { display:none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;}
.settings-popup-content { background-color: var(--card-bg); color: var(--text); padding:20px; border-radius:8px; width:300px; max-width:90%; display:flex; flex-direction:column;}
.settings-popup-content h3 { display:flex; justify-content: space-between; align-items:center; margin:0 0 15px 0; }
.settings-box-popup label { display:block; margin-bottom:10px;}
.settings-box-popup input[type="number"], .settings-box-popup input[type="color"], .settings-box-popup input[type="text"], .settings-box-popup input[type="checkbox"] { margin-left:10px; background-color: var(--card-bg); color: var(--text); border:1px solid var(--border-color); border-radius:4px;}
.settings-box-popup button { margin-top:10px; width:100%; padding:8px; font-size:14px; cursor:pointer;}

button { background-color: var(--card-bg); color: var(--text); border:1px solid var(--border-color); border-radius:4px; cursor:pointer; padding:5px 10px; transition: background 0.2s,color 0.2s;}
button:hover { background-color:#4CAF50; color:#fff;}
</style>
</head>
<body>

<header>
<h1>Producer Control</h1>
<div>
<button id="themeToggle"><?= $theme==='dark'?'☀️':'🌙' ?></button>
<button id="fullscreenToggle" title="Fullscreen">⛶</button>
<a href="logout.php">🔒</a>
</div>
</header>

<div class="main-container">
<div class="column left-column"></div>

<div class="column middle-column">
<h2>Messages Queue</h2>
<div id="messagesContainer"><ul id="messages"></ul></div>

<div id="newMessageBox">
<h2>Create / Edit Message</h2>
<input type="hidden" id="editId" value="">
<input type="text" id="newTitle" placeholder="Optional title">
<textarea id="newMessage" rows="3" placeholder="Enter message"></textarea>
<button id="saveMessage">💾 Save</button>
</div>
</div>

<div class="column right-column">
<div class="settings-box">
<h2>Presenter Settings</h2>
<!-- Empty for future use -->
</div>

<div class="presenter-view-box">
<h3>
Presenter view
<span>
<button id="refreshPresenter" title="Reload preview">🔄</button>
<button id="openSettingsPopup" title="Open settings">⚙️</button>
</span>
</h3>
<div class="presenter-frame-wrapper">
<div class="presenter-frame-inner">
<iframe id="presenterFrame" src="presenter.php"></iframe>
</div>
</div>
</div>

<div class="youtube-view-box">
<h3>
YouTube Live
<span>
<button id="refreshYoutube" title="Reload video">🔄</button>
<button id="openYoutubeSettings" title="YouTube settings">⚙️</button>
</span>
</h3>
<div class="youtube-frame-wrapper">
<!-- iframe will be added dynamically -->
</div>
</div>

<div id="settingsPopup" class="settings-popup">
<div class="settings-popup-content">
<h3>Presenter Settings <button id="closeSettingsPopup">✖</button></h3>
<div class="settings-box-popup">
<label>Screen Width: <input type="number" id="popupScreenWidth" min="100" max="3840"></label>
<label>Screen Height: <input type="number" id="popupScreenHeight" min="100" max="2160"></label>
<label>Header Height: <input type="number" id="popupHeaderHeight" min="10" max="500"></label>
<label>Clock Enabled: <input type="checkbox" id="popupClockEnabled"></label>
<label>24h Clock: <input type="checkbox" id="popupClock24h"></label>
<label>Text Color: <input type="color" id="popupColor"></label>
<button id="savePopupSettings">💾 Save Settings</button>
</div>
</div>
</div>

<div id="youtubeSettingsPopup" class="settings-popup">
<div class="settings-popup-content">
<h3>YouTube Settings <button id="closeYoutubeSettings">✖</button></h3>
<div class="settings-box-popup">
<label>YouTube URL: <input type="text" id="youtubeUrl" placeholder="Enter full YouTube URL"></label>
<button id="saveYoutubeSettings">💾 Save Settings</button>
</div>
</div>
</div>

<script>
// --- Messages ---
const listEl=document.getElementById('messages');
const newTitleEl=document.getElementById('newTitle');
const newMsgEl=document.getElementById('newMessage');
const editIdEl=document.getElementById('editId');
const saveBtn=document.getElementById('saveMessage');

saveBtn.onclick=()=>{
    const id=editIdEl.value.trim();
    const title=newTitleEl.value.trim();
    const content=newMsgEl.value.trim();
    if(!content)return alert('Message cannot be empty');
    const data=new URLSearchParams({content,title});
    if(id)data.append('id',id);
    fetch('api/save_message.php',{method:'POST',body:data}).then(r=>r.json()).then(res=>{
        if(res.ok){newTitleEl.value='';newMsgEl.value='';editIdEl.value='';loadMessages();}
        else alert(res.error||'Error saving message');
    }).catch(()=>alert('Network error'));
};

function loadMessages(){
    fetch('api/get_messages.php').then(r=>r.json()).then(res=>{
        if(!res.ok) return console.error(res.error);
        listEl.innerHTML='';
        res.messages.forEach(m=>{
            const li=document.createElement('li'); li.className=m.is_active?'active':'';
            const span=document.createElement('span'); span.textContent=m.title?m.title+': '+m.content:m.content; li.appendChild(span);
            const btnShow=document.createElement('button'); btnShow.textContent=m.is_active?'❌':'▶️'; btnShow.onclick=()=>setActive(m.is_active?null:m.id); li.appendChild(btnShow);
            const btnEdit=document.createElement('button'); btnEdit.textContent='✏️'; btnEdit.onclick=()=>{newTitleEl.value=m.title||''; newMsgEl.value=m.content; editIdEl.value=m.id; newMsgEl.focus();}; li.appendChild(btnEdit);
            const btnDel=document.createElement('button'); btnDel.textContent='🗑️'; btnDel.onclick=()=>{if(confirm('Delete this message?')) fetch('api/delete_message.php',{method:'POST',body:new URLSearchParams({id:m.id})}).then(()=>loadMessages());}; li.appendChild(btnDel);
            listEl.appendChild(li);
        });
    }).catch(console.error);
}

function setActive(id){
    const data=new URLSearchParams();
    if(id!==null)data.append('active_id',id);
    fetch('api/set_active.php',{method:'POST',body:data}).then(r=>r.json()).then(res=>{if(!res.ok) console.error(res.error); loadMessages();}).catch(console.error);
}

// --- Presenter ---
const themeToggleBtn=document.getElementById('themeToggle');
const fullscreenBtn=document.getElementById('fullscreenToggle');
const refreshBtn=document.getElementById('refreshPresenter');
const settingsPopup=document.getElementById('settingsPopup');
const openSettingsPopupBtn=document.getElementById('openSettingsPopup');
const closeSettingsPopupBtn=document.getElementById('closeSettingsPopup');
const savePopupSettingsBtn=document.getElementById('savePopupSettings');
const popupScreenWidth=document.getElementById('popupScreenWidth');
const popupScreenHeight=document.getElementById('popupScreenHeight');
const popupHeaderHeight=document.getElementById('popupHeaderHeight');
const popupClockEnabled=document.getElementById('popupClockEnabled');
const popupClock24h=document.getElementById('popupClock24h');
const popupColor=document.getElementById('popupColor');
const presenterWrapper=document.querySelector('.presenter-frame-wrapper');
const presenterInner=document.querySelector('.presenter-frame-inner');
const presenterFrame=document.getElementById('presenterFrame');

function updatePresenterAspect(){const w=parseInt(popupScreenWidth.value)||<?= $screenWidth ?>;const h=parseInt(popupScreenHeight.value)||<?= $screenHeight ?>;presenterWrapper.style.paddingTop=(h/w*100)+'%';}
function scalePresenterIframe(){const w=parseInt(popupScreenWidth.value)||<?= $screenWidth ?>;const h=parseInt(popupScreenHeight.value)||<?= $screenHeight ?>;const containerWidth=presenterWrapper.clientWidth;const scale=containerWidth/w;presenterInner.style.transform='scale('+scale+')';presenterInner.style.width=w+'px';presenterInner.style.height=h+'px';}

openSettingsPopupBtn.onclick=()=>{popupScreenWidth.value=<?= $screenWidth ?>;popupScreenHeight.value=<?= $screenHeight ?>;popupHeaderHeight.value=<?= $headerHeight ?>;popupClockEnabled.checked=<?= $clockEnabled==='1'?'true':'false' ?>;popupClock24h.checked=<?= $clock24h==='1'?'true':'false' ?>;popupColor.value='<?= $color ?>';settingsPopup.style.display='flex';};
closeSettingsPopupBtn.onclick=()=>settingsPopup.style.display='none';
savePopupSettingsBtn.onclick=()=>{const data=new URLSearchParams({screen_width:popupScreenWidth.value,screen_height:popupScreenHeight.value,header_height:popupHeaderHeight.value,clock_enabled:popupClockEnabled.checked?1:0,clock_24h:popupClock24h.checked?1:0,color:popupColor.value,theme:document.documentElement.dataset.theme});fetch('api/save_settings.php',{method:'POST',body:data}).then(r=>r.json()).then(res=>{if(!res.ok)console.error(res.error);});updatePresenterAspect();scalePresenterIframe();presenterFrame.contentWindow.location.reload();settingsPopup.style.display='none';};
refreshBtn.onclick=()=>presenterFrame.contentWindow.location.reload();
themeToggleBtn.onclick=()=>{const current=document.documentElement.dataset.theme;const next=current==='dark'?'light':'dark';document.documentElement.dataset.theme=next;themeToggleBtn.textContent=next==='dark'?'☀️':'🌙';};
fullscreenBtn.onclick=()=>{if(!document.fullscreenElement)document.documentElement.requestFullscreen().catch(err=>alert('Fullscreen error:'+err.message));else document.exitFullscreen();};

// --- YouTube ---
const refreshYoutubeBtn=document.getElementById('refreshYoutube');
const openYoutubeSettingsBtn=document.getElementById('openYoutubeSettings');
const closeYoutubeSettingsBtn=document.getElementById('closeYoutubeSettings');
const saveYoutubeSettingsBtn=document.getElementById('saveYoutubeSettings');
const youtubeUrlInput=document.getElementById('youtubeUrl');
const youtubeWrapper=document.querySelector('.youtube-frame-wrapper');

function getYoutubeVideoId(url){const match=url.match(/(?:v=|\/)([0-9A-Za-z_-]{11})/);return match?match[1]:'';}

function scaleYoutubeFrame(){
    const width=youtubeWrapper.clientWidth;
    const height=width*9/16;
    youtubeWrapper.style.height=height+'px';
    const iframe=youtubeWrapper.querySelector('iframe');
    if(iframe){iframe.width=width; iframe.height=height;}
}

function loadYoutubeVideo(){
    const url=localStorage.getItem('youtubeUrl')||'';
    const videoId=getYoutubeVideoId(url);
    if(!videoId) return;
    let iframe=youtubeWrapper.querySelector('iframe');
    if(!iframe){
        iframe=document.createElement('iframe');
        iframe.setAttribute('allow','autoplay; encrypted-media');
        iframe.setAttribute('allowfullscreen','');
        iframe.src=`https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&controls=1&playsinline=1`;
        youtubeWrapper.appendChild(iframe);
    } else {
        iframe.src=`https://www.youtube.com/embed/${videoId}?autoplay=1&mute=1&controls=1&playsinline=1`;
    }
    scaleYoutubeFrame();
}

openYoutubeSettingsBtn.onclick=()=>{youtubeUrlInput.value=localStorage.getItem('youtubeUrl')||'';document.getElementById('youtubeSettingsPopup').style.display='flex';};
closeYoutubeSettingsBtn.onclick=()=>document.getElementById('youtubeSettingsPopup').style.display='none';
saveYoutubeSettingsBtn.onclick=()=>{const url=youtubeUrlInput.value.trim(); if(!url) return alert('Enter a valid YouTube URL'); localStorage.setItem('youtubeUrl',url); loadYoutubeVideo(); document.getElementById('youtubeSettingsPopup').style.display='none';};
refreshYoutubeBtn.onclick=()=>{loadYoutubeVideo();};

window.addEventListener('resize',()=>{scalePresenterIframe(); scaleYoutubeFrame();});
window.addEventListener('load',()=>{updatePresenterAspect(); scalePresenterIframe(); scaleYoutubeFrame();});
loadMessages(); setInterval(loadMessages,15000);
</script>

</body>
</html>
