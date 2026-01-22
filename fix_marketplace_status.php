<?php
// Quick fix script - Run this once to update items with empty status
include 'config.php';

echo "<h2>Fixing Marketplace Items with Empty Status</h2>";

// Update items with empty or NULL status to 'Pending'
$sql = "UPDATE MARKETITEM SET Status = 'Pending' WHERE Status IS NULL OR Status = ''";
$result = $conn->query($sql);

if ($result) {
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'><strong>Success!</strong> Updated $affected item(s) to 'Pending' status.</p>";
} else {
    echo "<p style='color: red;'><strong>Error:</strong> " . $conn->error . "</p>";
}

// Show updated items
echo "<h3>Items now with Pending status:</h3>";
$result2 = $conn->query("SELECT ItemID, Title, Status, SellerID FROM MARKETITEM WHERE Status = 'Pending'");

if ($result2 && $result2->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ItemID</th><th>Title</th><th>Status</th><th>SellerID</th></tr>";
    while ($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['ItemID'] . "</td>";
        echo "<td>" . htmlspecialchars($row['Title']) . "</td>";
        echo "<td><strong>" . $row['Status'] . "</strong></td>";
        echo "<td>" . $row['SellerID'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p><strong>Next step:</strong> Go to <a href='admin.php'>admin.php</a> to approve these items.</p>";
} else {
    echo "<p>No pending items found.</p>";
}

// Notify admins about these items
echo "<h3>Sending notifications to admins...</h3>";
$adminStmt = $conn->prepare("SELECT UserID, FullName FROM USER WHERE Role = 'Admin'");
$adminStmt->execute();
$adminRes = $adminStmt->get_result();

$pendingItems = $conn->query("SELECT ItemID, Title FROM MARKETITEM WHERE Status = 'Pending'");
$itemCount = $pendingItems->num_rows;

if ($adminRes->num_rows > 0 && $itemCount > 0) {
    while ($admin = $adminRes->fetch_assoc()) {
        // Check if notification already exists
        $checkNotif = $conn->prepare("SELECT NotificationID FROM NOTIFICATION WHERE UserID = ? AND Title = 'Pending Items Reminder' AND CreatedAt > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $checkNotif->bind_param("i", $admin['UserID']);
        $checkNotif->execute();
        $existingNotif = $checkNotif->get_result();

        if ($existingNotif->num_rows == 0) {
            $notifStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Pending Items Reminder', ?)");
            $notifMsg = "You have $itemCount marketplace item(s) awaiting approval.";
            $notifStmt->bind_param("is", $admin['UserID'], $notifMsg);
            $notifStmt->execute();
            $notifStmt->close();
            echo "<p>✓ Notified admin: " . htmlspecialchars($admin['FullName']) . "</p>";
        } else {
            echo "<p>⊘ Admin " . htmlspecialchars($admin['FullName']) . " already has recent notification</p>";
        }
        $checkNotif->close();
    }
} else {
    if ($adminRes->num_rows == 0) {
        echo "<p style='color: orange;'><strong>Warning:</strong> No admin users found!</p>";
    }
    if ($itemCount == 0) {
        echo "<p>No pending items to notify about.</p>";
    }
}

echo "<hr>";
echo "<p><a href='debug_marketplace.php'>← Back to Debug Page</a> | <a href='admin.php'>Go to Admin Panel →</a></p>";
?>