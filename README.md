# BABA PANEL v2.0 Final
Premium Telegram Subscription Bot + Admin Panel

## Features

### Panel
- Modern dark theme
- Dashboard with live stats + 7 day earning graph
- Start Video + Welcome Message setup
- Plans: Add / Edit / Delete
- Payment Settings: UPI + QR (one time)
- Waiting / Approved / Rejected images
- Pending Payments with Approve / Reject
- Users list (All / Premium / Free)
- Broadcast (Text / Photo / Video) + Buy Now button
- Groups / Channels management
- Theme colours (also affect bot buttons)
- Backup export
- Easy new bot setup (just change Token + Chat IDs)

### Bot
- /start → Video + Welcome + Plan buttons
- Plan selection → UPI + instructions
- Screenshot → Auto pending + Admin notify
- File ID feature (send media to bot as admin → get file_id)
- New user log to channel + DM Now button
- Payment proof channel backup
- Premium status on approve

---

## Installation

1. Upload entire `baba-panel` folder to your hosting
2. Make sure PHP 7.4+ and `curl` + `sqlite` enabled
3. Open: `https://yourdomain.com/baba-panel/`
4. Login:
   - Username: `baba`
   - Password: `baba123`
5. **Immediately change password** from Settings
6. Go to **Settings** → Put your Bot Token + Admin Chat ID + Channel IDs
7. Open: `https://yourdomain.com/baba-panel/bot/set_webhook.php`
8. Done!

## Folder Permissions
Make sure `uploads/` folder is writable (755 or 775)

## Default Login
- User: baba
- Pass: baba123

**Change it after first login.**

## How to get File IDs
1. Send any Video/Photo to your bot (from admin account)
2. Bot will reply with `file_id`
3. Copy and paste in Panel (Start Message etc.)

## Notes
- Database is SQLite (`database.sqlite`) – auto created
- If bot is deleted, just put new Bot Token in Settings
- All data remains safe

Created by Baba | 2026
