# 📁 FTP Online Client

**Web-based FTP File Manager** – manage your server files directly in the browser with drag & drop uploads, folder navigation, and file operations.  
Version **1.0** – developed by Kevin Tobler 🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 🚀 Features

- 🔐 Login with FTP credentials (FTP/FTPS/SFTP)
- 🗂️ Navigate remote directories with folder tree
- 📂 Drag & Drop upload support
- 🧭 Browse, rename, move, delete files and folders
- 📄 Inline previews for images and files
- 📦 ZIP and unzip functionality
- 🌓 Modern, clean UI with responsive layout
- 🧩 Single PHP file – easy deployment

---

## 🔧 Installation

1. Upload `index.php` to your server
2. Open it in your browser
3. Enter your FTP credentials to connect

> ⚠️ Requires PHP 7.4 or higher. FTP functions (`ftp_*`) must be enabled on the server.

---

## 🌐 Protocol Support

By default, the tool uses FTP, FTPS or SFTP. SFTP need SSH2 to be installed.

---

## 🔒 Security Notes

- Credentials are not stored permanently.
- No database or backend storage – purely session-based.
- Use HTTPS to secure login and file transfers if possible.

---

## 📸 Screenshot

![FTP Online Client Screenshot](https://online.kevintobler.ch/projectimages/FTPOnlineClientLogin.png)

![FTP Online Client Screenshot](https://online.kevintobler.ch/projectimages/FTPOnlineClientBrowser.png)

---

## 🧑‍💻 Developer

**Kevin Tobler**  
🌐 [www.kevintobler.ch](https://www.kevintobler.ch)

---

## 📜 License

This project is licensed under the **MIT License** – free to use, modify, and distribute.
