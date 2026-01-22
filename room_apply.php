<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}

$roomId = isset($_GET['room_id']) ? (int) $_GET['room_id'] : 0;
$errors = [];
$success = false;

if ($roomId === 0) {
    header("Location: room_list.php");
    exit;
}

// Fetch room details
$stmt = $conn->prepare("
    SELECT r.Title, r.LocationArea, u.FullName AS OwnerName
    FROM ROOMLISTING r
    JOIN USER u ON r.OwnerID = u.UserID
    WHERE r.RoomID = ? AND r.Status = 'Available' AND r.IsVerified = 1
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $roomId);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$room) {
    header("Location: room_list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);

    $stmt = $conn->prepare("
        INSERT INTO ROOMAPPLICATION (RoomID, ApplicantID, Message)
        VALUES (?, ?, ?)
    ");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iis", $roomId, $_SESSION['user_id'], $message);
    if ($stmt->execute()) {
        $success = true;

        // Get owner ID and notify them
        $ownerStmt = $conn->prepare("SELECT OwnerID, Title FROM ROOMLISTING WHERE RoomID = ?");
        $ownerStmt->bind_param("i", $roomId);
        $ownerStmt->execute();
        $ownerRes = $ownerStmt->get_result();
        if ($ownerRow = $ownerRes->fetch_assoc()) {
            $ownerId = $ownerRow['OwnerID'];
            $roomTitle = $ownerRow['Title'];

            $notifStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'New Room Application', ?)");
            $notifMsg = "You have a new application for your room '$roomTitle'.";
            $notifStmt->bind_param("is", $ownerId, $notifMsg);
            $notifStmt->execute();
            $notifStmt->close();
        }
        $ownerStmt->close();
    } else {
        $errors[] = "Error applying. You may have already applied.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Apply for Room - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color: #e5e7eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: rgba(15, 23, 42, 0.96);
            border-radius: 12px;
            border: 1px solid #1f2937;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.85);
            padding: 1.5rem 1.75rem;
            width: 100%;
            max-width: 520px;
        }

        h2 {
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 0.75rem;
            font-size: 0.8rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            color: #9ca3af;
        }

        .form-group textarea {
            width: 100%;
            padding: 0.4rem 0.55rem;
            border-radius: 0.6rem;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            font-size: 0.85rem;
            outline: none;
            min-height: 80px;
            resize: vertical;
        }

        .btn {
            width: 100%;
            margin-top: 0.4rem;
            padding: 0.5rem 0.7rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            background: linear-gradient(to right, #22c55e, #16a34a);
            color: #022c22;
            box-shadow: 0 10px 24px rgba(22, 163, 74, 0.9);
        }

        .error {
            color: #fecaca;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .success {
            color: #bbf7d0;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .top-link {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-bottom: 0.5rem;
        }

        .top-link a {
            color: #38bdf8;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="top-link">← <a href="room_list.php">Back to rooms</a></div>
        <h2>Apply for <?php echo htmlspecialchars($room['Title']); ?></h2>
        <div class="subtitle">Area: <?php echo htmlspecialchars($room['LocationArea']); ?> | Owner:
            <?php echo htmlspecialchars($room['OwnerName']); ?></div>

        <?php if ($success): ?>
            <div class="success">Application sent successfully. Check your dashboard for status.</div>
        <?php endif; ?>

        <?php foreach ($errors as $e): ?>
            <div class="error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="form-group">
                <label>Message (optional)</label>
                <textarea name="message" placeholder="Introduce yourself or ask questions"></textarea>
            </div>
            <button type="submit" class="btn">Send Application</button>
        </form>
    </div>
</body>

</html>