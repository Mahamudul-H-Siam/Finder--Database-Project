<?php
// Debug script to check marketplace items status
include 'config.php';

echo "<h2>Marketplace Items Debug</h2>";
echo "<h3>All Items:</h3>";

$result = $conn->query("SELECT ItemID, Title, Status, SellerID, CreatedAt FROM MARKETITEM ORDER BY CreatedAt DESC LIMIT 20");

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ItemID</th><th>Title</th><th>Status</th><th>SellerID</th><th>CreatedAt</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['ItemID'] . "</td>";
        echo "<td>" . htmlspecialchars($row['Title']) . "</td>";
        echo "<td><strong>" . $row['Status'] . "</strong></td>";
        echo "<td>" . $row['SellerID'] . "</td>";
        echo "<td>" . $row['CreatedAt'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $conn->error;
}

echo "<h3>Pending Items Only:</h3>";
$result2 = $conn->query("SELECT ItemID, Title, Status FROM MARKETITEM WHERE Status = 'Pending'");
if ($result2) {
    if ($result2->num_rows > 0) {
        echo "<ul>";
        while ($row = $result2->fetch_assoc()) {
            echo "<li>ID: " . $row['ItemID'] . " - " . htmlspecialchars($row['Title']) . " - Status: " . $row['Status'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No pending items found.</p>";
    }
} else {
    echo "Error: " . $conn->error;
}

echo "<h3>Admin Users:</h3>";
$result3 = $conn->query("SELECT UserID, FullName, Email, Role FROM USER WHERE Role = 'Admin'");
if ($result3) {
    if ($result3->num_rows > 0) {
        echo "<ul>";
        while ($row = $result3->fetch_assoc()) {
            echo "<li>ID: " . $row['UserID'] . " - " . htmlspecialchars($row['FullName']) . " (" . $row['Email'] . ")</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'><strong>WARNING: No admin users found!</strong></p>";
    }
}
?>