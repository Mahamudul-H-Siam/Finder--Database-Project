<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$serviceType = isset($_GET['type']) ? $_GET['type'] : 'All';

// Basic query parts - Updated to work with PROVIDER_SERVICES
$whereMy = "WHERE sp.ProviderID = ?";
$whereOther = "WHERE sp.ProviderID != ? AND u.Status = 'Active' AND sp.IsApproved = 1 AND ps.IsApproved = 1 AND ps.IsActive = 1";
$paramsMy = [$_SESSION['user_id']];
$typesMy = "i";
$paramsOther = [$_SESSION['user_id']];
$typesOther = "i";

function applyServiceFilters(&$where, &$types, &$params, $filters)
{
    if ($filters['serviceType'] !== 'All') {
        $where .= " AND ps.ServiceType = ?";
        $types .= "s";
        $params[] = $filters['serviceType'];
    }
    if ($filters['search'] !== '') {
        $where .= " AND (sp.BusinessName LIKE ? OR sp.Area LIKE ? OR ps.Description LIKE ?)";
        $types .= "sss";
        $like = "%" . $filters['search'] . "%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }
}

$filters = ['serviceType' => $serviceType, 'search' => $search];
applyServiceFilters($whereMy, $typesMy, $paramsMy, $filters);
applyServiceFilters($whereOther, $typesOther, $paramsOther, $filters);

// Query My Services - Updated to work with PROVIDER_SERVICES table
$sqlMy = "
    SELECT sp.ProviderID, ps.ServiceType, sp.BusinessName, sp.Area, sp.AverageRating, sp.TotalReviews, sp.IsApproved, u.FullName
    FROM SERVICEPROVIDER sp
    JOIN USER u ON sp.ProviderID = u.UserID
    LEFT JOIN PROVIDER_SERVICES ps ON sp.ProviderID = ps.ProviderID AND ps.IsActive = 1
    $whereMy
    ORDER BY sp.AverageRating DESC
";
$stmtMy = $conn->prepare($sqlMy);
if (!$stmtMy) {
    die("Prepare failed (My Services): " . $conn->error . "<br>Query: " . $sqlMy);
}
if ($typesMy) {
    $stmtMy->bind_param($typesMy, ...$paramsMy);
}
$stmtMy->execute();
$resultMy = $stmtMy->get_result();
$stmtMy->close();

// Query Other Services
$sqlOther = "
    SELECT ps.ServiceID, ps.ProviderID, ps.ServiceType, ps.Description, ps.Price, 
           sp.BusinessName, sp.Area, sp.AverageRating, sp.TotalReviews, u.FullName
    FROM PROVIDER_SERVICES ps
    JOIN SERVICEPROVIDER sp ON ps.ProviderID = sp.ProviderID
    JOIN USER u ON sp.ProviderID = u.UserID
    $whereOther
    ORDER BY sp.AverageRating DESC
";
$stmtOther = $conn->prepare($sqlOther);
if ($typesOther) {
    if (!$stmtOther) {
        die("Prepare failed: " . $conn->error);
    }
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
    <title>Services - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --primary: #22c55e;
            /* Green for services */
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
        }

        h2 {
            margin: 0;
            font-size: 1.8rem;
            background: linear-gradient(90deg, #22c55e, #86efac);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .action-btn {
            background: var(--primary);
            color: #022c22;
            padding: 0.6rem 1.2rem;
            border-radius: 99px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .action-btn:hover {
            opacity: 0.9;
        }

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
            border-color: var(--primary);
        }

        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .business-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
            line-height: 1.4;
        }

        .rating {
            font-weight: 700;
            color: #fbbf24;
            font-size: 1rem;
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

        .btn-book {
            display: block;
            text-align: center;
            margin-top: auto;
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            padding: 0.8rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-book:hover {
            background: rgba(34, 197, 94, 0.2);
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
                <h2>Local Services</h2>
                <div class="subtitle">Book trusted cleaners, tutors, and more.</div>
            </div>
            <?php if ($_SESSION['role'] === 'ServiceProvider'): ?>
                <a href="service_add.php" class="action-btn">Manage Profile</a>
            <?php endif; ?>
        </div>

        <form method="get" class="filter-bar">
            <input type="text" name="q" placeholder="Search business or area..."
                value="<?php echo htmlspecialchars($search); ?>">
            <select name="type">
                <option value="All">All Services</option>
                <option value="Cleaner" <?php if ($serviceType == 'Cleaner')
                    echo 'selected'; ?>>Cleaner</option>
                <option value="Van" <?php if ($serviceType == 'Van')
                    echo 'selected'; ?>>Van</option>
                <option value="Tuition" <?php if ($serviceType == 'Tuition')
                    echo 'selected'; ?>>Tuition</option>
                <option value="Mess" <?php if ($serviceType == 'Mess')
                    echo 'selected'; ?>>Mess System</option>
            </select>
            <button type="submit">Filter</button>
        </form>

        <?php if ($resultMy->num_rows > 0): ?>
            <div class="section-title">My Service Profile</div>
            <div class="grid">
                <?php while ($row = $resultMy->fetch_assoc()): ?>
                    <div class="card" style="border-color: #22c55e;">
                        <div class="card-top">
                            <div class="business-name"><?php echo htmlspecialchars($row['BusinessName']); ?></div>
                            <div class="rating">★ <?php echo $row['AverageRating']; ?></div>
                        </div>
                        <div class="tags">
                            <div class="tag"><?php echo $row['ServiceType']; ?></div>
                            <div class="tag"
                                style="background:<?php echo $row['IsApproved'] ? 'rgba(34,197,94,0.2)' : 'rgba(234,179,8,0.2)'; ?>;color:<?php echo $row['IsApproved'] ? '#4ade80' : '#facc15'; ?>">
                                <?php echo $row['IsApproved'] ? 'Verified' : 'Pending'; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

        <div class="section-title">Available Providers</div>
        <?php if ($resultOther->num_rows === 0): ?>
            <p style="color:#94a3b8; text-align:center;">No services found.</p>
        <?php else: ?>
            <div class="grid">
                <?php while ($row = $resultOther->fetch_assoc()): ?>
                    <div class="card">
                        <div class="card-top">
                            <div class="business-name"><?php echo htmlspecialchars($row['BusinessName']); ?></div>
                            <div class="rating">★ <?php echo $row['AverageRating']; ?> <span
                                    style="color:#94a3b8;font-size:0.8rem;font-weight:400">/5</span></div>
                        </div>
                        <div class="tags">
                            <div class="tag"><?php echo $row['ServiceType']; ?></div>
                            <div class="tag">📍 <?php echo $row['Area']; ?></div>
                        </div>
                        <div class="meta-row">
                            <span>👤 <?php echo htmlspecialchars($row['FullName']); ?></span>
                            <span><?php echo $row['TotalReviews']; ?> reviews</span>
                        </div>



                        <div style="display:flex; gap:0.5rem; margin-top:auto;">
                            <a href="chat.php?user_id=<?php echo $row['ProviderID']; ?>" class="btn-book"
                                style="flex:1; background:#334155; color:white;">Message</a>
                            <a href="service_book.php?service_id=<?php echo $row['ServiceID']; ?>" class="btn-book"
                                style="flex:1;">Book Now</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>