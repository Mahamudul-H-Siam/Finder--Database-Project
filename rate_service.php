<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}

$bookingId = isset($_GET['booking_id']) ? (int) $_GET['booking_id'] : 0;
if ($bookingId === 0) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch Booking Info (Ensure it belongs to user and is Completed)
$stmt = $conn->prepare("
    SELECT sb.ProviderID, sb.ServiceType, sp.BusinessName, sb.BookingStatus
    FROM SERVICEBOOKING sb
    JOIN SERVICEPROVIDER sp ON sb.ProviderID = sp.ProviderID
    WHERE sb.BookingID = ? AND sb.UserID = ?
");
$stmt->bind_param("ii", $bookingId, $userId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    die("Booking not found.");
}

if ($booking['BookingStatus'] !== 'Completed') {
    die("Service must be marked as Completed before rating.");
}

// Check if already rated
$stmt = $conn->prepare("SELECT ReviewID FROM REVIEWS WHERE BookingID = ?");
$stmt->bind_param("i", $bookingId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    die("You have already rated this service.");
}
$stmt->close();

// Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int) $_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a valid rating.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO REVIEWS (BookingID, ProviderID, UserID, Rating, Comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiis", $bookingId, $booking['ProviderID'], $userId, $rating, $comment);
        if ($stmt->execute()) {
            $success = true;

            // Notify Provider
            $notifStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Type, Title, Message) VALUES (?, 'System', 'New Review Received', ?)");
            $notifMsg = "You received a $rating-star review for " . $booking['ServiceType'];
            $notifStmt->bind_param("is", $booking['ProviderID'], $notifMsg);
            $notifStmt->execute();
            $notifStmt->close();

        } else {
            $errors[] = "Error saving review.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rate Service - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .card {
            background: #1e293b;
            border-radius: 16px;
            border: 1px solid #334155;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        h2 {
            margin-top: 0;
            color: #38bdf8;
        }

        .rating-group {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin: 1.5rem 0;
            direction: rtl;
        }

        .rating-group input {
            display: none;
        }

        .rating-group label {
            font-size: 2rem;
            color: #334155;
            cursor: pointer;
            transition: 0.2s;
        }

        .rating-group input:checked~label,
        .rating-group label:hover,
        .rating-group label:hover~label {
            color: #fbbf24;
        }

        textarea {
            width: 100%;
            height: 100px;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            box-sizing: border-box;
            resize: none;
        }

        .btn {
            width: 100%;
            padding: 0.8rem;
            margin-top: 1rem;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            background: #22c55e;
            color: #022c22;
            cursor: pointer;
        }

        .success-area {
            background: rgba(34, 197, 94, 0.2);
            padding: 1rem;
            border-radius: 8px;
            color: #4ade80;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="card">
        <?php if ($success): ?>
            <div class="success-area">
                <strong>Thank You!</strong><br>
                Your review has been submitted.
            </div>
            <a href="index.php" style="color:white; text-decoration:none;">← Return to Dashboard</a>
        <?php else: ?>
            <h2>Rate your experience</h2>
            <div style="color:#cbd5e1; margin-bottom:1rem;">
                <?php echo htmlspecialchars($booking['ServiceType']); ?> with <strong>
                    <?php echo htmlspecialchars($booking['BusinessName']); ?>
                </strong>
            </div>

            <form method="POST">
                <div class="rating-group">
                    <input type="radio" id="star5" name="rating" value="5" required /><label for="star5">★</label>
                    <input type="radio" id="star4" name="rating" value="4" /><label for="star4">★</label>
                    <input type="radio" id="star3" name="rating" value="3" /><label for="star3">★</label>
                    <input type="radio" id="star2" name="rating" value="2" /><label for="star2">★</label>
                    <input type="radio" id="star1" name="rating" value="1" /><label for="star1">★</label>
                </div>
                <textarea name="comment" placeholder="Write a brief comment (optional)..."></textarea>
                <button type="submit" class="btn">Submit Review</button>
            </form>
            <div style="margin-top:1rem;">
                <a href="index.php" style="color:#94a3b8; font-size:0.9rem;">Cancel</a>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>