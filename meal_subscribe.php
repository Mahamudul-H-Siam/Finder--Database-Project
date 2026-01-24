<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}

$planId = isset($_GET['plan_id']) ? (int)$_GET['plan_id'] : 0;
$errors = [];
$success = false;

if ($planId === 0) {
    header("Location: meal_list.php");
    exit;
}

// Fetch plan
$stmt = $conn->prepare("
    SELECT mp.Name, mp.MonthlyPrice, mp.ProviderID
    FROM MEALPLAN mp
    WHERE mp.MealPlanID = ? AND mp.IsActive = 1
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $planId);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plan) {
    header("Location: meal_list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For simplicity, subscribe by booking a 'Meal' service monthly
    $date = date('Y-m-d'); // Start today
    $timeSlot = 'Monthly';
    $address = trim($_POST['address']);
    $price = $plan['MonthlyPrice'];

    if (empty($address)) {
        $errors[] = "Address required.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO SERVICEBOOKING (ProviderID, UserID, ServiceType, Date, TimeSlot, Address, Price, BookingStatus)
            VALUES (?, ?, 'Meal', ?, ?, ?, ?, 'Confirmed')
        ");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("iisssd", $plan['ProviderID'], $_SESSION['user_id'], $date, $timeSlot, $address, $price);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Error subscribing.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Subscribe to Meal Plan - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin:0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color:#e5e7eb;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .card {
            background: rgba(15,23,42,0.96);
            border-radius: 12px;
            border:1px solid #1f2937;
            box-shadow:0 18px 40px rgba(15,23,42,0.85);
            padding:1.5rem 1.75rem;
            width:100%;
            max-width:520px;
        }
        h2 { margin-bottom:0.5rem; }
        .subtitle { font-size:0.8rem; color:#9ca3af; margin-bottom:1rem; }
        .form-group { margin-bottom:0.75rem; font-size:0.8rem; }
        .form-group label { display:block; margin-bottom:0.25rem; color:#9ca3af; }
        .form-group input { width:100%; padding:0.4rem 0.55rem; border-radius:0.6rem; border:1px solid #374151; background:#020617; color:#e5e7eb; font-size:0.85rem; outline:none; }
        .btn { width:100%; margin-top:0.4rem; padding:0.5rem 0.7rem; border-radius:999px; border:none; cursor:pointer; font-size:0.85rem; font-weight:600; background:linear-gradient(to right,#22c55e,#16a34a); color:#022c22; box-shadow:0 10px 24px rgba(22,163,74,0.9); }
        .error { color:#fecaca; font-size:0.8rem; margin-bottom:0.3rem; }
        .success { color:#bbf7d0; font-size:0.8rem; margin-bottom:0.3rem; }
        .top-link { font-size:0.75rem; color:#9ca3af; margin-bottom:0.5rem; }
        .top-link a { color:#38bdf8; }
    </style>
</head>
<body>
<div class="card">
    <div class="top-link">← <a href="meal_list.php">Back</a></div>
    <h2>Subscribe to <?php echo htmlspecialchars($plan['Name']); ?></h2>
    <div class="subtitle">Monthly Price: BDT <?php echo number_format($plan['MonthlyPrice'], 2); ?></div>

    <?php if ($success): ?>
        <div class="success">Subscribed successfully.</div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <div class="error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <form method="post">
        <div class="form-group">
            <label>Delivery Address</label>
            <input type="text" name="address" required>
        </div>
        <button type="submit" class="btn">Subscribe</button>
    </form>
</div>
</body>
</html>