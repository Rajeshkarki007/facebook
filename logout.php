<?php
// Start the session to clear it
session_start();

// Unset all session variables (remove user data)
session_unset();

// Destroy the session completely (invalidates the session ID)
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out | Facebook</title>
    <!-- Include Bootstrap CSS for styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
</head>
<body class="auth-page d-flex align-items-center justify-content-center">
    <!-- Centered Card Container -->
    <div class="text-center" style="animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);">
        <div class="mb-4">
            <div class="fb-logo mb-2">facebook</div>
        </div>
        
        <!-- Goodbye Card -->
        <div class="card auth-card border-0 shadow mx-auto" style="max-width: 420px; border-radius: var(--radius-md);">
            <div class="card-body p-4 text-center">
                <div class="mb-3" style="font-size: 4rem;">👋</div>
                <h3 class="fw-bold mb-2">You've been logged out</h3>
                <p class="text-secondary mb-4">Thank you for using Facebook. See you again soon!</p>
                
                <!-- Login Button -->
                <a href="login.php" class="btn btn-fb-blue w-100 mb-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Log Back In
                </a>
                
                <!-- Sign Up Button -->
                <a href="signup.php" class="btn btn-outline-secondary w-100">
                    Create New Account
                </a>
            </div>
        </div>
        <p class="text-secondary small mt-4">&copy; <?= date('Y') ?> Facebook Clone</p>
    </div>
</body>
</html>
