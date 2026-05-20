# DevPanel 🚀

DevPanel is a lightweight development panel for Ubuntu built with PHP, XAMPP and JavaScript, designed to centralize and simplify the workflow of web development environments.

The project provides a modern interface to manage services, projects, deployments and Linux tools directly from the browser.

---

# ✨ Features

## 🔐 Security (NEW)

* Password authentication with bcrypt
* CSRF token protection
* Rate limiting (5 attempts in 15 min)
* Session security (httponly, samesite)
* Security headers (CSP, X-Frame-Options)
* Complete audit logging
* Output sanitization (XSS prevention)
* Change password functionality

## 🔧 Service Management

* Start/Stop Apache
* Start/Stop MariaDB
* Real-time service status

## 📁 Project Management

* Automatic project detection
* Open projects in browser
* Open folders directly in Linux
* Open projects in VS Code

## 📦 Deploy & Export

* ZIP export generation
* FTP / Strato deployment
* Dynamic deploy modal

## 🖥 System Tools

* Linux terminal integration
* Real-time Apache logs
* System monitoring
* CPU / RAM / Disk usage

## 🎨 UI

* Modern Bootstrap 5 interface
* Responsive layout
* Sidebar navigation
* Dashboard cards

---

# 🛠 Technologies Used

* PHP 7.4+
* JavaScript
* Bootstrap 5
* XAMPP
* MariaDB
* Apache
* xterm.js
* Ubuntu Linux
* lftp
* bcrypt (password hashing)

---

# 📂 Project Structure

```text
devpanel/
├── api/                     # API endpoints
├── assets/                  # CSS & JS
│   ├── css/
│   └── js/
├── includes/                # PHP utilities
├── layout/                  # Header, Sidebar
├── logs/                    # Audit logs
├── tmp/                     # Temporary files
├── config.php               # Configuration
├── setup.php                # Initial setup
├── login.html               # Login page
├── change_password.php      # Password change
├── index.php                # Dashboard
└── README.md
```

---

# ⚙️ Installation

## 1. Install XAMPP

Download XAMPP for Linux:
https://www.apachefriends.org/

## 2. Clone repository

```bash
git clone https://github.com/overzero18/DevPanel.git
```

## 3. Move project to htdocs

```bash
sudo mv DevPanel /opt/lampp/htdocs/devpanel
```

## 4. Set initial password

```bash
http://localhost/devpanel/setup.php
```

Enter your password (minimum 6 characters) and confirm. This page will auto-delete after first use.

## 5. Start XAMPP

```bash
sudo /opt/lampp/lampp start
```

## 6. Open browser

```
http://localhost/devpanel/login.html
```

---

# 🔑 Password Management

### Initial Setup
1. Visit `http://localhost/devpanel/setup.php`
2. Enter your password (6+ characters)
3. The setup page auto-deletes for security

### Change Password
**Option 1 - From Panel (Recommended):**
- Login to DevPanel
- Navigate to "Cambiar Contraseña" in sidebar
- Enter current password + new password

**Option 2 - Manual:**
```bash
php -r "echo password_hash('new_password', PASSWORD_BCRYPT, ['cost' => 10]);"
```
Then update `/config.php` with the generated hash.

---

# 🛡️ Security Features

### Authentication
- ✅ Bcrypt password hashing (PASSWORD_BCRYPT)
- ✅ Secure password verification
- ✅ Session timeout (1 hour)

### Request Protection
- ✅ CSRF tokens on all POST requests
- ✅ HTTP method validation
- ✅ Input sanitization
- ✅ Output encoding (XSS prevention)

### Rate Limiting
- ✅ Maximum 5 failed login attempts
- ✅ 15-minute lockout window
- ✅ Per-IP tracking

### Session Security
- ✅ HttpOnly cookies
- ✅ SameSite=Strict
- ✅ Secure flag enabled

### Headers
- ✅ Content-Security-Policy
- ✅ X-Frame-Options: DENY
- ✅ X-Content-Type-Options: nosniff
- ✅ X-XSS-Protection

### File Protection
- ✅ config.php blocked from direct access
- ✅ logs/ directory protected
- ✅ tmp/ directory protected
- ✅ .git/ directory protected

### Audit Logging
- ✅ All actions logged to `/logs/actions.log`
- ✅ Includes timestamp, IP, user, action
- ✅ Security events tracked

---

# 📌 Current Features

* ✅ Apache control
* ✅ MariaDB control
* ✅ Linux folder opening
* ✅ VS Code integration
* ✅ ZIP generation
* ✅ FTP deploy
* ✅ Logs viewer
* ✅ Terminal integration
* ✅ Project detection
* ✅ System monitor
* ✅ Password authentication
* ✅ Change password functionality
* ✅ Audit logging
* ✅ CSRF protection
* ✅ Rate limiting

---

# 🔒 Security Notes

DevPanel is intended for:

* local environments
* development environments
* personal workflows
* community use

### Security Best Practices

1. **Use Strong Passwords** - Minimum 6 characters recommended
2. **Don't Expose Publicly** - This is not designed for internet-facing deployments without additional security
3. **Regular Backups** - Backup your projects regularly
4. **Keep Updated** - Pull latest security updates
5. **Review Logs** - Check `/logs/actions.log` regularly

### Default Restrictions

- Only whitelisted commands are allowed in terminal
- Only whitelisted paths can be accessed
- Only authenticated users can perform actions
- All actions are logged

---

# 📝 Allowed Terminal Commands

For security, only these commands are allowed:

- `ls`, `cd`, `pwd`, `cat`, `grep`, `find`
- `git`, `svn`
- `npm`, `composer`, `php`, `python`

---

# 📈 Future Improvements

* Visual File Manager
* Git integration
* Multi PHP support
* Local domains
* Docker integration
* Two-factor authentication
* API tokens
* User roles

---

# 🎯 Project Goal

DevPanel was created as a lightweight Linux-native alternative inspired by tools such as:

* Laragon
* aaPanel
* CloudPanel

focused on:

* Ubuntu
* PHP
* XAMPP
* Strato workflows
* Community deployment

---

# 👨‍💻 Author

Carlos

GitHub: https://github.com/overzero18
