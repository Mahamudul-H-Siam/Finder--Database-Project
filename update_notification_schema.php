<?php
include 'config.php';

echo "<h2>Updating NOTIFICATION Table Schema...</h2>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// Check if Link column exists in NOTIFICATION table
$checkCol = $conn->query("SHOW COLUMNS FROM NOTIFICATION LIKE 'Link'");
if ($checkCol->num_rows == 0) {
    echo "<p class='warning'>⚠️ Link column missing in NOTIFICATION table. Adding it now...</p>";
    $sql = "ALTER TABLE NOTIFICATION ADD COLUMN Link VARCHAR(255) NULL AFTER Message";
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✅ Added Link column successfully.</p>";
    } else {
        echo "<p class='error'>❌ Error adding column: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✅ Link column already exists in NOTIFICATION table.</p>";
}

// Display current schema
echo "<h3>Current NOTIFICATION Schema:</h3>";
$result = $conn->query("DESCRIBE NOTIFICATION");
if ($result) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";
?>