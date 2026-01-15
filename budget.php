<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Fetch categories
$stmt = $conn->prepare("SELECT CategoryID, Name, MonthlyLimit FROM BUDGETCATEGORY WHERE UserID = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent transactions (last 5)
$stmt = $conn->prepare("
    SELECT bt.TransactionID, bt.Amount, bt.Type, bt.Note, bt.Date, bc.Name AS CategoryName
    FROM BUDGETTRANSACTION bt
    JOIN BUDGETCATEGORY bc ON bt.CategoryID = bc.CategoryID
    WHERE bt.UserID = ?
    ORDER BY bt.Date DESC LIMIT 5
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userId);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Add category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $limit = (float)$_POST['limit'] ?: null;

    if (empty($name)) {
        $errors[] = "Category name required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO BUDGETCATEGORY (UserID, Name, MonthlyLimit) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("isd", $userId, $name, $limit);
        if ($stmt->execute()) {
            $success = true;
            header("Location: budget.php"); // Refresh
            exit;
        } else {
            $errors[] = "Error adding category.";
        }
        $stmt->close();
    }
}

// Add transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $categoryId = (int)$_POST['category_id'];
    $amount = (float)$_POST['amount'];
    $type = $_POST['type'];
    $note = trim($_POST['note']);
    $date = $_POST['date'];

    if ($amount <= 0 || empty($date) || !in_array($type, ['Expense', 'Income'])) {
        $errors[] = "Invalid input.";
    } else {
        $stmt = $conn->prepare("INSERT INTO BUDGETTRANSACTION (UserID, CategoryID, Amount, Type, Note, Date) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("iidsss", $userId, $categoryId, $amount, $type, $note, $date);
        if ($stmt->execute()) {
            $success = true;
            header("Location: budget.php");
            exit;
        } else {
            $errors[] = "Error adding transaction.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Budget - FindR</title>
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
        .card ul { list-style: none; padding: 0; }
        .card li { margin-bottom: 0.5rem; }
        .form-row { display: flex; gap: 1rem; }
        .form-group { margin-bottom:0.75rem; font-size:0.8rem; }
        .form-group label { display:block; margin-bottom:0.25rem; color:#9ca3af; }
        .form-group input, .form-group select { width:100%; padding:0.4rem 0.55rem; border-radius:0.6rem; border:1px solid #374151; background:#020617; color:#e5e7eb; font-size:0.85rem; outline:none; }
        .btn { width:100%; margin-top:0.4rem; padding:0.5rem 0.7rem; border-radius:999px; border:none; cursor:pointer; font-size:0.85rem; font-weight:600; background:linear-gradient(to right,#22c55e,#16a34a); color:#022c22; box-shadow:0 10px 24px rgba(22,163,74,0.9); }
        .error { color:#fecaca; font-size:0.8rem; margin-bottom:0.3rem; }
        .success { color:#bbf7d0; font-size:0.8rem; margin-bottom:0.3rem; }
        .empty { color:#9ca3af; }
    </style>
</head>
<body>
<div class="container">
    <div class="header-line">
        <h2>Budget Planner</h2>
        <a href="index.php">← Dashboard</a>
    </div>

    <?php if ($success): ?>
        <div class="success">Added successfully.</div>
    <?php endif; ?>
    <?php foreach ($errors as $e): ?>
        <div class="error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <div class="grid">
        <div class="card">
            <h3>Your Categories</h3>
            <?php if (empty($categories)): ?>
                <p class="empty">Add your first category.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($categories as $cat): ?>
                        <li><?php echo htmlspecialchars($cat['Name']); ?> (Limit: <?php echo $cat['MonthlyLimit'] ? number_format($cat['MonthlyLimit'], 2) : 'None'; ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="add_category" value="1">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Monthly Limit (optional)</label>
                    <input type="number" step="0.01" name="limit">
                </div>
                <button type="submit" class="btn">Add Category</button>
            </form>
        </div>

        <div class="card">
            <h3>Recent Transactions</h3>
            <?php if (empty($transactions)): ?>
                <p class="empty">Add your first transaction.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($transactions as $tx): ?>
                        <li>
                            <strong><?php echo $tx['Type']; ?>:</strong> BDT <?php echo number_format($tx['Amount'], 2); ?> (<?php echo htmlspecialchars($tx['CategoryName']); ?>)<br>
                            Note: <?php echo htmlspecialchars($tx['Note'] ?? 'None'); ?> | Date: <?php echo $tx['Date']; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="add_transaction" value="1">
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['CategoryID']; ?>"><?php echo htmlspecialchars($cat['Name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="amount" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <select name="type" required>
                            <option value="Expense">Expense</option>
                            <option value="Income">Income</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Note (optional)</label>
                    <input type="text" name="note">
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>
                <button type="submit" class="btn">Add Transaction</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>