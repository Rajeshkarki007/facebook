# 📘 Facebook Clone

A full-featured **Facebook clone** built with PHP, MySQL, and Bootstrap 5. This project replicates core Facebook functionalities including user authentication, news feed, profiles, messaging, friend system, and a responsive premium UI.

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
| **User Profiles** | Profile page with cover photo, bio, posts, and friend list |
| **Friend System** | Send/accept/reject friend requests, unfriend, people suggestions |
| **Chat / Messenger** | **AJAX-powered** real-time messaging (no page reloads) |
| **Search** | Search users by name or email with friend status badges |
| **Settings** | Edit profile (name, bio, gender), change password, delete account |
| **Logout Confirmation** | Confirmation page before logout + goodbye screen |
| **Stories Section** | Visual story cards at the top of the feed |
| **Left Sidebar** | Navigation menu (Friends, Groups, Marketplace, Watch, etc.) |
| **Right Sidebar** | Sponsored ads + Contacts list with online indicators |
| **Profile Dropdown** | User profile menu with links to Profile, Settings, Logout |
| **Responsive UI** | Mobile-friendly design with Facebook's signature blue theme |
| **XSS Protection** | All output sanitized with `htmlspecialchars()` |
| **Time Ago** | Human-readable timestamps (e.g., "5 minutes ago") |
| **Educational Code** | **Heavily commented** source code to help beginners learn PHP & AJAX |

---

## 📁 Folder Structure

```
facebook/
│
├── assets/
│   └── css/
│       └── style.css           # Complete stylesheet (1100+ lines)
│
├── config.php                  # Database connection (PDO) + helper functions
├── database.sql                # MySQL database schema (6 tables)
├── index.php                   # Homepage — news feed, stories, posts, sidebars
├── login.php                   # Login page with email/password authentication
├── signup.php                  # User registration with form validation
├── logout.php                  # Logout confirmation + goodbye page
├── profile.php                 # User profile — cover, bio, posts, friends
├── friends.php                 # Friend requests, friend list, suggestions
├── chat.php                    # Messenger — conversations, chat bubbles, emoji
├── search.php                  # Search users by name/email
├── settings.php                # Edit profile, change password, delete account
└── README.md                   # Project documentation
```

---

## 🗄️ Database Schema

The project uses **6 tables** in the `facebook_clone` database:

```
┌─────────────────┐     ┌─────────────────┐     ┌──────────────────┐
│     users        │     │     posts        │     │    messages       │
├─────────────────┤     ├─────────────────┤     ├──────────────────┤
│ id (PK)          │◄───┤ user_id (FK)     │     │ id (PK)          │
│ first_name       │     │ id (PK)          │     │ sender_id (FK)   │
│ last_name        │     │ content          │     │ receiver_id (FK) │
│ email (UNIQUE)   │     │ image            │     │ message          │
│ password         │     │ created_at       │     │ is_read          │
│ gender           │     └────────┬─────────┘     │ created_at       │
│ birthdate        │              │               └──────────────────┘
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
| **Profile** | `/profile.php` | User profile with cover photo, bio, posts, friends list |
| **Friends** | `/friends.php` | Friend requests (accept/reject), friend list, people suggestions |
| **Messenger** | `/chat.php` | Chat conversations, real-time messaging, emoji, unread badges |
| **Search** | `/search.php` | Search users by name or email, friend status indicators |
| **Settings** | `/settings.php` | Edit profile, change password, delete account |
| **Logout** | `/logout.php` | Confirmation page → goodbye screen |

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
- ✅ **Account Deletion** — Requires typing "DELETE" to confirm

---

## 📸 Screenshots

> After running the project, navigate to `http://localhost/facebook/` to see:
> - 🔐 **Login Page** — Clean, centered Facebook-style login form
> - 📝 **Sign Up Page** — Full registration with gender and birthdate
> - 🏠 **Home Feed** — Complete Facebook-like layout with stories, posts, and sidebars
> - 👤 **Profile Page** — User profile with cover photo, bio, and posts
> - 👥 **Friends Page** — Friend requests, friend list, and suggestions
> - 💬 **Messenger** — Chat interface with conversations and real-time messaging
> - ⚙️ **Settings** — Edit profile, change password, account management
> - 🔍 **Search** — Find users by name or email

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
