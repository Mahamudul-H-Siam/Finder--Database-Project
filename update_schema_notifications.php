<?php
include 'config.php';

echo "<h2>Updating Database Schema...</h2>";

// 1. Add Link column to NOTIFICATION table if it doesn't exist
$checkCol = $conn->query("SHOW COLUMNS FROM NOTIFICATION LIKE 'Link'");
if ($checkCol->num_rows == 0) {
    $sql = "ALTER TABLE NOTIFICATION ADD COLUMN Link VARCHAR(255) NULL AFTER Message";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success: Added 'Link' column to NOTIFICATION table.</p>";
    } else {
        echo "<p style='color:red'>Error adding 'Link' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:orange'>Skipped: 'Link' column already exists in NOTIFICATION table.</p>";
}

// 2. Add LinkType column (optional, but good for filtering/icons)
$checkColType = $conn->query("SHOW COLUMNS FROM NOTIFICATION LIKE 'Type'");
if ($checkColType->num_rows == 0) {
    $sql = "ALTER TABLE NOTIFICATION ADD COLUMN Type ENUM('General','Message','Order','System') NOT NULL DEFAULT 'General' AFTER UserID";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>Success: Added 'Type' column to NOTIFICATION table.</p>";
    } else {
        echo "<p style='color:red'>Error adding 'Type' column: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:orange'>Skipped: 'Type' column already exists in NOTIFICATION table.</p>";
}

echo "<h3>Schema Update Complete.</h3>";
echo "<a href='index.php'>Go Back to Dashboard</a>";
?>
