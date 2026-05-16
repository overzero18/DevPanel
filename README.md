# DevPanel 🚀

DevPanel is a lightweight development panel for Ubuntu built with PHP, XAMPP and JavaScript, designed to centralize and simplify the workflow of web development environments.

The project provides a modern interface to manage services, projects, deployments and Linux tools directly from the browser.

---

# ✨ Features

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

* PHP
* JavaScript
* Bootstrap 5
* XAMPP
* MariaDB
* Apache
* xterm.js
* Ubuntu Linux
* lftp

---

# 📂 Project Structure

```text
devpanel/
├── api/
├── assets/
│   ├── css/
│   └── js/
├── docs/
├── includes/
├── layout/
├── tmp/
├── index.php
└── README.md
```

---

# ⚙️ Installation

## 1. Install XAMPP

Download XAMPP for Linux:

https://www.apachefriends.org/

---

## 2. Clone repository

```bash
git clone https://github.com/overzero18/DevPanel.git
```

---

## 3. Move project to htdocs

```bash
sudo mv DevPanel /opt/lampp/htdocs/devpanel
```

---

## 4. Start XAMPP

```bash
sudo /opt/lampp/lampp start
```

---

## 5. Open browser

```text
http://localhost/devpanel
```

---

# 📌 Current Features

* Apache control
* MariaDB control
* Linux folder opening
* VS Code integration
* ZIP generation
* FTP deploy
* Logs viewer
* Terminal integration
* Project detection
* System monitor

---

# 🔒 Security Note

DevPanel is intended for:

* local environments
* development environments
* personal workflows

It is not designed to be publicly exposed to the internet without authentication and additional security layers.

---

# 📈 Future Improvements

* Visual File Manager
* Git integration
* Multi PHP support
* Local domains
* Docker integration
* Kubernetes support
* Automatic backups

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

---

# 👨‍💻 Author

Carlos

GitHub:
https://github.com/overzero18
