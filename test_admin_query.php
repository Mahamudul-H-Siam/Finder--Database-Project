<?php
// Test if admin panel query works
include 'config.php';

echo "<h2>Admin Panel Query Test</h2>";

// First, update empty status items
echo "<h3>Step 1: Fixing empty status items...</h3>";
$fix = $conn->query("UPDATE MARKETITEM SET Status = 'Pending' WHERE Status IS NULL OR Status = ''");
if ($fix) {
    echo "<p style='color:green;'>✓ Updated " . $conn->affected_rows . " items</p>";
} else {
    echo "<p style='color:red;'>✗ Error: " . $conn->error . "</p>";
}

// Now test the admin query
echo "<h3>Step 2: Testing admin panel query...</h3>";
$sql = "
    SELECT m.ItemID, m.Title, m.Price, m.Category, m.Description, m.Status, u.FullName 
    FROM MARKETITEM m 
    JOIN USER u ON m.SellerID = u.UserID 
    WHERE m.Status = 'Pending'
    ORDER BY m.CreatedAt DESC
";

echo "<p><strong>Query:</strong></p>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";

$res = $conn->query($sql);

if (!$res) {
    echo "<p style='color:red;'><strong>SQL Error:</strong> " . $conn->error . "</p>";
} else {
    $count = $res->num_rows;
    echo "<p style='color:green;'><strong>Success!</strong> Found $count pending item(s)</p>";

    if ($count > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ItemID</th><th>Title</th><th>Status</th><th>Seller</th></tr>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['ItemID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['Title']) . "</td>";
            echo "<td><strong>" . $row['Status'] . "</strong></td>";
            echo "<td>" . htmlspecialchars($row['FullName']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";

        echo "<hr>";
        echo "<p><strong>✓ Query works! These items should appear in admin.php</strong></p>";
        echo "<p><a href='admin.php' style='background:#22c55e;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Admin Panel</a></p>";
    }
}
?>