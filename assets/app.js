document.addEventListener('DOMContentLoaded', () => {
  const messageForm = document.getElementById('messageForm');
  const messageInput = document.getElementById('messageInput');
  const messagesList = document.getElementById('messagesList');
  const settingsForm = document.getElementById('settingsForm');

  async function fetchMessages() {
    try {
      const res = await fetch('api/get_messages.php');
      return await res.json();
    } catch (err) { console.error(err); return []; }
  }

  async function loadMessages() {
    const data = await fetchMessages();
    messagesList.innerHTML = '';
    if (!data.messages || data.messages.length === 0) {
      messagesList.innerHTML = '<li>(no messages)</li>';
      return;
    }
    data.messages.forEach(msg => {
      const li = document.createElement('li');
      li.dataset.id = msg.id;
      li.className = msg.is_active ? 'active' : '';
      li.textContent = msg.content;

      // Activate button
      const actBtn = document.createElement('button');
      actBtn.textContent = 'Show';
      actBtn.onclick = () => setActive(msg.id);

      // Edit button
      const editBtn = document.createElement('button');
      editBtn.textContent = 'Edit';
      editBtn.onclick = () => editMessage(msg.id, msg.content);

      // Delete button
      const delBtn = document.createElement('button');
      delBtn.textContent = 'Delete';
      delBtn.onclick = () => deleteMessage(msg.id);

      li.appendChild(actBtn);
      li.appendChild(editBtn);
      li.appendChild(delBtn);
      messagesList.appendChild(li);
    });
  }

  // Add new message
  messageForm.addEventListener('submit', async e => {
    e.preventDefault();
    const content = messageInput.value.trim();
    if (!content) return;
    try {
      const res = await fetch('api/save_message.php', {
        method: 'POST',
        body: new URLSearchParams({ content })
      });
      const out = await res.json();
      if (out.ok) {
        messageInput.value = '';
        loadMessages();
      } else alert('Error: ' + out.error);
    } catch (err) { console.error(err); }
  });

  // Set active
  async function setActive(id) {
    try {
      const res = await fetch('api/set_active.php', {
        method: 'POST',
        body: new URLSearchParams({ active_id: id })
      });
      const out = await res.json();
      if (out.ok) loadMessages();
    } catch (err) { console.error(err); }
  }

  // Edit message
  function editMessage(id, content) {
    const newContent = prompt('Edit message:', content);
    if (newContent === null) return;
    fetch('api/save_message.php', {
      method: 'POST',
      body: new URLSearchParams({ id, content: newContent })
    }).then(res => res.json()).then(out => {
      if (out.ok) loadMessages();
      else alert('Error: ' + out.error);
    }).catch(err => console.error(err));
  }

  // Delete message
  function deleteMessage(id) {
    if (!confirm('Delete this message?')) return;
    fetch('api/delete_message.php', {
      method: 'POST',
      body: new URLSearchParams({ id })
    }).then(res => res.json()).then(out => {
      if (out.ok) loadMessages();
      else alert('Error: ' + out.error);
    }).catch(err => console.error(err));
  }

  // Save settings
  settingsForm.addEventListener('submit', e => {
    e.preventDefault();
    const formData = new FormData(settingsForm);
    for (const [k, v] of formData.entries()) {
      fetch('api/save_setting.php', { method: 'POST', body: new URLSearchParams({ k, v }) })
        .then(res => res.json())
        .then(out => { if (!out.ok) alert('Error saving ' + k); })
        .catch(err => console.error(err));
    }
  });

  // Initial load
  loadMessages();
});
