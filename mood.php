<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch history
$stmt = $conn->prepare("SELECT MoodLevel, Note, CreatedAt FROM MOODENTRY WHERE UserID = ? ORDER BY CreatedAt DESC LIMIT 10");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Add entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $level = (int)$_POST['level'];
    $note = trim($_POST['note']);

    if ($level < 1 || $level > 5) {
        $errors[] = "Invalid mood level.";
    } else {
        $stmt = $conn->prepare("INSERT INTO MOODENTRY (UserID, MoodLevel, Note) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("iis", $userId, $level, $note);
        if ($stmt->execute()) {
            $success = true;
            header("Location: mood.php");
            exit;
        } else {
            $errors[] = "Error logging mood.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mood Tracking - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin:0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color:#e5e7eb;
            min-height:100vh;
            display:flex;
            align-items:flex-start;
            justify-content:center;
            padding:1.5rem 0;
        }
        .container {
            max-width:900px;
            padding:0 1.5rem;
        }
        .header-line {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:1rem;
        }
        h2 { margin:0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }
        .card {
            background: rgba(15,23,42,0.96);
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .card h3 { margin-bottom: 0.5rem; }
        .card ul { list-style: none; padding: 0; }
        .card li { margin-bottom: 0.5rem; }
        .form-group { margin-bottom:0.75rem; font-size:0.8rem; }
        .form-group label { display:block; margin-bottom:0.25rem; color:#9ca3af; }
        .form-group select, .form-group textarea { width:100%; padding:0.4rem 0.55rem; border-radius:0.6rem; border:1px solid #374151; background:#020617; color:#e5e7eb; font-size:0.85rem; outline:none; }
        .form-group textarea { min-height:80px; resize:vertical; }
        .btn { width:100%; margin-top:0.4rem; padding:0.5rem 0.7rem; border-radius:999px; border:none; cursor:pointer; font-size:0.85rem; font-weight:600; background:linear-gradient(to right,#22c55e,#16a34a); color:#022c22; box-shadow:0 10px 24px rgba(22,163,74,0.9); }
        .error { color:#fecaca; font-size:0.8rem; margin-bottom:0.3rem; }
        .success { color:#bbf7d0; font-size:0.8rem; margin-bottom:0.3rem; }
        .empty { color:#9ca3af; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-line">
        <h2>Mood Tracker</h2>
        <a href="index.php">← Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="success">Mood logged.</div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div class="error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <div class="grid">
        <div class="card">
            <h3>Log Today's Mood</h3>
            <form method="post">
                <div class="form-group">
                    <label>Mood Level (1=Very Sad, 5=Very Happy)</label>
                    <select name="level" required>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Note (optional)</label>
                    <textarea name="note"></textarea>
                </div>
                <button type="submit" class="btn">Log Mood</button>
            </form>
        </div>

        <div class="card">
            <h3>Recent Entries</h3>
            <?php if (empty($history)): ?>
                <p class="empty">No entries yet.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($history as $entry): ?>
                        <li>Level: <?php echo $entry['MoodLevel']; ?>/5 | Note: <?php echo htmlspecialchars($entry['Note'] ?? 'None'); ?> | Date: <?php echo $entry['CreatedAt']; ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>