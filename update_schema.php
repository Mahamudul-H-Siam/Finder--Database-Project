<?php
include 'config.php';

try {
    $conn->query("ALTER TABLE SERVICEPROVIDER ADD COLUMN Description TEXT NULL");
    echo "Added Description column.\n";
} catch (Exception $e) {
    echo "Description column might already exist: " . $e->getMessage() . "\n";
}

try {
    $conn->query("ALTER TABLE SERVICEPROVIDER ADD COLUMN StartingPrice DECIMAL(10,2) NULL DEFAULT 0.00");
    echo "Added StartingPrice column.\n";
} catch (Exception $e) {
    echo "StartingPrice column might already exist: " . $e->getMessage() . "\n";
}

echo "Schema update complete.";
?>