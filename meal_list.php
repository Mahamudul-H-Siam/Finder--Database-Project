<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$sql = "
    SELECT mp.MealPlanID, mp.Name, mp.MonthlyPrice, mp.Details, sp.BusinessName, sp.Area
    FROM MEALPLAN mp
    JOIN SERVICEPROVIDER sp ON mp.ProviderID = sp.ProviderID
    WHERE mp.IsActive = 1
    ORDER BY mp.MonthlyPrice
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Meal Plans - FindR</title>
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
            <h2>Meal Plans</h2>
            <div class="subtitle">Subscribe to monthly meals.</div>
        </div>
        <div class="top-links">
            <a href="index.php">← Dashboard</a>
        </div>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="empty">No plans available.</div>
    <?php else: ?>
        <div class="grid">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="item-card">
                    <div class="item-top-line">
                        <div class="item-title"><?php echo htmlspecialchars($row['Name']); ?></div>
                        <div class="price">BDT <?php echo number_format($row['MonthlyPrice'], 2); ?>/month</div>
                    </div>
                    <div class="meta">
                        <span class="pill"><?php echo htmlspecialchars($row['BusinessName']); ?></span>
                        <span class="pill"><?php echo htmlspecialchars($row['Area']); ?></span>
                    </div>
                    <div class="desc"><?php echo nl2br(htmlspecialchars($row['Details'])); ?></div>
                    <a href="meal_subscribe.php?plan_id=<?php echo $row['MealPlanID']; ?>" class="btn">Subscribe</a>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>