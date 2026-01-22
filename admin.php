<?php
include 'config.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

// Redirect to new admin portal
header("Location: admin_portal.php" . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
exit;


// Handle Actions
if (isset($_GET['approve_sp'])) {
    $spId = intval($_GET['approve_sp']);
    $stmt = $conn->prepare("UPDATE SERVICEPROVIDER SET IsApproved = TRUE WHERE ProviderID = ?");
    $stmt->bind_param("i", $spId);
    if ($stmt->execute()) {
        $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Account Approved', 'Your Service Provider account has been approved. You can now access your dashboard.')");
        $nStmt->bind_param("i", $spId);
        $nStmt->execute();
        $nStmt->close();
    }
    $stmt->close();
    header("Location: admin.php?msg=approved");
    exit;
}

if (isset($_GET['approve_item'])) {
    $itemId = intval($_GET['approve_item']);
    // Fetch Seller ID
    $check = $conn->query("SELECT SellerID, Title FROM MARKETITEM WHERE ItemID = $itemId");
    if ($row = $check->fetch_assoc()) {
        $sellerId = $row['SellerID'];
        $title = $row['Title'];

        $stmt = $conn->prepare("UPDATE MARKETITEM SET Status = 'Available' WHERE ItemID = ?");
        $stmt->bind_param("i", $itemId);
        if ($stmt->execute()) {
            $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Item Approved', ?)");
            $msg = "Your item '$title' has been approved and is now listed.";
            $nStmt->bind_param("is", $sellerId, $msg);
            $nStmt->execute();
            $nStmt->close();
        }
        $stmt->close();
    }
    header("Location: admin.php?msg=item_approved");
    exit;
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
            $msg = "Your item '$title' has been declined. Please review the improved guidelines.";
            $nStmt->bind_param("is", $sellerId, $msg);
            $nStmt->execute();
            $nStmt->close();
        }
        $stmt->close();
    }
    header("Location: admin.php?msg=item_declined");
    exit;
}

if (isset($_GET['delete_user'])) {
    $uid = intval($_GET['delete_user']);
    // Simple delete (cascade will handle related data)
    $stmt = $conn->prepare("DELETE FROM USER WHERE UserID = ? AND Role != 'Admin'");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->close();
    header("Location: admin.php?msg=deleted");
    exit;
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

// Fetch All Users (Recent 20)
$users = [];
$res = $conn->query("SELECT UserID, FullName, Email, Role, Status, CreatedAt FROM USER ORDER BY CreatedAt DESC LIMIT 20");
while ($row = $res->fetch_assoc()) {
    $users[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Panel - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        h1,
        h2 {
            color: #f8fafc;
        }

        .card {
            background: #1e293b;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
        }

        .btn {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn-green {
            background: #22c55e;
            color: #022c22;
        }

        .btn-red {
            background: #ef4444;
            color: #white;
        }

        .btn-back {
            background: #475569;
            color: white;
            display: inline-block;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            text-decoration: none;
        }

        .alert {
            background: #064e3b;
            color: #a7f3d0;
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="btn-back">← Back to Dashboard</a>
        <h1>Admin Panel</h1>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert">Action completed successfully.</div>
        <?php endif; ?>

        <div class="card">
            <h2>User Statistics</h2>
            <div style="display: flex; gap: 2rem;">
                <?php foreach ($stats as $role => $count): ?>
                    <div>
                        <div style="font-size: 0.875rem; color: #94a3b8;"><?php echo $role; ?>s</div>
                        <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $count; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>Pending Service Provider Approvals</h2>
            <?php if (empty($pendingSPs)): ?>
                <p style="color: #94a3b8;">No pending approvals.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Business Name</th>
                            <th>Type</th>
                            <th>Area</th>
                            <th>Owner Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingSPs as $sp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sp['BusinessName']); ?></td>
                                <td><?php echo htmlspecialchars($sp['ServiceType']); ?></td>
                                <td><?php echo htmlspecialchars($sp['Area']); ?></td>
                                <td><?php echo htmlspecialchars($sp['FullName']); ?> <br>
                                    <small><?php echo htmlspecialchars($sp['Email']); ?></small>
                                </td>
                                <td>
                                    <a href="?approve_sp=<?php echo $sp['ProviderID']; ?>" class="btn btn-green">Approve</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- NEW: Marketplace Approvals -->
        <?php
        $pendingItems = [];
        $sql = "
            SELECT m.ItemID, m.Title, m.Price, m.Category, m.Description, m.Status, u.FullName 
            FROM MARKETITEM m 
            JOIN USER u ON m.SellerID = u.UserID 
            WHERE m.Status = 'Pending'
            ORDER BY m.CreatedAt DESC
        ";
        $res = $conn->query($sql);

        if (!$res) {
            echo "<div class='alert' style='background:#7f1d1d;color:#fecaca;'>SQL Error: " . $conn->error . "</div>";
        } else {
            while ($row = $res->fetch_assoc()) {
                $pendingItems[] = $row;
            }
        }
        ?>
        <div class="card">
            <h2>Pending Marketplace Items</h2>
            <?php if (empty($pendingItems)): ?>
                <p style="color: #94a3b8;">No pending items at the moment.</p>
                <p style="color: #64748b; font-size: 0.9rem;">When students post items to the marketplace, they will appear
                    here for approval.</p>
            <?php else: ?>
                <p style="color: #94a3b8; margin-bottom: 1rem;">
                    <?php echo count($pendingItems); ?> item(s) awaiting approval
                </p>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Seller</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingItems as $item): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($item['Title']); ?></strong></td>
                                <td style="max-width: 200px; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars(substr($item['Description'], 0, 100)); ?>...
                                </td>
                                <td>BDT <?php echo number_format($item['Price']); ?></td>
                                <td><?php echo htmlspecialchars($item['Category']); ?></td>
                                <td><?php echo htmlspecialchars($item['FullName']); ?></td>
                                <td>
                                    <a href="?approve_item=<?php echo $item['ItemID']; ?>" class="btn btn-green">Approve</a>
                                    <a href="?decline_item=<?php echo $item['ItemID']; ?>" class="btn btn-red"
                                        onclick="return confirm('Decline this item?')">Decline</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Recent Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
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
                            <td><?php echo date('M j, Y', strtotime($u['CreatedAt'])); ?></td>
                            <td>
                                <?php if ($u['Role'] !== 'Admin'): ?>
                                    <a href="?delete_user=<?php echo $u['UserID']; ?>" class="btn btn-red"
                                        onclick="return confirm('Are you sure?');">Delete</a>
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