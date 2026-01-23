<?php
include 'config.php';

echo "<h2>Database Schema Verification & Fix</h2>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

// Check if PROVIDER_SERVICES table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'PROVIDER_SERVICES'");
if ($checkTable->num_rows == 0) {
    echo "<p class='error'>❌ PROVIDER_SERVICES table does not exist. Creating it now...</p>";

    $sql = "CREATE TABLE IF NOT EXISTS PROVIDER_SERVICES (
        ServiceID INT AUTO_INCREMENT PRIMARY KEY,
        ProviderID INT NOT NULL,
        ServiceType VARCHAR(50) NOT NULL,
        Description TEXT NULL,
        Price DECIMAL(10,2) NULL,
        IsActive BOOLEAN DEFAULT TRUE,
        IsApproved TINYINT(1) DEFAULT 0,
        CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_ps_provider FOREIGN KEY (ProviderID) 
            REFERENCES SERVICEPROVIDER(ProviderID) ON DELETE CASCADE ON UPDATE CASCADE,
        INDEX idx_ps_provider (ProviderID),
        INDEX idx_ps_type (ServiceType)
    ) ENGINE=InnoDB;";

    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✅ Created PROVIDER_SERVICES table successfully.</p>";

        // Migrate existing data
        echo "<p>Migrating existing service provider data...</p>";
        $migSql = "INSERT INTO PROVIDER_SERVICES (ProviderID, ServiceType, IsApproved)
                   SELECT ProviderID, ServiceType, IsApproved FROM SERVICEPROVIDER";

        if ($conn->query($migSql) === TRUE) {
            echo "<p class='success'>✅ Migrated existing services successfully.</p>";
        } else {
            echo "<p class='error'>❌ Error migrating: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='error'>❌ Error creating table: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✅ PROVIDER_SERVICES table exists.</p>";

    // Check if IsApproved column exists
    $checkCol = $conn->query("SHOW COLUMNS FROM PROVIDER_SERVICES LIKE 'IsApproved'");
    if ($checkCol->num_rows == 0) {
        echo "<p class='warning'>⚠️ IsApproved column missing. Adding it now...</p>";
        $sql = "ALTER TABLE PROVIDER_SERVICES ADD COLUMN IsApproved TINYINT(1) DEFAULT 0 AFTER IsActive";
        if ($conn->query($sql) === TRUE) {
            echo "<p class='success'>✅ Added IsApproved column successfully.</p>";
        } else {
            echo "<p class='error'>❌ Error adding column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p class='success'>✅ IsApproved column exists.</p>";
    }
}

// Display current schema
echo "<h3>Current PROVIDER_SERVICES Schema:</h3>";
$result = $conn->query("DESCRIBE PROVIDER_SERVICES");
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

// Check data
echo "<h3>Current Service Data:</h3>";
$result = $conn->query("SELECT COUNT(*) as count FROM PROVIDER_SERVICES");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>Total services in PROVIDER_SERVICES: <strong>{$row['count']}</strong></p>";
}

echo "<hr>";
echo "<p><a href='service_list.php'>Go to Service List</a> | <a href='index.php'>Go to Dashboard</a></p>";
?>