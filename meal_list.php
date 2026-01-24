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
        :root {
            --primary: #22c55e;
            --primary-hover: #16a34a;
            --bg-dark: #020617;
            --card-bg: #0f172a;
            --text-main: #f8fafc;
            --text-secondary: #94a3b8;
            --border-color: #1e293b;
        }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .container {
            width: 100%;
            max-width: 1200px;
            padding: 2rem;
            box-sizing: border-box;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3rem;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 1.5rem;
        }
        .header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(to right, #4ade80, #22c55e);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .header .subtitle {
            margin-top: 0.5rem;
            color: var(--text-secondary);
            font-size: 1rem;
        }
        .back-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .back-link:hover {
            color: var(--primary);
        }

        /* Layout */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }
        
        /* Meal Plans Grid */
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .plan-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            border-color: var(--primary);
        }
        .plan-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #fff;
        }
        .plan-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        .plan-price span {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 400;
        }
        .plan-details {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }
        .provider-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        .provider-icon {
            width: 24px;
            height: 24px;
            background: rgba(34, 197, 94, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }
        .btn-subscribe {
            display: block;
            width: 100%;
            text-align: center;
            padding: 0.75rem;
            background: var(--primary);
            color: #000;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .btn-subscribe:hover {
            background: var(--primary-hover);
        }

        /* Calculator Sidebar */
        .calculator-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        .calculator-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            background: var(--bg-dark);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: #fff;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            border-color: var(--primary);
        }
        .calc-result {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(34, 197, 94, 0.1);
            border-radius: 8px;
            color: var(--primary);
            text-align: center;
            font-weight: 600;
        }

        @media (max-width: 900px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            .calculator-card {
                position: static;
                margin-top: 2rem;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2>Meal Plans</h2>
            <div class="subtitle">Delicious meals delivered to your doorstep</div>
        </div>
        <a href="index.php" class="back-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Dashboard
        </a>
    </div>

    <div class="main-content">
        <!-- Meal Plans Section -->
        <div class="plans-section">
            <?php if ($result->num_rows === 0): ?>
                <div style="text-align:center; padding: 4rem; color: var(--text-secondary);">
                    <h3>No meal plans available at the moment.</h3>
                    <p>Please check back later.</p>
                </div>
            <?php else: ?>
                <div class="plans-grid">
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <div class="plan-card">
                            <div class="plan-name"><?php echo htmlspecialchars($row['Name']); ?></div>
                            <div class="plan-price">
                                ৳<?php echo number_format($row['MonthlyPrice']); ?>
                                <span>/month</span>
                            </div>
                            <div class="plan-details">
                                <?php echo htmlspecialchars($row['Details']); ?>
                            </div>
                            <div class="provider-info">
                                <div class="provider-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 21h18M5 21V7l8-4 8 4v14M8 21v-8a2 2 0 012-2h4a2 2 0 012 2v8"/>
                                    </svg>
                                </div>
                                <?php echo htmlspecialchars($row['BusinessName']); ?> • <?php echo htmlspecialchars($row['Area']); ?>
                            </div>
                            <a href="meal_subscribe.php?plan_id=<?php echo $row['MealPlanID']; ?>" class="btn-subscribe">
                                Subscribe Now
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Calculator Section -->
        <div class="side-panel">
            <div class="calculator-card">
                <div class="calculator-title">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 3h16a2 2 0 012 2v14a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2z"/>
                        <rect x="6" y="7" width="12" height="2"/>
                        <path d="M6 13h4M14 13h4M6 17h4M14 17h4"/>
                    </svg>
                    Cost Calculator
                </div>
                <form method="post">
                    <input type="hidden" name="calculate_meal" value="1">
                    <div class="form-group">
                        <label>Monthly Price (BDT)</label>
                        <input type="number" step="0.01" name="monthly_price" placeholder="e.g. 5000" required>
                    </div>
                    <div class="form-group">
                        <label>Duration (Months)</label>
                        <input type="number" name="months" required min="1" value="1">
                    </div>
                    <button type="submit" class="btn-subscribe">Calculate Total</button>
                </form>

                <?php if ($calcResult !== null): ?>
                    <div class="calc-result">
                        Total: BDT <?php echo number_format($calcResult, 2); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php $stmt->close(); $conn->close(); ?>