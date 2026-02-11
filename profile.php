<?php
// Include configuration file
require_once 'config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current logged-in user data
$currentUser = getCurrentUser($pdo);
// Generate initials for current user (e.g., "JD")
$currentInitials = strtoupper(substr($currentUser['first_name'], 0, 1) . substr($currentUser['last_name'], 0, 1));

// Determine which profile to view
// If 'id' is in URL, view that user. Otherwise, view own profile.
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUser['id'];

// Fetch the profile user's data from database
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$profileId]);
$profileUser = $stmt->fetch();

// If user doesn't exist, redirect to home
if (!$profileUser) {
    redirect('index.php');
}

// Check if this is the current user's own profile
$isOwnProfile = ($profileUser['id'] == $currentUser['id']);
$profileInitials = strtoupper(substr($profileUser['first_name'], 0, 1) . substr($profileUser['last_name'], 0, 1));
$profileFullName = e($profileUser['first_name'] . ' ' . $profileUser['last_name']);

// Handle Bio Update (Only if it's your own profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bio']) && $isOwnProfile) {
    $bio = trim($_POST['bio'] ?? '');
    $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE id = ?");
    $stmt->execute([$bio, $currentUser['id']]);
    redirect('profile.php');
}

// Handle Friend Request (Sending)
if (isset($_GET['add_friend']) && !$isOwnProfile) {
    $receiverId = (int)$_GET['add_friend'];
    // Check if a request already exists to avoid duplicates
    $stmt = $pdo->prepare("SELECT id FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$currentUser['id'], $receiverId, $receiverId, $currentUser['id']]);
    if (!$stmt->fetch()) {
        // Create new friend request
        $stmt = $pdo->prepare("INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)");
        $stmt->execute([$currentUser['id'], $receiverId]);
    }
    redirect("profile.php?id=$receiverId");
}

// Check Friendship Status (to determine which button to show)
$friendStatus = null;
if (!$isOwnProfile) {
    $stmt = $pdo->prepare("SELECT * FROM friend_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
    $stmt->execute([$currentUser['id'], $profileUser['id'], $profileUser['id'], $currentUser['id']]);
    $friendReq = $stmt->fetch();
    if ($friendReq) {
        $friendStatus = $friendReq['status'];
        // Distinguish between request sent by me vs. received by me
        if ($friendStatus === 'pending' && $friendReq['sender_id'] == $currentUser['id']) {
            $friendStatus = 'request_sent';
        } elseif ($friendStatus === 'pending' && $friendReq['receiver_id'] == $currentUser['id']) {
            $friendStatus = 'request_received';
        }
    }
}

// Count total accepted friends
$stmt = $pdo->prepare("SELECT COUNT(*) FROM friend_requests WHERE (sender_id = ? OR receiver_id = ?) AND status = 'accepted'");
$stmt->execute([$profileUser['id'], $profileUser['id']]);
$friendCount = $stmt->fetchColumn();

// Fetch this user's posts
$stmt = $pdo->prepare("
    SELECT p.*, u.first_name, u.last_name,
        (SELECT COUNT(*) FROM likes WHERE post_id = p.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE post_id = p.id) AS comment_count
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
    LIMIT 20
");
$stmt->execute([$profileUser['id']]);
$posts = $stmt->fetchAll();

// Check which posts the current user has liked
foreach ($posts as &$post) {
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$post['id'], $currentUser['id']]);
    $post['user_liked'] = (bool)$stmt->fetch();
}
unset($post);

// Handle Like/Unlike action on profile page
if (isset($_GET['like'])) {
    $postId = (int)$_GET['like'];
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $currentUser['id']]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $currentUser['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $currentUser['id']]);
    }
    redirect("profile.php?id=$profileId");
}

// Get Mutual Friends (for display)
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name FROM users u
    INNER JOIN friend_requests fr ON ((fr.sender_id = u.id OR fr.receiver_id = u.id) AND u.id != ?)
    WHERE fr.status = 'accepted' AND (fr.sender_id = ? OR fr.receiver_id = ?)
    LIMIT 6
