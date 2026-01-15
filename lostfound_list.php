<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$postType = isset($_GET['type']) ? $_GET['type'] : 'All';

$sql = "
    SELECT lf.LFID, lf.PostType, lf.Title, lf.Description, lf.Location, lf.ContactInfo, lf.Date, u.FullName AS ReporterName
    FROM LOSTFOUND lf
    JOIN USER u ON lf.ReporterID = u.UserID
    WHERE lf.Status = 'Open'
";

$params = [];
$types = "";

if ($postType !== 'All') {
    $sql .= " AND lf.PostType = ?";
    $types .= "s";
    $params[] = $postType;
}

if ($search !== '') {
    $sql .= " AND (lf.Title LIKE ? OR lf.Description LIKE ? OR lf.Location LIKE ?)";
    $types .= "sss";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY lf.Date DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lost & Found - FindR</title>
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
    </style>
</head>
<body>
<div class="container">
    <div class="header-line">
        <div>
            <h2>Lost & Found</h2>
            <div class="subtitle">Report or find lost items.</div>
        </div>
        <div class="top-links">
            <a href="index.php">← Dashboard</a>
            <a href="lostfound_add.php">+ Post</a>
        </div>
    </div>

    <div class="filter-card">
        <form method="get" class="filter">
            <input type="text" name="q" placeholder="Search title, desc, location..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="type">
                <option value="All" <?php if ($postType==='All') echo 'selected'; ?>>All</option>
                <option value="Lost" <?php if ($postType==='Lost') echo 'selected'; ?>>Lost</option>
                <option value="Found" <?php if ($postType==='Found') echo 'selected'; ?>>Found</option>
            </select>
            <button type="submit">Apply</button>
        </form>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="empty">No posts found.</div>
    <?php else: ?>
        <div class="grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="item-card">
                    <div class="item-top-line">
                        <div class="item-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                    </div>
                    <div class="meta">
                        <span class="pill"><?php echo htmlspecialchars($row['PostType']); ?></span>
                        <span class="pill"><?php echo htmlspecialchars($row['Location']); ?></span>
                    </div>
                    <div class="desc"><?php echo nl2br(htmlspecialchars($row['Description'])); ?></div>
                    <div class="meta">Reporter: <?php echo htmlspecialchars($row['ReporterName']); ?> | Contact: <?php echo htmlspecialchars($row['ContactInfo']); ?> | Date: <?php echo $row['Date']; ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>