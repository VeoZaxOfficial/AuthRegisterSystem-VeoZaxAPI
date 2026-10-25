# 🔐 AuthRegisterSystem

**A lightweight register & login system for [VeoZaxAPI](https://github.com/VeoZaxOfficial/Multiversion-API-0.14.x-0.15.10-1.1.x-VeoZax-API-Build-3.0).**

Players have to `/register` and `/login` before they can move, chat, build, fight or run commands. Works as a `.phar` or as a plain source folder.

> Made by **VeoZax** for VeoZaxAPI - PocketMine-MP.

---

## ✨ Features

- **Register / Login / Change password** with simple in-game commands
- **Full lockdown until logged in** - unauthenticated players cannot:
  - move
  - chat (and they don't receive chat from others either)
  - use any command except `/register`, `/login` and `/changepassword`
  - take damage, break or place blocks, or drop items
- **Brute-force protection** - 3 wrong passwords and the player is kicked
- **Auto-kick on timeout** - players who haven't registered or logged in within **60 seconds** are kicked
- **Message filter** - messages from other plugins are hidden from players who aren't logged in yet, so the auth prompts stay visible
- **Admin command** - `/auth remove <player>` lets operators delete a player's stored account (e.g. for a forgotten password)
- **Folder or phar** - runs from a `.phar` or straight from the source folder
- Zero dependencies, no database - data is stored in `players.yml`

---

## 📋 Requirements

- A server running using **VeoZaxAPI**

---

## 📥 Installation

### As a `.phar`
1. Download the latest `AuthRegisterSystem.phar`
2. Drop it into your server's `plugins/` folder.
3. Restart the server.

### As a source folder
1. Download this repository as zip.
2. Put the `AuthRegisterSystem` folder into your server's `plugins/` folder.
   The folder must contain `plugin.yml` and a `src/` directory directly inside it.
3. Open `veozaxapi.yml` in your server's root folder and enable folder loading:
   ```yaml
   developer:
     folder-plugin-loader: true
   ```
4. Restart the server. (A reload is not enough - this setting is read at startup.)

---

## 💬 Commands

| Command | Description | Who can use it |
|---|---|---|
| `/register <password>` | Create your account password (minimum 6 characters) | Everyone |
| `/login <password>` | Log in to your account | Everyone |
| `/changepassword <new> <new>` | Change your password (must be logged in; both entries must match, minimum 6 characters) | Everyone |
| `/auth remove <player>` | Delete a player's stored account data | Operators |

## 🔑 Permissions

| Permission | Description | Default |
|---|---|---|
| `authregistersystem.admin` | Use the `/auth` admin command | `op` |

---

## ⚙️ How it works

1. A player joins and is shown either the register prompt (new player) or the login prompt (returning player).
2. Until they authenticate, movement, chat, commands, damage, building and item dropping are all blocked.
3. After `/register` or `/login` succeeds, they're free to play.
4. If they don't finish within 60 seconds, or fail `/login` three times, they're kicked and can rejoin to try again.

All messages are built into the plugin and can be edited in `AuthModule.php` (the `$messages` array).

---

## 🗂️ Data & configuration

- Accounts are stored in `plugins/AuthRegisterSystem/players.yml`.
- `config.yml` is currently a placeholder and has no options yet.

---

## ⚠️ Security notes

Please read these before using the plugin on a public server:

- **Passwords are stored as plain text** in `players.yml`. Keep that file private and don't reuse a password you use elsewhere.
- The registration success message **displays the password in chat**.
- `/changepassword` does not ask for the old password; it only requires the player to be logged in.
- The player's IP address is saved in `players.yml` when they first join.

Password hashing (`password_hash()` / `password_verify()`) is a good candidate for a future update.

---

## 🤝 Contributing

Issues and pull requests are welcome.

If you're reporting a bug, please include your PocketMine-MP version and relevant server log output.

---

## Credits

Developed by **[VeoZax](https://github.com/VeoZaxOfficial)**

---

## License

Released under the [MIT License](LICENSE). Feel free to use, modify, and redistribute with credit to the author VeoZax.

---

## 🔗 Links

<p align="left">
  <a href="https://info.veozax.xyz/"><img src="https://img.shields.io/badge/Website-000000?style=for-the-badge&logo=googlechrome&logoColor=white" alt="Website"/></a>
  <a href="https://www.youtube.com/channel/UC33-nbRHQn-3H56aQc0sanA"><img src="https://img.shields.io/badge/YouTube-FF0000?style=for-the-badge&logo=youtube&logoColor=white" alt="YouTube"/></a>
  <a href="https://discord.gg/dCzgPYam2J"><img src="https://img.shields.io/badge/Discord-5865F2?style=for-the-badge&logo=discord&logoColor=white" alt="Discord"/></a>
  <a href="https://www.instagram.com/veozax"><img src="https://img.shields.io/badge/Instagram-E4405F?style=for-the-badge&logo=instagram&logoColor=white" alt="Instagram"/></a>
  <a href="https://www.tiktok.com/@veozaxofficial"><img src="https://img.shields.io/badge/TikTok-000000?style=for-the-badge&logo=tiktok&logoColor=white" alt="TikTok"/></a>
  <a href="https://www.facebook.com/VeoZax/"><img src="https://img.shields.io/badge/Facebook-1877F2?style=for-the-badge&logo=facebook&logoColor=white" alt="Facebook"/></a>
  <a href="https://github.com/VeoZaxOfficial"><img src="https://img.shields.io/badge/GitHub-181717?style=for-the-badge&logo=github&logoColor=white" alt="GitHub"/></a>
</p>
