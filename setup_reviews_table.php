<?php
include 'config.php';

echo "<h2>Creating REVIEWS Table...</h2>";
echo "<style>body{font-family:sans-serif;padding:20px;} .success{color:green;} .error{color:red;}</style>";

// Check if REVIEWS table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'REVIEWS'");
if ($checkTable->num_rows == 0) {
    echo "<p class='error'>❌ REVIEWS table does not exist. Creating it now...</p>";

    $sql = "CREATE TABLE IF NOT EXISTS REVIEWS (
        ReviewID INT AUTO_INCREMENT PRIMARY KEY,
        BookingID INT NOT NULL,
        ProviderID INT NOT NULL,
        UserID INT NOT NULL,
        Rating TINYINT NOT NULL CHECK (Rating BETWEEN 1 AND 5),
        Comment TEXT NULL,
        CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_review_booking FOREIGN KEY (BookingID) 
            REFERENCES SERVICEBOOKING(BookingID) ON DELETE CASCADE,
        CONSTRAINT fk_review_provider FOREIGN KEY (ProviderID) 
            REFERENCES SERVICEPROVIDER(ProviderID) ON DELETE CASCADE,
        CONSTRAINT fk_review_user FOREIGN KEY (UserID) 
            REFERENCES USER(UserID) ON DELETE CASCADE,
        UNIQUE KEY unique_booking_review (BookingID),
        INDEX idx_provider_reviews (ProviderID),
        INDEX idx_user_reviews (UserID)
    ) ENGINE=InnoDB;";

    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✅ Created REVIEWS table successfully.</p>";
    } else {
        echo "<p class='error'>❌ Error creating table: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='success'>✅ REVIEWS table already exists.</p>";
}

// Display current schema
echo "<h3>Current REVIEWS Schema:</h3>";
$result = $conn->query("DESCRIBE REVIEWS");
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
} else {
    echo "<p class='error'>Error: " . $conn->error . "</p>";
}

// Add trigger to update provider ratings
echo "<h3>Creating Trigger to Update Provider Ratings...</h3>";

$triggerSQL = "
DROP TRIGGER IF EXISTS update_provider_rating_after_review;

CREATE TRIGGER update_provider_rating_after_review
AFTER INSERT ON REVIEWS
FOR EACH ROW
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    DECLARE total_reviews INT;
    
    SELECT AVG(Rating), COUNT(*) INTO avg_rating, total_reviews
    FROM REVIEWS
    WHERE ProviderID = NEW.ProviderID;
    
    UPDATE SERVICEPROVIDER
    SET AverageRating = avg_rating,
        TotalReviews = total_reviews
    WHERE ProviderID = NEW.ProviderID;
END;
";

// Execute trigger creation
$conn->multi_query($triggerSQL);
do {
    if ($result = $conn->store_result()) {
        $result->free();
    }
} while ($conn->next_result());

echo "<p class='success'>✅ Trigger created/updated successfully.</p>";

echo "<hr>";
echo "<p><a href='index.php'>Go to Dashboard</a></p>";
?>