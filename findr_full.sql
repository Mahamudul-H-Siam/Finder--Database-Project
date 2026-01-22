-- =============================================
-- FindR Database - Full Schema + Sample Data
-- Updated: Added MESSAGE table for user-to-user messaging
--          Added data cleanup for marketplace items
--          Fixed MARKETITEM default status to 'Pending'
-- Compatible with MySQL 8.0+ / MariaDB 10.3+
-- =============================================

-- 1. DATABASE CREATION
CREATE DATABASE IF NOT EXISTS findr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE findr;

-- 2. TABLE DEFINITIONS

-- 2.1 USER
CREATE TABLE IF NOT EXISTS USER (
  UserID       INT AUTO_INCREMENT PRIMARY KEY,
  FullName     VARCHAR(100) NOT NULL,
  Email        VARCHAR(100) NOT NULL UNIQUE,
  Phone        VARCHAR(20)  NOT NULL,
  PasswordHash VARCHAR(255) NOT NULL,
  Role         ENUM('Student','Owner','ServiceProvider','Admin') NOT NULL DEFAULT 'Student',
  AgeGroup     ENUM('18-21','22-25','26-30','Over30') NOT NULL,
  LivingStatus ENUM('Alone','Roommates','Family','Hostel') NOT NULL,
  Bio          TEXT NULL,
  Country      VARCHAR(100) NULL,
  CreatedAt    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Status       ENUM('Active','Blocked','Suspended') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

-- 2.2 SERVICEPROVIDER (with IsApproved added)
CREATE TABLE IF NOT EXISTS SERVICEPROVIDER (
  ProviderID     INT PRIMARY KEY,
  ServiceType    ENUM('Cleaner','Van','Mess','Tuition','Other') NOT NULL,
  BusinessName   VARCHAR(100) NOT NULL,
  Area           VARCHAR(100) NOT NULL,
  AverageRating  DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  TotalReviews   INT NOT NULL DEFAULT 0,
  IsApproved     BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Admin must approve before visible to students',
  CONSTRAINT fk_sp_user FOREIGN KEY (ProviderID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 2.3 ROOMLISTING
CREATE TABLE IF NOT EXISTS ROOMLISTING (
  RoomID            INT AUTO_INCREMENT PRIMARY KEY,
  OwnerID           INT NOT NULL,
  ListingType       ENUM('Room','HostelBed','RoommateWanted') NOT NULL,
  Title             VARCHAR(150) NOT NULL,
  Description       TEXT NOT NULL,
  LocationArea      VARCHAR(100) NOT NULL,
  Latitude          DECIMAL(10,8) NULL,
  Longitude         DECIMAL(11,8) NULL,
  RentAmount        DECIMAL(10,2) NOT NULL,
  UtilitiesIncluded BOOLEAN NOT NULL DEFAULT FALSE,
  GenderPreference  ENUM('Any','Male','Female') NOT NULL DEFAULT 'Any',
  IsVerified        BOOLEAN NOT NULL DEFAULT FALSE,
  Status            ENUM('Available','Booked','Closed') NOT NULL DEFAULT 'Available',
  CreatedAt         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_room_owner FOREIGN KEY (OwnerID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_room_owner (OwnerID),
  INDEX idx_room_area (LocationArea),
  INDEX idx_room_type_status (ListingType, Status)
) ENGINE=InnoDB;

-- 2.4 ROOMAPPLICATION
CREATE TABLE IF NOT EXISTS ROOMAPPLICATION (
  ApplicationID INT AUTO_INCREMENT PRIMARY KEY,
  RoomID        INT NOT NULL,
  ApplicantID   INT NOT NULL,
  Message       TEXT NULL,
  Status        ENUM('Pending','Accepted','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
  AppliedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_room FOREIGN KEY (RoomID)
    REFERENCES ROOMLISTING(RoomID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_app_user FOREIGN KEY (ApplicantID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_app_room (RoomID),
  INDEX idx_app_applicant (ApplicantID),
  CONSTRAINT uq_room_applicant UNIQUE (RoomID, ApplicantID)
) ENGINE=InnoDB;

-- 2.5 SERVICEBOOKING
CREATE TABLE IF NOT EXISTS SERVICEBOOKING (
  BookingID     INT AUTO_INCREMENT PRIMARY KEY,
  ProviderID    INT NOT NULL,
  UserID        INT NOT NULL,
  ServiceType   ENUM('Cleaner','Van','Tuition','Meal','Other') NOT NULL,
  Date          DATE NOT NULL,
  TimeSlot      VARCHAR(50) NOT NULL,
  Address       VARCHAR(255) NOT NULL,
  Price         DECIMAL(10,2) NOT NULL,
  PaymentStatus ENUM('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  BookingStatus ENUM('Requested','Confirmed','InProgress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
  CreatedAt     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_provider FOREIGN KEY (ProviderID)
    REFERENCES SERVICEPROVIDER(ProviderID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_booking_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_booking_provider_date (ProviderID, Date),
  INDEX idx_booking_user_date (UserID, Date)
) ENGINE=InnoDB;

-- 2.6 MEALPLAN
CREATE TABLE IF NOT EXISTS MEALPLAN (
  MealPlanID   INT AUTO_INCREMENT PRIMARY KEY,
  ProviderID   INT NOT NULL,
  Name         VARCHAR(100) NOT NULL,
  MonthlyPrice DECIMAL(10,2) NOT NULL,
  Details      TEXT NULL,
  IsActive     BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_mealplan_provider FOREIGN KEY (ProviderID)
    REFERENCES SERVICEPROVIDER(ProviderID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_mealplan_provider (ProviderID),
  INDEX idx_mealplan_active (IsActive)
) ENGINE=InnoDB;

-- 2.7 MARKETITEM
CREATE TABLE IF NOT EXISTS MARKETITEM (
  ItemID      INT AUTO_INCREMENT PRIMARY KEY,
  SellerID    INT NOT NULL,
  Title       VARCHAR(150) NOT NULL,
  Description TEXT NOT NULL,
  Category    ENUM('Electronics','Furniture','Books','Clothing','Sports','Others') NOT NULL,
  Price       DECIMAL(10,2) NOT NULL,
  `Condition` ENUM('New','LikeNew','Used','VeryUsed') NOT NULL,
  Status      ENUM('Available','Reserved','Sold','Inactive','Pending','Declined') NOT NULL DEFAULT 'Pending',
  CreatedAt   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_market_seller FOREIGN KEY (SellerID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_market_seller (SellerID),
  INDEX idx_market_cat_status (Category, Status)
) ENGINE=InnoDB;

-- 2.8 GROCERYPRICE
CREATE TABLE IF NOT EXISTS GROCERYPRICE (
  GroceryID   INT AUTO_INCREMENT PRIMARY KEY,
  ItemName    VARCHAR(100) NOT NULL,
  Unit        VARCHAR(20) NOT NULL,
  Price       DECIMAL(8,2) NOT NULL,
  MarketName  VARCHAR(100) NOT NULL,
  Date        DATE NOT NULL,
  INDEX idx_grocery_item_date (ItemName, Date),
  INDEX idx_grocery_market_date (MarketName, Date)
) ENGINE=InnoDB;

-- 2.9 BUSROUTE
CREATE TABLE IF NOT EXISTS BUSROUTE (
  RouteID       INT AUTO_INCREMENT PRIMARY KEY,
  RouteName     VARCHAR(100) NOT NULL,
  StartPoint    VARCHAR(100) NOT NULL,
  EndPoint      VARCHAR(100) NOT NULL,
  Fare          DECIMAL(6,2) NOT NULL,
  FirstBusTime  TIME NOT NULL,
  LastBusTime   TIME NOT NULL,
  INDEX idx_route_name (RouteName),
  INDEX idx_route_points (StartPoint, EndPoint)
) ENGINE=InnoDB;

-- 2.10 LOSTFOUND
CREATE TABLE IF NOT EXISTS LOSTFOUND (
  LFID        INT AUTO_INCREMENT PRIMARY KEY,
  ReporterID  INT NOT NULL,
  PostType    ENUM('Lost','Found') NOT NULL,
  Title       VARCHAR(150) NOT NULL,
  Description TEXT NOT NULL,
  Location    VARCHAR(150) NOT NULL,
  ContactInfo VARCHAR(100) NOT NULL,
  Status      ENUM('Open','Resolved','Expired') NOT NULL DEFAULT 'Open',
  Date        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lf_reporter FOREIGN KEY (ReporterID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_lf_reporter (ReporterID),
  INDEX idx_lf_status (Status)
) ENGINE=InnoDB;

-- 2.11 BUDGETCATEGORY
CREATE TABLE IF NOT EXISTS BUDGETCATEGORY (
  CategoryID   INT AUTO_INCREMENT PRIMARY KEY,
  UserID       INT NOT NULL,
  Name         VARCHAR(100) NOT NULL,
  MonthlyLimit DECIMAL(10,2) NULL,
  CONSTRAINT fk_budgetcat_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_budgetcat_user (UserID),
  CONSTRAINT uq_budgetcat_user_name UNIQUE (UserID, Name)
) ENGINE=InnoDB;

-- 2.12 BUDGETTRANSACTION
CREATE TABLE IF NOT EXISTS BUDGETTRANSACTION (
  TransactionID INT AUTO_INCREMENT PRIMARY KEY,
  UserID        INT NOT NULL,
  CategoryID    INT NOT NULL,
  Amount        DECIMAL(10,2) NOT NULL,
  Type          ENUM('Expense','Income') NOT NULL,
  Note          VARCHAR(255) NULL,
  Date          DATE NOT NULL,
  CONSTRAINT fk_tx_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_tx_category FOREIGN KEY (CategoryID)
    REFERENCES BUDGETCATEGORY(CategoryID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_tx_user_date (UserID, Date),
  INDEX idx_tx_category_date (CategoryID, Date)
) ENGINE=InnoDB;

-- 2.13 MOODENTRY (Extended)
CREATE TABLE IF NOT EXISTS MOODENTRY (
  MoodID     INT AUTO_INCREMENT PRIMARY KEY,
  UserID     INT NOT NULL,
  MoodLevel  TINYINT NOT NULL,
  MoodLabel  VARCHAR(50) NULL,
  EnergyLevel TINYINT NULL,
  StressLevel TINYINT NULL,
  Activities TEXT NULL,
  MedicationTaken BOOLEAN DEFAULT FALSE,
  Note       TEXT NULL,
  CreatedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mood_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_mood_level CHECK (MoodLevel BETWEEN 1 AND 10),
  INDEX idx_mood_user_date (UserID, CreatedAt)
) ENGINE=InnoDB;

-- 2.14 USER_POINTS (Gamification)
CREATE TABLE IF NOT EXISTS USER_POINTS (
  PointID     INT AUTO_INCREMENT PRIMARY KEY,
  UserID      INT NOT NULL UNIQUE,
  TotalPoints INT NOT NULL DEFAULT 0,
  TierLevel   ENUM('Bronze','Silver','Gold','Platinum') NOT NULL DEFAULT 'Bronze',
  StreakDays  INT NOT NULL DEFAULT 0,
  LastActivityDate DATE NULL,
  CONSTRAINT fk_points_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 2.15 USER_BADGES
CREATE TABLE IF NOT EXISTS USER_BADGES (
  BadgeID   INT AUTO_INCREMENT PRIMARY KEY,
  UserID    INT NOT NULL,
  BadgeName VARCHAR(100) NOT NULL,
  BadgeDescription VARCHAR(255) NULL,
  EarnedAt  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_badge_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 2.16 NOTIFICATION
CREATE TABLE IF NOT EXISTS NOTIFICATION (
  NotificationID INT AUTO_INCREMENT PRIMARY KEY,
  UserID         INT NOT NULL,
  Title          VARCHAR(100) NOT NULL,
  Message        TEXT NOT NULL,
  IsRead         BOOLEAN NOT NULL DEFAULT FALSE,
  CreatedAt      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notif_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_notif_user (UserID, IsRead)
) ENGINE=InnoDB;

-- 2.17 MESSAGE (User-to-User Messaging)
CREATE TABLE IF NOT EXISTS MESSAGE (
  MessageID       INT AUTO_INCREMENT PRIMARY KEY,
  SenderID        INT NOT NULL,
  ReceiverID      INT NOT NULL,
  Subject         VARCHAR(200) NULL COMMENT 'Optional subject line',
  MessageText     TEXT NOT NULL,
  IsRead          BOOLEAN NOT NULL DEFAULT FALSE,
  CreatedAt       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_message_sender FOREIGN KEY (SenderID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_message_receiver FOREIGN KEY (ReceiverID)
    REFERENCES USER(UserID) ON DELETE CASCADE ON UPDATE CASCADE,
    
  INDEX idx_message_sender (SenderID, CreatedAt),
  INDEX idx_message_receiver (ReceiverID, IsRead, CreatedAt),
  INDEX idx_message_conversation (SenderID, ReceiverID, CreatedAt)
) ENGINE=InnoDB;

-- 3. TRIGGERS

DELIMITER //

-- 3.1 Ensure SERVICEPROVIDER row only for Role = 'ServiceProvider'
DROP TRIGGER IF EXISTS trg_sp_role_check//

CREATE TRIGGER trg_sp_role_check
BEFORE INSERT ON SERVICEPROVIDER
FOR EACH ROW
BEGIN
  DECLARE r ENUM('Student','Owner','ServiceProvider','Admin');
  SELECT Role INTO r FROM USER WHERE UserID = NEW.ProviderID;
  IF r <> 'ServiceProvider' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Provider must have Role = ServiceProvider';
  END IF;
END//

-- 3.2 Prevent owner from applying to own room
DROP TRIGGER IF EXISTS trg_room_app_not_owner//

CREATE TRIGGER trg_room_app_not_owner
BEFORE INSERT ON ROOMAPPLICATION
FOR EACH ROW
BEGIN
  DECLARE vOwner INT;
  SELECT OwnerID INTO vOwner FROM ROOMLISTING WHERE RoomID = NEW.RoomID;
  IF vOwner = NEW.ApplicantID THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Owner cannot apply to own room';
  END IF;
END//

DELIMITER ;

-- 4. SAMPLE DATA

-- 4.1 Users (using simple hashes for demo)
INSERT IGNORE INTO USER (FullName, Email, Phone, PasswordHash, Role, AgeGroup, LivingStatus)
VALUES
  ('Rahim Uddin',      'rahim@example.com',      '01711111111', 'hash1', 'Student',        '18-21', 'Roommates'),
  ('Karim Hossain',    'karim.owner@example.com','01722222222', 'hash2', 'Owner',          '26-30', 'Family'),
  ('CleanPro Services','cleanpro@example.com',   '01733333333', 'hash3', 'ServiceProvider','22-25', 'Family'),
  ('Admin User',       'admin@example.com',      '01744444444', 'hash4', 'Admin',          'Over30', 'Family');

-- 4.2 Service provider (starts not approved)
INSERT IGNORE INTO SERVICEPROVIDER (ProviderID, ServiceType, BusinessName, Area, IsApproved)
VALUES (3, 'Cleaner', 'CleanPro Services', 'Dhanmondi', FALSE);

-- Approve it for testing (uncomment if you want it visible immediately)
-- UPDATE SERVICEPROVIDER SET IsApproved = TRUE WHERE ProviderID = 3;

-- 4.3 Room listing + application
INSERT IGNORE INTO ROOMLISTING
  (OwnerID, ListingType, Title, Description, LocationArea, Latitude, Longitude, RentAmount, UtilitiesIncluded, GenderPreference, IsVerified)
VALUES
  (2, 'Room', '2BHK near Dhanmondi 27', 'Spacious room with balcony, close to bus stop.', 'Dhanmondi',
   23.746466, 90.376015, 15000.00, TRUE, 'Male', TRUE);

INSERT IGNORE INTO ROOMAPPLICATION (RoomID, ApplicantID, Message)
VALUES (1, 1, 'I am a student at DU, looking for room from next month.');

-- 4.4 Service booking + mealplan
INSERT IGNORE INTO SERVICEBOOKING
  (ProviderID, UserID, ServiceType, Date, TimeSlot, Address, Price)
VALUES
  (3, 1, 'Cleaner', '2026-01-15', '09:00-11:00', 'House 10, Road 5, Dhanmondi', 600.00);

INSERT IGNORE INTO MEALPLAN (ProviderID, Name, MonthlyPrice, Details)
VALUES (3, '30-Day Full Board', 5500.00, 'Breakfast, lunch, dinner, 7 days a week.');

-- 4.5 Marketplace item
INSERT IGNORE INTO MARKETITEM
  (SellerID, Title, Description, Category, Price, `Condition`)
VALUES
  (1, 'Used Study Table', 'Wooden table, good condition.', 'Furniture', 2500.00, 'Used');

-- 4.6 Budget
INSERT IGNORE INTO BUDGETCATEGORY (UserID, Name, MonthlyLimit)
VALUES
  (1, 'Rent', 15000.00),
  (1, 'Food', 8000.00);

INSERT IGNORE INTO BUDGETTRANSACTION (UserID, CategoryID, Amount, Type, Note, Date)
VALUES
  (1, 1, 15000.00, 'Expense', 'Monthly room rent', '2026-01-01'),
  (1, 2, 300.00,  'Expense', 'Lunch at canteen',   '2026-01-02');

-- 4.7 Mood
INSERT IGNORE INTO MOODENTRY (UserID, MoodLevel, Note)
VALUES (1, 4, 'Feeling positive about studies today.');

-- =============================================
-- Optional: Quick test queries
-- =============================================

-- Check service providers (including approval status)
-- SELECT sp.*, u.FullName FROM SERVICEPROVIDER sp JOIN USER u ON sp.ProviderID = u.UserID;

-- Check if approved providers appear in your app logic
-- SELECT * FROM SERVICEPROVIDER WHERE IsApproved = TRUE;


-- ==========================================
-- FINDR MASSIVE DEMO DATA INSERT SCRIPT
-- ==========================================

-- 1. INSERT USERS (Sellers, Owners, Providers, Students)
-- Using dummy hashes for demonstration
SET @pass = '$2y$10$YourHashHere'; 

INSERT IGNORE INTO USER (FullName, Email, Phone, PasswordHash, Role, CreatedAt) VALUES 
('Rahim Seller', 'rahim@test.com', '01710000001', @pass, 'Student', NOW()),
('Karim Owner', 'karim@test.com', '01710000002', @pass, 'Owner', NOW()),
('Ayesha Tutor', 'ayesha@test.com', '01710000003', @pass, 'ServiceProvider', NOW()),
('Clean Pro', 'clean@test.com', '01710000004', @pass, 'ServiceProvider', NOW()),
('Fast Movers', 'move@test.com', '01710000005', @pass, 'ServiceProvider', NOW()),
('Jamal Student', 'jamal@test.com', '01710000006', @pass, 'Student', NOW()),
('Owner Salma', 'salma@test.com', '01710000007', @pass, 'Owner', NOW()),
('Tech Guy', 'tech@test.com', '01710000008', @pass, 'Student', NOW()),
('Mess Manager', 'mess@test.com', '01710000009', @pass, 'ServiceProvider', NOW()),
('Book Worm', 'books@test.com', '01710000010', @pass, 'Student', NOW());

-- 2. INSERT MARKETPLACE ITEMS
INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'iPhone X Used', 'Black color, 64GB, 80% battery health.', 25000, 'Electronics', 'Used', 'Available', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM USER WHERE Email = 'rahim@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Study Table Wooden', 'Solid wood table, perfect condition.', 3000, 'Furniture', 'Like New', 'Available', DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM USER WHERE Email = 'rahim@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Physics H.C. Verma Vol 1', 'Essential for physics students.', 400, 'Books', 'Used', 'Available', DATE_SUB(NOW(), INTERVAL 5 HOUR)
FROM USER WHERE Email = 'books@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Gaming Mouse Logitech', 'G502 Hero, barely used.', 3500, 'Electronics', 'Like New', 'Available', DATE_SUB(NOW(), INTERVAL 3 DAY)
FROM USER WHERE Email = 'tech@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Plastic Chairs (Set of 2)', 'Red color, RFL plastic.', 1200, 'Furniture', 'Used', 'Available', DATE_SUB(NOW(), INTERVAL 10 DAY)
FROM USER WHERE Email = 'rahim@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Bicycle - Veloce', 'Needs some brake work but rides fine.', 8000, 'Sports', 'Used', 'Available', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM USER WHERE Email = 'jamal@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Monitor 24 inch HP', 'IPS Display, no dead pixels.', 10500, 'Electronics', 'Used', 'Available', DATE_SUB(NOW(), INTERVAL 4 DAY)
FROM USER WHERE Email = 'tech@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Introduction to Algorithms', 'CLRS 3rd Edition. Hardcover.', 1500, 'Books', 'Like New', 'Available', DATE_SUB(NOW(), INTERVAL 12 HOUR)
FROM USER WHERE Email = 'books@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Curtains (Blue)', 'Set of 4 window curtains.', 800, 'Furniture', 'New', 'Available', DATE_SUB(NOW(), INTERVAL 6 DAY)
FROM USER WHERE Email = 'jamal@test.com';

INSERT IGNORE INTO MARKETITEM (SellerID, Title, Description, Price, Category, `Condition`, Status, CreatedAt) 
SELECT UserID, 'Sony Headphones', 'Wireless noise cancelling.', 4500, 'Electronics', 'Used', 'Available', DATE_SUB(NOW(), INTERVAL 1 WEEK)
FROM USER WHERE Email = 'rahim@test.com';


-- 3. INSERT ROOM LISTINGS (ROOMLISTING)

INSERT IGNORE INTO ROOMLISTING (OwnerID, Title, Description, LocationArea, RentAmount, UtilitiesIncluded, GenderPreference, ListingType, CreatedAt, Status, IsVerified)
SELECT UserID, 'Single Room in Bashundhara', 'Attached bath, south facing, 5th floor. Lift available.', 'Bashundhara R/A', 8500, 0, 'Male', 'Room', DATE_SUB(NOW(), INTERVAL 2 DAY), 'Available', 1
FROM USER WHERE Email = 'karim@test.com';

INSERT IGNORE INTO ROOMLISTING (OwnerID, Title, Description, LocationArea, RentAmount, UtilitiesIncluded, GenderPreference, ListingType, CreatedAt, Status, IsVerified)
SELECT UserID, 'Shared Room for Female', 'Near NSU, quiet environment. 2 person room.', 'Bashundhara R/A', 4500, 1, 'Female', 'Room', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Available', 1
FROM USER WHERE Email = 'salma@test.com';

INSERT IGNORE INTO ROOMLISTING (OwnerID, Title, Description, LocationArea, RentAmount, UtilitiesIncluded, GenderPreference, ListingType, CreatedAt, Status, IsVerified)
SELECT UserID, 'Seat in Master Bedroom', 'Nice view, balcony attached. Meal system active.', 'Baridhara', 5000, 1, 'Male', 'HostelBed', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Available', 1
FROM USER WHERE Email = 'karim@test.com';

INSERT IGNORE INTO ROOMLISTING (OwnerID, Title, Description, LocationArea, RentAmount, UtilitiesIncluded, GenderPreference, ListingType, CreatedAt, Status, IsVerified)
SELECT UserID, 'Family Flat for Rent', '2 Bedrooms, 2 Bath. Gas line available.', 'Uttara Sector 7', 18000, 0, 'Any', 'Room', DATE_SUB(NOW(), INTERVAL 1 WEEK), 'Available', 1
FROM USER WHERE Email = 'salma@test.com';

INSERT IGNORE INTO ROOMLISTING (OwnerID, Title, Description, LocationArea, RentAmount, UtilitiesIncluded, GenderPreference, ListingType, CreatedAt, Status, IsVerified)
SELECT UserID, 'Looking for Roommate', 'I have a 3 room flat, need 1 person for small room.', 'Mirpur DOHS', 6000, 1, 'Male', 'RoommateWanted', DATE_SUB(NOW(), INTERVAL 3 HOUR), 'Available', 1
FROM USER WHERE Email = 'rahim@test.com';


-- 4. INSERT SERVICE PROVIDERS (SERVICEPROVIDER)

INSERT IGNORE INTO SERVICEPROVIDER (ProviderID, ServiceType, BusinessName, Area, AverageRating, TotalReviews, IsApproved)
SELECT UserID, 'Tuition', 'Math & Physics Care', 'Bashundhara R/A', 4.9, 25, 1
FROM USER WHERE Email = 'ayesha@test.com';

INSERT IGNORE INTO SERVICEPROVIDER (ProviderID, ServiceType, BusinessName, Area, AverageRating, TotalReviews, IsApproved)
SELECT UserID, 'Cleaner', 'Daily Home Clean', 'Dhanmondi', 4.2, 40, 1
FROM USER WHERE Email = 'clean@test.com';

INSERT IGNORE INTO SERVICEPROVIDER (ProviderID, ServiceType, BusinessName, Area, AverageRating, TotalReviews, IsApproved)
SELECT UserID, 'Van', 'Easy Shift Movers', 'All Dhaka', 4.7, 15, 1
FROM USER WHERE Email = 'move@test.com';

INSERT IGNORE INTO SERVICEPROVIDER (ProviderID, ServiceType, BusinessName, Area, AverageRating, TotalReviews, IsApproved)
SELECT UserID, 'Mess', 'Mayer Doa Meal System', 'Kuril', 3.8, 8, 1
FROM USER WHERE Email = 'mess@test.com';


-- 5. INSERT LOST & FOUND ITEMS (LOSTFOUND)

INSERT IGNORE INTO LOSTFOUND (ReporterID, ItemName, Description, Type, Status, ContactPhone, CreatedAt)
SELECT UserID, 'Black Wallet', 'Lost near Gate 1. Contains ID card.', 'Lost', 'Open', '01710000001', DATE_SUB(NOW(), INTERVAL 1 DAY)
FROM USER WHERE Email = 'rahim@test.com';

INSERT IGNORE INTO LOSTFOUND (ReporterID, ItemName, Description, Type, Status, ContactPhone, CreatedAt)
SELECT UserID, 'Blue Umbrella', 'Found in library cafeteria.', 'Found', 'Open', '01710000006', DATE_SUB(NOW(), INTERVAL 2 DAY)
FROM USER WHERE Email = 'jamal@test.com';

INSERT IGNORE INTO LOSTFOUND (ReporterID, ItemName, Description, Type, Status, ContactPhone, CreatedAt)
SELECT UserID, 'Casio Watch', 'Lost somewhere in Block C.', 'Lost', 'Open', '01710000008', DATE_SUB(NOW(), INTERVAL 6 HOUR)
FROM USER WHERE Email = 'tech@test.com';

-- 6. INSERT ROOM APPLICATIONS & BOOKINGS

INSERT IGNORE INTO ROOMAPPLICATION (RoomID, ApplicantID, Status, Message, AppliedAt)
SELECT r.RoomID, u.UserID, 'Pending', 'I am interested in this room.', NOW()
FROM ROOMLISTING r JOIN USER u
WHERE r.Title = 'Single Room in Bashundhara' AND u.Email = 'jamal@test.com';

INSERT IGNORE INTO SERVICEBOOKING (ProviderID, UserID, ServiceType, Date, TimeSlot, Address, Price, BookingStatus, ContactPhone)
SELECT sp.ProviderID, u.UserID, sp.ServiceType, DATE_ADD(NOW(), INTERVAL 2 DAY), '10:00 AM', 'House 5, Rd 2', 500, 'Pending', '01700000000'
FROM SERVICEPROVIDER sp JOIN USER u
WHERE sp.BusinessName = 'Daily Home Clean' AND u.Email = 'rahim@test.com';

-- =============================================
-- 7. DATA CLEANUP AND FIXES
-- =============================================

-- Fix any marketplace items with NULL or empty status
-- This ensures all items have a proper status value
UPDATE MARKETITEM 
SET Status = 'Pending' 
WHERE Status IS NULL OR Status = '';

-- Ensure MARKETITEM table has correct default for Status column
-- This prevents future items from having empty status
ALTER TABLE MARKETITEM 
MODIFY COLUMN Status ENUM('Available','Reserved','Sold','Inactive','Pending','Declined') 
NOT NULL DEFAULT 'Pending';

-- =============================================
-- END OF SCHEMA
-- =============================================
