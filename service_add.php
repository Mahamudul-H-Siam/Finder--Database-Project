<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ServiceProvider') {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$success = false;

// Handle DELETE
if (isset($_GET['delete'])) {
    $delId = (int) $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM PROVIDER_SERVICES WHERE ServiceID = ? AND ProviderID = ?");
    $stmt->bind_param("ii", $delId, $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: service_add.php");
    exit;
}

// Handle ADD/UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Update Business Details (Provider Table)
    $businessName = trim($_POST['business_name']);
    $area = trim($_POST['area']);

    // We update business details first
    if (!empty($businessName) && !empty($area)) {
        $stmt = $conn->prepare("UPDATE SERVICEPROVIDER SET BusinessName = ?, Area = ? WHERE ProviderID = ?");
        $stmt->bind_param("ssi", $businessName, $area, $userId);
        $stmt->execute();
        $stmt->close();
    }

    // 2. Add New Service (if provided)
    if (!empty($_POST['new_service_type'])) {
        $svcType = $_POST['new_service_type'];
        $svcDesc = trim($_POST['description'] ?? '');
        $svcPrice = (float) ($_POST['price'] ?? 0);

        // Default IsApproved to 0 (Pending) for new services
        $stmt = $conn->prepare("INSERT INTO PROVIDER_SERVICES (ProviderID, ServiceType, Description, Price, IsApproved) VALUES (?, ?, ?, ?, 0)");
        $stmt->bind_param("issd", $userId, $svcType, $svcDesc, $svcPrice);
        if ($stmt->execute()) {
            $success = true;
            // Prevent form resubmission on refresh by redirecting to self
            // header("Location: service_add.php"); // Optional: User might want to see success message first
        } else {
            $errors[] = "Error adding service: " . $conn->error;
        }
        $stmt->close();
    }
}

// Fetch Basic Profile
$stmt = $conn->prepare("SELECT BusinessName, Area FROM SERVICEPROVIDER WHERE ProviderID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch Services
$services = [];
$stmt = $conn->prepare("SELECT * FROM PROVIDER_SERVICES WHERE ProviderID = ? ORDER BY CreatedAt DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Services - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .card {
            background: #1e293b;
            border-radius: 12px;
            border: 1px solid #334155;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        h2 {
            margin: 0;
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            color: #94a3b8;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 0.6rem;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            box-sizing: border-box;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-remove {
            background: #ef4444;
            color: white;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            text-decoration: none;
            display: inline-block;
            border-radius: 6px;
        }

        .service-list {
            display: grid;
            gap: 1rem;
            margin-top: 1rem;
        }

        .service-item {
            background: #0f172a;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .home-btn {
            text-decoration: none;
            color: white;
            font-size: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div style="display:flex; align-items:center; gap:1rem;">
                <a href="index.php" class="home-btn">🏠</a>
                <h2>Manage Services</h2>
            </div>
        </div>

        <!-- Business Info -->
        <div class="card">
            <h3 style="margin-top:0;">Business Details</h3>
            <form method="POST">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label>Business Name</label>
                        <input type="text" name="business_name"
                            value="<?php echo htmlspecialchars($profile['BusinessName'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Area</label>
                        <input type="text" name="area" value="<?php echo htmlspecialchars($profile['Area'] ?? ''); ?>"
                            required>
                    </div>
                </div>

                <h3 style="margin-bottom:0.8rem;">Add New Service</h3>
                <!-- Add Service Form -->
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem;">
                    <div class="form-group">
                        <label>Service Type</label>
                        <select name="new_service_type">
                            <option value="">-- Select Service --</option>
                            <?php
                            $types = ['Cleaner', 'Van', 'Mess', 'Tuition', 'Other'];
                            foreach ($types as $t)
                                echo "<option value=\"$t\">$t</option>";
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Starts From (Price)</label>
                        <input type="number" name="price" placeholder="Min. Price">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="2" placeholder="Describe your service briefly..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Save & Add Service</button>
            </form>
        </div>

        <!-- Active Services -->
        <h3 style="margin-bottom:1rem;">My Active Services</h3>
        <div class="service-list">
            <?php if (empty($services)): ?>
                <div style="color:#94a3b8; text-align:center;">No services added yet.</div>
            <?php else: ?>
                <?php foreach ($services as $s): ?>
                    <div class="service-item">
                        <div>
                            <div style="font-weight:700; color:#38bdf8; font-size:1.1rem;">
                                <?php echo htmlspecialchars($s['ServiceType']); ?>
                            </div>
                            <div style="color:#cbd5e1; font-size:0.9rem; margin-top:0.3rem;">
                                <?php echo htmlspecialchars($s['Description']); ?>
                            </div>
                            <?php if ($s['Price'] > 0): ?>
                                <div style="color:#bef264; font-size:0.85rem; margin-top:0.3rem;">
                                    Starts from <?php echo number_format($s['Price'], 0); ?> BDT
                                </div>
                            <?php endif; ?>
                            <div style="margin-top:0.3rem;">
                                <?php if ($s['IsApproved']): ?>
                                    <span
                                        style="font-size:0.75rem; background:rgba(34,197,94,0.2); color:#4ade80; padding:0.1rem 0.4rem; border-radius:4px;">Verified</span>
                                <?php else: ?>
                                    <span
                                        style="font-size:0.75rem; background:rgba(234,179,8,0.2); color:#facc15; padding:0.1rem 0.4rem; border-radius:4px;">Pending
                                        Approval</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <a href="?delete=<?php echo $s['ServiceID']; ?>" class="btn-remove"
                            onclick="return confirm('Remove this service?')">Remove</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</body>

</html>