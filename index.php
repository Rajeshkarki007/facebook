<?php
require_once 'config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

$user = getCurrentUser($pdo);
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$fullName = e($user['first_name'] . ' ' . $user['last_name']);

// Handle new post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);
    if (!empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user['id'], $content]);
        redirect('index.php');
    }
}

// Handle like/unlike
if (isset($_GET['like'])) {
    $postId = (int)$_GET['like'];
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $user['id']]);
    if ($stmt->fetch()) {
        // Unlike
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $user['id']]);
    } else {
        // Like
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $user['id']]);
    }
    redirect('index.php');
}

// Handle comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content']) && isset($_POST['comment_post_id'])) {
    $commentContent = trim($_POST['comment_content']);
    $commentPostId = (int)$_POST['comment_post_id'];
    if (!empty($commentContent)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$commentPostId, $user['id'], $commentContent]);
    }
    redirect('index.php');
}

// Handle post deletion
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$deleteId, $user['id']]);
    redirect('index.php');
}

// Fetch all posts with user info, like count, and comment count
$posts = $pdo->query("
    SELECT p.*, u.first_name, u.last_name, u.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 50
")->fetchAll();

// For each post, check if current user liked it and fetch comments
foreach ($posts as &$post) {
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post['id'], $user['id']]);
    $post['user_liked'] = (bool)$stmt->fetch();

    $stmt = $pdo->prepare("
        SELECT c.*, u.first_name, u.last_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.post_id = ? 
        ORDER BY c.created_at ASC 
        LIMIT 5
    ");
    $stmt->execute([$post['id']]);
    $post['comments'] = $stmt->fetchAll();
}
unset($post);

