<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Owner') {
    header("Location: index.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $area = trim($_POST['area']);
    $rent = (float) $_POST['rent'];
    $gender = $_POST['gender_pref']; // Any, Male, Female
    $utilInc = isset($_POST['utilities']) ? 1 : 0;
    $listingType = $_POST['listing_type']; // Room, HostelBed, RoommateWanted
    $ownerId = $_SESSION['user_id'];

    if ($title === '' || $desc === '' || $area === '' || $rent <= 0) {
        $errors[] = "Please fill all fields correctly.";
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "INSERT INTO ROOMLISTING
             (OwnerID, ListingType, Title, Description, LocationArea, RentAmount,
              UtilitiesIncluded, GenderPreference, IsVerified)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param(
            "issssdis",
            $ownerId,
            $listingType,
            $title,
            $desc,
            $area,
            $rent,
            $utilInc,
            $gender
        );
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Error saving room listing.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Post new room - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color: #e5e7eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: rgba(15, 23, 42, 0.96);
            border-radius: 12px;
            border: 1px solid #1f2937;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.85);
            padding: 1.5rem 1.75rem;
            width: 100%;
            max-width: 520px;
        }

        h2 {
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .form-row {
            display: flex;
            gap: 0.75rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-group {
            margin-bottom: 0.75rem;
            font-size: 0.8rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            color: #9ca3af;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.4rem 0.55rem;
            border-radius: 0.6rem;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            font-size: 0.85rem;
            outline: none;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-helper {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.15rem;
        }

        .checkbox-line {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.8rem;
            color: #9ca3af;
            margin-bottom: 0.75rem;
        }

        .btn {
            width: 100%;
            margin-top: 0.4rem;
            padding: 0.5rem 0.7rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            background: linear-gradient(to right, #22c55e, #16a34a);
            color: #022c22;
            box-shadow: 0 10px 24px rgba(22, 163, 74, 0.9);
        }

        .error {
            color: #fecaca;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .success {
            color: #bbf7d0;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .top-link {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-bottom: 0.5rem;
        }

        .top-link a {
            color: #38bdf8;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="top-link">
            ← <a href="index.php">Back to dashboard</a>
        </div>
        <h2>Post new room</h2>
        <div class="subtitle">Create a listing so students can find your room or hostel bed.</div>

        <?php if ($success): ?>
            <div class="success">Listing created successfully. You can see it in the Rooms section.</div>
        <?php endif; ?>

        <?php foreach ($errors as $e): ?>
            <div class="error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="form-group">
                <label>Listing type</label>
                <select name="listing_type" required>
                    <option value="Room">Room</option>
                    <option value="HostelBed">Hostel bed</option>
                    <option value="RoommateWanted">Roommate wanted</option>
                </select>
            </div>

            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" placeholder="e.g., 2BHK near Dhanmondi 27" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Short details about the room, facilities, nearby places"
                    required></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Area</label>
                    <input type="text" name="area" placeholder="e.g., Dhanmondi" required>
                </div>
                <div class="form-group">
                    <label>Rent (BDT)</label>
                    <input type="number" step="0.01" name="rent" placeholder="e.g., 6500" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Gender preference</label>
                    <select name="gender_pref" required>
                        <option value="Any">Any</option>
                        <option value="Male">Male only</option>
                        <option value="Female">Female only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="checkbox-line">
                        <input type="checkbox" name="utilities" id="utilities">
                        <label for="utilities">Utilities included</label>
                    </div>
                </div>
            </div>

            <div class="form-helper">
                Latitude/longitude and verification can be added later from admin side.
            </div>

            <button type="submit" class="btn">Save listing</button>
        </form>
    </div>
</body>

</html>