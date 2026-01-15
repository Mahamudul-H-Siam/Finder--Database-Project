<?php
include 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];

// Fetch summaries (using your common queries)

// Recent available rooms (limited to 3)
$stmt = $conn->prepare("
    SELECT r.RoomID, r.Title, r.LocationArea, r.RentAmount, r.CreatedAt, u.FullName AS OwnerName
    FROM ROOMLISTING r
    JOIN USER u ON r.OwnerID = u.UserID
    WHERE r.Status = 'Available' AND r.IsVerified = 1
    ORDER BY r.CreatedAt DESC LIMIT 3
");
if (!$stmt) {
    die("Prepare failed for recent rooms: " . $conn->error);
}
$stmt->execute();
$recentRooms = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Your bookings (if student/service user)
$yourBookings = [];
if ($role === 'Student') {
    $stmt = $conn->prepare("
        SELECT sb.BookingID, sb.ServiceType, sb.Date, sb.BookingStatus, sp.BusinessName
        FROM SERVICEBOOKING sb
        JOIN SERVICEPROVIDER sp ON sb.ProviderID = sp.ProviderID
        WHERE sb.UserID = ?
        ORDER BY sb.Date DESC LIMIT 3
    ");
    if (!$stmt) {
        die("Prepare failed for bookings: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $yourBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Your listings/applications (role-based)
$yourListings = [];
if ($role === 'Owner') {
    $stmt = $conn->prepare("
        SELECT RoomID, Title, Status, CreatedAt
        FROM ROOMLISTING
        WHERE OwnerID = ?
        ORDER BY CreatedAt DESC LIMIT 3
    ");
    if (!$stmt) {
        die("Prepare failed for listings: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $yourListings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} elseif ($role === 'Student') {
    $stmt = $conn->prepare("
        SELECT ra.ApplicationID, ra.Status, r.Title, ra.AppliedAt
        FROM ROOMAPPLICATION ra
        JOIN ROOMLISTING r ON ra.RoomID = r.RoomID
        WHERE ra.ApplicantID = ?
        ORDER BY ra.AppliedAt DESC LIMIT 3
    ");
    if (!$stmt) {
        die("Prepare failed for applications: " . $conn->error);
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $yourListings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Budget summary (monthly expenses) - FIXED HERE: changed "isssi" to "issi"
$budgetSummary = [];
$stmt = $conn->prepare("
    SELECT bc.Name, bc.MonthlyLimit,
           SUM(CASE WHEN bt.Type = 'Expense' THEN bt.Amount ELSE 0 END) AS TotalExpense
    FROM BUDGETCATEGORY bc
    LEFT JOIN BUDGETTRANSACTION bt ON bc.CategoryID = bt.CategoryID
        AND bt.UserID = ? AND bt.Date BETWEEN ? AND ?
    WHERE bc.UserID = ?
    GROUP BY bc.CategoryID
    LIMIT 5
");
if (!$stmt) {
    die("Prepare failed for budget: " . $conn->error);
}
$startDate = date('Y-m-01'); // Current month start
$endDate = date('Y-m-t'); // End
$stmt->bind_param("issi", $userId, $startDate, $endDate, $userId);
$stmt->execute();
$budgetSummary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Recent mood entries (last 3)
$moodHistory = [];
$stmt = $conn->prepare("
    SELECT MoodLevel, Note, CreatedAt
    FROM MOODENTRY
    WHERE UserID = ?
    ORDER BY CreatedAt DESC LIMIT 3
");
if (!$stmt) {
    die("Prepare failed for mood: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$moodHistory = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FindR Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color: #e5e7eb;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(15,23,42,0.95);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #1f2937;
        }
        .brand { font-weight: bold; font-size: 1.2rem; }
        .nav-links a { color: #9ca3af; margin-left: 1rem; text-decoration: none; }
        .nav-links a:hover { color: #e5e7eb; }
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .welcome { font-size: 1.5rem; margin-bottom: 1rem; }
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
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: linear-gradient(to right, #22c55e, #16a34a);
            color: #022c22;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
        }
        .empty { color: #9ca3af; }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="brand">FindR Dashboard</div>
        <div class="nav-links">
            <a href="marketplace_list.php">Marketplace</a>
            <a href="room_list.php">Rooms</a>
            <a href="service_list.php">Services</a>
            <a href="budget.php">Budget</a>
            <a href="mood.php">Mood</a>
            <a href="lostfound_list.php">Lost & Found</a>
            <a href="utilities.php">Utilities</a>
            <a href="meal_list.php">Meals</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>
    <div class="container">
        <div class="welcome">Welcome back, <?php echo htmlspecialchars($fullName); ?>! (Role: <?php echo $role; ?>)</div>
        <div class="grid">
            <div class="card">
                <h3>Recent Rooms</h3>
                <?php if (empty($recentRooms)): ?>
                    <p class="empty">No recent rooms available.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($recentRooms as $room): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($room['Title']); ?></strong> - BDT <?php echo number_format($room['RentAmount'], 2); ?><br>
                                Area: <?php echo htmlspecialchars($room['LocationArea']); ?> | Posted: <?php echo $room['CreatedAt']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="room_list.php" class="btn">Browse All Rooms</a>
                <?php if ($role === 'Owner'): ?>
                    <a href="room_new.php" class="btn" style="margin-left: 1rem;">Post New</a>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Your <?php echo ($role === 'Owner' ? 'Listings' : 'Applications'); ?></h3>
                <?php if (empty($yourListings)): ?>
                    <p class="empty">None yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($yourListings as $item): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($item['Title']); ?></strong> - Status: <?php echo $item['Status']; ?><br>
                                Date: <?php echo $item[($role === 'Owner' ? 'CreatedAt' : 'AppliedAt')]; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Your Bookings</h3>
                <?php if (empty($yourBookings)): ?>
                    <p class="empty">No bookings yet.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($yourBookings as $booking): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($booking['ServiceType']); ?></strong> with <?php echo htmlspecialchars($booking['BusinessName']); ?><br>
                                Date: <?php echo $booking['Date']; ?> | Status: <?php echo $booking['BookingStatus']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="service_list.php" class="btn">Browse Services</a>
            </div>

            <div class="card">
                <h3>Budget Overview (This Month)</h3>
                <?php if (empty($budgetSummary)): ?>
                    <p class="empty">Add categories to start tracking.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($budgetSummary as $cat): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($cat['Name']); ?></strong>: Spent BDT <?php echo number_format($cat['TotalExpense'], 2); ?>
                                <?php if ($cat['MonthlyLimit']): ?> / Limit <?php echo number_format($cat['MonthlyLimit'], 2); ?><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="budget.php" class="btn">Manage Budget</a>
            </div>

            <div class="card">
                <h3>Recent Mood Entries</h3>
                <?php if (empty($moodHistory)): ?>
                    <p class="empty">Log your first mood.</p>
                <?php else: ?>
                    <ul>
                        <?php foreach ($moodHistory as $mood): ?>
                            <li>
                                Level: <?php echo $mood['MoodLevel']; ?>/5 | Note: <?php echo htmlspecialchars($mood['Note'] ?? 'None'); ?><br>
                                Date: <?php echo $mood['CreatedAt']; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="mood.php" class="btn">Track Mood</a>
            </div>
        </div>
    </div>
</body>
</html>