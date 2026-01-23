<?php
include 'config.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$adminId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Approve Room
    if (isset($_POST['approve_room'])) {
        $roomId = intval($_POST['room_id']);
        $stmt = $conn->prepare("UPDATE ROOMLISTING SET IsVerified = 1 WHERE RoomID = ?");
        $stmt->bind_param("i", $roomId);

        if ($stmt->execute()) {
            // Notify owner
            $ownerStmt = $conn->prepare("SELECT OwnerID, Title FROM ROOMLISTING WHERE RoomID = ?");
            $ownerStmt->bind_param("i", $roomId);
            $ownerStmt->execute();
            $result = $ownerStmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $notifStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Room Listed', ?)");
                $msg = "Your room listing '" . $row['Title'] . "' has been approved and is now visible.";
                $notifStmt->bind_param("is", $row['OwnerID'], $msg);
                $notifStmt->execute();
                $notifStmt->close();
            }
            $ownerStmt->close();

            $message = "Room approved!";
            $messageType = "success";
        }
        $stmt->close();
    }

    // Decline Room
    if (isset($_POST['decline_room'])) {
        $roomId = intval($_POST['room_id']);
        
        // Get owner info first
        $check = $conn->query("SELECT OwnerID, Title FROM ROOMLISTING WHERE RoomID = $roomId");
        if ($row = $check->fetch_assoc()) {
            $ownerId = $row['OwnerID'];
            $title = $row['Title'];
            
            $stmt = $conn->prepare("DELETE FROM ROOMLISTING WHERE RoomID = ?");
            $stmt->bind_param("i", $roomId);

            if ($stmt->execute()) {
                $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Room Declined', ?)");
                $msg = "Your listing '$title' was declined.";
                $nStmt->bind_param("is", $ownerId, $msg);
                $nStmt->execute();
                $nStmt->close();
                
                $message = "Room declined (deleted).";
                $messageType = "warning";
            }
            $stmt->close();
        }
    }

    // Delete Room
    if (isset($_POST['delete_room'])) {
        $roomId = intval($_POST['room_id']);
        $stmt = $conn->prepare("DELETE FROM ROOMLISTING WHERE RoomID = ?");
        $stmt->bind_param("i", $roomId);

        if ($stmt->execute()) {
            $message = "Room deleted!";
            $messageType = "success";
        }
        $stmt->close();
    }
}

// Fetch filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$typeFilter = isset($_GET['type']) ? $_GET['type'] : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($statusFilter === 'pending') {
    $where .= " AND r.IsVerified = 0";
} elseif ($statusFilter === 'approved') {
    $where .= " AND r.IsVerified = 1";
}

if ($typeFilter !== 'all') {
    $where .= " AND r.ListingType = ?";
    $types .= "s";
    $params[] = $typeFilter;
}

