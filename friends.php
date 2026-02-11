<?php
// Include configuration file
require_once 'config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current user info for navbar
$user = getCurrentUser($pdo);
// Generate initials (e.g., "JD")
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));

// === Handle Friend Request Actions ===

// Action: Accept a friend request
if (isset($_GET['accept'])) {
    $requestId = (int)$_GET['accept'];
    // Update status to 'accepted' ONLY if the current user is the receiver
    $stmt = $pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$requestId, $user['id']]);
    redirect('friends.php');
}

// Action: Reject a friend request
if (isset($_GET['reject'])) {
    $requestId = (int)$_GET['reject'];
    // Delete the request from database
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$requestId, $user['id']]);
    redirect('friends.php');
}

// Action: Unfriend a user
if (isset($_GET['unfriend'])) {
    $friendId = (int)$_GET['unfriend'];
    // Delete the accepted friendship record (checking both sender and receiver roles)
    $stmt = $pdo->prepare("DELETE FROM friend_requests WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) AND status = 'accepted'");
    $stmt->execute([$user['id'], $friendId, $friendId, $user['id']]);
    redirect('friends.php');
}

// === Fetch Data for Display ===

// 1. Fetch Received Pending Requests
$stmt = $pdo->prepare("
    SELECT fr.id as request_id, u.id, u.first_name, u.last_name, u.email, fr.created_at
    FROM friend_requests fr
    JOIN users u ON fr.sender_id = u.id
    WHERE fr.receiver_id = ? AND fr.status = 'pending'
    ORDER BY fr.created_at DESC
");
$stmt->execute([$user['id']]);
$pendingRequests = $stmt->fetchAll();

// 2. Fetch All Accepted Friends
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.email, u.bio, fr.created_at as friend_since
    FROM friend_requests fr
    JOIN users u ON (CASE WHEN fr.sender_id = ? THEN fr.receiver_id ELSE fr.sender_id END) = u.id
    WHERE (fr.sender_id = ? OR fr.receiver_id = ?) AND fr.status = 'accepted'
    ORDER BY u.first_name
");
$stmt->execute([$user['id'], $user['id'], $user['id']]);
$friends = $stmt->fetchAll();

// 3. Fetch Suggestions (People You May Know)
// Logic: Users who are NOT me AND NOT already my friends/requests
$stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.bio
    FROM users u
    WHERE u.id != ?
    AND u.id NOT IN (
        SELECT CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END
        FROM friend_requests
        WHERE sender_id = ? OR receiver_id = ?
    )
    ORDER BY RAND()  -- Randomize suggestions
    LIMIT 8
");
$stmt->execute([$user['id'], $user['id'], $user['id'], $user['id']]);
$suggestions = $stmt->fetchAll();

// Background gradients for avatars
$gradients = [
    'linear-gradient(135deg, #667eea, #764ba2)',
    'linear-gradient(135deg, #f093fb, #f5576c)',
    'linear-gradient(135deg, #4facfe, #00f2fe)',
    'linear-gradient(135deg, #43e97b, #38f9d7)',
    'linear-gradient(135deg, #fa709a, #fee140)',
    'linear-gradient(135deg, #a18cd1, #fbc2eb)',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friends | Facebook</title>
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <!-- NANDBAR (Same as index.php) -->
    <nav class="navbar navbar-expand navbar-light bg-white navbar-fb fixed-top">
        <div class="container-fluid">
            <!-- Logo & Search -->
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="navbar-brand mb-0 fw-bold" style="color: var(--fb-blue); font-size: 2rem; letter-spacing: -1px;">f</a>
                <div class="search-fb d-none d-sm-block">
                    <i class="bi bi-search search-icon"></i>
                    <form action="search.php" method="GET">
                        <input type="text" name="q" class="form-control" placeholder="Search Facebook">
                    </form>
                </div>
            </div>
            <!-- Center Tabs -->
            <div class="d-none d-md-flex align-items-center gap-1 mx-auto">
                <a href="index.php" class="nav-tab-fb" title="Home"><i class="bi bi-house-door-fill"></i></a>
                <a href="friends.php" class="nav-tab-fb active" title="Friends"><i class="bi bi-people-fill"></i></a>
                <a href="#" class="nav-tab-fb" title="Watch"><i class="bi bi-play-btn-fill"></i></a>
                <a href="#" class="nav-tab-fb" title="Marketplace"><i class="bi bi-shop"></i></a>
                <a href="#" class="nav-tab-fb" title="Groups"><i class="bi bi-grid-fill"></i></a>
            </div>
            <!-- Right Profile Menu -->
            <div class="d-flex align-items-center gap-2">
                <button class="btn-icon-fb" title="Messenger"><i class="bi bi-chat-dots-fill"></i></button>
                <button class="btn-icon-fb" title="Notifications"><i class="bi bi-bell-fill"></i></button>
                <div class="dropdown">
                    <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <span class="avatar-circle avatar-circle-sm"><?= $initials ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-fb">
                        <li><a class="dropdown-item rounded-3 py-2" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item rounded-3 py-2" href="settings.php"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item rounded-3 py-2 text-danger" href="#" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="bi bi-box-arrow-right me-2"></i>Log Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- FRIENDS PAGE CONTENT -->
    <div class="container" style="margin-top: 72px; max-width: 940px;">

        <!-- Section 1: Friend Requests -->
        <?php if (!empty($pendingRequests)): ?>
        <div class="mb-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-person-plus-fill me-2" style="color: var(--fb-blue);"></i>Friend Requests <span class="badge rounded-pill" style="background: var(--fb-blue);"><?= count($pendingRequests) ?></span></h4>
            <div class="row g-3">
                <?php foreach ($pendingRequests as $idx => $request):
                    $rInitials = strtoupper(substr($request['first_name'], 0, 1) . substr($request['last_name'], 0, 1));
                    $grad = $gradients[$idx % count($gradients)];
                ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 friend-card">
                            <div class="card-body text-center p-3">
                                <a href="profile.php?id=<?= $request['id'] ?>" class="text-decoration-none text-dark">
                                    <span class="avatar-circle d-inline-flex mb-2" style="width: 80px; height: 80px; font-size: 1.8rem; background: <?= $grad ?>;"><?= $rInitials ?></span>
                                    <h6 class="fw-bold mb-1"><?= e($request['first_name'] . ' ' . $request['last_name']) ?></h6>
                                </a>
                                <p class="text-secondary small mb-2"><?= timeAgo($request['created_at']) ?></p>
                                <div class="d-grid gap-2">
                                    <a href="friends.php?accept=<?= $request['request_id'] ?>" class="btn btn-fb-blue btn-sm">Confirm</a>
                                    <a href="friends.php?reject=<?= $request['request_id'] ?>" class="btn btn-secondary btn-sm">Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Section 2: Current Friends List -->
        <div class="mb-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-people-fill me-2" style="color: var(--fb-blue);"></i>Your Friends <span class="text-secondary fw-normal fs-6">(<?= count($friends) ?>)</span></h4>
            <?php if (empty($friends)): ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-people display-4 text-secondary mb-3 d-block"></i>
                        <h5 class="fw-semibold text-secondary">No friends yet</h5>
                        <p class="text-secondary small">Start connecting with people!</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($friends as $idx => $friend):
                        $fInitials = strtoupper(substr($friend['first_name'], 0, 1) . substr($friend['last_name'], 0, 1));
                        $grad = $gradients[$idx % count($gradients)];
                    ?>
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 shadow-sm rounded-3 h-100 friend-card">
                                <div class="card-body text-center p-3">
                                    <a href="profile.php?id=<?= $friend['id'] ?>" class="text-decoration-none text-dark">
                                        <span class="avatar-circle d-inline-flex mb-2" style="width: 80px; height: 80px; font-size: 1.8rem; background: <?= $grad ?>;"><?= $fInitials ?></span>
                                        <h6 class="fw-bold mb-1"><?= e($friend['first_name'] . ' ' . $friend['last_name']) ?></h6>
                                    </a>
                                    <?php if (!empty($friend['bio'])): ?>
                                        <p class="text-secondary small mb-2 text-truncate"><?= e($friend['bio']) ?></p>
                                    <?php endif; ?>
                                    <a href="friends.php?unfriend=<?= $friend['id'] ?>" class="btn btn-outline-secondary btn-sm w-100" onclick="return confirm('Unfriend <?= e($friend['first_name']) ?>?')">
                                        <i class="bi bi-person-dash me-1"></i> Unfriend
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section 3: People You May Know -->
        <?php if (!empty($suggestions)): ?>
        <div class="mb-4">
            <h4 class="fw-bold mb-3"><i class="bi bi-person-badge me-2" style="color: var(--fb-blue);"></i>People You May Know</h4>
            <div class="row g-3">
                <?php foreach ($suggestions as $idx => $person):
                    $pInitials = strtoupper(substr($person['first_name'], 0, 1) . substr($person['last_name'], 0, 1));
                    $grad = $gradients[$idx % count($gradients)];
                ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-3 h-100 friend-card">
                            <div class="card-body text-center p-3">
                                <a href="profile.php?id=<?= $person['id'] ?>" class="text-decoration-none text-dark">
                                    <span class="avatar-circle d-inline-flex mb-2" style="width: 80px; height: 80px; font-size: 1.8rem; background: <?= $grad ?>;"><?= $pInitials ?></span>
                                    <h6 class="fw-bold mb-1"><?= e($person['first_name'] . ' ' . $person['last_name']) ?></h6>
                                </a>
                                <a href="profile.php?id=<?= $person['id'] ?>&add_friend=<?= $person['id'] ?>" class="btn btn-fb-blue btn-sm w-100">
                                    <i class="bi bi-person-plus me-1"></i> Add Friend
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
