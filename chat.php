<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$otherUserId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

if (!$otherUserId) {
    header("Location: messages.php");
    exit;
}

// Get other user info
$userStmt = $conn->prepare("SELECT FullName, Role FROM USER WHERE UserID = ?");
$userStmt->bind_param("i", $otherUserId);
$userStmt->execute();
$otherUser = $userStmt->get_result()->fetch_assoc();
$userStmt->close();

if (!$otherUser) {
    header("Location: messages.php");
    exit;
}

// Handle send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $messageText = trim($_POST['message']);

    if (!empty($messageText)) {
        $stmt = $conn->prepare("INSERT INTO MESSAGE (SenderID, ReceiverID, MessageText) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $userId, $otherUserId, $messageText);
        $stmt->execute();
        $stmt->close();

        // Send notification
        $notifStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'New Message', ?)");
        $notifMsg = "You have a new message from " . $_SESSION['full_name'];
        $notifStmt->bind_param("is", $otherUserId, $notifMsg);
        $notifStmt->execute();
        $notifStmt->close();

        header("Location: chat.php?user_id=$otherUserId");
        exit;
    }
}

// Mark messages as read
$markReadStmt = $conn->prepare("UPDATE MESSAGE SET IsRead = 1 WHERE SenderID = ? AND ReceiverID = ? AND IsRead = 0");
$markReadStmt->bind_param("ii", $otherUserId, $userId);
$markReadStmt->execute();
$markReadStmt->close();

// Get conversation messages
$messagesStmt = $conn->prepare("
    SELECT m.*, u.FullName as SenderName 
    FROM MESSAGE m
    JOIN USER u ON m.SenderID = u.UserID
    WHERE (m.SenderID = ? AND m.ReceiverID = ?) 
       OR (m.SenderID = ? AND m.ReceiverID = ?)
    ORDER BY m.CreatedAt ASC
");
$messagesStmt->bind_param("iiii", $userId, $otherUserId, $otherUserId, $userId);
$messagesStmt->execute();
$messages = $messagesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$messagesStmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Chat with
        <?php echo htmlspecialchars($otherUser['FullName']); ?> - FindR
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #e5e7eb;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #334155;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-user-info h2 {
            font-size: 1.3rem;
            color: #f8fafc;
            margin-bottom: 0.25rem;
        }

        .chat-user-role {
            font-size: 0.85rem;
            color: #94a3b8;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            background: #475569;
            color: white;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #64748b;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            max-width: 60%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            word-wrap: break-word;
        }

        .message-sent {
            align-self: flex-end;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            color: white;
        }

        .message-received {
            align-self: flex-start;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
        }

        .message-text {
            margin-bottom: 0.25rem;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
        }

        .message-sender {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            opacity: 0.8;
        }

        .chat-input-container {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-top: 1px solid #334155;
            padding: 1.5rem 2rem;
        }

        .chat-input-form {
            display: flex;
            gap: 1rem;
        }

        .chat-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: white;
            font-size: 0.95rem;
            resize: none;
            font-family: 'Inter', sans-serif;
        }

        .chat-input:focus {
            outline: none;
            border-color: #38bdf8;
        }

        .btn-send {
            padding: 0.75rem 2rem;
            border-radius: 8px;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-send:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3);
        }

        .empty-chat {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        /* Scrollbar */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <div class="chat-header">
        <div class="chat-user-info">
            <h2>
                <?php echo htmlspecialchars($otherUser['FullName']); ?>
            </h2>
            <div class="chat-user-role">
                <?php echo $otherUser['Role']; ?>
            </div>
        </div>
        <a href="messages.php" class="btn">← Back to Messages</a>
    </div>

    <div class="chat-messages" id="chatMessages">
        <?php if (empty($messages)): ?>
            <div class="empty-chat">
                <p>No messages yet. Start the conversation!</p>
            </div>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message <?php echo $msg['SenderID'] == $userId ? 'message-sent' : 'message-received'; ?>">
                    <?php if ($msg['SenderID'] != $userId): ?>
                        <div class="message-sender">
                            <?php echo htmlspecialchars($msg['SenderName']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="message-text">
                        <?php echo nl2br(htmlspecialchars($msg['MessageText'])); ?>
                    </div>
                    <div class="message-time">
                        <?php echo date('M d, h:i A', strtotime($msg['CreatedAt'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="chat-input-container">
        <form method="POST" class="chat-input-form">
            <textarea name="message" class="chat-input" placeholder="Type your message..." rows="1" required></textarea>
            <button type="submit" name="send_message" class="btn-send">Send</button>
        </form>
    </div>

    <script>
        // Auto-scroll to bottom
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.scrollTop = chatMessages.scrollHeight;

        // Auto-expand textarea
        const textarea = document.querySelector('.chat-input');
        textarea.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 150) + 'px';
        });

        // Submit on Enter (Shift+Enter for new line)
        textarea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.form.submit();
            }
        });
    </script>
</body>

</html>