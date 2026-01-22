<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ServiceProvider') {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Check if profile exists
$stmt = $conn->prepare("SELECT BusinessName, ServiceType, Area, Description, StartingPrice FROM SERVICEPROVIDER WHERE ProviderID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $businessName = trim($_POST['business_name']);
    $serviceType = $_POST['service_type'];
    $area = trim($_POST['area']);
    $description = trim($_POST['description']);
    $startingPrice = floatval($_POST['starting_price']);

    if (empty($businessName) || empty($serviceType) || empty($area)) {
        $errors[] = "All fields are required.";
    } else {
        if ($profile) {
            // Update
            $stmt = $conn->prepare("UPDATE SERVICEPROVIDER SET BusinessName = ?, ServiceType = ?, Area = ?, Description = ?, StartingPrice = ? WHERE ProviderID = ?");
            $stmt->bind_param("ssssdi", $businessName, $serviceType, $area, $description, $startingPrice, $userId);
        } else {
            // Insert (if they somehow skipped signup logic)
            $stmt = $conn->prepare("INSERT INTO SERVICEPROVIDER (ProviderID, BusinessName, ServiceType, Area, Description, StartingPrice, IsApproved) VALUES (?, ?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("issssd", $userId, $businessName, $serviceType, $area, $description, $startingPrice);
        }

        if ($stmt->execute()) {
            $success = true;
            $profile = [
                'BusinessName' => $businessName,
                'ServiceType' => $serviceType,
                'Area' => $area,
                'Description' => $description,
                'StartingPrice' => $startingPrice
            ];
            // Refresh profile data
        } else {
            $errors[] = "Error saving details.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Service - FindR</title>
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

        .form-group input,
        .form-group select {
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
        <div class="top-link">← <a href="service_list.php">Back to services</a></div>
        <h2>Manage Service Profile</h2>
        <div class="subtitle">Update your business details.</div>

        <?php if ($success): ?>
            <div class="success">Profile updated successfully.</div>
        <?php endif; ?>

        <?php foreach ($errors as $e): ?>
            <div class="error">
                <?php echo htmlspecialchars($e); ?>
            </div>
        <?php endforeach; ?>

        <form method="post">
            <div class="form-group">
                <label>Business Name</label>
                <input type="text" name="business_name"
                    value="<?php echo htmlspecialchars($profile['BusinessName'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label>Service Type</label>
                <select name="service_type" required>
                    <?php
                    $types = ['Cleaner', 'Van', 'Mess', 'Tuition', 'Other'];
                    foreach ($types as $t) {
                        $sel = ($profile && $profile['ServiceType'] === $t) ? 'selected' : '';
                        echo "<option value=\"$t\" $sel>$t</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Area</label>
                <input type="text" name="area" value="<?php echo htmlspecialchars($profile['Area'] ?? ''); ?>" required>
            </div>

            <div class="form-group">
                <label>Description of Service</label>
                <textarea name="description" rows="4"
                    style="width:100%; padding:0.4rem 0.55rem; border-radius:0.6rem; border:1px solid #374151; background:#020617; color:#e5e7eb; font-size:0.85rem; outline:none; font-family:inherit;"
                    placeholder="Describe what you offer..."><?php echo htmlspecialchars($profile['Description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Starting Price (BDT)</label>
                <input type="number" name="starting_price" step="0.01"
                    value="<?php echo htmlspecialchars($profile['StartingPrice'] ?? '0.00'); ?>">
            </div>
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>
</body>

</html>