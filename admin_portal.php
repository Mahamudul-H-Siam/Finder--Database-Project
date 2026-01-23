<?php
include 'config.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Auto-fix empty status items on page load
$conn->query("UPDATE MARKETITEM SET Status = 'Pending' WHERE Status IS NULL OR Status = ''");

// Handle Actions
$message = '';
$messageType = '';

if (isset($_GET['approve_sp'])) {
    $spId = intval($_GET['approve_sp']);
    $stmt = $conn->prepare("UPDATE SERVICEPROVIDER SET IsApproved = TRUE WHERE ProviderID = ?");
    $stmt->bind_param("i", $spId);
    if ($stmt->execute()) {
        $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Account Approved', 'Your Service Provider account has been approved. You can now access your dashboard.')");
        $nStmt->bind_param("i", $spId);
        $nStmt->execute();
        $nStmt->close();
        $message = "Service provider approved successfully!";
        $messageType = "success";
    }
    $stmt->close();
}

if (isset($_GET['approve_item'])) {
    $itemId = intval($_GET['approve_item']);
    $check = $conn->query("SELECT SellerID, Title FROM MARKETITEM WHERE ItemID = $itemId");
    if ($row = $check->fetch_assoc()) {
        $sellerId = $row['SellerID'];
        $title = $row['Title'];

        $stmt = $conn->prepare("UPDATE MARKETITEM SET Status = 'Available' WHERE ItemID = ?");
        $stmt->bind_param("i", $itemId);
        if ($stmt->execute()) {
            $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Item Approved', ?)");
            $msg = "Your item '$title' has been approved and is now listed in the marketplace.";
            $nStmt->bind_param("is", $sellerId, $msg);
            $nStmt->execute();
            $nStmt->close();
            $message = "Item '$title' approved successfully!";
            $messageType = "success";
        }
        $stmt->close();
    }
}

if (isset($_GET['decline_item'])) {
    $itemId = intval($_GET['decline_item']);
    $check = $conn->query("SELECT SellerID, Title FROM MARKETITEM WHERE ItemID = $itemId");
    if ($row = $check->fetch_assoc()) {
        $sellerId = $row['SellerID'];
        $title = $row['Title'];

        $stmt = $conn->prepare("UPDATE MARKETITEM SET Status = 'Declined' WHERE ItemID = ?");
        $stmt->bind_param("i", $itemId);
        if ($stmt->execute()) {
            $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Item Declined', ?)");
            $msg = "Your item '$title' has been declined. Please review the marketplace guidelines.";
            $nStmt->bind_param("is", $sellerId, $msg);
            $nStmt->execute();
            $nStmt->close();
            $message = "Item '$title' declined.";
            $messageType = "warning";
        }
        $stmt->close();
    }
}

if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    $stmt = $conn->prepare("DELETE FROM USER WHERE UserID = ? AND Role != 'Admin'");
    $stmt->bind_param("i", $uid);
    if ($stmt->execute()) {
        $message = "User deleted successfully!";
        $messageType = "success";
    }
    if ($stmt->execute()) {
        $message = "User deleted successfully!";
        $messageType = "success";
    }
    $stmt->close();
}

if (isset($_GET['approve_room'])) {
    $roomId = intval($_GET['approve_room']);
    $stmt = $conn->prepare("UPDATE ROOMLISTING SET IsVerified = 1 WHERE RoomID = ?");
    $stmt->bind_param("i", $roomId);
    if ($stmt->execute()) {
        // Fetch Owner ID for notification
        $check = $conn->query("SELECT OwnerID, Title FROM ROOMLISTING WHERE RoomID = $roomId");
        if ($row = $check->fetch_assoc()) {
            $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Room Listed', ?)");
            $msg = "Your room listing '{$row['Title']}' has been approved and is now visible.";
            $nStmt->bind_param("is", $row['OwnerID'], $msg);
            $nStmt->execute();
        }
        $message = "Room listing approved successfully!";
        $messageType = "success";
    }
    $stmt->close();
}

if (isset($_GET['decline_room'])) {
    $roomId = intval($_GET['decline_room']);
    // Fetch Owner ID first
    $check = $conn->query("SELECT OwnerID, Title FROM ROOMLISTING WHERE RoomID = $roomId");
    if ($row = $check->fetch_assoc()) {
        $ownerId = $row['OwnerID'];
        $title = $row['Title'];
        // Rejecting usually implies deleting or setting to a 'Rejected' status.
        // For simplicity, let's delete it or add a 'Rejected' status to ENUM? 
        // ENUM is 'Available','Booked','Closed'. Let's set IsVerified=0 (keep pending) or delete.
        // User request changes implied restricted/blocked. Let's Delete for clean decline.
        $stmt = $conn->prepare("DELETE FROM ROOMLISTING WHERE RoomID = ?");
        $stmt->bind_param("i", $roomId);
        if ($stmt->execute()) {
            $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Room Declined', ?)");
            $msg = "Your listing '$title' was declined.";
            $nStmt->bind_param("is", $ownerId, $msg);
            $nStmt->execute();
            $message = "Room listing declined (deleted).";
            $messageType = "warning";
        }
        $stmt->close();
    }
}

