<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Helpers
$firstDay = date('Y-m-01');
$lastDay = date('Y-m-t');

// 1. Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = trim($_POST['name']);
        $limit = (float) $_POST['limit'] ?: null;
        if (empty($name)) {
            $errors[] = "Category name required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO BUDGETCATEGORY (UserID, Name, MonthlyLimit) VALUES (?, ?, ?)");
            $stmt->bind_param("isd", $userId, $name, $limit);
            if ($stmt->execute()) {
                $success = "Category added.";
            } else {
                $errors[] = "Error adding category.";
            }
            $stmt->close();
        }
    }
    if (isset($_POST['add_transaction'])) {
        $categoryId = (int) $_POST['category_id'];
        $amount = (float) $_POST['amount'];
        $type = $_POST['type'];
        $note = trim($_POST['note']);
        $date = $_POST['date'];

        if ($amount <= 0 || empty($date)) {
            $errors[] = "Invalid amount or date.";
        } else {
            $stmt = $conn->prepare("INSERT INTO BUDGETTRANSACTION (UserID, CategoryID, Amount, Type, Note, Date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iidsss", $userId, $categoryId, $amount, $type, $note, $date);
            if ($stmt->execute()) {
                $success = "Transaction recorded.";
            } else {
                $errors[] = "Error adding transaction.";
            }
            $stmt->close();
        }
    }
}

// 2. Fetch Data for Calculations

// Summary Totals (This Month)
$totalIncome = 0;
$totalExpense = 0;

$stmt = $conn->prepare("
    SELECT Type, SUM(Amount) as Total 
    FROM BUDGETTRANSACTION 
    WHERE UserID = ? AND Date BETWEEN ? AND ? 
    GROUP BY Type
");
$stmt->bind_param("iss", $userId, $firstDay, $lastDay);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if ($row['Type'] === 'Income')
        $totalIncome = $row['Total'];
    if ($row['Type'] === 'Expense')
        $totalExpense = $row['Total'];
}
$stmt->close();
$balance = $totalIncome - $totalExpense;

// Category Breakdown (This Month)
// We need: Category Name, Limit, Spent (Sum of Expense transactions)
$categories = [];
$catQuery = "
    SELECT c.CategoryID, c.Name, c.MonthlyLimit, 
           COALESCE(SUM(t.Amount), 0) as Spent
    FROM BUDGETCATEGORY c
    LEFT JOIN BUDGETTRANSACTION t 
           ON c.CategoryID = t.CategoryID 
           AND t.Type = 'Expense' 
           AND t.Date BETWEEN '$firstDay' AND '$lastDay'
    WHERE c.UserID = ?
    GROUP BY c.CategoryID
";
$stmt = $conn->prepare($catQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $categories[] = $row;
}
$stmt->close();

