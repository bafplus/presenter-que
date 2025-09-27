<?php
require __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <title>Presenter Screen</title>
    <style>
        :root {
            --bg-light: #ffffff;
            --text-light: #000000;
            --bg-dark: #121212;
            --text-dark: #f4f4f4;
        }
        [data-theme="light"] body { background: var(--bg-light); color: var(--text-light); }
        [data-theme="dark"]  body { background: var(--bg-dark);  color: var(--text-dark); }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            text-align: center;
            transition: background 0.5s, color 0.5s;
        }

        #message {
            max-width: 90%;
        }
        #msgTitle, #msgContent {
            transition: opacity 0.5s ease;
            opacity: 1;
        }
        #msgTitle.fade, #msgContent.fade { opacity: 0; }

        #msgTitle { font-weight: bold; font-size: 1.2em; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div id="message">
        <div id="msgTitle"></div>
        <div id="msgContent"></div>
    </div>

<script>
const titleEl = document.getElementById('msgTitle');
const contentEl = document.getElementById('msgContent');

function updateState(){
    fetch('api/get_state.php')
        .then(r => r.json())
        .then(res => {
            if(!res.ok) return;

            const m = res.message;
            const s = res.settings || {};

            // Fade effect for message change
            if(titleEl.textContent !== (m && m.title ? m.title : '') ||
               contentEl.textContent !== (m ? m.content : '')) {
                titleEl.classList.add('fade');
                contentEl.classList.add('fade');
                setTimeout(()=>{ 
                    titleEl.textContent = m && m.title ? m.title : '';
                    contentEl.textContent = m ? m.content : '';
                    titleEl.classList.remove('fade');
                    contentEl.classList.remove('fade');
                }, 200);
            }

            // Apply settings
            contentEl.style.fontSize = s.font_size ? s.font_size + "px" : "48px";
            if(s.color) contentEl.style.color = s.color;
            document.documentElement.dataset.theme = s.theme === 'dark' ? 'dark' : 'light';
        }).catch(err => console.error(err));
}

// Initial load + polling every 2 seconds
updateState();
setInterval(updateState, 2000);
</script>
</body>
</html>