if (isset($_GET['block_user'])) {
    $uid = intval($_GET['block_user']);
    $stmt = $conn->prepare("UPDATE USER SET Status = 'Blocked' WHERE UserID = ? AND Role != 'Admin'");
    $stmt->bind_param("i", $uid);
    if ($stmt->execute()) {
        $message = "User blocked successfully.";
        $messageType = "warning";
    }
    $stmt->close();
}

if (isset($_GET['unblock_user'])) {
    $uid = intval($_GET['unblock_user']);
    $stmt = $conn->prepare("UPDATE USER SET Status = 'Active' WHERE UserID = ?");
    $stmt->bind_param("i", $uid);
    if ($stmt->execute()) {
        $message = "User unblocked successfully.";
        $messageType = "success";
    }
    $stmt->close();
}

// Redirect to clean URL after action
if ($message) {
    $_SESSION['admin_message'] = $message;
    $_SESSION['admin_message_type'] = $messageType;
    header("Location: admin_portal.php");
    exit;
}

// Get stored message
if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    $messageType = $_SESSION['admin_message_type'];
    unset($_SESSION['admin_message']);
    unset($_SESSION['admin_message_type']);
}

// Fetch Stats
$stats = [];
$res = $conn->query("SELECT Role, COUNT(*) as Count FROM USER GROUP BY Role");
while ($row = $res->fetch_assoc()) {
    $stats[$row['Role']] = $row['Count'];
}

// Fetch Pending Service Providers
$pendingSPs = [];
$res = $conn->query("
    SELECT u.FullName, u.Email, sp.ProviderID, sp.BusinessName, sp.ServiceType, sp.Area
    FROM SERVICEPROVIDER sp
    JOIN USER u ON sp.ProviderID = u.UserID
    WHERE sp.IsApproved = FALSE
");
while ($row = $res->fetch_assoc()) {
    $pendingSPs[] = $row;
}

// Fetch Pending Marketplace Items
$pendingItems = [];
$sql = "
    SELECT m.ItemID, m.Title, m.Price, m.Category, m.Description, m.Status, m.CreatedAt, u.FullName, u.Email
    FROM MARKETITEM m 
    JOIN USER u ON m.SellerID = u.UserID 
    WHERE m.Status = 'Pending'
    ORDER BY m.CreatedAt DESC
";
$res = $conn->query($sql);

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pendingItems[] = $row;
    }
}

// Fetch Pending Rooms
$pendingRooms = [];
$res = $conn->query("
    SELECT r.RoomID, r.Title, r.RentAmount, r.LocationArea, r.ListingType, r.CreatedAt, u.FullName 
    FROM ROOMLISTING r
    JOIN USER u ON r.OwnerID = u.UserID
    WHERE r.IsVerified = 0
    ORDER BY r.CreatedAt DESC
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pendingRooms[] = $row;
    }
}

