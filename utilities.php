<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Recent grocery prices (last 10)
$stmt = $conn->prepare("SELECT ItemName, Unit, Price, MarketName, Date FROM GROCERYPRICE ORDER BY Date DESC LIMIT 10");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$groceries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Bus routes (all, limit 10)
$stmt = $conn->prepare("SELECT RouteName, StartPoint, EndPoint, Fare, FirstBusTime, LastBusTime FROM BUSROUTE LIMIT 10");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->execute();
$routes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Utilities - FindR</title>
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
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem; }
        .card {
            background: rgba(15,23,42,0.96);
            border: 1px solid #1f2937;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .card h3 { margin-bottom: 0.5rem; }
        table { width:100%; border-collapse: collapse; font-size:0.8rem; }
        th, td { padding:0.5rem; border:1px solid #1f2937; text-align:left; }
        th { background: #1f2937; }
        .empty { color:#9ca3af; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-line">
        <h2>Utilities & Info</h2>
        <a href="index.php">← Dashboard</a>
    </div>

    <div class="grid">
        <div class="card">
            <h3>Recent Grocery Prices</h3>
            <?php if (empty($groceries)): ?>
                <p class="empty">No data yet.</p>
            <?php else: ?>
                <table>
                    <tr><th>Item</th><th>Price (BDT)</th><th>Market</th><th>Date</th></tr>
                    <?php foreach ($groceries as $g): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($g['ItemName']); ?> (<?php echo $g['Unit']; ?>)</td>
                            <td><?php echo number_format($g['Price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($g['MarketName']); ?></td>
                            <td><?php echo $g['Date']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Bus Routes</h3>
            <?php if (empty($routes)): ?>
                <p class="empty">No data yet.</p>
            <?php else: ?>
                <table>
                    <tr><th>Route</th><th>From-To</th><th>Fare (BDT)</th><th>Times</th></tr>
                    <?php foreach ($routes as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['RouteName']); ?></td>
                            <td><?php echo htmlspecialchars($r['StartPoint']); ?> to <?php echo htmlspecialchars($r['EndPoint']); ?></td>
                            <td><?php echo number_format($r['Fare'], 2); ?></td>
                            <td><?php echo $r['FirstBusTime']; ?> - <?php echo $r['LastBusTime']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>