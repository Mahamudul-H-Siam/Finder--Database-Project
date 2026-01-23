<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}

$serviceId = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;
// Fallback for old links: redirect if necessary, or show error
if ($serviceId === 0 && isset($_GET['provider_id'])) {
    // If only provider ID is given, we need to pick a service. 
    // For now, let's just redirect to service_list.php filtering by that provider?
    // Or just pick the first service? Let's error for safety or redirect.
    header("Location: service_list.php?q=" . $_GET['provider_id']);
    exit;
}

$errors = [];
$success = false;

// Fetch Service Details
// Join with SERVICEPROVIDER and USER
$stmt = $conn->prepare("
    SELECT ps.*, sp.BusinessName, sp.Area, u.FullName, u.UserID as ProviderUserId
    FROM PROVIDER_SERVICES ps
    JOIN SERVICEPROVIDER sp ON ps.ProviderID = sp.ProviderID
    JOIN USER u ON sp.ProviderID = u.UserID
    WHERE ps.ServiceID = ?
");
$stmt->bind_param("i", $serviceId);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$service) {
    header("Location: service_list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $timeSlot = trim($_POST['time_slot']);
    $address = trim($_POST['address']);
    $price = (float) $_POST['price'];

    if (empty($date) || empty($timeSlot) || empty($address) || $price <= 0) {
        $errors[] = "Please fill all fields correctly.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("
            INSERT INTO SERVICEBOOKING (ProviderID, UserID, ServiceType, Date, TimeSlot, Address, Price)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        // Note: Booking table still uses 'ServiceType' as string. We pass the string name.
        $stmt->bind_param("iissssd", $service['ProviderID'], $_SESSION['user_id'], $service['ServiceType'], $date, $timeSlot, $address, $price);

        if ($stmt->execute()) {
            $success = true;
            // Notify Provider
            $notifStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Type, Title, Message, Link) VALUES (?, 'Order', 'New Booking Request', ?, 'provider_bookings.php')");
            $notifMsg = "New booking: " . $service['ServiceType'] . " on $date at $timeSlot.";
            $notifStmt->bind_param("is", $service['ProviderID'], $notifMsg);
            $notifStmt->execute();
            $notifStmt->close();
        } else {
            $errors[] = "Error processing booking.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Book Service - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color: #e5e7eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .card {
            background: rgba(15, 23, 42, 0.95);
            border-radius: 16px;
            border: 1px solid #1f2937;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            padding: 2rem;
            width: 100%;
            max-width: 600px;
        }

        h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            color: white;
        }

        .subtitle {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #334155;
        }

        .service-info {
            background: rgba(30, 41, 59, 0.5);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #334155;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            color: #cbd5e1;
            font-size: 0.85rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.7rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: white;
            box-sizing: border-box;
            font-family: inherit;
        }

        .form-group input:focus {
            border-color: #38bdf8;
            outline: none;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .btn {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: #022c22;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 1rem;
            transition: transform 0.1s;
        }

        .btn:hover {
            transform: scale(1.02);
        }

        .success-msg {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .error-msg {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .back-link {
            display: block;
            margin-bottom: 1rem;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            color: white;
        }
    </style>
</head>

<body>
    <div class="card">
        <a href="service_list.php" class="back-link">← Cancel & Back</a>

        <?php if ($success): ?>
            <div class="success-msg">
                <strong>Booking Requested!</strong><br>
                The provider has been notified. You can track status in your dashboard.
                <div style="margin-top:1rem;">
                    <a href="index.php" style="color:white; text-decoration:underline;">Go to Dashboard</a>
                </div>
            </div>
        <?php else: ?>

            <h2>Confirm Booking</h2>
            <div class="subtitle">Complete the details below to request this service.</div>

            <?php foreach ($errors as $e)
                echo "<div class='error-msg'>$e</div>"; ?>

            <div class="service-info">
                <div style="font-weight:700; font-size:1.1rem; color:#38bdf8; margin-bottom:0.2rem;">
                    <?php echo htmlspecialchars($service['ServiceType']); ?>
                </div>
                <div style="color:#94a3b8; font-size:0.9rem;">
                    by <?php echo htmlspecialchars($service['BusinessName']); ?>
                </div>
                <div style="margin-top:0.5rem; font-size:0.85rem; color:#cbd5e1;">
                    📍 <?php echo htmlspecialchars($service['Area']); ?>
                </div>
                <?php if ($service['Description']): ?>
                    <div style="margin-top:0.5rem; font-size:0.85rem; color:#94a3b8; font-style:italic;">
                        "<?php echo htmlspecialchars($service['Description']); ?>"
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Date Needed</label>
                        <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Preferred Time Slot</label>
                        <input type="text" name="time_slot" placeholder="e.g. 10:00 AM - 12:00 PM" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Service Location</label>
                        <input type="text" name="address" placeholder="House 12, Road 5, Block B..." required>
                    </div>
                    <div class="form-group full-width">
                        <label>Proposed Price (BDT)</label>
                        <input type="number" name="price" step="0.01"
                            value="<?php echo $service['Price'] > 0 ? $service['Price'] : ''; ?>" placeholder="Enter amount"
                            required>
                        <?php if ($service['Price'] > 0): ?>
                            <div style="font-size:0.75rem; color:#bef264; margin-top:0.3rem;">
                                Provider's base price: <?php echo number_format($service['Price']); ?> BDT
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <button type="submit" class="btn">Send Request</button>
            </form>
        <?php endif; ?>
    </div>
</body>

</html>