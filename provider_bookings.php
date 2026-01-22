<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ServiceProvider') {
    header("Location: index.php");
    exit;
}

$providerId = $_SESSION['user_id'];

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $bookingId = intval($_GET['id']);
    $action = $_GET['action']; // confirm, cancel

    // Verify ownership and get client details
    $check = $conn->prepare("SELECT BookingID, UserID, ServiceType FROM SERVICEBOOKING WHERE BookingID = ? AND ProviderID = ?");
    $check->bind_param("ii", $bookingId, $providerId);
    $check->execute();
    $res = $check->get_result();
    if ($row = $res->fetch_assoc()) {
        $check->close();

        $clientId = $row['UserID'];
        $serviceType = $row['ServiceType'];

        $newStatus = '';
        if ($action === 'confirm')
            $newStatus = 'Confirmed';
        if ($action === 'cancel')
            $newStatus = 'Cancelled';

        if ($newStatus) {
            $stmt = $conn->prepare("UPDATE SERVICEBOOKING SET BookingStatus = ? WHERE BookingID = ?");
            $stmt->bind_param("si", $newStatus, $bookingId);
            if ($stmt->execute()) {
                // Send Notification
                $notifTitle = "Booking " . $newStatus;
                $notifMsg = "Your booking for '$serviceType' has been $newStatus.";
                $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, ?, ?)");
                $nStmt->bind_param("iss", $clientId, $notifTitle, $notifMsg);
                $nStmt->execute();
                $nStmt->close();
            }
            $stmt->close();
        }
    } else {
        $check->close();
    }
    header("Location: provider_bookings.php");
    exit;
}

// Fetch Bookings
$bookings = [];
$stmt = $conn->prepare("
    SELECT sb.BookingID, sb.ServiceType, sb.Date, sb.TimeSlot, sb.Address, sb.Price, sb.BookingStatus,
           u.FullName AS ClientName, u.Phone, u.Email
    FROM SERVICEBOOKING sb
    JOIN USER u ON sb.UserID = u.UserID
    WHERE sb.ProviderID = ?
    ORDER BY sb.Date DESC
");
$stmt->bind_param("i", $providerId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $bookings[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Bookings - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color: #e5e7eb;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 1000px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        h1 {
            margin: 0;
        }

        a.back {
            color: #94a3b8;
            text-decoration: none;
        }

        .card {
            background: rgba(15, 23, 42, 0.96);
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: space-between;
            align-items: center;
        }

        .info h3 {
            margin: 0 0 0.5rem 0;
            color: #38bdf8;
        }

        .details {
            font-size: 0.9rem;
            color: #cbd5e1;
            line-height: 1.5;
        }

        .status {
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-Requested {
            background: #f59e0b;
            color: #451a03;
        }

        .status-Confirmed {
            background: #22c55e;
            color: #022c22;
        }

        .status-Cancelled {
            background: #ef4444;
            color: white;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
        }

        .btn-confirm {
            background: #22c55e;
            color: #022c22;
        }

        .btn-cancel {
            background: #ef4444;
            color: white;
        }

        .empty {
            text-align: center;
            color: #94a3b8;
            margin-top: 3rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Booking Requests</h1>
            <a href="index.php" class="back">← Dashboard</a>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="empty">No bookings found.</div>
        <?php else: ?>
            <?php foreach ($bookings as $b): ?>
                <div class="card">
                    <div class="info">
                        <h3>
                            <?php echo htmlspecialchars($b['ServiceType']); ?>
                        </h3>
                        <div class="details">
                            Date: <strong>
                                <?php echo $b['Date']; ?>
                            </strong> (
                            <?php echo htmlspecialchars($b['TimeSlot']); ?>)<br>
                            Client:
                            <?php echo htmlspecialchars($b['ClientName']); ?> (
                            <?php echo htmlspecialchars($b['Phone']); ?>)<br>
                            Address:
                            <?php echo htmlspecialchars($b['Address']); ?><br>
                            Price: BDT
                            <?php echo number_format($b['Price'], 2); ?>
                        </div>
                    </div>
                    <div class="state">
                        <span class="status status-<?php echo $b['BookingStatus']; ?>">
                            <?php echo $b['BookingStatus']; ?>
                        </span>
                    </div>
                    <div class="actions">
                        <?php if ($b['BookingStatus'] === 'Requested'): ?>
                            <a href="?action=confirm&id=<?php echo $b['BookingID']; ?>" class="btn btn-confirm">Confirm</a>
                            <a href="?action=cancel&id=<?php echo $b['BookingID']; ?>" class="btn btn-cancel">Decline</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>