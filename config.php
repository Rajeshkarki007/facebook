<?php
// Start the session to track user login state across pages
session_start();

// Database configuration
// ----------------------
// Connection details for MySQL database
$host = 'localhost';
$dbname = 'facebook_clone';
$username = 'root';
$password = ''; // Default XAMPP password is empty

try {
    // Create a new PDO instance to connect to the database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set error mode to exception to catch database errors easily
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array (fetch rows as ['col_name' => 'value'])
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If connection fails, stop execution and show error message
    die("Connection failed: " . $e->getMessage());
}

// Helper Functions
// ----------------

/**
 * Redirect to a specific URL and stop script execution
 * @param string $url The destination URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if a user is currently logged in
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    // Check if 'user_id' exists in the session
    return isset($_SESSION['user_id']);
}

/**
 * Get the currently logged-in user's data from the database
 * @param PDO $pdo The database connection object
 * @return array|null User data array or null if not logged in
 */
function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    
    // Prepare SQL to prevent SQL injection
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Sanitize output to prevent XSS (Cross-Site Scripting) attacks
 * Always use this when displaying user-generated content
 * @param string $string The raw string
 * @return string The sanitized string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format a timestamp into a human-readable "time ago" string
 * @param string $datetime The timestamp string
 * @return string Formatted string (e.g., "5 minutes ago")
 */
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago); // Calculate difference

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>
