<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}

$providerId = isset($_GET['provider_id']) ? (int) $_GET['provider_id'] : 0;
$errors = [];
$success = false;

if ($providerId === 0) {
    header("Location: service_list.php");
    exit;
}

// Fetch provider details
$stmt = $conn->prepare("
    SELECT sp.ServiceType, sp.BusinessName, sp.Area, u.FullName
    FROM SERVICEPROVIDER sp
    JOIN USER u ON sp.ProviderID = u.UserID
    WHERE sp.ProviderID = ?
");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $providerId);
$stmt->execute();
$provider = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$provider) {
    header("Location: service_list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceType = $provider['ServiceType']; // From provider
    $date = $_POST['date'];
    $timeSlot = trim($_POST['time_slot']);
    $address = trim($_POST['address']);
    $price = (float) $_POST['price']; // Assume user enters or prefill

    if (empty($date) || empty($timeSlot) || empty($address) || $price <= 0) {
        $errors[] = "Fill all fields correctly.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("
            INSERT INTO SERVICEBOOKING (ProviderID, UserID, ServiceType, Date, TimeSlot, Address, Price)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("iissssd", $providerId, $_SESSION['user_id'], $serviceType, $date, $timeSlot, $address, $price);
        if ($stmt->execute()) {
            $success = true;

            // Notify the service provider
            $notifStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'New Booking Request', ?)");
            $notifMsg = "You have a new booking request for $serviceType on $date at $timeSlot.";
            $notifStmt->bind_param("is", $providerId, $notifMsg);
            $notifStmt->execute();
            $notifStmt->close();
        } else {
            $errors[] = "Error booking service.";
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
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color: #e5e7eb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card {
            background: rgba(15, 23, 42, 0.96);
            border-radius: 12px;
            border: 1px solid #1f2937;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.85);
            padding: 1.5rem 1.75rem;
            width: 100%;
            max-width: 520px;
        }

        h2 {
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 0.75rem;
            font-size: 0.8rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            color: #9ca3af;
        }

        .form-group input {
            width: 100%;
            padding: 0.4rem 0.55rem;
            border-radius: 0.6rem;
            border: 1px solid #374151;
            background: #020617;
            color: #e5e7eb;
            font-size: 0.85rem;
            outline: none;
        }

        .btn {
            width: 100%;
            margin-top: 0.4rem;
            padding: 0.5rem 0.7rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            background: linear-gradient(to right, #22c55e, #16a34a);
            color: #022c22;
            box-shadow: 0 10px 24px rgba(22, 163, 74, 0.9);
        }

        .error {
            color: #fecaca;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .success {
            color: #bbf7d0;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .top-link {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-bottom: 0.5rem;
        }

        .top-link a {
            color: #38bdf8;
        }
    </style>
</head>

<body>
    <div class="card">
        <div class="top-link">
            ← <a href="service_list.php">Back to services</a>
        </div>
        <h2>Book <?php echo htmlspecialchars($provider['ServiceType']); ?> from
            <?php echo htmlspecialchars($provider['BusinessName']); ?></h2>
        <div class="subtitle">Area: <?php echo htmlspecialchars($provider['Area']); ?> | Provider:
            <?php echo htmlspecialchars($provider['FullName']); ?></div>

        <?php if ($success): ?>
            <div class="success">Booking requested successfully. Check your dashboard.</div>
        <?php endif; ?>

        <?php foreach ($errors as $e): ?>
            <div class="error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" required min="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Time Slot</label>
                <input type="text" name="time_slot" placeholder="e.g., 09:00-11:00" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <input type="text" name="address" placeholder="Your address" required>
            </div>
            <div class="form-group">
                <label>Price (BDT)</label>
                <input type="number" step="0.01" name="price" placeholder="Agreed price" required>
            </div>
            <button type="submit" class="btn">Book Now</button>
        </form>
    </div>
</body>

</html>