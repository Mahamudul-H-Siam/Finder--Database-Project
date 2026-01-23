<?php
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$success = "";

// Mark as Read
if (isset($_POST['mark_read'])) {
    $notifId = (int) $_POST['mark_read'];
    $stmt = $conn->prepare("UPDATE NOTIFICATION SET IsRead = 1 WHERE NotificationID = ? AND UserID = ?");
    $stmt->bind_param("ii", $notifId, $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit;
}

// Mark ALL as Read
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE NOTIFICATION SET IsRead = 1 WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    $success = "All marked as read.";
}

// Fetch Notifications
$notifs = [];
$stmt = $conn->prepare("SELECT * FROM NOTIFICATION WHERE UserID = ? ORDER BY CreatedAt DESC LIMIT 50");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc())
    $notifs[] = $row;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Notifications - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #fbbf24;
            --border: #334155;
            --read-bg: rgba(255, 255, 255, 0.02);
            --unread-bg: rgba(251, 191, 36, 0.05);
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .home-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: white;
            text-decoration: none;
            font-size: 1.4rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-sm:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .notif-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .notif-item {
            background: var(--read-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            position: relative;
            transition: 0.2s;
        }

        .notif-item.unread {
            background: var(--unread-bg);
            border-left: 4px solid var(--accent);
        }

        .n-title {
            font-weight: 700;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .n-msg {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .n-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            font-size: 0.8rem;
            color: #64748b;
        }

        .mark-read-btn {
            background: none;
            border: none;
            color: var(--accent);
            cursor: pointer;
            padding: 0;
            font-size: 0.8rem;
        }

        .mark-read-btn:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 4rem;
            color: var(--text-muted);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
            opacity: 0.5;
        }
    </style>
</head>

<body>

    <div class="container">
        <a href="index.php" class="home-btn">🏠</a>

        <div class="header">
            <h1>Notifications</h1>
            <?php if (!empty($notifs)): ?>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="mark_all_read" value="1">
                    <button type="submit" class="btn-sm">Mark All Read</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div
                style="background:rgba(34,197,94,0.2); color:#4ade80; padding:1rem; border-radius:12px; margin-bottom:1rem; text-align:center;">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="notif-list">
            <?php if (empty($notifs)): ?>
                <div class="empty-state">
                    <span class="empty-icon">🔕</span>
                    No notifications yet.
                </div>
            <?php else: ?>
                <?php foreach ($notifs as $n): ?>
                    <?php
                    $link = !empty($n['Link']) ? $n['Link'] : '#';
                    $isClickable = !empty($n['Link']);
                    $onclick = $isClickable ? "onclick=\"markAndGo(event, " . $n['NotificationID'] . ", '" . $link . "')\"" : "";
                    $cursorStyle = $isClickable ? "cursor:pointer;" : "";
                    ?>
                    <div class="notif-item <?php echo $n['IsRead'] ? '' : 'unread'; ?>" style="<?php echo $cursorStyle; ?>"
                        <?php echo $onclick; ?>>
                        <div class="n-title">
                            <?php echo htmlspecialchars($n['Title']); ?>
                        </div>
                        <div class="n-msg">
                            <?php echo htmlspecialchars($n['Message']); ?>
                        </div>
                        <div class="n-meta">
                            <span>
                                <?php echo date('M d, h:i A', strtotime($n['CreatedAt'])); ?>
                            </span>
                            <?php if (!$n['IsRead']): ?>
                                <form method="POST" style="display:inline;" onsubmit="event.stopPropagation();">
                                    <input type="hidden" name="mark_read" value="<?php echo $n['NotificationID']; ?>">
                                    <button type="submit" class="mark-read-btn">Mark as Read</button>
                                </form>
                            <?php else: ?>
                                <span>Read</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <script>
                    function markAndGo(e, id, url) {
                        if (url === '#') return;
                        // Send request to mark as read then redirect
                        e.preventDefault();

                        const formData = new FormData();
                        formData.append('mark_read', id);

                        fetch('notifications.php', {
                            method: 'POST',
                            body: formData
                        }).then(() => {
                            window.location.href = url;
                        });
                    }
                </script>
            <?php endif; ?>
        </div>
    </div>

</body>

</html>