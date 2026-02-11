<?php
// Include configuration file
require_once 'config.php';

// Redirect to login page if user is not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current user data
$user = getCurrentUser($pdo);
// Generate initials for the avatar (e.g., "John Doe" -> "JD")
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$fullName = e($user['first_name'] . ' ' . $user['last_name']);

// Handle new post creation (when form is submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = trim($_POST['post_content']);
    // Only save if content is not empty
    if (!empty($content)) {
        // Insert new post into database
        $stmt = $pdo->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
        $stmt->execute([$user['id'], $content]);
        // Redirect to avoid form resubmission on refresh
        redirect('index.php');
    }
}

// Handle like/unlike actions
if (isset($_GET['like'])) {
    $postId = (int)$_GET['like'];
    // Check if user already liked this post
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $user['id']]);
    if ($stmt->fetch()) {
        // If already liked, remove like (unlike)
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $user['id']]);
    } else {
        // If not liked, add like
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $user['id']]);
    }
    redirect('index.php');
}

// Handle new comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content']) && isset($_POST['comment_post_id'])) {
    $commentContent = trim($_POST['comment_content']);
    $commentPostId = (int)$_POST['comment_post_id'];
    if (!empty($commentContent)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$commentPostId, $user['id'], $commentContent]);
    }
    redirect('index.php');
}

// Handle post deletion (only for post owner)
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    // Delete only if the post belongs to the current user
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$deleteId, $user['id']]);
    redirect('index.php');
}

// Fetch recent posts from the database (feed)
$posts = $pdo->query("
    SELECT p.*, u.first_name, u.last_name, u.profile_pic,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 50
")->fetchAll();

// Add extra data to each post (liked status, recent comments)
foreach ($posts as &$post) {
    // Check if current user liked this post
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post['id'], $user['id']]);
    $post['user_liked'] = (bool)$stmt->fetch();

    // Fetch top 5 recent comments for this post
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
unset($post); // Break reference

