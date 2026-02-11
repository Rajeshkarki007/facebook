<?php
// Include configuration for database and helper functions
require_once 'config.php';

// Redirect to home if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Initialize variables to store form data and errors
$error = '';
$formData = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'gender' => '',
    'birth_month' => '',
    'birth_day' => '',
    'birth_year' => '',
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input
    $formData['first_name'] = trim($_POST['first_name'] ?? '');
    $formData['last_name'] = trim($_POST['last_name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $formData['gender'] = $_POST['gender'] ?? '';
    $formData['birth_month'] = $_POST['birth_month'] ?? '';
    $formData['birth_day'] = $_POST['birth_day'] ?? '';
    $formData['birth_year'] = $_POST['birth_year'] ?? '';

    // Step 1: Validation
    // Check if required fields are empty
    if (empty($formData['first_name']) || empty($formData['last_name']) || empty($formData['email']) || empty($password) || empty($formData['gender'])) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        // Validate email format
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        // Enforce password length
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        // Check if passwords match
        $error = 'Passwords do not match.';
    } else {
        // Step 2: Check for existing account
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            // Step 3: Create new account
            
            // Format birthdate as YYYY-MM-DD for MySQL
            $birthdate = sprintf('%04d-%02d-%02d', $formData['birth_year'], $formData['birth_month'], $formData['birth_day']);
            
            // Hash the password securely before storing
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user into database
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, gender, birthdate) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $formData['first_name'],
                $formData['last_name'],
                $formData['email'],
                $hashedPassword,
                $formData['gender'],
                $birthdate
            ]);

            // Redirect to login page with success flag
            redirect('login.php?registered=1');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up for Facebook</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <div class="auth-page d-flex align-items-center justify-content-center">
        <div class="container">
            <div class="row align-items-center justify-content-center g-5">
                <!-- Branding Section -->
                <div class="col-lg-5 text-center text-lg-start">
                    <div class="fb-logo mb-2">facebook</div>
                    <p class="fs-5 text-secondary">It's quick and easy.</p>
                </div>

                <!-- Signup Form Card -->
                <div class="col-lg-6">
                    <div class="card shadow-lg border-0 rounded-4 p-4 auth-card">
                        <div class="card-body">
                            <h2 class="fw-bold mb-1">Create a new account</h2>
                            <p class="text-secondary mb-3">It's quick and easy.</p>

                            <!-- Error Alert -->
                            <?php if ($error): ?>
                                <div class="alert alert-danger d-flex align-items-center" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <?= e($error) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Signup Form -->
                            <form method="POST" action="signup.php">
                                <div class="row g-2 mb-3">
                                    <div class="col">
                                        <input type="text" name="first_name" class="form-control form-control-fb" placeholder="First name" value="<?= e($formData['first_name']) ?>" required>
                                    </div>
                                    <div class="col">
                                        <input type="text" name="last_name" class="form-control form-control-fb" placeholder="Last name" value="<?= e($formData['last_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <input type="email" name="email" class="form-control form-control-fb" placeholder="Email address" value="<?= e($formData['email']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <input type="password" name="password" class="form-control form-control-fb" placeholder="New password" required>
                                </div>

                                <div class="mb-3">
                                    <input type="password" name="confirm_password" class="form-control form-control-fb" placeholder="Confirm password" required>
                                </div>

                                <!-- Date of Birth Selection -->
                                <div class="mb-3">
                                    <label class="form-label text-secondary small">Date of birth</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <select name="birth_month" class="form-select form-control-fb" required>
                                                <option value="">Month</option>
                                                <?php
                                                $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                                for ($i = 1; $i <= 12; $i++):
                                                ?>
                                                    <option value="<?= $i ?>" <?= $formData['birth_month'] == $i ? 'selected' : '' ?>><?= $months[$i-1] ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col">
                                            <select name="birth_day" class="form-select form-control-fb" required>
                                                <option value="">Day</option>
                                                <?php for ($i = 1; $i <= 31; $i++): ?>
                                                    <option value="<?= $i ?>" <?= $formData['birth_day'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col">
                                            <select name="birth_year" class="form-select form-control-fb" required>
                                                <option value="">Year</option>
                                                <?php for ($i = date('Y'); $i >= 1905; $i--): ?>
                                                    <option value="<?= $i ?>" <?= $formData['birth_year'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Gender Selection -->
                                <div class="mb-3">
                                    <label class="form-label text-secondary small">Gender</label>
                                    <div class="row g-2">
                                        <div class="col">
                                            <label class="gender-option w-100">
                                                Female
                                                <input type="radio" name="gender" value="Female" <?= $formData['gender'] === 'Female' ? 'checked' : '' ?> required>
                                            </label>
                                        </div>
                                        <div class="col">
                                            <label class="gender-option w-100">
                                                Male
                                                <input type="radio" name="gender" value="Male" <?= $formData['gender'] === 'Male' ? 'checked' : '' ?>>
                                            </label>
                                        </div>
                                        <div class="col">
                                            <label class="gender-option w-100">
                                                Other
                                                <input type="radio" name="gender" value="Other" <?= $formData['gender'] === 'Other' ? 'checked' : '' ?>>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <p class="small text-secondary lh-sm">People who use our service may have uploaded your contact information to Facebook. <a href="#">Learn more</a>.</p>
                                <p class="small text-secondary mb-3 lh-sm">By clicking Sign Up, you agree to our <a href="#">Terms</a>, <a href="#">Privacy Policy</a> and <a href="#">Cookies Policy</a>.</p>

                                <div class="text-center">
                                    <button type="submit" class="btn btn-lg btn-fb-green px-5">Sign Up</button>
                                </div>
                            </form>

                            <hr>
                            <div class="text-center">
                                <a href="login.php" class="text-decoration-none fw-semibold" style="color: var(--fb-blue);">Already have an account?</a>
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
