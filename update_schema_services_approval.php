<?php
include 'config.php';

echo "<h2>Updating Schema for Service Approval...</h2>";

// Add IsApproved column to PROVIDER_SERVICES
$checkCol = $conn->query("SHOW COLUMNS FROM PROVIDER_SERVICES LIKE 'IsApproved'");
if ($checkCol->num_rows == 0) {
    $sql = "ALTER TABLE PROVIDER_SERVICES ADD COLUMN IsApproved TINYINT(1) DEFAULT 0 AFTER IsActive";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success: Added 'IsApproved' column (Default: 0/False).</p>";
    } else {
        echo "<p style='color:red'>Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:orange'>Skipped: 'IsApproved' column already exists.</p>";
}

echo "<h3>Schema Update Complete.</h3>";
echo "<a href='index.php'>Go Back to Dashboard</a>";
?>