// Fetch suggested contacts (users other than self)
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <!-- ====== NAVBAR ====== -->
    <nav class="navbar navbar-expand navbar-light bg-white navbar-fb fixed-top">
        <div class="container-fluid">
            <!-- Left: Logo + Search -->
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="navbar-brand mb-0 fw-bold" style="color: var(--fb-blue); font-size: 2rem; letter-spacing: -1px;">f</a>
                <div class="search-fb d-none d-sm-block">
                    <i class="bi bi-search search-icon"></i>
                    <form action="search.php" method="GET">
                        <input type="text" name="q" class="form-control" placeholder="Search Facebook">
                    </form>
                </div>
            </div>

            <!-- Center: Nav Tabs (Hidden on mobile) -->
            <div class="d-none d-md-flex align-items-center gap-1 mx-auto">
                <a href="index.php" class="nav-tab-fb active" title="Home"><i class="bi bi-house-door-fill"></i></a>
                <a href="friends.php" class="nav-tab-fb" title="Friends"><i class="bi bi-people-fill"></i></a>
                <a href="#" class="nav-tab-fb" title="Watch"><i class="bi bi-play-btn-fill"></i></a>
                <a href="#" class="nav-tab-fb" title="Marketplace"><i class="bi bi-shop"></i></a>
                <a href="#" class="nav-tab-fb" title="Groups"><i class="bi bi-grid-fill"></i></a>
            </div>

            <!-- Right: Icons + Profile -->
            <div class="d-flex align-items-center gap-2">
                <button class="btn-icon-fb" title="Menu"><i class="bi bi-list"></i></button>
                <a href="chat.php" class="btn-icon-fb" title="Messenger"><i class="bi bi-chat-dots-fill"></i></a>
                <button class="btn-icon-fb" title="Notifications"><i class="bi bi-bell-fill"></i></button>
                
                <!-- Profile Dropdown -->
                <div class="dropdown">
                    <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="avatar-circle avatar-circle-sm"><?= $initials ?></span>
                        <span class="d-none d-lg-inline"><?= e($user['first_name']) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-fb">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 p-2 rounded-3" href="profile.php" style="background: var(--bg-primary);">
                                <span class="avatar-circle avatar-circle-md"><?= $initials ?></span>
                                <div>
                                    <div class="fw-bold"><?= $fullName ?></div>
                                    <div class="small text-secondary"><?= e($user['email']) ?></div>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item d-flex align-items-center gap-3 rounded-3 py-2" href="settings.php"><span class="avatar-circle avatar-circle-sm" style="background: var(--bg-primary); color: var(--text-primary);"><i class="bi bi-gear-fill"></i></span>Settings</a></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 rounded-3 py-2" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal" style="color: #dc2626;">
                                <span class="avatar-circle avatar-circle-sm" style="background: #fef2f2; color: #dc2626;"><i class="bi bi-box-arrow-right"></i></span>
                                Log Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- ====== MAIN CONTENT ====== -->
    <div class="container-fluid homepage-layout" style="margin-top: 56px;">
        <div class="row g-3">
            <!-- ====== LEFT SIDEBAR ====== -->
            <div class="col-lg-3 col-xl-3 sidebar-left-col d-none d-lg-block">
                <div class="sidebar-left p-2">
                    <a href="#" class="sidebar-item">
                        <span class="avatar-circle avatar-circle-sm"><?= $initials ?></span>
                        <?= $fullName ?>
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #1877f2;"><i class="bi bi-people-fill"></i></span>
                        Friends
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #f59e0b;"><i class="bi bi-clock-history"></i></span>
                        Memories
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #8b5cf6;"><i class="bi bi-bookmark-fill"></i></span>
                        Saved
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #3b82f6;"><i class="bi bi-people"></i></span>
                        Groups
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #10b981;"><i class="bi bi-shop-window"></i></span>
                        Marketplace
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #ef4444;"><i class="bi bi-play-btn-fill"></i></span>
                        Watch
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #ec4899;"><i class="bi bi-calendar-event-fill"></i></span>
                        Events
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #f97316;"><i class="bi bi-flag-fill"></i></span>
                        Pages
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #6366f1;"><i class="bi bi-controller"></i></span>
                        Gaming
                    </a>

                    <hr class="my-2">
                    <p class="small text-secondary fw-semibold px-3 mb-1">Your Shortcuts</p>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #e74c3c; border-radius: 8px;"><i class="bi bi-code-slash"></i></span>
                        Web Developer Group
                    </a>
                    <a href="#" class="sidebar-item">
                        <span class="sidebar-icon" style="background: #3498db; border-radius: 8px;"><i class="bi bi-filetype-php"></i></span>
                        PHP Developers
                    </a>
                </div>
            </div>

            <!-- ====== FEED CENTER ====== -->
            <div class="col-lg-6 col-xl-6">
                <!-- Stories Carousel -->
                <div class="d-flex stories-scroll mb-3">
                    <div class="create-story text-center">
                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1" style="width: 40px; height: 40px; background: var(--fb-blue); color: #fff; font-size: 1.5rem;">
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <span class="small fw-semibold">Create Story</span>
                    </div>
                    <?php 
                    // Example story data
                    $storyUsers = [
                        ['JD' => 'John Doe', 'bg' => 'linear-gradient(135deg, #667eea, #764ba2)'],
                        ['AS' => 'Alice Smith', 'bg' => 'linear-gradient(135deg, #f093fb, #f5576c)'],
                        ['BW' => 'Bob Wilson', 'bg' => 'linear-gradient(135deg, #4facfe, #00f2fe)'],
                        ['CJ' => 'Clara Jones', 'bg' => 'linear-gradient(135deg, #43e97b, #38f9d7)'],
                        ['MK' => 'Mark Kim', 'bg' => 'linear-gradient(135deg, #fa709a, #fee140)'],
                    ];
                    foreach ($storyUsers as $story):
                        $initKey = array_keys($story)[0];
                        $storyName = $story[$initKey];
                        $storyBg = $story['bg'];
                    ?>
                    <div class="story-card">
                        <div class="story-bg" style="background: <?= $storyBg ?>;">
                            <div class="story-avatar"><?= $initKey ?></div>
                            <span class="story-name"><?= $storyName ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Create Post Box -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="avatar-circle avatar-circle-lg"><?= $initials ?></span>
                            <input type="text" class="form-control create-post-input" placeholder="What's on your mind, <?= e($user['first_name']) ?>?" data-bs-toggle="modal" data-bs-target="#createPostModal" readonly>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex">
                            <button class="post-action-btn">
                                <i class="bi bi-camera-video-fill text-danger"></i> Live video
                            </button>
                            <button class="post-action-btn">
                                <i class="bi bi-image-fill text-success"></i> Photo/video
                            </button>
                            <button class="post-action-btn" data-bs-toggle="modal" data-bs-target="#createPostModal">
                                <i class="bi bi-emoji-smile-fill text-warning"></i> Feeling/activity
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Posts Feed -->
                <?php if (empty($posts)): ?>
                    <div class="card border-0 shadow-sm rounded-3 post-card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-pencil-square display-4 text-secondary mb-3 d-block"></i>
                            <h5 class="fw-semibold text-secondary">No Posts Yet</h5>
                            <p class="text-secondary small">Be the first one to share something!</p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php foreach ($posts as $post): 
                    $postInitials = strtoupper(substr($post['first_name'], 0, 1) . substr($post['last_name'], 0, 1));
                    $postAuthor = e($post['first_name'] . ' ' . $post['last_name']);
                ?>
                    <div class="card border-0 shadow-sm rounded-3 mb-3 post-card">
                        <div class="card-body">
                            <!-- Post Header -->
                            <div class="d-flex align-items-center mb-3">
                                <span class="avatar-circle avatar-circle-lg me-3"><?= $postInitials ?></span>
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?= $postAuthor ?></div>
                                    <div class="small text-secondary"><?= timeAgo($post['created_at']) ?> · <i class="bi bi-globe2"></i></div>
                                </div>
                                <?php if ($post['user_id'] == $user['id']): ?>
                                    <a href="index.php?delete=<?= $post['id'] ?>" class="btn-icon-fb text-danger" title="Delete post" onclick="return confirm('Delete this post?')">
                                        <i class="bi bi-trash3"></i>
                                    </a>
                                <?php else: ?>
                                    <button class="btn-icon-fb"><i class="bi bi-three-dots"></i></button>
                                <?php endif; ?>
                            </div>

                            <!-- Post Content -->
                            <p class="mb-3"><?= nl2br(e($post['content'])) ?></p>

                            <!-- Post Stats (Likes/Comments count) -->
                            <div class="d-flex justify-content-between text-secondary small mb-2">
                                <div>
                                    <?php if ($post['like_count'] > 0): ?>
                                        <span class="badge rounded-pill" style="background: var(--fb-blue);"><i class="bi bi-hand-thumbs-up-fill"></i></span>
                                        <span><?= $post['like_count'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($post['comment_count'] > 0): ?>
                                        <?= $post['comment_count'] ?> comment<?= $post['comment_count'] > 1 ? 's' : '' ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <hr class="my-2">

                            <!-- Post Actions (Like, Comment, Share buttons) -->
                            <div class="d-flex">
                                <a href="index.php?like=<?= $post['id'] ?>" class="post-action-btn text-decoration-none <?= $post['user_liked'] ? 'liked' : '' ?>">
                                    <i class="bi bi-hand-thumbs-up<?= $post['user_liked'] ? '-fill' : '' ?>"></i>
                                    <?= $post['user_liked'] ? 'Liked' : 'Like' ?>
                                </a>
                                <button class="post-action-btn" onclick="toggleComments(<?= $post['id'] ?>)">
                                    <i class="bi bi-chat"></i> Comment
                                </button>
                                <button class="post-action-btn">
                                    <i class="bi bi-share"></i> Share
                                </button>
                            </div>

                            <!-- Comments Section -->
                            <div id="comments-<?= $post['id'] ?>" style="display: none;" class="mt-3 pt-3 border-top">
                                <?php foreach ($post['comments'] as $comment): 
                                    $commentInitials = strtoupper(substr($comment['first_name'], 0, 1) . substr($comment['last_name'], 0, 1));
                                ?>
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="avatar-circle avatar-circle-sm flex-shrink-0"><?= $commentInitials ?></span>
                                        <div class="comment-bubble flex-grow-1">
                                            <div class="fw-bold small"><?= e($comment['first_name'] . ' ' . $comment['last_name']) ?></div>
                                            <div class="small"><?= e($comment['content']) ?></div>
                                            <div class="text-secondary" style="font-size: 0.7rem;"><?= timeAgo($comment['created_at']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Comment Input Form -->
                                <form method="POST" class="d-flex gap-2 mt-2">
                                    <input type="hidden" name="comment_post_id" value="<?= $post['id'] ?>">
                                    <span class="avatar-circle avatar-circle-sm flex-shrink-0"><?= $initials ?></span>
                                    <input type="text" name="comment_content" class="form-control form-control-sm rounded-pill border-0" placeholder="Write a comment..." required style="background: var(--bg-primary);">
                                    <button type="submit" class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: var(--fb-blue); color: #fff; flex-shrink: 0;">
                                        <i class="bi bi-send-fill" style="font-size: 0.75rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- ====== RIGHT SIDEBAR ====== -->
            <div class="col-lg-3 col-xl-3 sidebar-right-col d-none d-xl-block">
                <div class="sidebar-right p-2">
                    <!-- Sponsored Section (Static for now) -->
                    <p class="small text-secondary fw-semibold mb-2">Sponsored</p>
                    <div class="d-flex gap-3 mb-3 cursor-pointer">
                        <div class="rounded-3 flex-shrink-0" style="width: 130px; height: 130px; background: linear-gradient(135deg, #f093fb, #f5576c);"></div>
                        <div>
                            <h6 class="fw-bold mb-1">Learn PHP Today</h6>
                            <p class="text-secondary small mb-0">phptutorials.com</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mb-3 cursor-pointer">
                        <div class="rounded-3 flex-shrink-0" style="width: 130px; height: 130px; background: linear-gradient(135deg, #4facfe, #00f2fe);"></div>
                        <div>
                            <h6 class="fw-bold mb-1">Web Dev Bootcamp</h6>
                            <p class="text-secondary small mb-0">webdevcamp.io</p>
                        </div>
                    </div>

                    <hr>

                    <!-- Contacts / Friends List -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <p class="small text-secondary fw-semibold mb-0">Contacts</p>
                        <div>
                            <button class="btn-icon-fb" style="width: 28px; height: 28px; font-size: 0.8rem;" title="Search"><i class="bi bi-search"></i></button>
                            <button class="btn-icon-fb" style="width: 28px; height: 28px; font-size: 0.8rem;" title="Options"><i class="bi bi-three-dots"></i></button>
                        </div>
                    </div>

                    <?php if (empty($contacts)): ?>
                        <div class="text-center text-secondary small p-3">No contacts yet. Invite friends!</div>
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
                            <div class="sidebar-item">
                                <span class="position-relative d-inline-flex">
                                    <span class="avatar-circle avatar-circle-sm" style="background: <?= $grad ?>;"><?= $cInitials ?></span>
                                    <span class="contact-dot"></span>
                                </span>
                                <span class="small fw-medium"><?= e($contact['first_name'] . ' ' . $contact['last_name']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ====== CREATE POST MODAL (Bootstrap) ====== -->
    <div class="modal fade modal-fb" id="createPostModal" tabindex="-1" aria-labelledby="createPostModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="createPostModalLabel">Create post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="avatar-circle avatar-circle-lg"><?= $initials ?></span>
                        <div>
                            <div class="fw-bold"><?= $fullName ?></div>
                            <div class="small text-secondary"><i class="bi bi-globe2"></i> Public</div>
                        </div>
                    </div>
                    <form method="POST" action="index.php">
                        <textarea name="post_content" class="form-control border-0" placeholder="What's on your mind, <?= e($user['first_name']) ?>?" rows="4" required style="resize: none; font-size: 1.1rem;"></textarea>
                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-fb-blue">Post</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle visibility of comments section
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
    </script>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade logout-modal" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="logout-icon mx-auto"><i class="bi bi-box-arrow-right"></i></div>
                    <h5 class="fw-bold mb-2">Log Out?</h5>
                    <p class="text-secondary small mb-4">Are you sure you want to log out of your account?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button class="btn btn-logout-cancel px-4" data-bs-dismiss="modal">Cancel</button>
                        <a href="logout.php" class="btn btn-logout-confirm px-4">Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Blur backdrop for logout modal
    const logoutModal = document.getElementById('logoutModal');
    if (logoutModal) {
        logoutModal.addEventListener('show.bs.modal', function () {
            setTimeout(() => {
                document.querySelector('.modal-backdrop')?.classList.add('modal-backdrop-blur');
            }, 0);
        });
    }
    </script>
</body>
</html>