// Recent Transactions
$history = [];
$stmt = $conn->prepare("
    SELECT t.*, c.Name as CategoryName
    FROM BUDGETTRANSACTION t
    LEFT JOIN BUDGETCATEGORY c ON t.CategoryID = c.CategoryID
    WHERE t.UserID = ?
    ORDER BY t.Date DESC LIMIT 10
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

// List of all categories for dropdown (regardless of spending)
$allCats = [];
$stmt = $conn->prepare("SELECT CategoryID, Name FROM BUDGETCATEGORY WHERE UserID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $allCats[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Smart Budget - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --accent: #38bdf8;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        .home-btn {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid #334155;
            color: #f8fafc;
            text-decoration: none;
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: transform 0.2s, background 0.2s;
            z-index: 10;
        }
        .home-btn:hover { transform: scale(1.05); background: #334155; }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text-main);
            padding: 1rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            border-bottom: 1px solid #334155;
            padding-bottom: 1rem;
        }

        h1 {
            margin: 0;
            font-size: 1.8rem;
            background: linear-gradient(90deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        a {
            color: var(--text-muted);
            text-decoration: none;
        }

        a:hover {
            color: var(--accent);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid #334155;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 0.5rem 0;
        }

        .inc {
            color: var(--success);
        }

        .exp {
            color: var(--danger);
        }

        .bal {
            color: var(--accent);
        }

        /* Main Content Grid */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            /* 2 cols: Progress vs Forms */
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Progress Section */
        .section-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid #334155;
        }

        h2 {
            margin-top: 0;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
        }

        .cat-item {
            margin-bottom: 1.5rem;
        }

        .cat-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .cat-limit {
            color: var(--text-muted);
            font-size: 0.85rem;
        }

        .progress-track {
            background: #334155;
            height: 10px;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 99px;
            transition: width 0.5s ease;
        }

        /* History Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th {
            text-align: left;
            color: var(--text-muted);
            font-weight: 500;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid #334155;
            font-size: 0.85rem;
        }

        td {
            padding: 0.8rem 0;
            border-bottom: 1px solid #1e293b;
            font-size: 0.9rem;
            color: #cbd5e1;
        }

        .col-amt {
            text-align: right;
            font-weight: 600;
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Forms */
        .form-box {
            background: #0f172a;
            /* Darker inset */
            padding: 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #334155;
        }

        .form-box h3 {
            margin-top: 0;
            font-size: 1rem;
            color: var(--accent);
        }

        input,
        select {
            width: 100%;
            padding: 0.6rem;
            background: #1e293b;
            border: 1px solid #475569;
            color: #fff;
            border-radius: 8px;
            margin-bottom: 0.8rem;
            box-sizing: border-box;
        }

        .btn {
            width: 100%;
            padding: 0.7rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, var(--accent), #2563eb);
            color: #fff;
            transition: opacity 0.2s;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .msg-success {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .msg-error {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="home-btn" title="Back to Dashboard">🏠</a>
    <div class="header">
        <div>
            <h1>Budget Planner</h1>
            <div style="font-size:0.9rem; color:#94a3b8; margin-top:0.3rem;">Overview for <?php echo date('F Y'); ?></div>
        </div>
    </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Income</div>
                <div class="stat-value inc">+<?php echo number_format($totalIncome, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Spent</div>
                <div class="stat-value exp">-<?php echo number_format($totalExpense, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Remaining Balance</div>
                <div class="stat-value bal"><?php echo number_format($balance, 2); ?></div>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="dashboard-grid">
            <!-- Left Col: Analysis & History -->
            <div>
                <?php if ($success): ?>
                    <div class="msg-success"><?php echo $success; ?></div><?php endif; ?>
                <?php foreach ($errors as $e): ?>
                    <div class="msg-error"><?php echo $e; ?></div><?php endforeach; ?>

                <div class="section-card">
                    <h2>Category Budgets</h2>
                    <?php if (empty($categories)): ?>
                        <p style="color:#94a3b8;">No categories set. Add one to start tracking.</p>
                    <?php else: ?>
                        <?php foreach ($categories as $cat):
                            $spent = $cat['Spent'];
                            $limit = $cat['MonthlyLimit'];
                            $percent = 0;
                            $color = '#22c55e'; // Green
                    
                            if ($limit > 0) {
                                $percent = ($spent / $limit) * 100;
                                if ($percent > 75)
                                    $color = '#f59e0b'; // Orange
                                if ($percent > 100)
                                    $color = '#ef4444'; // Red
                                if ($percent > 100)
                                    $percent = 100; // Cap visual
                            } else {
                                // No limit
                                $color = '#38bdf8';
                                $percent = 0; // Just show empty or full? Let's hide bar logic if no limit or just show simple spent
                            }
                            ?>
                            <div class="cat-item">
                                <div class="cat-header">
                                    <strong><?php echo htmlspecialchars($cat['Name']); ?></strong>
                                    <span>
                                        <?php echo number_format($spent, 2); ?>
                                        <span class="cat-limit">/ <?php echo $limit ? number_format($limit, 0) : '∞'; ?></span>
                                    </span>
                                </div>
                                <?php if ($limit > 0): ?>
                                    <div class="progress-track" title="<?php echo number_format($percent, 1); ?>%">
                                        <div class="progress-bar"
                                            style="width: <?php echo $percent; ?>%; background: <?php echo $color; ?>;"></div>
                                    </div>
                                    <?php if ($percent >= 100): ?>
                                        <div style="font-size:0.75rem; color:#ef4444; margin-top:0.2rem;">Over Budget!</div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="section-card" style="margin-top: 2rem;">
                    <h2>Recent Activity</h2>
                    <?php if (empty($history)): ?>
                        <p style="color:#94a3b8;">No transactions found.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Note</th>
                                    <th>Category</th>
                                    <th class="col-amt">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><?php echo date('M d', strtotime($h['Date'])); ?></td>
                                        <td><?php echo htmlspecialchars($h['Note'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($h['CategoryName']); ?></td>
                                        <td class="col-amt"
                                            style="color: <?php echo ($h['Type'] == 'Income' ? '#22c55e' : '#e2e8f0'); ?>">
                                            <?php echo ($h['Type'] == 'Income' ? '+' : '-'); ?>
                                            <?php echo number_format($h['Amount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Col: Actions -->
            <div>
                <div class="section-card">
                    <h2 style="margin-bottom:1rem;">Actions</h2>

                    <div class="form-box">
                        <h3>+ Add Transaction</h3>
                        <form method="post">
                            <input type="hidden" name="add_transaction" value="1">
                            <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                            <select name="type" required
                                onchange="this.style.borderColor = this.value=='Income'?'#22c55e':'#ef4444'">
                                <option value="Expense">Expense</option>
                                <option value="Income">Income</option>
                            </select>
                            <select name="category_id" required>
                                <option value="" disabled selected>Select Category</option>
                                <?php foreach ($allCats as $c): ?>
                                    <option value="<?php echo $c['CategoryID']; ?>">
                                        <?php echo htmlspecialchars($c['Name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" step="0.01" name="amount" placeholder="Amount (BDT)" required>
                            <input type="text" name="note" placeholder="Description (optional)">
                            <button class="btn">Save Transaction</button>
                        </form>
                    </div>

                    <div class="form-box">
                        <h3>+ New Category</h3>
                        <form method="post">
                            <input type="hidden" name="add_category" value="1">
                            <input type="text" name="name" placeholder="Category Name (e.g. Food)" required>
                            <input type="number" step="0.01" name="limit" placeholder="Monthly Limit (optional)">
                            <button class="btn" style="background:#475569;">Create Category</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>

</html>