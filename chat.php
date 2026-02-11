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
$fullName = e($user['first_name'] . ' ' . $user['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messenger | Facebook</title>
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📘</text></svg>">
    <style>
        /* Chat-specific styles */
        body { overflow: hidden; height: 100vh; } /* Prevent main scrollbar */
        .chat-layout { height: calc(100vh - 56px); display: flex; }
        
        /* Sidebar (Conversation List) */
        .chat-sidebar {
            width: 360px;
            border-right: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            background: #fff;
        }
        .chat-list { overflow-y: auto; flex: 1; }
        .chat-item {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            border-radius: 8px;
            margin: 4px 8px;
            transition: background 0.2s;
        }
        .chat-item:hover, .chat-item.active { background-color: #f3f4f6; }
        .chat-item.unread { background-color: #edf2fa; }
        .chat-item.unread .fw-bold { color: var(--fb-blue); }
        
        /* Main Chat Area */
        .chat-main { flex: 1; display: flex; flex-direction: column; background: #fff; }
        .chat-header {
            padding: 10px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        /* Message Bubbles */
        .chat-bubble {
            max-width: 70%;
            padding: 8px 12px;
            border-radius: 18px;
            font-size: 0.95rem;
            position: relative;
            word-wrap: break-word;
        }
        .chat-bubble-mine {
            background-color: var(--fb-blue);
            color: white;
            border-bottom-right-radius: 4px; /* Distinct shape for own messages */
        }
        .chat-bubble-theirs {
            background-color: #f0f2f5;
            color: #050505;
            border-bottom-left-radius: 4px;
        }
        
        /* Input Area */
        .chat-input-area {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            background: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Responsive: Hide sidebar on mobile when chat is open */
        @media (max-width: 768px) {
            .chat-sidebar { width: 100%; }
            .chat-main { display: none; width: 100%; position: absolute; z-index: 10; height: 100%; }
            .chat-main.active { display: flex; }
            .chat-sidebar.hidden { display: none; }
        }
    </style>
</head>
<body>
    <!-- NBSVAR (Same as index.php) -->
    <nav class="navbar navbar-expand navbar-light bg-white navbar-fb fixed-top">
        <div class="container-fluid">
            <!-- Logo & Link to Home -->
            <div class="d-flex align-items-center gap-2">
                <a href="index.php" class="navbar-brand mb-0 fw-bold" style="color: var(--fb-blue); font-size: 2rem; letter-spacing: -1px;">f</a>
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
                <button class="btn-icon-fb active" title="Messenger"><i class="bi bi-chat-dots-fill"></i></button>
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

    <!-- CHAT LAYOUT -->
    <div class="container-fluid p-0 chat-layout" style="margin-top: 56px;">
        
        <!-- SIDEBAR: Conversation List -->
        <div class="chat-sidebar" id="chatSidebar">
            <div class="p-3">
                <h4 class="fw-bold mb-3">Chats</h4>
                <div class="input-group rounded-pill bg-light border-0 px-3 py-1">
                    <span class="input-group-text bg-transparent border-0 text-secondary"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-transparent border-0 shadow-none" placeholder="Search Messenger">
                </div>
            </div>
            
            <!-- Conversation List Container (Populated via JS) -->
            <div class="chat-list" id="conversationList">
                <div class="text-center text-secondary mt-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CHAT AREA -->
        <!-- Initially Hidden Empty State -->
        <div class="chat-main justify-content-center align-items-center bg-white" id="chatEmptyState">
            <div class="text-center">
                <div class="display-1 text-secondary opacity-25 mb-3"><i class="bi bi-chat-square-text-fill"></i></div>
                <h4 class="text-secondary">Select a conversation to start messaging</h4>
            </div>
        </div>

        <!-- Active Chat Interface (Hidden by default) -->
        <div class="chat-main" id="chatInterface" style="display: none !important;">
            <!-- Header -->
            <div class="chat-header">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-icon-fb d-md-none me-2" onclick="closeChat()"><i class="bi bi-arrow-left"></i></button>
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar-circle avatar-circle-sm" id="chatHeaderAvatar"></span>
                        <div>
                            <div class="fw-bold" id="chatHeaderName">User</div>
                            <div class="small text-secondary">Active now</div>
                        </div>
                    </div>
                </div>
                <button class="btn-icon-fb text-primary"><i class="bi bi-info-circle-fill"></i></button>
            </div>

            <!-- Messages Container -->
            <div class="chat-messages" id="messagesContainer">
                <!-- Messages will be injected here via JS -->
            </div>

            <!-- Input Area -->
            <div class="chat-input-area">
                <button class="btn-icon-fb text-primary"><i class="bi bi-plus-circle-fill"></i></button>
                <button class="btn-icon-fb text-primary"><i class="bi bi-image-fill"></i></button>
                <button class="btn-icon-fb text-primary"><i class="bi bi-filetype-gif"></i></button>
                
                <form id="messageForm" class="flex-grow-1 d-flex gap-2">
                    <input type="hidden" id="activeReceiverId" name="receiver_id">
                    <input type="text" id="messageInput" class="form-control rounded-pill bg-light border-0 px-3" placeholder="Aa" required autocomplete="off">
                    <button type="submit" class="btn-icon-fb text-primary"><i class="bi bi-send-fill"></i></button>
                </form>
                
                <button class="btn-icon-fb text-primary"><i class="bi bi-hand-thumbs-up-fill"></i></button>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
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

    // === CHAT JAVASCRIPT LOGIC ===
    let currentReceiverId = null;
    let lastMessageId = 0;
    let pollInterval = null;

    // Load initial conversation list
    document.addEventListener('DOMContentLoaded', function() {
        loadConversations();
        // Poll for new conversations/updates every 5 seconds
        setInterval(loadConversations, 5000);
    });

    /**
     * Function to load list of recent conversations via AJAX
     */
    function loadConversations() {
        fetch('ajax_chat.php?action=get_conversations')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const list = document.getElementById('conversationList');
                    // Only update if content changed or first load (simplified)
                    // For now, simple redraw. In prod, differencing would be better.
                    let html = '';
                    if (data.conversations.length === 0) {
                        html = '<div class="text-center text-secondary mt-5"><p>No conversations yet.</p><a href="friends.php" class="btn btn-sm btn-fb-blue">Find Friends</a></div>';
                    } else {
                        data.conversations.forEach(conv => {
                            const initials = conv.first_name[0] + conv.last_name[0];
                            const isActive = currentReceiverId == conv.user_id ? 'active' : '';
                            const isUnread = false; // Logic for unread could be added here
                            
                            html += `
                                <div class="chat-item ${isActive}" onclick="openChat(${conv.user_id}, '${conv.first_name} ${conv.last_name}', '${initials}')">
                                    <span class="avatar-circle avatar-circle-md flex-shrink-0" style="background: linear-gradient(135deg, #667eea, #764ba2);">${initials}</span>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-bold text-truncate">${conv.first_name} ${conv.last_name}</div>
                                        <div class="small text-secondary text-truncate">${conv.last_message || 'Start a conversation'}</div>
                                    </div>
                                    <div class="small text-secondary" style="font-size: 0.7rem;">${timeAgo(conv.last_activity)}</div>
                                </div>
                            `;
                        });
                    }
                    list.innerHTML = html;
                }
            })
            .catch(error => console.error('Error loading conversations:', error));
    }

    /**
     * Helper to format time relative (e.g. "5m")
     */
    function timeAgo(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm';
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h';
        const days = Math.floor(hours / 24);
        return days + 'd';
    }

    /**
     * Open a specific chat conversation
     */
    function openChat(userId, name, initials) {
        currentReceiverId = userId;
        document.getElementById('activeReceiverId').value = userId;
        document.getElementById('chatHeaderName').innerText = name;
        document.getElementById('chatHeaderAvatar').innerText = initials;
        
        // Show chat UI
        document.getElementById('chatEmptyState').style.setProperty('display', 'none', 'important');
        document.getElementById('chatInterface').style.setProperty('display', 'flex', 'important');
        document.getElementById('chatInterface').classList.add('active');
        
        // Mobile view adjustments
        if (window.innerWidth <= 768) {
            document.getElementById('chatSidebar').classList.add('hidden');
        }
        
        // Reset messages and load new ones
        document.getElementById('messagesContainer').innerHTML = '';
        lastMessageId = 0;
        loadMessages();
        
        // Start polling for this chat
        if (pollInterval) clearInterval(pollInterval);
        pollInterval = setInterval(loadMessages, 2000); // Poll every 2s
        
        // Highlight active sidebar item
        loadConversations();
    }

    /**
     * Close active chat (for mobile mostly)
     */
    function closeChat() {
        currentReceiverId = null;
        document.getElementById('chatInterface').classList.remove('active');
        document.getElementById('chatInterface').style.setProperty('display', 'none', 'important');
        document.getElementById('chatEmptyState').style.setProperty('display', 'flex', 'important');
        document.getElementById('chatSidebar').classList.remove('hidden');
        if (pollInterval) clearInterval(pollInterval);
        loadConversations();
    }

    /**
     * Fetch messages for current conversation
     */
    function loadMessages() {
        if (!currentReceiverId) return;

        fetch(`ajax_chat.php?action=get_messages&receiver_id=${currentReceiverId}&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages.length > 0) {
                    const container = document.getElementById('messagesContainer');
                    // Check if user is scrolled to bottom to auto-scroll
                    let shouldScroll = (container.scrollTop + container.clientHeight >= container.scrollHeight - 50);
                    if (lastMessageId === 0) shouldScroll = true; // Always scroll on first load

                    const fragment = document.createDocumentFragment();

                    data.messages.forEach(msg => {
                        lastMessageId = msg.id; // Update tracker
                        
                        const div = document.createElement('div');
                        div.className = `d-flex mb-1 ${msg.is_mine ? 'justify-content-end' : 'justify-content-start'}`;
                        
                        const avatar = !msg.is_mine ? 
                            `<span class="avatar-circle avatar-circle-sm me-2 align-self-end flex-shrink-0" style="width: 28px; height: 28px; font-size: 0.6rem; background: linear-gradient(135deg, #667eea, #764ba2);"></span>` : '';
                        
                        div.innerHTML = `
                            ${avatar}
                            <div class="chat-bubble ${msg.is_mine ? 'chat-bubble-mine' : 'chat-bubble-theirs'}" title="${msg.time}">
                                ${msg.message.replace(/\n/g, '<br>')}
                            </div>
                        `;
                        fragment.appendChild(div);
                    });
                    
                    container.appendChild(fragment);

                    if (shouldScroll) {
                        scrollToBottom();
                    }
                }
            })
            .catch(error => console.error('Error loading messages:', error));
    }

    /**
     * Scroll chat to bottom
     */
    function scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        container.scrollTop = container.scrollHeight;
    }

    /**
     * Handle Message Sending
     */
    document.getElementById('messageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message || !currentReceiverId) return;
        
        // Optimistic UI Update: Add message immediately
        const container = document.getElementById('messagesContainer');
        const tempDiv = document.createElement('div');
        tempDiv.className = 'd-flex mb-1 justify-content-end opacity-50'; // Dimmed until confirmed
        tempDiv.innerHTML = `<div class="chat-bubble chat-bubble-mine">${message.replace(/\n/g, '<br>')}</div>`;
        container.appendChild(tempDiv);
        scrollToBottom();
        
        input.value = ''; // Clear input

        // Send to server
        fetch('ajax_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&receiver_id=${currentReceiverId}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                tempDiv.remove(); // Remove temporary one, next poll or explicit add will handle it.
                // Or better: update status. For now, simple reload triggers immediately.
                loadMessages();
            } else {
                alert('Failed to send message');
                tempDiv.remove();
                input.value = message; // Restore input
            }
        });
    });
    </script>
</body>
</html>