if ($searchQuery !== '') {
    $where .= " AND (r.Title LIKE ? OR r.Description LIKE ? OR r.LocationArea LIKE ?)";
    $types .= "sss";
    $like = "%$searchQuery%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Fetch all rooms
$sql = "SELECT r.*, u.FullName, u.Email 
        FROM ROOMLISTING r 
        JOIN USER u ON r.OwnerID = u.UserID 
        $where
        ORDER BY r.CreatedAt DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$rooms = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count by status
$counts = [
    'all' => 0,
    'pending' => 0,
    'approved' => 0
];

$countResult = $conn->query("SELECT IsVerified, COUNT(*) as count FROM ROOMLISTING GROUP BY IsVerified");
while ($row = $countResult->fetch_assoc()) {
    if ($row['IsVerified'] == 0) {
        $counts['pending'] = $row['count'];
    } else {
        $counts['approved'] = $row['count'];
    }
    $counts['all'] += $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Homes | FindR</title>
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
            max-width: 1400px;
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
            background: linear-gradient(90deg, #10b981, #059669);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(90deg, #10b981, #059669);
            color: white;
        }

        .btn-success {
            background: #22c55e;
            color: #022c22;
        }

        .btn-warning {
            background: #f59e0b;
            color: #451a03;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-secondary {
            background: #475569;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid #22c55e;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid #ef4444;
        }

        .alert-warning {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid #f59e0b;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .stat-card:hover {
            border-color: #10b981;
            transform: translateY(-2px);
        }

        .stat-card.active {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .stat-label {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #10b981;
        }

        .filters {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters input,
        .filters select {
            padding: 0.6rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: white;
            font-size: 0.9rem;
        }

        .filters input {
            flex: 1;
            min-width: 250px;
        }

        .rooms-grid {
            display: grid;
            gap: 1.5rem;
        }

        .room-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.5rem;
        }

        .room-info h3 {
            color: #f8fafc;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .room-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #94a3b8;
            flex-wrap: wrap;
        }

        .room-desc {
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .room-footer {
            display: flex;
            gap: 1rem;
            align-items: center;
            font-size: 0.875rem;
            color: #64748b;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #f59e0b;
            color: #451a03;
        }

        .badge-approved {
            background: #22c55e;
            color: #022c22;
        }

        .room-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 150px;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏠 Manage Homes</h1>
            <div style="display: flex; gap: 1rem;">
                <a href="index.php" class="btn btn-secondary">← Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <a href="?status=all" class="stat-card <?php echo $statusFilter === 'all' ? 'active' : ''; ?>"
                style="text-decoration: none;">
                <div class="stat-label">Total Rooms</div>
                <div class="stat-value">
                    <?php echo $counts['all']; ?>
                </div>
            </a>
            <a href="?status=pending" class="stat-card <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>"
                style="text-decoration: none;">
                <div class="stat-label">Pending</div>
                <div class="stat-value">
                    <?php echo $counts['pending']; ?>
                </div>
            </a>
            <a href="?status=approved" class="stat-card <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>"
                style="text-decoration: none;">
                <div class="stat-label">Approved</div>
                <div class="stat-value">
                    <?php echo $counts['approved']; ?>
                </div>
            </a>
        </div>

        <!-- Filters -->
        <form class="filters" method="GET">
            <input type="text" name="search" placeholder="Search rooms..."
                value="<?php echo htmlspecialchars($searchQuery); ?>">
            <select name="type">
                <option value="all">All Types</option>
                <option value="Room" <?php echo $typeFilter === 'Room' ? 'selected' : ''; ?>>Room</option>
                <option value="HostelBed" <?php echo $typeFilter === 'HostelBed' ? 'selected' : ''; ?>>Hostel Bed</option>
                <option value="RoommateWanted" <?php echo $typeFilter === 'RoommateWanted' ? 'selected' : ''; ?>>Roommate Wanted</option>
            </select>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($searchQuery || $typeFilter !== 'all'): ?>
                <a href="?status=<?php echo $statusFilter; ?>" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Rooms Grid -->
        <div class="rooms-grid">
            <?php if (empty($rooms)): ?>
                <div class="empty-state">
                    <h3>No rooms found</h3>
                    <p>Try adjusting your filters.</p>
                </div>
            <?php else: ?>
                <?php foreach ($rooms as $room): ?>
                    <div class="room-card">
                        <div class="room-info">
                            <h3>
                                <?php echo htmlspecialchars($room['Title']); ?>
                            </h3>
                            <div class="room-meta">
                                <span>BDT
                                    <?php echo number_format($room['RentAmount']); ?>/month
                                </span>
                                <span>•</span>
                                <span>
                                    <?php echo $room['ListingType']; ?>
                                </span>
                                <span>•</span>
                                <span>
                                    <?php echo $room['LocationArea']; ?>
                                </span>
                                <span>•</span>
                                <span>
                                    <?php echo $room['GenderPreference']; ?>
                                </span>
                                <span>•</span>
                                <span class="badge badge-<?php echo $room['IsVerified'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $room['IsVerified'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                            <div class="room-desc">
                                <?php echo nl2br(htmlspecialchars($room['Description'])); ?>
                            </div>
                            <div class="room-footer">
                                <span>Owner: <strong>
                                        <?php echo htmlspecialchars($room['FullName']); ?>
                                    </strong></span>
                                <span>•</span>
                                <span>
                                    <?php echo date('M d, Y h:i A', strtotime($room['CreatedAt'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="room-actions">
                            <?php if (!$room['IsVerified']): ?>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="room_id" value="<?php echo $room['RoomID']; ?>">
                                    <button type="submit" name="approve_room" class="btn btn-success" style="width: 100%;">✓
                                        Approve</button>
                                </form>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="room_id" value="<?php echo $room['RoomID']; ?>">
                                    <button type="submit" name="decline_room" class="btn btn-warning" style="width: 100%;"
                                        onclick="return confirm('Decline and delete this room?')">✗
                                        Decline</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this room?');">
                                <input type="hidden" name="room_id" value="<?php echo $room['RoomID']; ?>">
                                <button type="submit" name="delete_room" class="btn btn-danger" style="width: 100%;">🗑
                                    Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
