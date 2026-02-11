<?php
// Include configuration file for database connection and helper functions
require_once 'config.php';

// Redirect to home page if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Initialize error and success variables
$error = '';
$success = '';

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get email and password from form, using trim() to remove extra spaces
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation: Check if fields are empty
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // Prepare SQL statement to find user by email
        // Using prepared statements prevents SQL injection attacks
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verify if user exists AND password matches the hash in database
        if ($user && password_verify($password, $user['password'])) {
            // Login successful: Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Redirect to home page
            redirect('index.php');
        } else {
            // Login failed
            $error = 'Invalid email or password. Please try again.';
        }
    }
}

// Check for signup success message in URL (e.g., login.php?registered=1)
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
    <!-- Bootstrap 5 CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <div class="auth-page d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row align-items-center justify-content-center g-5">
                <!-- Left Side: Facebook Branding -->
                <div class="col-lg-6 text-center text-lg-start">
                    <div class="fb-logo mb-2">facebook</div>
                    <p class="fs-5 text-secondary">Facebook helps you connect and share with the people in your life.</p>
                </div>

                <!-- Right Side: Login Form Card -->
                <div class="col-lg-5">
                    <div class="card shadow-lg border-0 rounded-4 p-4 auth-card">
                        <div class="card-body">
                            <!-- Display Error Message -->
                            <?php if ($error): ?>
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <?= e($error) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Display Success Message -->
                            <?php if ($success): ?>
                                <div class="alert alert-success d-flex align-items-center" role="alert">
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    <?= e($success) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Login Form -->
                            <form method="POST" action="login.php">
                                <div class="mb-3">
                                    <input type="email" name="email" class="form-control form-control-lg form-control-fb" placeholder="Email address" value="<?= e($email ?? '') ?>" required>
                                </div>
                                <div class="mb-3">
                                    <input type="password" name="password" class="form-control form-control-lg form-control-fb" placeholder="Password" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-lg btn-fb-blue">Log In</button>
                                </div>
                            </form>

                            <div class="text-center mt-3">
                                <a href="#" class="text-decoration-none" style="color: var(--fb-blue);">Forgotten password?</a>
                            </div>

                            <div class="divider-text">
                                <span>or</span>
                            </div>

                            <!-- Link to Signup Page -->
                            <div class="text-center">
                                <a href="signup.php" class="btn btn-lg btn-fb-green">Create New Account</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
