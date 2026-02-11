<?php
// Include configuration file
require_once 'config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current user info
$user = getCurrentUser($pdo);
// Generate initials
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));

// Get search query from URL
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchResults = [];

// Perform search if query is not empty
if ($query) {
    // Search for users where first or last name matches the query
    // wildcards (%) are used for partial matches
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE (first_name LIKE ? OR last_name LIKE ?) 
        AND id != ? 
        LIMIT 20
    ");
    $searchTerm = "%$query%";
    $stmt->execute([$searchTerm, $searchTerm, $user['id']]);
    $searchResults = $stmt->fetchAll();
}

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
    <title>Search | Facebook</title>
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <!-- NBSVAR (Same as index.php) -->
    <nav class="navbar navbar-expand navbar-light bg-white navbar-fb fixed-top">
        <div class="container-fluid">
            <!-- Logo & Search Input -->
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="navbar-brand mb-0 fw-bold" style="color: var(--fb-blue); font-size: 2rem; letter-spacing: -1px;">f</a>
                <div class="search-fb d-none d-sm-block">
                    <i class="bi bi-search search-icon"></i>
                    <form action="search.php" method="GET">
                        <input type="text" name="q" class="form-control" placeholder="Search Facebook" value="<?= e($query) ?>" autofocus>
                    </form>
                </div>
            </div>
            <!-- Center Tabs -->
            <div class="d-none d-md-flex align-items-center gap-1 mx-auto">
                <a href="index.php" class="nav-tab-fb" title="Home"><i class="bi bi-house-door-fill"></i></a>
                <a href="friends.php" class="nav-tab-fb" title="Friends"><i class="bi bi-people-fill"></i></a>
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

    <!-- SEARCH RESULTS CONTENT -->
    <div class="container" style="margin-top: 72px; max-width: 800px;">
        <h4 class="fw-bold mb-4">Search Results for "<?= e($query) ?>"</h4>

        <?php if (empty($searchResults)): ?>
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body text-center py-5">
                    <i class="bi bi-search display-4 text-secondary mb-3 d-block"></i>
                    <h5 class="fw-semibold text-secondary">No results found</h5>
                    <p class="text-secondary small">Try checking for typos or searching for someone else.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="card border-0 shadow-sm rounded-3">
                <div class="list-group list-group-flush rounded-3">
                    <?php foreach ($searchResults as $idx => $result):
                        $rInitials = strtoupper(substr($result['first_name'], 0, 1) . substr($result['last_name'], 0, 1));
                        $grad = $gradients[$idx % count($gradients)];
                    ?>
                        <div class="list-group-item p-3 d-flex align-items-center justify-content-between">
                            <a href="profile.php?id=<?= $result['id'] ?>" class="d-flex align-items-center text-decoration-none text-dark gap-3">
                                <span class="avatar-circle d-inline-flex" style="width: 60px; height: 60px; font-size: 1.5rem; background: <?= $grad ?>;"><?= $rInitials ?></span>
                                <div>
                                    <h6 class="fw-bold mb-0"><?= e($result['first_name'] . ' ' . $result['last_name']) ?></h6>
                                    <?php if (!empty($result['bio'])): ?>
                                        <div class="small text-secondary text-truncate" style="max-width: 250px;"><?= e($result['bio']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <a href="profile.php?id=<?= $result['id'] ?>&add_friend=<?= $result['id'] ?>" class="btn btn-secondary btn-sm bg-light text-dark border-0">
                                <i class="bi bi-person-plus-fill me-1"></i> Add Friend
                            </a>
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
