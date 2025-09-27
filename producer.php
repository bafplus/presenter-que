<?php
session_start();
require __DIR__ . '/config/db.php';

if (empty($_SESSION['producer_logged'])) {
    header('Location: login.php');
    exit;
}

// Load current settings
$stmt = $pdo->query("SELECT k, v FROM settings");
$settings = [];
foreach ($stmt as $row) {
    $settings[$row['k']] = $row['v'];
}
$fontSize = $settings['font_size'] ?? '48';
$color    = $settings['color'] ?? '#ffffff';
$theme    = $settings['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
    <meta charset="UTF-8">
    <title>Producer Control</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        :root {
            --bg-light: #f4f4f4;
            --text-light: #000;
            --bg-dark: #121212;
            --text-dark: #f4f4f4;
        }
        [data-theme="light"] body { background: var(--bg-light); color: var(--text-light); }
        [data-theme="dark"]  body { background: var(--bg-dark);  color: var(--text-dark); }

        body { font-family: Arial, sans-serif; padding:20px; position: relative; }
        h1 { margin-bottom:20px; }

        #messages { list-style:none; padding:0; }
        #messages li { background:#fff; padding:10px; margin-bottom:10px; border-radius:5px;
                       display:flex; align-items:center; justify-content:space-between; transition: background 0.3s; }
        [data-theme="dark"] #messages li { background:#222; }
        #messages li.active { border: 2px solid #4CAF50; background-color: #e8f5e9; }
        [data-theme="dark"] #messages li.active { background-color: #2c3e50; }
        #messages span.text { flex:1; margin-right:10px; }

        button { margin-left:5px; padding:6px 12px; font-size:16px; border-radius:4px; cursor:pointer; }
        #newTitle, #newMessage { width:100%; padding:8px; font-size:16px; margin-bottom:5px; }

        .settings-box { margin-top:20px; padding:10px; border:1px solid #ccc; border-radius:5px; }

        /* Top-right icons */
        .top-right {
            position: fixed;
            top: 15px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        .top-right button {
            background: transparent;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: inherit;
        }
        .top-right button:focus { outline: none; }

        @media(max-width:600px){
            #messages li { flex-direction: column; align-items: flex-start; }
            #messages button { margin:5px 0 0 0; width:100%; }
        }
    </style>
</head>
<body>
    <!-- Top-right controls -->
    <div class="top-right">
        <button id="themeToggle" title="Toggle theme"><?= $theme === 'dark' ? '☀️' : '🌙' ?></button>
        <a href="logout.php" title="Logout" style="font-size:24px; text-decoration:none; color:inherit;">🔒</a>
    </div>

    <h1>Producer Panel</h1>

    <h2>Create / Edit Message</h2>
    <input type="hidden" id="editId" value="">
    <input type="text" id="newTitle" placeholder="Optional title">
    <textarea id="newMessage" rows="3" placeholder="Enter message"></textarea><br>
    <button id="saveMessage">Save Message</button>

    <h2>Messages</h2>
    <ul id="messages"></ul>

    <div class="settings-box">
        <h2>Presenter Settings</h2>
        <label>
            Font size:
            <input type="number" id="fontSize" value="<?= htmlspecialchars($fontSize) ?>" min="10" max="200">
        </label><br><br>
        <label>
            Text color:
            <input type="color" id="textColor" value="<?= htmlspecialchars($color) ?>">
        </label>
    </div>

<script>
const listEl = document.getElementById('messages');
const newTitleEl = document.getElementById('newTitle');
const newMsgEl = document.getElementById('newMessage');
const editIdEl = document.getElementById('editId');
const saveBtn = document.getElementById('saveMessage');
const fontSizeEl = document.getElementById('fontSize');
const colorEl = document.getElementById('color');
const themeToggleBtn = document.getElementById('themeToggle');

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

function loadMessages(){
    fetch('api/get_messages.php')
        .then(r=>r.json())
        .then(res=>{
            if(!res.ok) return console.error(res.error);
            listEl.innerHTML = '';
            res.messages.forEach(m=>{
                const li = document.createElement('li');
                li.className = m.is_active ? 'active' : '';

                const span = document.createElement('span');
                span.className = 'text';
                span.textContent = m.title ? m.title + ': ' + m.content : m.content;
                li.appendChild(span);

                const btnShowRemove = document.createElement('button');
                if (m.is_active) {
                    btnShowRemove.textContent = 'Remove';
                    btnShowRemove.style.background = '#ff9800';
                    btnShowRemove.onclick = () => setActive(null);
                } else {
                    btnShowRemove.textContent = 'Show';
                    btnShowRemove.style.background = '#4CAF50';
                    btnShowRemove.style.color = '#fff';
                    btnShowRemove.onclick = () => setActive(m.id);
                }
                li.appendChild(btnShowRemove);

                const btnEdit = document.createElement('button');
                btnEdit.textContent = 'Edit';
                btnEdit.onclick = () => {
                    newTitleEl.value = m.title || '';
                    newMsgEl.value = m.content;
                    editIdEl.value = m.id;
                    newMsgEl.focus();
                };
                li.appendChild(btnEdit);

                const btnDel = document.createElement('button');
                btnDel.textContent = 'Delete';
                btnDel.onclick = () => {
                    if(confirm('Delete this message?')){
                        fetch('api/delete_message.php',{
                            method:'POST',
                            body:new URLSearchParams({id:m.id})
                        }).then(()=>loadMessages());
                    }
                };
                li.appendChild(btnDel);

                listEl.appendChild(li);
            });
        }).catch(err=>console.error(err));
}

function setActive(id){
    const data = new URLSearchParams();
    if (id !== null) data.append('active_id', id);
    fetch('api/set_active.php',{ method:'POST', body:data })
        .then(()=>loadMessages()).catch(err=>console.error(err));
}

function saveSettings(){
    const data = new URLSearchParams({
        font_size: fontSizeEl.value,
        color: colorEl.value,
        theme: document.documentElement.dataset.theme
    });
    fetch('api/save_settings.php',{ method:'POST', body:data }).catch(err=>console.error(err));
}

fontSizeEl.onchange = fontSizeEl.onblur = fontSizeEl.onkeydown = e => { if(e.type==='change'||e.type==='blur'||(e.key==='Enter')) saveSettings(); };
colorEl.onchange = colorEl.onblur = saveSettings;

themeToggleBtn.onclick = () => {
    const current = document.documentElement.dataset.theme;
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.dataset.theme = next;
    themeToggleBtn.textContent = next === 'dark' ? '☀️' : '🌙';
    saveSettings();
};

loadMessages();
setInterval(loadMessages, 15000);
</script>
</body>
</html>


