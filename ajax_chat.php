<?php
// Include configuration file
require_once 'config.php';

// Set header to return JSON (since this is an API)
header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get current user info
$user = getCurrentUser($pdo);
// Get action parameter (what to do)
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// === ACTION: SEND MESSAGE ===
if ($action === 'send_message' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiverId = (int)$_POST['receiver_id'];
    $message = trim($_POST['message']);

    if (!empty($message)) {
        // Insert message into database
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        if ($stmt->execute([$user['id'], $receiverId, $message])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
    }
    exit;
}

// === ACTION: GET MESSAGES (for a specific chat) ===
if ($action === 'get_messages' && isset($_GET['receiver_id'])) {
    $receiverId = (int)$_GET['receiver_id'];
    $lastId = (int)($_GET['last_id'] ?? 0); // Only fetch messages NEWER than this ID

    // Fetch messages between current user and receiver, newer than lastId
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
        AND id > ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$user['id'], $receiverId, $receiverId, $user['id'], $lastId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format messages for frontend
    $formattedMessages = [];
    foreach ($messages as $msg) {
        $formattedMessages[] = [
            'id' => $msg['id'],
            'message' => e($msg['message']), // Sanitize output
            'is_mine' => ($msg['sender_id'] == $user['id']), // Boolean flag
            'time' => date('H:i', strtotime($msg['created_at'])) // Format time
        ];
    }

    echo json_encode(['success' => true, 'messages' => $formattedMessages]);
    exit;
}

// === ACTION: GET CONVERSATIONS (list of recent chats) ===
if ($action === 'get_conversations') {
    // Complex query to get latest message for each conversation
    // 1. Get all unique users interacted with
    // 2. Get the latest message for each pair
    // 3. Join with users table to get names
    
    // Simplified logic: find latest message per conversation pair
    $stmt = $pdo->prepare("
        SELECT 
            u.id as user_id, 
            u.first_name, 
            u.last_name, 
            u.profile_pic,
            m.message as last_message,
            m.created_at as last_activity
        FROM users u
        JOIN (
            SELECT 
                CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id,
                MAX(id) as max_msg_id
            FROM messages
            WHERE sender_id = ? OR receiver_id = ?
            GROUP BY other_user_id
        ) latest ON u.id = latest.other_user_id
        JOIN messages m ON m.id = latest.max_msg_id
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'conversations' => $conversations]);
    exit;
}

// Default response if no action matches
echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
