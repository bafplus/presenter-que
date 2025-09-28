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
body {
    margin:0;
    font-family: Arial, sans-serif;
    background-color: var(--bg);
    color: var(--text);
}
:root[data-theme="light"] { --bg: #f0f0f0; --text:#000; --card-bg:#fff; }
:root[data-theme="dark"]  { --bg: #121212; --text:#f4f4f4; --card-bg:#222; }

header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px 20px;
    background-color: var(--card-bg);
    border-bottom: 1px solid #888;
}
header h1 { margin:0; font-size:1.5em; }

header button, header a {
    background:transparent;
    border:none;
    font-size:24px;
    cursor:pointer;
    color:inherit;
    text-decoration:none;
}

.main-container {
    display:flex;
    height: calc(100vh - 60px); /* adjust for header */
}

/* Columns */
.column { flex:1; display:flex; flex-direction:column; padding:10px; }
.middle-column { flex:2; display:flex; flex-direction:column; }
.right-column { flex:1; }

#messagesContainer { flex:1; overflow-y:auto; margin-bottom:10px; background-color: var(--card-bg); border-radius:5px; padding:5px; }
#messages { list-style:none; padding:0; margin:0; }
#messages li {
    display:flex;
    justify-content:space-between;
    align-items:center;
    background-color: var(--card-bg);
    margin-bottom:5px;
    padding:5px 10px;
    border-radius:4px;
}
#messages li.active { border:2px solid #4CAF50; }
#messages li span { flex:1; margin-right:10px; }

#newMessageBox input, #newMessageBox textarea, #newMessageBox button { margin-bottom:5px; }
#newMessageBox button { padding:5px 10px; font-size:16px; border-radius:4px; cursor:pointer; }

.settings-box { background-color: var(--card-bg); padding:10px; border-radius:5px; height:100%; overflow:auto; }

</style>
</head>
<body>

<header>
    <h1>Producer Control</h1>
    <div>
        <button id="themeToggle"><?= $theme==='dark'?'☀️':'🌙' ?></button>
        <a href="logout.php">🔒</a>
    </div>
</header>

<div class="main-container">
    <div class="column left-column">
        <!-- Empty for now -->
    </div>

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

    <div class="column right-column">
        <div class="settings-box">
            <h2>Presenter Settings</h2>

            <label>Screen Width: <input type="number" id="screenWidth" value="<?= htmlspecialchars($screenWidth) ?>" min="100" max="3840"></label>
            <label>Screen Height: <input type="number" id="screenHeight" value="<?= htmlspecialchars($screenHeight) ?>" min="100" max="2160"></label>
            <label>Header Height: <input type="number" id="headerHeight" value="<?= htmlspecialchars($headerHeight) ?>" min="10" max="500"></label>
            <label>Clock Enabled: <input type="checkbox" id="clockEnabled" <?= $clockEnabled==='1'?'checked':'' ?>></label>
            <label>24h Clock: <input type="checkbox" id="clock24h" <?= $clock24h==='1'?'checked':'' ?>></label>
            <label>Text Color: <input type="color" id="color" value="<?= htmlspecialchars($color) ?>"></label>
        </div>
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

// Save new message
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
            if(res.ok){
                newTitleEl.value='';
                newMsgEl.value='';
                editIdEl.value='';
                loadMessages();
            } else alert(res.error || 'Error saving message');
        }).catch(err=>alert("Network error"));
};

// Load messages
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
                btnEdit.onclick=()=>{
                    newTitleEl.value=m.title||'';
                    newMsgEl.value=m.content;
                    editIdEl.value=m.id;
                    newMsgEl.focus();
                };
                li.appendChild(btnEdit);

                const btnDel=document.createElement('button');
                btnDel.textContent='🗑️';
                btnDel.onclick=()=>{
                    if(confirm('Delete this message?')){
                        fetch('api/delete_message.php',{ method:'POST', body:new URLSearchParams({id:m.id}) })
                            .then(()=>loadMessages());
                    }
                };
                li.appendChild(btnDel);

                listEl.appendChild(li);
            });
        }).catch(console.error);
}

// Set active message
function setActive(id){
    const data = new URLSearchParams();
    if(id!==null) data.append('active_id',id);
    fetch('api/set_active.php',{ method:'POST', body:data })
        .then(()=>loadMessages()).catch(console.error);
}

// Save settings
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
        .then(r=>r.json())
        .then(res=>{ if(!res.ok) console.error(res.error); })
        .catch(console.error);
}

// Bind changes
[screenWidthEl, screenHeightEl, headerHeightEl, clockEnabledEl, clock24hEl, colorEl].forEach(el=>{
    el.onchange = saveSettings;
});

// Theme toggle
themeToggleBtn.onclick = ()=>{
    const current=document.documentElement.dataset.theme;
    const next=current==='dark'?'light':'dark';
    document.documentElement.dataset.theme=next;
    themeToggleBtn.textContent=next==='dark'?'☀️':'🌙';
    saveSettings();
};

loadMessages();
setInterval(loadMessages,15000);
</script>

</body>
</html>
