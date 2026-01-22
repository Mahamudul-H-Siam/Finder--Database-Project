<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$listingType = isset($_GET['type']) ? $_GET['type'] : 'All';
$gender = isset($_GET['gender']) ? $_GET['gender'] : 'All';
$minPrice = isset($_GET['min_price']) ? (float) $_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (float) $_GET['max_price'] : PHP_FLOAT_MAX;

// Basic filter conditions
$whereMy = "WHERE rl.OwnerID = ?";
$whereOther = "WHERE rl.OwnerID != ? AND rl.Status = 'Available' AND rl.IsVerified = 1";
$paramsMy = [$_SESSION['user_id']];
$typesMy = "i";
$paramsOther = [$_SESSION['user_id']];
$typesOther = "i";

// Combined filter logic
function applyFilters(&$where, &$types, &$params, $filters)
{
    if ($filters['listingType'] !== 'All' && $filters['listingType'] !== '') {
        $where .= " AND rl.ListingType = ?";
        $types .= "s";
        $params[] = $filters['listingType'];
    }
    if ($filters['gender'] !== 'All' && $filters['gender'] !== '') {
        $where .= " AND rl.GenderPreference = ?";
        $types .= "s";
        $params[] = $filters['gender'];
    }
    $where .= " AND rl.RentAmount BETWEEN ? AND ?";
    $types .= "dd";
    $params[] = $filters['minPrice'];
    $params[] = $filters['maxPrice'];

    if ($filters['search'] !== '') {
        $where .= " AND (rl.Title LIKE ? OR rl.Description LIKE ? OR rl.LocationArea LIKE ?)";
        $types .= "sss";
        $like = "%" . $filters['search'] . "%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
}

$filters = [
    'listingType' => $listingType,
    'gender' => $gender,
    'minPrice' => $minPrice,
    'maxPrice' => $maxPrice,
    'search' => $search
];

applyFilters($whereMy, $typesMy, $paramsMy, $filters);
applyFilters($whereOther, $typesOther, $paramsOther, $filters);

// Queries
$sqlMy = "SELECT rl.RoomID, rl.Title, rl.Description, rl.LocationArea, rl.RentAmount, 
               rl.UtilitiesIncluded, rl.GenderPreference, rl.ListingType, rl.CreatedAt, rl.Status, rl.IsVerified,
               u.FullName AS OwnerName
        FROM ROOMLISTING rl
        JOIN USER u ON rl.OwnerID = u.UserID
        $whereMy
        ORDER BY rl.CreatedAt DESC";

$stmtMy = $conn->prepare($sqlMy);
if ($typesMy) {
    $stmtMy->bind_param($typesMy, ...$paramsMy);
}
$stmtMy->execute();
$resultMy = $stmtMy->get_result();
$stmtMy->close();

$sqlOther = "SELECT rl.RoomID, rl.Title, rl.Description, rl.LocationArea, rl.RentAmount, 
               rl.UtilitiesIncluded, rl.GenderPreference, rl.ListingType, rl.CreatedAt,
               u.FullName AS OwnerName
        FROM ROOMLISTING rl
        JOIN USER u ON rl.OwnerID = u.UserID
        $whereOther
        ORDER BY rl.CreatedAt DESC";

$stmtOther = $conn->prepare($sqlOther);
if ($typesOther) {
    $stmtOther->bind_param($typesOther, ...$paramsOther);
}
$stmtOther->execute();
$resultOther = $stmtOther->get_result();
$stmtOther->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rooms - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --primary: #3b82f6;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Home Icon */
        .home-btn {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border);
            color: var(--text);
            text-decoration: none;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: transform 0.2s, background 0.2s;
            z-index: 10;
        }

        .home-btn:hover {
            transform: scale(1.05);
            background: #334155;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            margin-left: 4rem;
            /* Spacing for home btn on mobile */
        }

        h2 {
            margin: 0;
            font-size: 1.8rem;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .action-btn {
            background: #3b82f6;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 99px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .action-btn:hover {
            opacity: 0.9;
        }

        /* Filter Bar */
        .filter-bar {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            padding: 1rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 0.6rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: rgba(15, 23, 42, 0.8);
            color: var(--text);
            font-size: 0.9rem;
            flex: 1;
            min-width: 120px;
        }

        .filter-bar button {
            padding: 0.6rem 1.5rem;
            background: #334155;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .filter-bar button:hover {
            background: #475569;
        }

        /* Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-4px);
            border-color: #60a5fa;
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .card-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            line-height: 1.4;
        }

        .price {
            font-weight: 700;
            color: #4ade80;
            font-size: 1.1rem;
            whitespace: nowrap;
        }

        .tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .tag {
            font-size: 0.75rem;
            padding: 0.25rem 0.6rem;
            background: #334155;
            border-radius: 6px;
            color: #cbd5e1;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .btn-apply {
            display: block;
            text-align: center;
            margin-top: auto;
            background: rgba(59, 130, 246, 0.1);
            color: #60a5fa;
            padding: 0.8rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-apply:hover {
            background: rgba(59, 130, 246, 0.2);
        }

        .section-title {
            font-size: 1.2rem;
            margin: 2rem 0 1rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.5rem;
            color: #e2e8f0;
        }
    </style>
</head>

<body>

    <a href="index.php" class="home-btn" title="Home">🏠</a>

    <div class="container">
        <div class="header">
            <div>
                <h2>Rooms & Housing</h2>
                <div class="subtitle">Find your next home or roommate.</div>
            </div>
            <?php if ($_SESSION['role'] === 'Owner'): ?>
                <a href="room_new.php" class="action-btn">+ Post Ad</a>
            <?php endif; ?>
        </div>

        <form method="get" class="filter-bar">
            <input type="text" name="q" placeholder="Search location..."
                value="<?php echo htmlspecialchars($search); ?>">
            <select name="type">
                <option value="All">All Types</option>
                <option value="Room" <?php if ($listingType == 'Room')
                    echo 'selected'; ?>>Room</option>
                <option value="HostelBed" <?php if ($listingType == 'HostelBed')
                    echo 'selected'; ?>>Hostel Bed</option>
                <option value="RoommateWanted" <?php if ($listingType == 'RoommateWanted')
                    echo 'selected'; ?>>Roommate
                    Wanted</option>
            </select>
            <select name="gender">
                <option value="All">All Genders</option>
                <option value="Any" <?php if ($gender == 'Any')
                    echo 'selected'; ?>>Any</option>
                <option value="Male" <?php if ($gender == 'Male')
                    echo 'selected'; ?>>Male</option>
                <option value="Female" <?php if ($gender == 'Female')
                    echo 'selected'; ?>>Female</option>
            </select>
            <button type="submit">Filter</button>
        </form>

        <?php if ($resultMy->num_rows > 0): ?>
            <div class="section-title">My Listings</div>
            <div class="grid">
                <?php while ($row = $resultMy->fetch_assoc()): ?>
                    <div class="card" style="border-color: #3b82f6;">
                        <div class="card-top">
                            <div class="card-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                            <div class="price">৳<?php echo number_format($row['RentAmount']); ?></div>
                        </div>
                        <div class="tags">
                            <div class="tag"><?php echo $row['ListingType']; ?></div>
                            <div class="tag"
                                style="background:<?php echo $row['IsVerified'] ? 'rgba(34,197,94,0.2)' : 'rgba(234,179,8,0.2)'; ?>;color:<?php echo $row['IsVerified'] ? '#4ade80' : '#facc15'; ?>">
                                <?php echo $row['IsVerified'] ? 'Verified' : 'Pending'; ?>
                            </div>
                            <div class="tag"><?php echo $row['Status']; ?></div>
                        </div>
                        <div class="meta-row">
                            <span><?php echo htmlspecialchars($row['LocationArea']); ?></span>
                            <span><?php echo date('M d', strtotime($row['CreatedAt'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <div class="section-title">Available Listings</div>
        <?php if ($resultOther->num_rows === 0): ?>
            <p style="color:#94a3b8; text-align:center;">No listings match your search.</p>
        <?php else: ?>
            <div class="grid">
                <?php while ($row = $resultOther->fetch_assoc()): ?>
                    <div class="card">
                        <div class="card-top">
                            <div class="card-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                            <div class="price">৳<?php echo number_format($row['RentAmount']); ?></div>
                        </div>
                        <div class="tags">
                            <div class="tag"><?php echo $row['ListingType']; ?></div>
                            <div class="tag"><?php echo $row['GenderPreference']; ?></div>
                            <?php if ($row['UtilitiesIncluded']): ?>
                                <div class="tag">Utils Inc.</div><?php endif; ?>
                        </div>
                        <div class="meta-row">
                            <span>📍 <?php echo htmlspecialchars($row['LocationArea']); ?></span>
                            <span>👤 <?php echo htmlspecialchars($row['OwnerName']); ?></span>
                        </div>
                        <a href="room_apply.php?room_id=<?php echo $row['RoomID']; ?>" class="btn-apply">View & Apply</a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

    </div>

</body>

</html>