// Fetch Recent Users
$users = [];
$res = $conn->query("
    SELECT u.UserID, u.FullName, u.Email, u.Role, u.Status, u.CreatedAt,
    (SELECT COUNT(*) FROM ROOMLISTING r WHERE r.OwnerID = u.UserID) as PostCount
    FROM USER u 
    ORDER BY u.CreatedAt DESC LIMIT 20
");
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Portal - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
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
            background: linear-gradient(90deg, #fbbf24, #f59e0b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn-back {
            background: #475569;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-back:hover {
            background: #64748b;
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

        .alert-warning {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid #f59e0b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #fbbf24;
        }

        .card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        h2 {
            color: #f8fafc;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        .badge-warning {
            background: #f59e0b;
            color: #451a03;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th,
        td {
            text-align: left;
            padding: 0.75rem;
            border-bottom: 1px solid #334155;
        }

        th {
            color: #94a3b8;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
        }

        td {
            color: #e5e7eb;
        }

        .btn {
            display: inline-block;
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            margin-right: 0.5rem;
            transition: all 0.2s;
        }

        .btn-green {
            background: #22c55e;
            color: #022c22;
        }

        .btn-green:hover {
            background: #16a34a;
        }

        .btn-red {
            background: #ef4444;
            color: white;
        }

        .btn-red:hover {
            background: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }

        .item-desc {
            max-width: 300px;
            font-size: 0.85rem;
            color: #94a3b8;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .timestamp {
            font-size: 0.75rem;
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ Admin Portal</h1>
            <a href="index.php" class="btn-back">← Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <?php foreach ($stats as $role => $count): ?>
                <div class="stat-card">
                    <div class="stat-label"><?php echo $role; ?>s</div>
                    <div class="stat-value"><?php echo $count; ?></div>
                </div>
            <?php endforeach; ?>
            <div class="stat-card">
                <div class="stat-label">Pending Rooms</div>
                <div class="stat-value"><?php echo count($pendingRooms); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending Items</div>
                <div class="stat-value"><?php echo count($pendingItems); ?></div>
            </div>
        </div>

        <!-- Pending Rooms -->
        <div class="card">
            <h2>
                🏠 Pending Room Listings
                <?php if (count($pendingRooms) > 0): ?>
                    <span class="badge badge-warning"><?php echo count($pendingRooms); ?> pending</span>
                <?php endif; ?>
            </h2>
            <?php if (empty($pendingRooms)): ?>
                <div class="empty-state">
                    <p>✓ No pending room approvals.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Rent</th>
                            <th>Owner</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRooms as $room): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($room['Title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($room['ListingType']); ?></td>
                                <td><?php echo htmlspecialchars($room['LocationArea']); ?></td>
                                <td>BDT <?php echo number_format($room['RentAmount']); ?></td>
                                <td><?php echo htmlspecialchars($room['FullName']); ?></td>
                                <td>
                                    <a href="?approve_room=<?php echo $room['RoomID']; ?>" class="btn btn-green">✓ Approve</a>
                                    <a href="?decline_room=<?php echo $room['RoomID']; ?>" class="btn btn-red"
                                        onclick="return confirm('Decline and delete this listing?')">✗ Decline</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pending Marketplace Items -->
        <div class="card">
            <h2>
                📦 Pending Marketplace Items
                <?php if (count($pendingItems) > 0): ?>
                    <span class="badge badge-warning"><?php echo count($pendingItems); ?> pending</span>
                <?php endif; ?>
            </h2>
            <?php if (empty($pendingItems)): ?>
                <div class="empty-state">
                    <p>✓ No pending items at the moment.</p>
                    <p style="font-size: 0.9rem; margin-top: 0.5rem;">When students post items to the marketplace, they will
                        appear here for approval.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Seller</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingItems as $item): ?>
                            <tr>
                                <td><?php echo $item['ItemID']; ?></td>
                                <td><strong><?php echo htmlspecialchars($item['Title']); ?></strong></td>
                                <td class="item-desc" title="<?php echo htmlspecialchars($item['Description']); ?>">
                                    <?php echo htmlspecialchars(substr($item['Description'], 0, 80)); ?>...
                                </td>
                                <td>BDT <?php echo number_format($item['Price']); ?></td>
                                <td><?php echo htmlspecialchars($item['Category']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($item['FullName']); ?><br>
                                    <span class="timestamp"><?php echo htmlspecialchars($item['Email']); ?></span>
                                </td>
                                <td class="timestamp"><?php echo date('M d, h:i A', strtotime($item['CreatedAt'])); ?></td>
                                <td>
                                    <a href="?approve_item=<?php echo $item['ItemID']; ?>" class="btn btn-green">✓ Approve</a>
                                    <a href="?decline_item=<?php echo $item['ItemID']; ?>" class="btn btn-red"
                                        onclick="return confirm('Decline this item?')">✗ Decline</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Pending Service Providers -->
        <div class="card">
            <h2>
                🔧 Pending Service Provider Approvals
                <?php if (count($pendingSPs) > 0): ?>
                    <span class="badge badge-warning"><?php echo count($pendingSPs); ?> pending</span>
                <?php endif; ?>
            </h2>
            <?php if (empty($pendingSPs)): ?>
                <div class="empty-state">
                    <p>✓ No pending service provider approvals.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Type</th>
                            <th>Area</th>
                            <th>Owner</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingSPs as $sp): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sp['BusinessName']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sp['ServiceType']); ?></td>
                                <td><?php echo htmlspecialchars($sp['Area']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($sp['FullName']); ?><br>
                                    <span class="timestamp"><?php echo htmlspecialchars($sp['Email']); ?></span>
                                </td>
                                <td>
                                    <a href="?approve_sp=<?php echo $sp['ProviderID']; ?>" class="btn btn-green">✓ Approve</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Recent Users -->
        <div class="card">
            <h2>👥 Recent Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Posts</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['UserID']; ?></td>
                            <td><?php echo htmlspecialchars($u['FullName']); ?></td>
                            <td><?php echo htmlspecialchars($u['Email']); ?></td>
                            <td><?php echo $u['Role']; ?></td>
                            <td style="text-align:center; font-weight:bold; color:#fbbf24;"><?php echo $u['PostCount']; ?>
                            </td>
                            <td class="timestamp"><?php echo date('M j, Y', strtotime($u['CreatedAt'])); ?></td>
                            <td>
                                <?php if ($u['Role'] !== 'Admin'): ?>
                                    <?php if ($u['Status'] === 'Blocked'): ?>
                                        <a href="?unblock_user=<?php echo $u['UserID']; ?>" class="btn"
                                            style="background:#fbbf24; color:#451a03">Unblock</a>
                                    <?php else: ?>
                                        <a href="?block_user=<?php echo $u['UserID']; ?>" class="btn"
                                            style="background:#f97316; color:white"
                                            onclick="return confirm('Block this user?')">Block</a>
                                    <?php endif; ?>
                                    <a href="?delete_user=<?php echo $u['UserID']; ?>" class="btn btn-red"
                                        onclick="return confirm('Are you sure you want to PERMANENTLY delete this user?');">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>