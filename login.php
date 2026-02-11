<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            redirect('index.php');
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

// Check for signup success message
if (isset($_GET['registered'])) {
    $success = 'Account created successfully! Please log in.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facebook - Log In or Sign Up</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YcnS/1WR6zNg1OZw0YEwBDSzQGWBkDJQIBhGab" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <!-- Left Branding -->
            <div class="auth-branding">
                <div class="logo">facebook</div>
                <p class="tagline">Facebook helps you connect and share with the people in your life.</p>
            </div>

            <!-- Login Card -->
            <div class="auth-card">
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠️</span>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">✅</span>
                        <?= e($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email address" value="<?= e($email ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Log In</button>
                </form>

                <a href="#" class="forgot-link">Forgotten password?</a>

                <div class="divider">
                    <span>or</span>
                </div>

                <div style="text-align: center;">
                    <a href="signup.php" class="btn btn-success">Create New Account</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
