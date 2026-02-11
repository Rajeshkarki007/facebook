<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

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

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['first_name'] = trim($_POST['first_name'] ?? '');
    $formData['last_name'] = trim($_POST['last_name'] ?? '');
    $formData['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $formData['gender'] = $_POST['gender'] ?? '';
    $formData['birth_month'] = $_POST['birth_month'] ?? '';
    $formData['birth_day'] = $_POST['birth_day'] ?? '';
    $formData['birth_year'] = $_POST['birth_year'] ?? '';

    // Validation
    if (empty($formData['first_name']) || empty($formData['last_name']) || empty($formData['email']) || empty($password) || empty($formData['gender'])) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$formData['email']]);
        if ($stmt->fetch()) {
            $error = 'An account with this email already exists.';
        } else {
            // Build birthdate
            $birthdate = sprintf('%04d-%02d-%02d', $formData['birth_year'], $formData['birth_month'], $formData['birth_day']);
            
            // Hash password and insert user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, gender, birthdate) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $formData['first_name'],
                $formData['last_name'],
                $formData['email'],
                $hashedPassword,
                $formData['gender'],
                $birthdate
            ]);

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
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <!-- Left Branding -->
            <div class="auth-branding">
                <div class="logo">facebook</div>
                <p class="tagline">It's quick and easy.</p>
            </div>

            <!-- Signup Card -->
            <div class="auth-card" style="width: 440px;">
                <h2>Create a new account</h2>
                <p class="subtitle">It's quick and easy.</p>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠️</span>
                        <?= e($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="signup.php">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="first_name" placeholder="First name" value="<?= e($formData['first_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="last_name" placeholder="Last name" value="<?= e($formData['last_name']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email address" value="<?= e($formData['email']) ?>" required>
                    </div>

                    <div class="form-group">
                        <input type="password" name="password" placeholder="New password" required>
                    </div>

                    <div class="form-group">
                        <input type="password" name="confirm_password" placeholder="Confirm password" required>
                    </div>

                    <div class="form-group">
                        <label>Date of birth</label>
                        <div class="form-row">
                            <div class="form-group">
                                <select name="birth_month" required>
                                    <option value="">Month</option>
                                    <?php
                                    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                                    for ($i = 1; $i <= 12; $i++):
                                    ?>
                                        <option value="<?= $i ?>" <?= $formData['birth_month'] == $i ? 'selected' : '' ?>><?= $months[$i-1] ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="birth_day" required>
                                    <option value="">Day</option>
                                    <?php for ($i = 1; $i <= 31; $i++): ?>
                                        <option value="<?= $i ?>" <?= $formData['birth_day'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <select name="birth_year" required>
                                    <option value="">Year</option>
                                    <?php for ($i = date('Y'); $i >= 1905; $i--): ?>
                                        <option value="<?= $i ?>" <?= $formData['birth_year'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Gender</label>
                        <div class="form-row">
                            <label style="display:flex;align-items:center;gap:6px;padding:10px 14px;border:2px solid var(--border-color);border-radius:var(--radius-md);cursor:pointer;flex:1;justify-content:space-between;font-size:0.95rem;font-weight:400;text-transform:none;letter-spacing:0;">
                                Female
                                <input type="radio" name="gender" value="Female" <?= $formData['gender'] === 'Female' ? 'checked' : '' ?> required>
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;padding:10px 14px;border:2px solid var(--border-color);border-radius:var(--radius-md);cursor:pointer;flex:1;justify-content:space-between;font-size:0.95rem;font-weight:400;text-transform:none;letter-spacing:0;">
                                Male
                                <input type="radio" name="gender" value="Male" <?= $formData['gender'] === 'Male' ? 'checked' : '' ?>>
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;padding:10px 14px;border:2px solid var(--border-color);border-radius:var(--radius-md);cursor:pointer;flex:1;justify-content:space-between;font-size:0.95rem;font-weight:400;text-transform:none;letter-spacing:0;">
                                Other
                                <input type="radio" name="gender" value="Other" <?= $formData['gender'] === 'Other' ? 'checked' : '' ?>>
                            </label>
                        </div>
                    </div>

                    <p style="font-size:0.75rem;color:var(--text-secondary);margin:10px 0;line-height:1.4;">
                        People who use our service may have uploaded your contact information to Facebook. 
                        <a href="#">Learn more</a>.
                    </p>
                    <p style="font-size:0.75rem;color:var(--text-secondary);margin-bottom:16px;line-height:1.4;">
                        By clicking Sign Up, you agree to our <a href="#">Terms</a>, <a href="#">Privacy Policy</a> and <a href="#">Cookies Policy</a>.
                    </p>

                    <div style="text-align: center;">
                        <button type="submit" class="btn btn-success" style="padding:14px 80px;font-size:1.1rem;">Sign Up</button>
                    </div>
                </form>

                <div class="auth-footer">
                    <p><a href="login.php">Already have an account?</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
