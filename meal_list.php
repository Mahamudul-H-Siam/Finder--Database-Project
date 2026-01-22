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

// Meal calculator
$calcResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_meal'])) {
    $monthlyPrice = (float)$_POST['monthly_price'];
    $months = (int)$_POST['months'];
    $calcResult = $monthlyPrice * $months;
}
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
            gap:1rem;
        }
        .card {
            background: rgba(15,23,42,0.96);
            border-radius: 12px;
            border:1px solid #1f2937;
            padding:1rem;
        }
        .btn {
            padding:0.5rem 1rem;
            background:linear-gradient(to right,#22c55e,#16a34a);
            color:#022c22;
            border-radius:999px;
            text-decoration:none;
        }
        .form-group { margin-bottom:0.75rem; }
        .form-group input { width:100%; padding:0.4rem; border:1px solid #374151; background:#020617; color:#e5e7eb; border-radius:0.5rem; }
        .result { color:#bbf7d0; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-line">
        <div>
            <h2>Meal Plans</h2>
            <div class="subtitle">Subscribe to monthly meals and calculate costs.</div>
        </div>
        <div class="top-links">
            <a href="index.php">← Dashboard</a>
        </div>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Available Plans</h3>
            <?php if ($result->num_rows === 0): ?>
                <p>No plans available.</p>
            <?php else: ?>
                <ul>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($row['Name']); ?></strong> - BDT <?php echo number_format($row['MonthlyPrice'], 2); ?>/month<br>
                            Details: <?php echo htmlspecialchars($row['Details']); ?><br>
                            Provider: <?php echo htmlspecialchars($row['BusinessName']); ?> (<?php echo htmlspecialchars($row['Area']); ?>)<br>
                            <a href="meal_subscribe.php?plan_id=<?php echo $row['MealPlanID']; ?>" class="btn">Subscribe</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Meal Cost Calculator</h3>
            <form method="post">
                <input type="hidden" name="calculate_meal" value="1">
                <div class="form-group">
                    <label>Monthly Price (BDT)</label>
                    <input type="number" step="0.01" name="monthly_price" required>
                </div>
                <div class="form-group">
                    <label>Number of Months</label>
                    <input type="number" name="months" required min="1">
                </div>
                <button type="submit" class="btn">Calculate</button>
            </form>
            <?php if ($calcResult !== null): ?>
                <div class="result">Total Cost: BDT <?php echo number_format($calcResult, 2); ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>