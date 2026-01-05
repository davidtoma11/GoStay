<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check session
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();
$userId = $_SESSION['user_id'];

// Get unique contacts (including yourself for testing purposes)
// We remove the "u.id != ?" check so self-messages show up in the sidebar
$stmt_contacts = $db->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.role
    FROM users u
    JOIN messages m ON (u.id = m.sender_id OR u.id = m.receiver_id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?)
    ORDER BY (SELECT MAX(created_at) FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)) DESC
");
$stmt_contacts->execute([$userId, $userId, $userId, $userId]);
$contacts = $stmt_contacts->fetchAll(PDO::FETCH_ASSOC);

// Determine which chat is active
$activeContactId = $_GET['chat_with'] ?? ($contacts[0]['id'] ?? null);

// Mark messages as read when opening the chat
if ($activeContactId) {
    $stmt_read = $db->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
    $stmt_read->execute([$userId, $activeContactId]);
}

// Fetch messages for the active conversation
$messages = [];
if ($activeContactId) {
    $stmt_msg = $db->prepare("
        SELECT * FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt_msg->execute([$userId, $activeContactId, $activeContactId, $userId]);
    $messages = $stmt_msg->fetchAll(PDO::FETCH_ASSOC);
}

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message_body'])) {
    $body = htmlspecialchars(strip_tags($_POST['message_body']));
    $stmt_send = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message_body) VALUES (?, ?, ?)");
    $stmt_send->execute([$userId, $activeContactId, $body]);

    // Refresh to show the new message
    header("Location: messages.php?chat_with=$activeContactId");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Messages - GoStay</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles/manager.css">
    <link rel="stylesheet" href="../styles/search_results.css">
    <link rel="stylesheet" href="../styles/footer.css">
</head>

<body style="background: #f4f7ff;">
    <nav class="results-nav">
        <div class="nav-left">
            <div class="nav-logo"></div>
            <h2 class="manager-header-text">Messages</h2>
        </div>
        <div class="nav-icons">
            <a href="home.php" title="Home"><i class="fa-solid fa-house"></i></a>
        </div>
    </nav>

    <div class="results-wrapper">
        <div class="chat-container">

            <div class="chat-sidebar">
                <div style="padding: 25px; border-bottom: 1px solid #f0f0f0;">
                    <h3 style="margin:0; color: #280a3c; font-weight: 900;">Inbox</h3>
                </div>

                <div class="chat-list">
                    <?php if (empty($contacts)): ?>
                        <div style="padding: 20px; text-align: center; color: #888;">
                            <p>No conversations yet.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($contacts as $c):
                        $stmt_unread = $db->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
                        $stmt_unread->execute([$c['id'], $userId]);
                        $unreadCount = $stmt_unread->fetchColumn();
                    ?>
                        <a href="?chat_with=<?= $c['id'] ?>" style="text-decoration: none; color: inherit;">
                            <div class="chat-list-item <?= $activeContactId == $c['id'] ? 'active' : '' ?>">
                                <div class="stat-icon" style="width: 50px; height: 50px; background: #f0e6ff; font-weight: 900; color: #7b2bd4; position: relative;">
                                    <?= strtoupper(substr($c['first_name'], 0, 1)) ?>
                                    <?php if ($unreadCount > 0): ?>
                                        <span style="position: absolute; top: -2px; right: -2px; width: 12px; height: 12px; background: #e74c3c; border: 2px solid #fff; border-radius: 50%;"></span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1; overflow: hidden;">
                                    <div style="font-weight: 700; color: #280a3c;"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></div>
                                    <small style="color: #888; text-transform: capitalize;"><?= $c['role'] ?><?php if ($c['id'] == $userId) echo " (You)"; ?></small>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="chat-main">
                <?php if ($activeContactId): ?>
                    <?php
                    $stmt_header = $db->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
                    $stmt_header->execute([$activeContactId]);
                    $headerUser = $stmt_header->fetch();
                    ?>
                    <div style="padding: 15px 30px; background: #fff; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center; gap: 15px;">
                        <div class="stat-icon" style="width: 35px; height: 35px; font-size: 0.8rem; background: #f8f9fe;"><?= strtoupper(substr($headerUser['first_name'], 0, 1)) ?></div>
                        <h4 style="margin:0;"><?= htmlspecialchars($headerUser['first_name'] . ' ' . $headerUser['last_name']) ?></h4>
                    </div>

                    <div class="chat-messages" id="chatWindow">
                        <?php foreach ($messages as $m): ?>
                            <div class="message-bubble <?= $m['sender_id'] == $userId ? 'message-sent' : 'message-received' ?>">
                                <?= htmlspecialchars($m['message_body']) ?>
                                <div style="font-size: 0.6rem; opacity: 0.6; margin-top: 5px; text-align: right;">
                                    <?= date('H:i', strtotime($m['created_at'])) ?>
                                    <?php if ($m['sender_id'] == $userId): ?>
                                        <i class="fa-solid fa-check<?= $m['is_read'] ? '-double' : '' ?>" style="margin-left: 3px;"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form method="POST" class="chat-input-area">
                        <input type="text" name="message_body" placeholder="Write your message..." required autocomplete="off">
                        <button type="submit" class="action-btn approve" style="width: 45px; height: 45px; border-radius: 50%; background: #7b2bd4; color: #fff;">
                            <i class="fa-solid fa-paper-plane"></i>
                        </button>
                    </form>

                <?php else: ?>
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: #888;">
                        <div class="modal-main-icon" style="width: 100px; height: 100px; font-size: 3rem; background: #f8f9fe;">
                            <i class="fa-regular fa-comments"></i>
                        </div>
                        <h3>Your Workspace Inbox</h3>
                        <p>Select a conversation from the left to start messaging.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../utils/includes/footer.php'; ?>

    <script>
        // Auto-scroll chat to the bottom on load
        const chatWindow = document.getElementById('chatWindow');
        if (chatWindow) {
            chatWindow.scrollTop = chatWindow.scrollHeight;
        }
    </script>
</body>

</html>