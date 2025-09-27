# Presenter Queue Project - Development Notes

## Project Overview
A live-event system where:
- **Producer panel** controls messages, settings, and active messages.
- **Presenter screen** displays the active message in real-time.
- Messages and settings are stored in **MySQL**.
- Supports optional **titles**, **font size/color**, **theme toggle**, and **active message management**.

## Current Features
- Producer panel:
  - Create/Edit/Delete messages
  - Optional title for messages
  - Show/Remove active messages
  - Font size & color customization
  - Light/Dark theme toggle (top-right icon)
  - Logout (top-right icon)
- Presenter screen:
  - Displays active message + title
  - Applies font/color/theme settings
  - Smooth fade-in transitions
  - Real-time updates (polling every 2s)
- APIs:
  - `save_message.php` – saves message (with optional title)
  - `get_messages.php` – retrieves all messages with active state
  - `get_state.php` – retrieves active message and settings
  - `set_active.php` – sets active message
  - `save_settings.php` – updates font/color/theme
  - `delete_message.php` – deletes message

## Database Structure
### `messages`
- `id` INT AUTO_INCREMENT
- `title` VARCHAR(255) NULL
- `content` TEXT
- `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP

### `state`
- `id` INT PRIMARY KEY (always 1)
- `active_message_id` INT NULL

### `settings`
- `k` VARCHAR(50) PRIMARY KEY
- `v` VARCHAR(255)

## Development Notes
- Polling intervals:
  - Presenter: 2s
  - Producer: 15s
- CSS transitions for smooth message fade
- All inputs validated on server-side
- Optional features can be toggled by producer:
  - Title display
  - Font size/color adjustments
  - Theme toggle

## Next Steps / TODO
- [ ] Add message expiration or scheduled display times
- [ ] Add multi-presenter support
- [ ] Optimize API calls using websockets or Server-Sent Events
- [ ] Add producer user management
- [ ] Mobile-friendly producer panel improvements
- [ ] Optional: sound/alert when a new message is activated
- [ ] Optional: queue ordering or prioritization