// Fetch some users for contacts sidebar (excluding current user)
$contacts = $pdo->prepare("SELECT * FROM users WHERE id != ? ORDER BY first_name LIMIT 15");
$contacts->execute([$user['id']]);
$contacts = $contacts->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YcnS/1WR6zNg1OZw0YEwBDSzQGWBkDJQIBhGab" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <!-- ====== NAVBAR ====== -->
    <nav class="navbar">
        <div class="navbar-left">
            <a href="index.php" class="navbar-logo" style="text-decoration:none;">f</a>
            <div class="navbar-search">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search Facebook">
            </div>
        </div>

        <div class="navbar-center">
            <a href="index.php" class="nav-tab active" title="Home">🏠</a>
            <a href="#" class="nav-tab" title="Friends">👥</a>
            <a href="#" class="nav-tab" title="Watch">📺</a>
            <a href="#" class="nav-tab" title="Marketplace">🏪</a>
            <a href="#" class="nav-tab" title="Groups">👨‍👩‍👧‍👦</a>
        </div>

        <div class="navbar-right">
            <button class="btn-icon" title="Menu">☰</button>
            <button class="btn-icon" title="Messenger">💬</button>
            <button class="btn-icon" title="Notifications">🔔</button>
            <a href="#" class="navbar-profile" id="profileDropdownToggle">
                <div class="navbar-avatar-placeholder"><?= $initials ?></div>
                <?= e($user['first_name']) ?>
            </a>
            <!-- Dropdown -->
            <div id="profileDropdown" style="display:none;position:absolute;top:56px;right:16px;background:var(--bg-white);border-radius:var(--radius-lg);box-shadow:var(--shadow-xl);width:300px;padding:12px;z-index:9999;">
                <div style="display:flex;align-items:center;gap:12px;padding:12px;border-radius:var(--radius-md);cursor:pointer;margin-bottom:8px;background:var(--bg-primary);">
                    <div class="navbar-avatar-placeholder" style="width:40px;height:40px;font-size:1rem;"><?= $initials ?></div>
                    <div>
                        <div style="font-weight:700;"><?= $fullName ?></div>
                        <div style="font-size:0.8rem;color:var(--text-secondary);"><?= e($user['email']) ?></div>
                    </div>
                </div>
                <a href="logout.php" class="sidebar-menu-item" style="color:#dc2626;">
                    <div class="menu-icon" style="background:#fef2f2;color:#dc2626;">🚪</div>
                    Log Out
                </a>
            </div>
        </div>
    </nav>

    <!-- ====== MAIN CONTENT ====== -->
    <div class="homepage">
        <!-- ====== LEFT SIDEBAR ====== -->
        <aside class="sidebar-left">
            <a href="#" class="sidebar-menu-item profile-item">
                <div class="menu-icon"><?= $initials ?></div>
                <?= $fullName ?>
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-friends">👥</div>
                Friends
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-memories">⏰</div>
                Memories
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-saved">🔖</div>
                Saved
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-groups">👨‍👩‍👧‍👦</div>
                Groups
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-marketplace">🏪</div>
                Marketplace
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-watch">📺</div>
                Watch
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-events">📅</div>
                Events
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-pages">📄</div>
                Pages
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon icon-gaming">🎮</div>
                Gaming
            </a>

            <div class="sidebar-divider"></div>
            <div class="sidebar-heading">Your Shortcuts</div>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon" style="background:#e74c3c;color:white;border-radius:var(--radius-md);">🎸</div>
                Web Developer Group
            </a>
            <a href="#" class="sidebar-menu-item">
                <div class="menu-icon" style="background:#3498db;color:white;border-radius:var(--radius-md);">💻</div>
                PHP Developers
            </a>
        </aside>

        <!-- ====== FEED CENTER ====== -->
        <main class="feed-center">
            <!-- Stories -->
            <div class="stories-container">
                <div class="create-story-card">
                    <div class="create-story-icon">+</div>
                    <span>Create Story</span>
                </div>
                <div class="story-card">
                    <div class="story-card-bg">
                        <div class="story-avatar">JD</div>
                        <span class="story-name">John Doe</span>
                    </div>
                </div>
                <div class="story-card">
                    <div class="story-card-bg">
                        <div class="story-avatar">AS</div>
                        <span class="story-name">Alice Smith</span>
                    </div>
                </div>
                <div class="story-card">
                    <div class="story-card-bg">
                        <div class="story-avatar">BW</div>
                        <span class="story-name">Bob Wilson</span>
                    </div>
                </div>
                <div class="story-card">
                    <div class="story-card-bg">
                        <div class="story-avatar">CJ</div>
                        <span class="story-name">Clara Jones</span>
                    </div>
                </div>
                <div class="story-card">
                    <div class="story-card-bg">
                        <div class="story-avatar">MK</div>
                        <span class="story-name">Mark Kim</span>
                    </div>
                </div>
            </div>

            <!-- Create Post -->
            <div class="create-post-card">
                <div class="create-post-top">
                    <div class="create-post-avatar"><?= $initials ?></div>
                    <input type="text" class="create-post-input" placeholder="What's on your mind, <?= e($user['first_name']) ?>?" onclick="openModal()" readonly>
                </div>
                <div class="create-post-divider"></div>
                <div class="create-post-actions">
                    <button class="create-post-action">
                        <span class="action-icon action-video">🎥</span>
                        Live video
                    </button>
                    <button class="create-post-action">
                        <span class="action-icon action-photo">🖼️</span>
                        Photo/video
                    </button>
                    <button class="create-post-action" onclick="openModal()">
                        <span class="action-icon action-feeling">😊</span>
                        Feeling/activity
                    </button>
                </div>
            </div>

            <!-- Posts Feed -->
            <?php if (empty($posts)): ?>
                <div class="post-card" style="text-align:center;padding:40px;">
                    <div style="font-size:3rem;margin-bottom:12px;">📝</div>
                    <h3 style="color:var(--text-secondary);font-weight:600;">No Posts Yet</h3>
                    <p style="color:var(--text-secondary);font-size:0.9rem;">Be the first one to share something!</p>
                </div>
            <?php endif; ?>

            <?php foreach ($posts as $post): 
                $postInitials = strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1));
                $postAuthor = e($post['first_name'] . ' ' . $post['last_name']);
            ?>
                <div class="post-card">
                    <div class="post-header">
                        <div class="post-avatar"><?= $postInitials ?></div>
                        <div class="post-info">
                            <div class="post-author"><?= $postAuthor ?></div>
                            <div class="post-meta">
                                <?= timeAgo($post['created_at']) ?> · 🌍
                            </div>
                        </div>
                        <?php if ($post['user_id'] == $user['id']): ?>
                            <a href="index.php?delete=<?= $post['id'] ?>" class="post-options" title="Delete post" onclick="return confirm('Delete this post?')">🗑️</a>
                        <?php else: ?>
                            <button class="post-options">⋯</button>
                        <?php endif; ?>
                    </div>

                    <div class="post-content"><?= nl2br(e($post['content'])) ?></div>

                    <div class="post-stats">
                        <div class="post-likes">
                            <?php if ($post['like_count'] > 0): ?>
                                <div class="like-emoji"><span>👍</span></div>
                                <span><?= $post['like_count'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($post['comment_count'] > 0): ?>
                                <?= $post['comment_count'] ?> comment<?= $post['comment_count'] > 1 ? 's' : '' ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="post-stats-divider"></div>

                    <div class="post-actions">
                        <a href="index.php?like=<?= $post['id'] ?>" class="post-action-btn <?= $post['user_liked'] ? 'liked' : '' ?>" style="text-decoration:none;">
                            <?= $post['user_liked'] ? '👍' : '👍' ?>
                            <?= $post['user_liked'] ? 'Liked' : 'Like' ?>
                        </a>
                        <button class="post-action-btn" onclick="toggleComments(<?= $post['id'] ?>)">
                            💬 Comment
                        </button>
                        <button class="post-action-btn">
                            ↗️ Share
                        </button>
                    </div>

                    <!-- Comments Section -->
                    <div id="comments-<?= $post['id'] ?>" style="display:none;padding:8px 16px 16px;border-top:1px solid var(--border-color);">
                        <?php foreach ($post['comments'] as $comment): 
                            $commentInitials = strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1));
                        ?>
                            <div style="display:flex;gap:8px;margin-bottom:10px;">
                                <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--fb-blue),#4a90d9);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.7rem;flex-shrink:0;">
                                    <?= $commentInitials ?>
                                </div>
                                <div style="background:var(--bg-primary);border-radius:18px;padding:8px 14px;flex:1;">
                                    <div style="font-weight:700;font-size:0.85rem;"><?= e($comment['first_name'] . ' ' . $comment['last_name']) ?></div>
                                    <div style="font-size:0.9rem;"><?= e($comment['content']) ?></div>
                                    <div style="font-size:0.7rem;color:var(--text-secondary);margin-top:4px;"><?= timeAgo($comment['created_at']) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Comment Input -->
                        <form method="POST" style="display:flex;gap:8px;margin-top:8px;">
                            <input type="hidden" name="comment_post_id" value="<?= $post['id'] ?>">
                            <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--fb-blue),#4a90d9);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.7rem;flex-shrink:0;">
                                <?= $initials ?>
                            </div>
                            <input type="text" name="comment_content" placeholder="Write a comment..." required style="flex:1;padding:8px 14px;border:none;border-radius:50px;background:var(--bg-primary);font-size:0.9rem;">
                            <button type="submit" class="btn-icon" style="background:var(--fb-blue);color:white;width:32px;height:32px;">➤</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </main>

        <!-- ====== RIGHT SIDEBAR ====== -->
        <aside class="sidebar-right">
            <!-- Sponsored -->
            <div class="sidebar-section-title">Sponsored</div>
            <div class="sponsored-card">
                <div class="sponsored-img" style="background:linear-gradient(135deg,#f093fb,#f5576c);"></div>
                <div class="sponsored-info">
                    <h4>Learn PHP Today</h4>
                    <p>phptutorials.com</p>
                </div>
            </div>
            <div class="sponsored-card">
                <div class="sponsored-img" style="background:linear-gradient(135deg,#4facfe,#00f2fe);"></div>
                <div class="sponsored-info">
                    <h4>Web Dev Bootcamp</h4>
                    <p>webdevcamp.io</p>
                </div>
            </div>

            <div class="sidebar-divider" style="margin:12px 0;"></div>

            <!-- Contacts -->
            <div class="sidebar-section-title">
                Contacts
                <span>
                    <button class="btn-icon" style="width:28px;height:28px;font-size:0.9rem;" title="Search">🔍</button>
                    <button class="btn-icon" style="width:28px;height:28px;font-size:0.9rem;" title="Options">⋯</button>
                </span>
            </div>
            
            <?php if (empty($contacts)): ?>
                <div style="padding:12px;text-align:center;color:var(--text-secondary);font-size:0.85rem;">
                    No contacts yet. Invite friends!
                </div>
            <?php else: ?>
                <?php 
                $gradients = [
                    'linear-gradient(135deg, #667eea, #764ba2)',
                    'linear-gradient(135deg, #f093fb, #f5576c)',
                    'linear-gradient(135deg, #4facfe, #00f2fe)',
                    'linear-gradient(135deg, #43e97b, #38f9d7)',
                    'linear-gradient(135deg, #fa709a, #fee140)',
                    'linear-gradient(135deg, #a18cd1, #fbc2eb)',
                    'linear-gradient(135deg, #ffecd2, #fcb69f)',
                ];
                foreach ($contacts as $idx => $contact): 
                    $cInitials = strtoupper(substr($contact['first_name'], 0, 1) . substr($contact['last_name'], 0, 1));
                    $grad = $gradients[$idx % count($gradients)];
                ?>
                    <div class="contact-item">
                        <div class="contact-avatar online" style="background:<?= $grad ?>;"><?= $cInitials ?></div>
                        <span class="contact-name"><?= e($contact['first_name'] . ' ' . $contact['last_name']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </aside>
    </div>

    <!-- ====== CREATE POST MODAL ====== -->
    <div class="modal-overlay" id="createPostModal">
        <div class="modal">
            <div class="modal-header">
                <h3>Create post</h3>
                <button class="modal-close" onclick="closeModal()">✕</button>
            </div>
            <div class="modal-body">
                <div class="modal-user">
                    <div class="modal-avatar"><?= $initials ?></div>
                    <div>
                        <div style="font-weight:700;"><?= $fullName ?></div>
                        <div style="font-size:0.75rem;color:var(--text-secondary);display:flex;align-items:center;gap:4px;">
                            🌍 Public
                        </div>
                    </div>
                </div>
                <form method="POST" action="index.php">
                    <textarea name="post_content" placeholder="What's on your mind, <?= e($user['first_name']) ?>?" required></textarea>
                    <div class="modal-footer" style="padding:0;border:none;margin-top:12px;">
                        <button type="submit" class="btn btn-primary">Post</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Profile dropdown toggle
        document.getElementById('profileDropdownToggle').addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = document.getElementById('profileDropdown');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        });

        // Close dropdown on outside click
        document.addEventListener('click', function(e) {
            const toggle = document.getElementById('profileDropdownToggle');
            const dropdown = document.getElementById('profileDropdown');
            if (!toggle.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Create Post Modal
        function openModal() {
            document.getElementById('createPostModal').classList.add('active');
            document.querySelector('#createPostModal textarea').focus();
        }

        function closeModal() {
            document.getElementById('createPostModal').classList.remove('active');
        }

        // Close modal on overlay click
        document.getElementById('createPostModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // Toggle comments
        function toggleComments(postId) {
            const section = document.getElementById('comments-' + postId);
            if (section.style.display === 'none') {
                section.style.display = 'block';
                const input = section.querySelector('input[name="comment_content"]');
                if (input) input.focus();
            } else {
                section.style.display = 'none';
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
