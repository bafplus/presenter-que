# Presenter Queue System
test
## Overview
This project provides a live-event message system with:

- **Producer panel**: create/edit/delete messages, manage active messages, adjust font/color/theme, and optional titles.
- **Presenter screen**: displays active messages in real-time with smooth transitions.

All messages and settings are stored in **MySQL**.

---

## Setup Instructions

1. **Configure Database**:
- Create a MySQL database and import `database.sql`.
- Update `config/db.php` with your database credentials.

2. **Start the server**:
- Ensure PHP + MySQL are running.
- Access `producer.php` for the producer panel and `presenter.php` for the presenter screen.

3. **Login for Producer Panel**:
- The login system uses sessions. Check `login.php` for credentials or adjust as needed.

---

## Key Files

- `producer.php` — Producer control panel.
- `presenter.php` — Full-screen presenter view.
- `api/` — All API endpoints:
  - `save_message.php`
  - `get_messages.php`
  - `get_state.php`
  - `set_active.php`
  - `save_settings.php`
  - `delete_message.php`

---

## DEV Notes

For full development context, feature list, and next steps, refer to [`DEV_NOTES.md`](DEV_NOTES.md).

---

## Optional Enhancements

- Multi-presenter support
- Scheduled messages or expiration
- Sound/alert for new messages
- Queue ordering or prioritization
- WebSocket/SSE optimization for real-time updates
