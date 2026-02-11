# 📘 Facebook Clone

A full-featured **Facebook clone** built with PHP, MySQL, and BoostTrap CSS. This project replicates core Facebook functionalities including user authentication, news feed, post creation, likes, comments, and a responsive UI with stories, sidebars, and contact lists.

---

## 🌟 Features

| Feature | Description |
|---|---|
| **User Authentication** | Secure signup and login with password hashing (bcrypt) |
| **News Feed** | Real-time post feed sorted by newest first |
| **Create Posts** | Create posts via a modal dialog (Facebook-style) |
| **Like / Unlike** | Toggle likes on posts with live count |
| **Comments** | Add comments on posts with threaded display |
| **Delete Posts** | Post owners can delete their own posts |
| **Stories Section** | Visual story cards at the top of the feed |
| **Left Sidebar** | Navigation menu (Friends, Groups, Marketplace, Watch, etc.) |
| **Right Sidebar** | Sponsored ads + Contacts list with online indicators |
| **Profile Dropdown** | User profile menu with logout option |
| **Responsive UI** | Mobile-friendly design with Facebook's signature blue theme |
| **XSS Protection** | All output sanitized with `htmlspecialchars()` |
| **Time Ago** | Human-readable timestamps (e.g., "5 minutes ago") |

---

## 📁 Folder Structure

```
facebook/
│
├── assets/
│   └── css/
│       └── style.css           # Complete stylesheet (navbar, feed, sidebar, modals)
│
├── config.php                  # Database connection (PDO) + helper functions
├── database.sql                # MySQL database schema
├── index.php                   # Homepage — news feed, stories, create post, likes, comments
├── login.php                   # Login page with email/password authentication
├── logout.php                  # Session destroy and redirect to login
├── signup.php                  # User registration with form validation
└── README.md                   # Project documentation
```

---

## 🗄️ Database Schema

The project uses **5 tables** in the `facebook_clone` database:

```
┌─────────────────┐     ┌─────────────────┐
│     users        │     │     posts        │
├─────────────────┤     ├─────────────────┤
│ id (PK)          │◄───┤ user_id (FK)     │
│ first_name       │     │ id (PK)          │
│ last_name        │     │ content          │
│ email (UNIQUE)   │     │ image            │
│ password         │     │ created_at       │
│ gender           │     └────────┬─────────┘
│ birthdate        │              │
│ profile_pic      │     ┌────────┴─────────┐
│ cover_pic        │     │                  │
│ bio              │     ▼                  ▼
│ created_at       │  ┌──────────┐   ┌───────────┐
└────────┬─────────┘  │  likes   │   │ comments  │
         │            ├──────────┤   ├───────────┤
         │            │ id (PK)  │   │ id (PK)   │
         │            │ post_id  │   │ post_id   │
         └───────────►│ user_id  │   │ user_id   │
                      │ created  │   │ content   │
                      └──────────┘   │ created   │
                                     └───────────┘
┌──────────────────────┐
│   friend_requests    │
├──────────────────────┤
│ id (PK)              │
│ sender_id (FK)       │
│ receiver_id (FK)     │
│ status (pending/     │
│   accepted/rejected) │
│ created_at           │
└──────────────────────┘
```

---

## 🚀 Getting Started

### Prerequisites

- **XAMPP** (or any Apache + PHP + MySQL stack)
- PHP 7.4+ with PDO extension
- MySQL 5.7+

### Installation

1. **Clone or copy** the project to your XAMPP htdocs directory:
   ```bash
   cd C:\xampp\htdocs
   git clone <repository-url> facebook
   ```

2. **Start XAMPP** — Launch Apache and MySQL from the XAMPP Control Panel.

3. **Create the database** — Open [phpMyAdmin](http://localhost/phpmyadmin) and:
   - Click **Import** tab
   - Select the `database.sql` file from the project
   - Click **Go** to execute

   Or run via MySQL CLI:
   ```sql
   source C:\xampp\htdocs\facebook\database.sql;
   ```

4. **Configure the database** — Edit `config.php` if needed:
   ```php
   $host     = 'localhost';
   $dbname   = 'facebook_clone';
   $username = 'root';
   $password = '';  // default XAMPP password is empty
   ```

5. **Open in browser:**
   ```
   http://localhost/facebook/
   ```

---

## 🖥️ Pages Overview

| Page | URL | Description |
|---|---|---|
| **Login** | `/login.php` | Email & password login form |
| **Sign Up** | `/signup.php` | Registration with name, email, password, gender, birthdate |
| **Home Feed** | `/index.php` | Main dashboard — stories, post creation, feed, sidebars |
| **Logout** | `/logout.php` | Destroys session and redirects to login |

---

## ⚙️ Tech Stack

| Layer | Technology |
|---|---|
| **Frontend** | HTML5, CSS3 (Custom Properties), Vanilla JavaScript, Bootstrap 5.3.3 (CDN), Bootstrap Icons |
| **Backend** | PHP 7.4+ (PDO for database) |
| **Database** | MySQL with foreign keys and cascading deletes |
| **Server** | Apache (XAMPP) |
| **Security** | bcrypt password hashing, prepared statements (SQL injection prevention), XSS sanitization |

---

## 🔒 Security Features

- ✅ **Password Hashing** — Uses `password_hash()` with bcrypt
- ✅ **Prepared Statements** — All SQL queries use PDO prepared statements
- ✅ **XSS Prevention** — All user output escaped via `htmlspecialchars()`
- ✅ **Session Management** — PHP sessions for authentication
- ✅ **CSRF-safe Actions** — Post deletion requires ownership verification

---

## 📸 Screenshots

> After running the project, navigate to `http://localhost/facebook/` to see:
> - 🔐 **Login Page** — Clean, centered Facebook-style login form
> - 📝 **Sign Up Page** — Full registration with gender and birthdate
> - 🏠 **Home Feed** — Complete Facebook-like layout with stories, posts, and sidebars

---

## 📝 License

This project is for **educational purposes only**. It is not affiliated with or endorsed by Meta/Facebook.

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit changes (`git commit -m 'Add new feature'`)
4. Push to branch (`git push origin feature/new-feature`)
5. Open a Pull Request

---

> Built with ❤️ using PHP & MySQL
