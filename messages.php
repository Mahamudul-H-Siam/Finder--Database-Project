<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Get all conversations (users I've messaged or who messaged me)
$sql = "SELECT DISTINCT 
    CASE 
        WHEN m.SenderID = ? THEN m.ReceiverID
        ELSE m.SenderID
    END as OtherUserID,
    u.FullName,
    u.Role,
    (SELECT MessageText FROM MESSAGE 
     WHERE (SenderID = ? AND ReceiverID = OtherUserID) 
        OR (SenderID = OtherUserID AND ReceiverID = ?)
     ORDER BY CreatedAt DESC LIMIT 1) as LastMessage,
    (SELECT CreatedAt FROM MESSAGE 
     WHERE (SenderID = ? AND ReceiverID = OtherUserID) 
        OR (SenderID = OtherUserID AND ReceiverID = ?)
     ORDER BY CreatedAt DESC LIMIT 1) as LastMessageTime,
    (SELECT COUNT(*) FROM MESSAGE 
     WHERE SenderID = OtherUserID AND ReceiverID = ? AND IsRead = 0) as UnreadCount
FROM MESSAGE m
JOIN USER u ON u.UserID = CASE 
    WHEN m.SenderID = ? THEN m.ReceiverID
    ELSE m.SenderID
END
WHERE m.SenderID = ? OR m.ReceiverID = ?
ORDER BY LastMessageTime DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiiiiii", $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total unread count
$unreadStmt = $conn->prepare("SELECT COUNT(*) as total FROM MESSAGE WHERE ReceiverID = ? AND IsRead = 0");
$unreadStmt->bind_param("i", $userId);
$unreadStmt->execute();
$totalUnread = $unreadStmt->get_result()->fetch_assoc()['total'];
$unreadStmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Messages - FindR</title>
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
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #334155;
        }

        h1 {
            font-size: 2rem;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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

        .conversations {
            display: grid;
            gap: 1rem;
        }

        .conversation-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
        }

        .conversation-card:hover {
            border-color: #38bdf8;
            transform: translateY(-2px);
        }

        .conversation-info {
            flex: 1;
        }

        .conversation-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 0.25rem;
        }

        .conversation-role {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .conversation-preview {
            color: #cbd5e1;
            font-size: 0.9rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 500px;
        }

        .conversation-meta {
            text-align: right;
        }

        .conversation-time {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .unread-badge {
            background: #f43f5e;
            color: white;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-state h3 {
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .stats {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #38bdf8;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>💬 Messages</h1>
            <a href="index.php" class="btn">← Dashboard</a>
        </div>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-value">
                    <?php echo count($conversations); ?>
                </div>
                <div class="stat-label">Conversations</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">
                    <?php echo $totalUnread; ?>
                </div>
                <div class="stat-label">Unread</div>
            </div>
        </div>

        <div class="conversations">
            <?php if (empty($conversations)): ?>
                <div class="empty-state">
                    <h3>No messages yet</h3>
                    <p>Start a conversation by messaging a seller from the marketplace!</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <a href="chat.php?user_id=<?php echo $conv['OtherUserID']; ?>" class="conversation-card">
                        <div class="conversation-info">
                            <div class="conversation-name">
                                <?php echo htmlspecialchars($conv['FullName']); ?>
                            </div>
                            <div class="conversation-role">
                                <?php echo $conv['Role']; ?>
                            </div>
                            <div class="conversation-preview">
                                <?php echo htmlspecialchars(substr($conv['LastMessage'], 0, 80)); ?>
                                <?php if (strlen($conv['LastMessage']) > 80)
                                    echo '...'; ?>
                            </div>
                        </div>
                        <div class="conversation-meta">
                            <div class="conversation-time">
                                <?php echo date('M d, h:i A', strtotime($conv['LastMessageTime'])); ?>
                            </div>
                            <?php if ($conv['UnreadCount'] > 0): ?>
                                <span class="unread-badge">
                                    <?php echo $conv['UnreadCount']; ?> new
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>