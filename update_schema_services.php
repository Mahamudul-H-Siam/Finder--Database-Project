<?php
include 'config.php';

echo "<h2>Updating Database Schema for Services...</h2>";

// 1. Create PROVIDER_SERVICES table
$sql = "CREATE TABLE IF NOT EXISTS PROVIDER_SERVICES (
    ServiceID INT AUTO_INCREMENT PRIMARY KEY,
    ProviderID INT NOT NULL,
    ServiceType VARCHAR(50) NOT NULL,
    Description TEXT NULL,
    Price DECIMAL(10,2) NULL,
    IsActive BOOLEAN DEFAULT TRUE,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ps_provider FOREIGN KEY (ProviderID) 
        REFERENCES SERVICEPROVIDER(ProviderID) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_ps_provider (ProviderID),
    INDEX idx_ps_type (ServiceType)
) ENGINE=InnoDB;";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>Success: Created 'PROVIDER_SERVICES' table.</p>";
} else {
    echo "<p style='color:red'>Error creating table: " . $conn->error . "</p>";
}

// 2. Migrate existing data
// Check if table is empty first to avoid duplicates
$check = $conn->query("SELECT COUNT(*) as count FROM PROVIDER_SERVICES");
$row = $check->fetch_assoc();

if ($row['count'] == 0) {
    echo "<p>Migrating existing services...</p>";
    $migSql = "INSERT INTO PROVIDER_SERVICES (ProviderID, ServiceType)
               SELECT ProviderID, ServiceType FROM SERVICEPROVIDER";

    if ($conn->query($migSql) === TRUE) {
        echo "<p style='color:green'>Success: Migrated existing services.</p>";
    } else {
        echo "<p style='color:red'>Error migrating: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:orange'>Skipped migration: Data already exists.</p>";
}

echo "<h3>Schema Update Complete.</h3>";
echo "<a href='index.php'>Go Back to Dashboard</a>";
?>