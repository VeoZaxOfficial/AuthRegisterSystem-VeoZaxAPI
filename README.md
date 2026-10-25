# 🔐 AuthRegisterSystem

**A lightweight register & login system for [VeoZaxAPI](https://info.veozax.xyz).**

Players have to `/register` and `/login` before they can move, chat, build, fight or run commands. Works as a `.phar` or as a plain source folder.

> Made by **VeoZax** for VeoZaxAPI — the legacy-friendly PocketMine-MP API.

---

## ✨ Features

- **Register / Login / Change password** with simple in-game commands
- **Full lockdown until logged in** — unauthenticated players cannot:
  - move
  - chat (and they don't receive chat from others either)
  - use any command except `/register`, `/login` and `/changepassword`
  - take damage, break or place blocks, or drop items
- **Brute-force protection** — 3 wrong passwords and the player is kicked
- **Auto-kick on timeout** — players who haven't registered or logged in within **60 seconds** are kicked
- **Message filter** — messages from other plugins are hidden from players who aren't logged in yet, so the auth prompts stay visible
- **Admin command** — `/auth remove <player>` lets operators delete a player's stored account (e.g. for a forgotten password)
- **Folder or phar** — runs from a `.phar` or straight from the source folder
- Zero dependencies, no database — data is stored in `players.yml`

---

## 📋 Requirements

- A server running **VeoZaxAPI** (`api: ["VeoZaxAPI"]`)

---

## 📥 Installation

### As a `.phar`
1. Download the latest `AuthRegisterSystem.phar` from the [Releases](../../releases) page.
2. Drop it into your server's `plugins/` folder.
3. Restart the server.

### As a source folder
1. Download or clone this repository.
2. Put the `AuthRegisterSystem` folder into your server's `plugins/` folder.
   The folder must contain `plugin.yml` and a `src/` directory directly inside it.
3. Open `veozaxapi.yml` in your server's root folder and enable folder loading:
   ```yaml
   developer:
     folder-plugin-loader: true
   ```
4. Restart the server. (A reload is not enough — this setting is read at startup.)

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

Bug reports and pull requests are welcome. Please open an [issue](../../issues) describing the problem and your VeoZaxAPI version.

---

## 📺 Follow VeoZax

- ▶️ YouTube: [@VeoZax](https://www.youtube.com/@VeoZax)
- 💬 Discord: [discord.gg/dCzgPYam2J](https://discord.gg/dCzgPYam2J)
- 🌐 Website: [info.veozax.xyz](https://info.veozax.xyz)

---

## 📄 License

See the [LICENSE](LICENSE) file.
