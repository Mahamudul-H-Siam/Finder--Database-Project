<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$message = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = intval($_POST['service_id']);

    if (isset($_POST['approve'])) {
        $stmt = $conn->prepare("UPDATE PROVIDER_SERVICES SET IsApproved = 1 WHERE ServiceID = ?");
        $stmt->bind_param("i", $serviceId);
        if ($stmt->execute()) {
            $message = "Service approved.";

            // Notify Provider
            $provStmt = $conn->prepare("SELECT ProviderID, ServiceType FROM PROVIDER_SERVICES WHERE ServiceID = ?");
            $provStmt->bind_param("i", $serviceId);
            $provStmt->execute();
            $provRes = $provStmt->get_result()->fetch_assoc();
            $provStmt->close();

            if ($provRes) {
                $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Service Approved', ?)");
                $msg = "Your service '{$provRes['ServiceType']}' is now live.";
                $nStmt->bind_param("is", $provRes['ProviderID'], $msg);
                $nStmt->execute();
                $nStmt->close();
            }
        }
        $stmt->close();
    }

    if (isset($_POST['reject'])) {
        // We might want to just delete it or mark as rejected. For now let's just delete or set IsActive=0?
        // Let's delete it to keep it simple, or maybe add a 'Rejected' status column?
        // Current schema is Boolean IsApproved. So 0 is Pending. 
        // If we want to Reject, we should probably delete it or add a Status column.
        // For now: Delete and notify.
        $provStmt = $conn->prepare("SELECT ProviderID, ServiceType FROM PROVIDER_SERVICES WHERE ServiceID = ?");
        $provStmt->bind_param("i", $serviceId);
        $provStmt->execute();
        $provRes = $provStmt->get_result()->fetch_assoc();
        $provStmt->close();

        $stmt = $conn->prepare("DELETE FROM PROVIDER_SERVICES WHERE ServiceID = ?");
        $stmt->bind_param("i", $serviceId);
        if ($stmt->execute()) {
            $message = "Service rejected/removed.";
            if ($provRes) {
                $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Service Rejected', ?)");
                $msg = "Your service '{$provRes['ServiceType']}' was rejected by admin.";
                $nStmt->bind_param("is", $provRes['ProviderID'], $msg);
                $nStmt->execute();
                $nStmt->close();
            }
        }
        $stmt->close();
    }
}

// Fetch Pending Services
$sql = "
    SELECT ps.*, sp.BusinessName, u.FullName, u.Email
    FROM PROVIDER_SERVICES ps
    JOIN SERVICEPROVIDER sp ON ps.ProviderID = sp.ProviderID
    JOIN USER u ON sp.ProviderID = u.UserID
    WHERE ps.IsApproved = 0
    ORDER BY ps.CreatedAt ASC
";
$result = $conn->query($sql);
$pendingServices = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Services - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: #0f172a;
            color: #f8fafc;
            padding: 2rem;
        }

        .container {
            max-width: 1000px;
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
            color: #38bdf8;
        }

        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
        }

        .meta {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .desc {
            margin-top: 0.5rem;
            color: #cbd5e1;
            font-style: italic;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-approve {
            background: #22c55e;
            color: #022c22;
        }

        .btn-reject {
            background: #ef4444;
            color: white;
        }

        .btn-back {
            background: #475569;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Pending Service Approvals</h1>
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div
                style="background:rgba(56,189,248,0.2); color:#38bdf8; padding:1rem; border-radius:8px; margin-bottom:1.5rem;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($pendingServices)): ?>
            <div style="text-align:center; color:#64748b; padding:3rem;">No pending services.</div>
        <?php else: ?>
            <?php foreach ($pendingServices as $svc): ?>
                <div class="card">
                    <div class="info">
                        <h3>
                            <?php echo htmlspecialchars($svc['ServiceType']); ?> <span
                                style="font-size:0.9rem; font-weight:400; color:#94a3b8;">by
                                <?php echo htmlspecialchars($svc['BusinessName']); ?>
                            </span>
                        </h3>
                        <div class="desc">"
                            <?php echo htmlspecialchars($svc['Description']); ?>"
                        </div>
                        <div class="meta" style="margin-top:0.5rem;">
                            Price:
                            <?php echo $svc['Price'] > 0 ? number_format($svc['Price']) . ' BDT' : 'Negotiable'; ?>
                        </div>
                    </div>
                    <form method="POST" class="actions">
                        <input type="hidden" name="service_id" value="<?php echo $svc['ServiceID']; ?>">
                        <button type="submit" name="approve" class="btn btn-approve">Approve</button>
                        <button type="submit" name="reject" class="btn btn-reject"
                            onclick="return confirm('Reject this service? It will be deleted.')">Reject</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>