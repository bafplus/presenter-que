<?php
session_start();
require __DIR__ . '/config/db.php';

if (empty($_SESSION['producer_logged'])) {
    header('Location: login.php');
    exit;
}

// Load current settings
$stmt = $pdo->query("SELECT k,v FROM settings");
$settings = [];
foreach ($stmt as $row) {
    $settings[$row['k']] = $row['v'];
}

// Load defaults
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
/* Body theme colors */
[data-theme="light"] body {
    background: #f0f0f0;
    color: #000;
}
[data-theme="dark"] body {
    background: #121212;
    color: #f4f4f4;
}

body { font-family: Arial, sans-serif; padding:20px; margin:0; }
h1,h2 { margin-bottom:10px; }
label { display:block; margin-bottom:8px; }
input[type=number] { width:80px; }
.settings-box { margin-top:20px; padding:10px; border:1px solid #ccc; border-radius:5px; }
[data-theme="light"] .settings-box { background:#fff; color:#000; }
[data-theme="dark"] .settings-box { background:#222; color:#f4f4f4; border-color:#444; }

.top-right { position: fixed; top: 15px; right: 20px; display:flex; gap:10px; }
.top-right button { background:transparent; border:none; font-size:24px; cursor:pointer; color:inherit; }
#messages { list-style:none; padding:0; margin-top:10px; }
#messages li { background:#fff; padding:10px; margin-bottom:8px; border-radius:5px; display:flex; justify-content:space-between; align-items:center; }
[data-theme="dark"] #messages li { background:#222; }
#messages li.active { border:2px solid #4CAF50; }
#messages button { margin-left:5px; padding:5px 8px; font-size:16px; border-radius:4px; cursor:pointer; }
.message-buttons { display:flex; gap:5px; }
</style>
</head>
<body>

<div class="top-right">
    <button id="themeToggle" title="Toggle theme"><?= $theme==='dark'?'☀️':'🌙' ?></button>
    <a href="logout.php" style="font-size:24px; text-decoration:none; color:inherit;">🔒</a>
</div>

<h1>Producer Panel</h1>

<h2>Create / Edit Message</h2>
<input type="hidden" id="editId" value="">
<input type="text" id="newTitle" placeholder="Optional title">
<textarea id="newMessage" rows="3" placeholder="Enter message"></textarea><br>
<button id="saveMessage">💾 Save</button>

<h2>Messages Queue</h2>
<ul id="messages"></ul>

<div class="settings-box">
    <h2>Presenter Settings</h2>

    <label>
        Screen Width:
        <input type="number" id="screenWidth" value="<?= htmlspecialchars($screenWidth) ?>" min="100" max="3840">
    </label>

    <label>
        Screen Height:
        <input type="number" id="screenHeight" value="<?= htmlspecialchars($screenHeight) ?>" min="100" max="2160">
    </label>

    <label>
        Header Height:
        <input type="number" id="headerHeight" value="<?= htmlspecialchars($headerHeight) ?>" min="10" max="500">
    </label>

    <label>
        Clock Enabled:
        <input type="checkbox" id="clockEnabled" <?= $clockEnabled==='1'?'checked':'' ?>>
    </label>

    <label>
        24h Clock:
        <input type="checkbox" id="clock24h" <?= $clock24h==='1'?'checked':'' ?>>
    </label>

    <label>
        Text Color:
        <input type="color" id="color" value="<?= htmlspecialchars($color) ?>">
    </label>

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

                const btnContainer = document.createElement('div');
                btnContainer.className = 'message-buttons';

                // Show/Remove
                const btnShow=document.createElement('button');
                btnShow.textContent=m.is_active?'❌':'▶️';
                btnShow.onclick=()=>setActive(m.is_active?null:m.id);
                btnContainer.appendChild(btnShow);

                // Edit
                const btnEdit=document.createElement('button');
                btnEdit.textContent='✏️';
                btnEdit.onclick=()=>{
                    newTitleEl.value=m.title||'';
                    newMsgEl.value=m.content;
                    editIdEl.value=m.id;
                    newMsgEl.focus();
                };
                btnContainer.appendChild(btnEdit);

                // Delete
                const btnDel=document.createElement('button');
                btnDel.textContent='🗑️';
                btnDel.onclick=()=>{
                    if(confirm('Delete this message?')){
                        fetch('api/delete_message.php',{ method:'POST', body:new URLSearchParams({id:m.id}) })
                            .then(()=>loadMessages());
                    }
                };
                btnContainer.appendChild(btnDel);

                li.appendChild(btnContainer);
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
