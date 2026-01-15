<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$listingType = isset($_GET['type']) ? $_GET['type'] : 'All';
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'All';
$minPrice = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : PHP_FLOAT_MAX;

$sql = "SELECT rl.RoomID, rl.Title, rl.Description, rl.LocationArea, rl.RentAmount, 
               rl.UtilitiesIncluded, rl.GenderPreference, rl.ListingType, rl.CreatedAt,
               u.FullName AS OwnerName
        FROM ROOMLISTING rl
        JOIN USER u ON rl.OwnerID = u.UserID
        WHERE rl.Status = 'Available' AND rl.IsVerified = 1";  // Only show verified listings

$params = [];
$types = "";

if ($listingType !== 'All' && $listingType !== '') {
    $sql .= " AND rl.ListingType = ?";
    $types .= "s";
    $params[] = $listingType;
}

if ($gender !== 'All' && $gender !== '') {
    $sql .= " AND rl.GenderPreference = ?";
    $types .= "s";
    $params[] = $gender;
}

$sql .= " AND rl.RentAmount BETWEEN ? AND ?";
$types .= "dd";
$params[] = $minPrice;
$params[] = $maxPrice;

if ($search !== '') {
    $sql .= " AND (rl.Title LIKE ? OR rl.Description LIKE ? OR rl.LocationArea LIKE ?)";
    $types .= "sss";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY rl.CreatedAt DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rooms - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin:0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color:#e5e7eb;
            min-height:100vh;
            display:flex;
            align-items:flex-start;
            justify-content:center;
            padding:1.5rem 0;
        }
        .container {
            width:100%;
            max-width:900px;
            padding:0 1.5rem;
        }
        .header-line {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:1rem;
        }
        h2 { margin:0; }
        .subtitle {
            font-size:0.8rem;
            color:#9ca3af;
            margin-bottom:1rem;
        }
        .top-links {
            font-size:0.8rem;
            color:#9ca3af;
        }
        .top-links a { color:#38bdf8; margin-left:0.6rem; }
        .filter-card, .item-card {
            background: rgba(15,23,42,0.96);
            border-radius: 12px;
            border:1px solid #1f2937;
            box-shadow:0 18px 40px rgba(15,23,42,0.85);
            padding:0.9rem 1rem;
        }
        .filter-card {
            margin-bottom:1rem;
        }
        form.filter {
            display:flex;
            flex-wrap:wrap;
            gap:0.6rem;
            align-items:center;
        }
        .filter input, .filter select {
            padding:0.35rem 0.55rem;
            border-radius:999px;
            border:1px solid #374151;
            background:#020617;
            color:#e5e7eb;
            font-size:0.8rem;
        }
        .filter button {
            padding:0.35rem 0.8rem;
            border-radius:999px;
            border:none;
            cursor:pointer;
            font-size:0.8rem;
            font-weight:600;
            background:linear-gradient(to right,#22c55e,#16a34a);
            color:#022c22;
        }
        .grid {
            display:grid;
            grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
            gap:0.75rem;
            margin-top:0.75rem;
        }
        .item-card {
            display:flex;
            flex-direction:column;
            gap:0.4rem;
            font-size:0.85rem;
        }
        .item-title {
            font-size:0.95rem;
            font-weight:600;
        }
        .item-top-line {
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:0.5rem;
        }
        .pill {
            padding:0.2rem 0.55rem;
            border-radius:999px;
            border:1px solid #374151;
            font-size:0.7rem;
            color:#9ca3af;
        }
        .price {
            font-weight:600;
            color:#bbf7d0;
        }
        .meta {
            font-size:0.75rem;
            color:#9ca3af;
        }
        .desc {
            font-size:0.8rem;
            color:#d1d5db;
        }
        .empty {
            font-size:0.85rem;
            color:#9ca3af;
            margin-top:0.6rem;
        }
        .btn {
            display:inline-block;
            padding:0.3rem 0.6rem;
            background:#38bdf8;
            color:#022c22;
            border-radius:999px;
            text-decoration:none;
            font-weight:600;
            text-align:center;
            margin-top:0.5rem;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-line">
        <div>
            <h2>Rooms & Hostels</h2>
            <div class="subtitle">Browse available rooms, hostel beds, or roommate ads.</div>
        </div>
        <div class="top-links">
            <a href="index.php">← Back to dashboard</a>
            <?php if ($_SESSION['role'] === 'Owner'): ?>
                <a href="room_new.php">+ Post listing</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="filter-card">
        <form method="get" class="filter">
            <input type="text" name="q" placeholder="Search title, desc, area..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="type">
                <option value="All" <?php if ($listingType==='All') echo 'selected'; ?>>All types</option>
                <option value="Room" <?php if ($listingType==='Room') echo 'selected'; ?>>Room</option>
                <option value="HostelBed" <?php if ($listingType==='HostelBed') echo 'selected'; ?>>Hostel Bed</option>
                <option value="RoommateWanted" <?php if ($listingType==='RoommateWanted') echo 'selected'; ?>>Roommate Wanted</option>
            </select>
            <select name="gender">
                <option value="All" <?php if ($gender==='All') echo 'selected'; ?>>All genders</option>
                <option value="Any" <?php if ($gender==='Any') echo 'selected'; ?>>Any</option>
                <option value="Male" <?php if ($gender==='Male') echo 'selected'; ?>>Male</option>
                <option value="Female" <?php if ($gender==='Female') echo 'selected'; ?>>Female</option>
            </select>
            <input type="number" name="min_price" placeholder="Min rent" value="<?php echo $minPrice > 0 ? $minPrice : ''; ?>">
            <input type="number" name="max_price" placeholder="Max rent" value="<?php echo $maxPrice < PHP_FLOAT_MAX ? $maxPrice : ''; ?>">
            <button type="submit">Apply</button>
        </form>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="empty">No listings found. Try changing filters or <?php if ($_SESSION['role'] === 'Owner'): ?><a href="room_new.php" style="color:#38bdf8;">post a new listing</a><?php endif; ?>.</div>
    <?php else: ?>
        <div class="grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="item-card">
                    <div class="item-top-line">
                        <div class="item-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                        <div class="price">BDT <?php echo number_format($row['RentAmount'], 2); ?></div>
                    </div>
                    <div class="meta">
                        <span class="pill"><?php echo htmlspecialchars($row['ListingType']); ?></span>
                        <span class="pill"><?php echo htmlspecialchars($row['GenderPreference']); ?></span>
                        <?php if ($row['UtilitiesIncluded']): ?><span class="pill">Utilities Inc.</span><?php endif; ?>
                    </div>
                    <div class="meta">
                        Area: <?php echo htmlspecialchars($row['LocationArea']); ?> •
                        Owner: <?php echo htmlspecialchars($row['OwnerName']); ?> •
                        Posted: <?php echo htmlspecialchars($row['CreatedAt']); ?>
                    </div>
                    <div class="desc">
                        <?php echo nl2br(htmlspecialchars($row['Description'])); ?>
                    </div>
                    <a href="room_apply.php?room_id=<?php echo $row['RoomID']; ?>" class="btn">Apply</a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>