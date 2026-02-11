<?php
// Include configuration file
require_once 'config.php';

// Redirect to login if not logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Get current user info to pre-fill forms
$user = getCurrentUser($pdo);
// Generate initials
$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));

// Initialize messages for feedback
$success = '';
$error = '';

// === Handle Profile Update ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Get and trim inputs
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $bio = trim($_POST['bio'] ?? ''); // Bio is optional
    $gender = $_POST['gender'] ?? '';

    // Validate required fields
    if (empty($firstName) || empty($lastName)) {
        $error = 'First name and last name are required.';
    } else {
        // Update user record in database
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ?, gender = ? WHERE id = ?");
        $stmt->execute([$firstName, $lastName, $bio, $gender, $user['id']]);
        
        $success = 'Profile updated successfully!';
        
        // Refresh user data from DB to show updated values immediately
        $user = getCurrentUser($pdo);
        $initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
    }
}

// === Handle Password Change ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validate all fields filled
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All password fields are required.';
    } elseif (!password_verify($currentPassword, $user['password'])) {
        // Check if current password matches database hash
        $error = 'Current password is incorrect.';
    } elseif (strlen($newPassword) < 6) {
        // Enforce minimum length
        $error = 'New password must be at least 6 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        // Check matching new passwords
        $error = 'New passwords do not match.';
    } else {
        // Hash the new password and update database
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $user['id']]);
        $success = 'Password changed successfully!';
    }
}

// === Handle Account Deletion ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $confirmDelete = $_POST['confirm_delete'] ?? '';
    // Require user to type exact string 'DELETE' for safety
    if ($confirmDelete === 'DELETE') {
        // Delete user record
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Destroy session and redirect to login
        session_destroy();
        redirect('login.php');
    } else {
        $error = 'Type DELETE to confirm account deletion.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | Facebook</title>
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

    <!-- SETTINGS CONTENT -->
    <div class="container" style="margin-top: 72px; max-width: 680px;">
        <h3 class="fw-bold mb-4"><i class="bi bi-gear-wide-connected me-2"></i>Settings</h3>

        <!-- Feedback Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <!-- Section 1: Edit Basic Profile -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-person-circle me-2" style="color: var(--fb-blue);"></i>Edit Profile</h5>
                <form method="POST">
                    <!-- Profile Picture (Placeholder) -->
                    <div class="text-center mb-4">
                        <span class="avatar-circle d-inline-flex" style="width: 100px; height: 100px; font-size: 2.5rem;"><?= $initials ?></span>
                        <div class="small text-secondary mt-2">Profile picture coming soon</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">First Name</label>
                            <input type="text" name="first_name" class="form-control form-control-fb" value="<?= e($user['first_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary">Last Name</label>
                            <input type="text" name="last_name" class="form-control form-control-fb" value="<?= e($user['last_name']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Email (cannot be changed)</label>
                        <input type="email" class="form-control form-control-fb" value="<?= e($user['email']) ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Bio</label>
                        <textarea name="bio" class="form-control form-control-fb" rows="3" placeholder="Tell people about yourself..."><?= e($user['bio'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Gender</label>
                        <div class="row g-2">
                            <div class="col">
                                <label class="gender-option w-100">
                                    Female <input type="radio" name="gender" value="Female" <?= $user['gender'] === 'Female' ? 'checked' : '' ?>>
                                </label>
                            </div>
                            <div class="col">
                                <label class="gender-option w-100">
                                    Male <input type="radio" name="gender" value="Male" <?= $user['gender'] === 'Male' ? 'checked' : '' ?>>
                                </label>
                            </div>
                            <div class="col">
                                <label class="gender-option w-100">
                                    Other <input type="radio" name="gender" value="Other" <?= $user['gender'] === 'Other' ? 'checked' : '' ?>>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" name="update_profile" class="btn btn-fb-blue">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 2: Change Password -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-shield-lock-fill me-2" style="color: var(--fb-blue);"></i>Change Password</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Current Password</label>
                        <input type="password" name="current_password" class="form-control form-control-fb" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">New Password</label>
                        <input type="password" name="new_password" class="form-control form-control-fb" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control form-control-fb" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="change_password" class="btn btn-fb-blue">Change Password</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Section 3: Read-Only Account Info -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <h5 class="fw-bold mb-3"><i class="bi bi-info-circle-fill me-2" style="color: var(--fb-blue);"></i>Account Info</h5>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Email</span>
                    <span class="fw-medium"><?= e($user['email']) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-secondary">Member since</span>
                    <span class="fw-medium"><?= date('F j, Y', strtotime($user['created_at'])) ?></span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-secondary">Birthday</span>
                    <span class="fw-medium"><?= $user['birthdate'] ? date('F j, Y', strtotime($user['birthdate'])) : 'Not set' ?></span>
                </div>
            </div>
        </div>

        <!-- Section 4: Danger Zone (Delete Account) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4" style="border-left: 4px solid #dc2626 !important;">
            <div class="card-body">
                <h5 class="fw-bold mb-3 text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Danger Zone</h5>
                <p class="text-secondary small">Once you delete your account, there is no going back. All your posts, comments, and data will be permanently removed.</p>
                <form method="POST" onsubmit="return confirm('Are you absolutely sure? This action cannot be undone!');">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Type DELETE to confirm</label>
                        <input type="text" name="confirm_delete" class="form-control form-control-fb" placeholder="DELETE" required>
                    </div>
                    <button type="submit" name="delete_account" class="btn btn-danger">
                        <i class="bi bi-trash3 me-1"></i> Delete My Account
                    </button>
                </form>
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
