<?php
include 'config.php';

echo "<h2>Updating Database Schema for Reviews...</h2>";

// 1. Create REVIEWS table
$sql = "CREATE TABLE IF NOT EXISTS REVIEWS (
    ReviewID INT AUTO_INCREMENT PRIMARY KEY,
    BookingID INT NOT NULL UNIQUE,
    ProviderID INT NOT NULL,
    UserID INT NOT NULL,
    Rating INT NOT NULL CHECK (Rating BETWEEN 1 AND 5),
    Comment TEXT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_review_booking FOREIGN KEY (BookingID) 
        REFERENCES SERVICEBOOKING(BookingID) ON DELETE CASCADE,
    CONSTRAINT fk_review_provider FOREIGN KEY (ProviderID) 
        REFERENCES SERVICEPROVIDER(ProviderID) ON DELETE CASCADE,
    CONSTRAINT fk_review_user FOREIGN KEY (UserID) 
        REFERENCES USER(UserID) ON DELETE CASCADE,
    INDEX idx_review_provider (ProviderID)
) ENGINE=InnoDB;";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green'>Success: Created 'REVIEWS' table.</p>";
} else {
    echo "<p style='color:red'>Error creating table: " . $conn->error . "</p>";
}

// 2. Create Trigger to Auto-Update Average Rating
$trigger = "
CREATE TRIGGER trg_after_review_insert
AFTER INSERT ON REVIEWS
FOR EACH ROW
BEGIN
    UPDATE SERVICEPROVIDER sp
    SET 
        TotalReviews = (SELECT COUNT(*) FROM REVIEWS WHERE ProviderID = NEW.ProviderID),
        AverageRating = (SELECT IFNULL(AVG(Rating), 0) FROM REVIEWS WHERE ProviderID = NEW.ProviderID)
    WHERE ProviderID = NEW.ProviderID;
END;
";

// Drop trigger if exists to avoid error
$conn->query("DROP TRIGGER IF EXISTS trg_after_review_insert");

if ($conn->multi_query($trigger)) {
    // Flush multi_queries
    while ($conn->more_results() && $conn->next_result()) {
        ;
    }
    echo "<p style='color:green'>Success: Created Rating Trigger.</p>";
} else {
    echo "<p style='color:red'>Error creating trigger: " . $conn->error . "</p>";
}

echo "<h3>Schema Update Complete.</h3>";
echo "<a href='index.php'>Go Back to Dashboard</a>";
?>