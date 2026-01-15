-- 1. DATABASE
CREATE DATABASE IF NOT EXISTS findr
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE findr;

-- 2. TABLE DEFINITIONS

-- 2.1 USER
CREATE TABLE IF NOT EXISTS USER (
  UserID INT AUTO_INCREMENT PRIMARY KEY,
  FullName     VARCHAR(100) NOT NULL,
  Email        VARCHAR(100) NOT NULL UNIQUE,
  Phone        VARCHAR(20)  NOT NULL,
  PasswordHash VARCHAR(255) NOT NULL,
  Role ENUM('Student','Owner','ServiceProvider','Admin') NOT NULL DEFAULT 'Student',
  AgeGroup ENUM('18-21','22-25','26-30','Over30') NOT NULL,
  LivingStatus ENUM('Alone','Roommates','Family','Hostel') NOT NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Status ENUM('Active','Blocked','Suspended') NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB;

-- 2.2 SERVICEPROVIDER
CREATE TABLE IF NOT EXISTS SERVICEPROVIDER (
  ProviderID INT PRIMARY KEY,
  ServiceType ENUM('Cleaner','Van','Mess','Tuition','Other') NOT NULL,
  BusinessName VARCHAR(100) NOT NULL,
  Area VARCHAR(100) NOT NULL,
  AverageRating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
  TotalReviews INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_sp_user FOREIGN KEY (ProviderID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 2.3 ROOMLISTING
CREATE TABLE IF NOT EXISTS ROOMLISTING (
  RoomID INT AUTO_INCREMENT PRIMARY KEY,
  OwnerID INT NOT NULL,
  ListingType ENUM('Room','HostelBed','RoommateWanted') NOT NULL,
  Title VARCHAR(150) NOT NULL,
  Description TEXT NOT NULL,
  LocationArea VARCHAR(100) NOT NULL,
  Latitude  DECIMAL(10,8) NULL,
  Longitude DECIMAL(11,8) NULL,
  RentAmount DECIMAL(10,2) NOT NULL,
  UtilitiesIncluded BOOLEAN NOT NULL DEFAULT FALSE,
  GenderPreference ENUM('Any','Male','Female') NOT NULL DEFAULT 'Any',
  IsVerified BOOLEAN NOT NULL DEFAULT FALSE,
  Status ENUM('Available','Booked','Closed') NOT NULL DEFAULT 'Available',
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_room_owner FOREIGN KEY (OwnerID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_room_owner (OwnerID),
  INDEX idx_room_area (LocationArea),
  INDEX idx_room_type_status (ListingType, Status)
) ENGINE=InnoDB;

-- 2.4 ROOMAPPLICATION
CREATE TABLE IF NOT EXISTS ROOMAPPLICATION (
  ApplicationID INT AUTO_INCREMENT PRIMARY KEY,
  RoomID INT NOT NULL,
  ApplicantID INT NOT NULL,
  Message TEXT NULL,
  Status ENUM('Pending','Accepted','Rejected','Withdrawn') NOT NULL DEFAULT 'Pending',
  AppliedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_room FOREIGN KEY (RoomID)
    REFERENCES ROOMLISTING(RoomID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_app_user FOREIGN KEY (ApplicantID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_app_room (RoomID),
  INDEX idx_app_applicant (ApplicantID),
  CONSTRAINT uq_room_applicant UNIQUE (RoomID, ApplicantID)
) ENGINE=InnoDB;

-- 2.5 SERVICEBOOKING
CREATE TABLE IF NOT EXISTS SERVICEBOOKING (
  BookingID INT AUTO_INCREMENT PRIMARY KEY,
  ProviderID INT NOT NULL,
  UserID INT NOT NULL,
  ServiceType ENUM('Cleaner','Van','Tuition','Meal','Other') NOT NULL,
  Date DATE NOT NULL,
  TimeSlot VARCHAR(50) NOT NULL,
  Address VARCHAR(255) NOT NULL,
  Price DECIMAL(10,2) NOT NULL,
  PaymentStatus ENUM('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  BookingStatus ENUM('Requested','Confirmed','InProgress','Completed','Cancelled') NOT NULL DEFAULT 'Requested',
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_provider FOREIGN KEY (ProviderID)
    REFERENCES SERVICEPROVIDER(ProviderID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_booking_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_booking_provider_date (ProviderID, Date),
  INDEX idx_booking_user_date (UserID, Date)
) ENGINE=InnoDB;

-- 2.6 MEALPLAN
CREATE TABLE IF NOT EXISTS MEALPLAN (
  MealPlanID INT AUTO_INCREMENT PRIMARY KEY,
  ProviderID INT NOT NULL,
  Name VARCHAR(100) NOT NULL,
  MonthlyPrice DECIMAL(10,2) NOT NULL,
  Details TEXT NULL,
  IsActive BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_mealplan_provider FOREIGN KEY (ProviderID)
    REFERENCES SERVICEPROVIDER(ProviderID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_mealplan_provider (ProviderID),
  INDEX idx_mealplan_active (IsActive)
) ENGINE=InnoDB;

-- 2.7 MARKETITEM
CREATE TABLE IF NOT EXISTS MARKETITEM (
  ItemID INT AUTO_INCREMENT PRIMARY KEY,
  SellerID INT NOT NULL,
  Title VARCHAR(150) NOT NULL,
  Description TEXT NOT NULL,
  Category ENUM('Electronics','Furniture','Books','Clothing','Sports','Others') NOT NULL,
  Price DECIMAL(10,2) NOT NULL,
  `Condition` ENUM('New','LikeNew','Used','VeryUsed') NOT NULL,
  Status ENUM('Available','Reserved','Sold','Inactive') NOT NULL DEFAULT 'Available',
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_market_seller FOREIGN KEY (SellerID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_market_seller (SellerID),
  INDEX idx_market_cat_status (Category, Status)
) ENGINE=InnoDB;

-- 2.8 GROCERYPRICE
CREATE TABLE IF NOT EXISTS GROCERYPRICE (
  GroceryID INT AUTO_INCREMENT PRIMARY KEY,
  ItemName VARCHAR(100) NOT NULL,
  Unit VARCHAR(20) NOT NULL,
  Price DECIMAL(8,2) NOT NULL,
  MarketName VARCHAR(100) NOT NULL,
  Date DATE NOT NULL,
  INDEX idx_grocery_item_date (ItemName, Date),
  INDEX idx_grocery_market_date (MarketName, Date)
) ENGINE=InnoDB;

-- 2.9 BUSROUTE
CREATE TABLE IF NOT EXISTS BUSROUTE (
  RouteID INT AUTO_INCREMENT PRIMARY KEY,
  RouteName VARCHAR(100) NOT NULL,
  StartPoint VARCHAR(100) NOT NULL,
  EndPoint VARCHAR(100) NOT NULL,
  Fare DECIMAL(6,2) NOT NULL,
  FirstBusTime TIME NOT NULL,
  LastBusTime TIME NOT NULL,
  INDEX idx_route_name (RouteName),
  INDEX idx_route_points (StartPoint, EndPoint)
) ENGINE=InnoDB;

-- 2.10 LOSTFOUND
CREATE TABLE IF NOT EXISTS LOSTFOUND (
  LFID INT AUTO_INCREMENT PRIMARY KEY,
  ReporterID INT NOT NULL,
  PostType ENUM('Lost','Found') NOT NULL,
  Title VARCHAR(150) NOT NULL,
  Description TEXT NOT NULL,
  Location VARCHAR(150) NOT NULL,
  ContactInfo VARCHAR(100) NOT NULL,
  Status ENUM('Open','Resolved','Expired') NOT NULL DEFAULT 'Open',
  Date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_lf_reporter FOREIGN KEY (ReporterID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_lf_reporter (ReporterID),
  INDEX idx_lf_status (Status)
) ENGINE=InnoDB;

-- 2.11 BUDGETCATEGORY
CREATE TABLE IF NOT EXISTS BUDGETCATEGORY (
  CategoryID INT AUTO_INCREMENT PRIMARY KEY,
  UserID INT NOT NULL,
  Name VARCHAR(100) NOT NULL,
  MonthlyLimit DECIMAL(10,2) NULL,
  CONSTRAINT fk_budgetcat_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_budgetcat_user (UserID),
  CONSTRAINT uq_budgetcat_user_name UNIQUE (UserID, Name)
) ENGINE=InnoDB;

-- 2.12 BUDGETTRANSACTION
CREATE TABLE IF NOT EXISTS BUDGETTRANSACTION (
  TransactionID INT AUTO_INCREMENT PRIMARY KEY,
  UserID INT NOT NULL,
  CategoryID INT NOT NULL,
  Amount DECIMAL(10,2) NOT NULL,
  Type ENUM('Expense','Income') NOT NULL,
  Note VARCHAR(255) NULL,
  Date DATE NOT NULL,
  CONSTRAINT fk_tx_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_tx_category FOREIGN KEY (CategoryID)
    REFERENCES BUDGETCATEGORY(CategoryID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX idx_tx_user_date (UserID, Date),
  INDEX idx_tx_category_date (CategoryID, Date)
) ENGINE=InnoDB;

-- 2.13 MOODENTRY
CREATE TABLE IF NOT EXISTS MOODENTRY (
  MoodID INT AUTO_INCREMENT PRIMARY KEY,
  UserID INT NOT NULL,
  MoodLevel TINYINT NOT NULL,
  Note TEXT NULL,
  CreatedAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mood_user FOREIGN KEY (UserID)
    REFERENCES USER(UserID)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_mood_level CHECK (MoodLevel BETWEEN 1 AND 5),
  INDEX idx_mood_user_date (UserID, CreatedAt)
) ENGINE=InnoDB;

-- 3. TRIGGERS

DELIMITER $$

-- 3.1 Ensure SERVICEPROVIDER row only for Role = 'ServiceProvider'
CREATE TRIGGER trg_sp_role_check
BEFORE INSERT ON SERVICEPROVIDER
FOR EACH ROW
BEGIN
  DECLARE r ENUM('Student','Owner','ServiceProvider','Admin');
  SELECT Role INTO r FROM USER WHERE UserID = NEW.ProviderID;
  IF r <> 'ServiceProvider' THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Provider must have Role = ServiceProvider';
  END IF;
END$$

-- 3.2 Prevent owner from applying to own room
CREATE TRIGGER trg_room_app_not_owner
BEFORE INSERT ON ROOMAPPLICATION
FOR EACH ROW
BEGIN
  DECLARE vOwner INT;
  SELECT OwnerID INTO vOwner FROM ROOMLISTING WHERE RoomID = NEW.RoomID;
  IF vOwner = NEW.ApplicantID THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Owner cannot apply to own room';
  END IF;
END$$

DELIMITER ;

-- 4. SAMPLE DATA

-- 4.1 Users
INSERT INTO USER (FullName, Email, Phone, PasswordHash, Role, AgeGroup, LivingStatus)
VALUES
  ('Rahim Uddin', 'rahim@example.com', '01711111111', 'hash1', 'Student', '18-21', 'Roommates'),
  ('Karim Hossain', 'karim.owner@example.com', '01722222222', 'hash2', 'Owner', '26-30', 'Family'),
  ('CleanPro Services', 'cleanpro@example.com', '01733333333', 'hash3', 'ServiceProvider', '22-25', 'Family'),
  ('Admin User', 'admin@example.com', '01744444444', 'hash4', 'Admin', 'Over30', 'Family');

-- 4.2 Service provider specialization
INSERT INTO SERVICEPROVIDER (ProviderID, ServiceType, BusinessName, Area)
VALUES (3, 'Cleaner', 'CleanPro Services', 'Dhanmondi');

-- 4.3 Room listing + application
INSERT INTO ROOMLISTING
  (OwnerID, ListingType, Title, Description, LocationArea, Latitude, Longitude, RentAmount, UtilitiesIncluded, GenderPreference, IsVerified)
VALUES
  (2, 'Room', '2BHK near Dhanmondi 27', 'Spacious room with balcony, close to bus stop.', 'Dhanmondi',
   23.746466, 90.376015, 15000.00, TRUE, 'Male', TRUE);

INSERT INTO ROOMAPPLICATION (RoomID, ApplicantID, Message)
VALUES (1, 1, 'I am a student at DU, looking for room from next month.');

-- 4.4 Service booking + mealplan
INSERT INTO SERVICEBOOKING
  (ProviderID, UserID, ServiceType, Date, TimeSlot, Address, Price)
VALUES
  (3, 1, 'Cleaner', '2026-01-15', '09:00-11:00', 'House 10, Road 5, Dhanmondi', 600.00);

INSERT INTO MEALPLAN (ProviderID, Name, MonthlyPrice, Details)
VALUES (3, '30-Day Full Board', 5500.00, 'Breakfast, lunch, dinner, 7 days a week.');

-- 4.5 Marketplace item
INSERT INTO MARKETITEM
  (SellerID, Title, Description, Category, Price, `Condition`)
VALUES
  (1, 'Used Study Table', 'Wooden table, good condition.', 'Furniture', 2500.00, 'Used');

-- 4.6 Budget
INSERT INTO BUDGETCATEGORY (UserID, Name, MonthlyLimit)
VALUES
  (1, 'Rent', 15000.00),
  (1, 'Food', 8000.00);

INSERT INTO BUDGETTRANSACTION (UserID, CategoryID, Amount, Type, Note, Date)
VALUES
  (1, 1, 15000.00, 'Expense', 'Monthly room rent', '2026-01-01'),
  (1, 2, 300.00, 'Expense', 'Lunch at canteen', '2026-01-02');

-- 4.7 Mood
INSERT INTO MOODENTRY (UserID, MoodLevel, Note)
VALUES (1, 4, 'Feeling positive about studies today.');

-- 5. COMMON SELECT QUERIES FOR YOUR SYSTEM (for reference)

-- 5.1 Authentication: find user by email
-- SELECT * FROM USER WHERE Email = 'rahim@example.com';

-- 5.8 Filter marketplace items by category and price range
-- SELECT mi.*, u.FullName AS SellerName, u.Phone AS SellerPhone
-- FROM MARKETITEM mi
-- JOIN USER u ON mi.SellerID = u.UserID
-- WHERE mi.Status = 'Available'
--   AND mi.Category = 'Furniture'
--   AND mi.Price BETWEEN 1000 AND 5000
-- ORDER BY mi.CreatedAt DESC;