");
$stmt->execute([$profileUser['id'], $profileUser['id'], $profileUser['id']]);
$friendsList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $profileFullName ?> | Facebook</title>
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <!-- NAVBAR (Same as index.php) -->
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
            <!-- Center: Nav Tabs -->
            <div class="d-none d-md-flex align-items-center gap-1 mx-auto">
                <a href="index.php" class="nav-tab-fb" title="Home"><i class="bi bi-house-door-fill"></i></a>
                <a href="friends.php" class="nav-tab-fb" title="Friends"><i class="bi bi-people-fill"></i></a>
                <a href="#" class="nav-tab-fb" title="Watch"><i class="bi bi-play-btn-fill"></i></a>
                <a href="#" class="nav-tab-fb" title="Marketplace"><i class="bi bi-shop"></i></a>
                <a href="#" class="nav-tab-fb" title="Groups"><i class="bi bi-grid-fill"></i></a>
            </div>
            <!-- Right: Icons + Profile -->
            <div class="d-flex align-items-center gap-2">
                <button class="btn-icon-fb" title="Messenger"><i class="bi bi-chat-dots-fill"></i></button>
                <button class="btn-icon-fb" title="Notifications"><i class="bi bi-bell-fill"></i></button>
                <div class="dropdown">
                    <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <span class="avatar-circle avatar-circle-sm"><?= $currentInitials ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-fb">
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-3 rounded-3 py-2" href="profile.php">
                                <span class="avatar-circle avatar-circle-sm"><?= $currentInitials ?></span>
                                <?= e($currentUser['first_name'] . ' ' . $currentUser['last_name']) ?>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item rounded-3 py-2" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><a class="dropdown-item rounded-3 py-2 text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- PROFILE PAGE CONTENT -->
    <div class="profile-page" style="margin-top: 56px;">
        <!-- Cover Photo Area -->
        <div class="profile-cover">
            <div class="profile-cover-gradient"></div>
        </div>

        <div class="container" style="max-width: 940px;">
            <!-- Profile Header Info -->
            <div class="profile-header">
                <div class="d-flex flex-column flex-md-row align-items-center align-items-md-end gap-3" style="margin-top: -40px;">
                    <!-- Avatar -->
                    <div class="profile-avatar-wrapper">
                        <span class="avatar-circle" style="width: 168px; height: 168px; font-size: 3.5rem; border: 5px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.15);"><?= $profileInitials ?></span>
                    </div>
                    <!-- Name & Friend Count -->
                    <div class="flex-grow-1 text-center text-md-start pb-3">
                        <h1 class="fw-bold mb-0" style="font-size: 2rem;"><?= $profileFullName ?></h1>
                        <p class="text-secondary mb-0"><?= $friendCount ?> friend<?= $friendCount != 1 ? 's' : '' ?></p>
                    </div>
                    <!-- Action Buttons (Edit Profile, Add Friend, etc.) -->
                    <div class="pb-3 d-flex gap-2">
                        <?php if ($isOwnProfile): ?>
                            <a href="settings.php" class="btn btn-fb-blue btn-sm px-3"><i class="bi bi-pencil-fill me-1"></i> Edit Profile</a>
                        <?php else: ?>
                            <?php if ($friendStatus === null): ?>
                                <a href="profile.php?id=<?= $profileUser['id'] ?>&add_friend=<?= $profileUser['id'] ?>" class="btn btn-fb-blue btn-sm px-3"><i class="bi bi-person-plus-fill me-1"></i> Add Friend</a>
                            <?php elseif ($friendStatus === 'request_sent'): ?>
                                <button class="btn btn-secondary btn-sm px-3" disabled><i class="bi bi-clock me-1"></i> Request Sent</button>
                            <?php elseif ($friendStatus === 'accepted'): ?>
                                <button class="btn btn-secondary btn-sm px-3"><i class="bi bi-person-check-fill me-1"></i> Friends</button>
                            <?php elseif ($friendStatus === 'request_received'): ?>
                                <a href="friends.php" class="btn btn-fb-blue btn-sm px-3"><i class="bi bi-person-check me-1"></i> Respond</a>
                            <?php endif; ?>
                            <button class="btn btn-secondary btn-sm px-3"><i class="bi bi-chat-dots-fill me-1"></i> Message</button>
                        <?php endif; ?>
                    </div>
                </div>
                <hr>
                <!-- Profile Navigation Tabs -->
                <div class="d-flex gap-1 profile-tabs">
                    <a href="profile.php?id=<?= $profileUser['id'] ?>" class="profile-tab active">Posts</a>
                    <a href="#" class="profile-tab">About</a>
                    <a href="#" class="profile-tab">Friends</a>
                    <a href="#" class="profile-tab">Photos</a>
                </div>
            </div>

            <!-- Profile Body Layout -->
            <div class="row g-3 mt-1">
                <!-- Left Column: Intro & Friends -->
                <div class="col-lg-5">
                    <!-- Intro Card -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Intro</h5>
                            <?php if (!empty($profileUser['bio'])): ?>
                                <p class="text-center text-secondary mb-3"><?= e($profileUser['bio']) ?></p>
                            <?php endif; ?>

                            <!-- Edit Bio Form (Only if own profile) -->
                            <?php if ($isOwnProfile): ?>
                                <form method="POST">
                                    <textarea name="bio" class="form-control form-control-fb mb-2" rows="2" placeholder="Describe who you are..."><?= e($profileUser['bio'] ?? '') ?></textarea>
                                    <button type="submit" name="update_bio" class="btn btn-fb-blue btn-sm w-100">Save Bio</button>
                                </form>
                            <?php endif; ?>

                            <!-- User Details -->
                            <div class="mt-3">
                                <div class="d-flex align-items-center gap-2 mb-2 text-secondary">
                                    <i class="bi bi-envelope-fill"></i>
                                    <span class="small"><?= e($profileUser['email']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2 text-secondary">
                                    <i class="bi bi-calendar3"></i>
                                    <span class="small">Joined <?= date('F Y', strtotime($profileUser['created_at'])) ?></span>
                                </div>
                                <?php if ($profileUser['gender']): ?>
                                <div class="d-flex align-items-center gap-2 mb-2 text-secondary">
                                    <i class="bi bi-person-fill"></i>
                                    <span class="small"><?= e($profileUser['gender']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Friends Card -->
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="fw-bold mb-0">Friends</h5>
                                    <small class="text-secondary"><?= $friendCount ?> friend<?= $friendCount != 1 ? 's' : '' ?></small>
                                </div>
                                <a href="friends.php" class="text-decoration-none small" style="color: var(--fb-blue);">See all</a>
                            </div>
                            <?php if (empty($friendsList)): ?>
                                <p class="text-secondary small text-center">No friends yet</p>
                            <?php else: ?>
                                <div class="row g-2">
                                    <?php foreach ($friendsList as $friend):
                                        $fInitials = strtoupper(substr($friend['first_name'], 0, 1) . substr($friend['last_name'], 0, 1));
                                    ?>
                                        <div class="col-4 text-center">
                                            <a href="profile.php?id=<?= $friend['id'] ?>" class="text-decoration-none text-dark">
                                                <span class="avatar-circle d-inline-flex mb-1"><?= $fInitials ?></span>
                                                <div class="small fw-medium text-truncate"><?= e($friend['first_name']) ?></div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Posts -->
                <div class="col-lg-7">
                    <!-- Create Post Box (Only if own profile) -->
                    <?php if ($isOwnProfile): ?>
                    <div class="card border-0 shadow-sm rounded-3 mb-3">
                        <div class="card-body">
                            <form method="POST" action="index.php">
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <span class="avatar-circle avatar-circle-lg"><?= $currentInitials ?></span>
                                    <input type="text" name="post_content_trigger" class="form-control create-post-input" placeholder="What's on your mind?" data-bs-toggle="modal" data-bs-target="#createPostModal" readonly>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- User's Posts Feed -->
                    <?php if (empty($posts)): ?>
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-pencil-square display-4 text-secondary mb-3 d-block"></i>
                                <h5 class="fw-semibold text-secondary">No Posts Yet</h5>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($posts as $post): ?>
                        <div class="card border-0 shadow-sm rounded-3 mb-3 post-card">
                            <div class="card-body">
                                <!-- Post Header -->
                                <div class="d-flex align-items-center mb-3">
                                    <span class="avatar-circle avatar-circle-lg me-3"><?= $profileInitials ?></span>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= $profileFullName ?></div>
                                        <div class="small text-secondary"><?= timeAgo($post['created_at']) ?> · <i class="bi bi-globe2"></i></div>
                                    </div>
                                </div>
                                <!-- Post Content -->
                                <p class="mb-3"><?= nl2br(e($post['content'])) ?></p>
                                
                                <!-- Post Stats -->
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
                                <!-- Post Actions -->
                                <div class="d-flex">
                                    <a href="profile.php?id=<?= $profileId ?>&like=<?= $post['id'] ?>" class="post-action-btn text-decoration-none <?= $post['user_liked'] ? 'liked' : '' ?>">
                                        <i class="bi bi-hand-thumbs-up<?= $post['user_liked'] ? '-fill' : '' ?>"></i>
                                        <?= $post['user_liked'] ? 'Liked' : 'Like' ?>
                                    </a>
                                    <button class="post-action-btn"><i class="bi bi-chat"></i> Comment</button>
                                    <button class="post-action-btn"><i class="bi bi-share"></i> Share</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Post Modal (Same as index.php) -->
    <div class="modal fade modal-fb" id="createPostModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Create post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="index.php">
                        <textarea name="post_content" class="form-control border-0" placeholder="What's on your mind?" rows="4" required style="resize: none; font-size: 1.1rem;"></textarea>
                        <div class="d-grid mt-3">
                            <button type="submit" class="btn btn-fb-blue">Post</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Bootstrap JS -->
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
