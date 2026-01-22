<?php
include 'config.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Owner') {
    header("Location: index.php");
    exit;
}

$ownerId = $_SESSION['user_id'];

// Handle Actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appId = intval($_GET['id']);
    $action = $_GET['action']; // accept, reject, delete

    // Verify ownership and get applicant details
    $check = $conn->prepare("
        SELECT ra.ApplicationID, ra.ApplicantID, r.Title 
        FROM ROOMAPPLICATION ra
        JOIN ROOMLISTING r ON ra.RoomID = r.RoomID
        WHERE ra.ApplicationID = ? AND r.OwnerID = ?
    ");
    $check->bind_param("ii", $appId, $ownerId);
    $check->execute();
    $res = $check->get_result();
    if ($row = $res->fetch_assoc()) {
        $check->close();

        $applicantId = $row['ApplicantID'];
        $roomTitle = $row['Title'];

        $newStatus = '';
        if ($action === 'accept')
            $newStatus = 'Accepted';
        if ($action === 'reject')
            $newStatus = 'Rejected';

        if ($newStatus) {
            // Update Status
            $update = $conn->prepare("UPDATE ROOMAPPLICATION SET Status = ? WHERE ApplicationID = ?");
            $update->bind_param("si", $newStatus, $appId);
            if ($update->execute()) {
                // Send Notification
                $notifTitle = "Application " . $newStatus;
                $notifMsg = "Your application for '$roomTitle' has been $newStatus.";
                $nStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, ?, ?)");
                $nStmt->bind_param("iss", $applicantId, $notifTitle, $notifMsg);
                $nStmt->execute();
                $nStmt->close();
            }
            $update->close();
        } elseif ($action === 'delete') {
            $del = $conn->prepare("DELETE FROM ROOMAPPLICATION WHERE ApplicationID = ?");
            $del->bind_param("i", $appId);
            $del->execute();
            $del->close();
        }
    } else {
        $check->close();
    }
    header("Location: owner_applications.php");
    exit;
}

// Fetch Applications
$applications = [];
$stmt = $conn->prepare("
    SELECT ra.ApplicationID, ra.Message, ra.Status, ra.AppliedAt,
           r.Title AS RoomTitle, r.RentAmount,
           u.FullName AS ApplicantName, u.Email, u.Phone, u.AgeGroup, u.LivingStatus
    FROM ROOMAPPLICATION ra
    JOIN ROOMLISTING r ON ra.RoomID = r.RoomID
    JOIN USER u ON ra.ApplicantID = u.UserID
    WHERE r.OwnerID = ?
    ORDER BY ra.AppliedAt DESC
");
$stmt->bind_param("i", $ownerId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $applications[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Applications - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .btn-back {
            background: #475569;
            color: white;
            display: inline-block;
            padding: 0.5rem 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            text-decoration: none;
        }

        h1 {
            color: #f8fafc;
            border-bottom: 1px solid #334155;
            padding-bottom: 1rem;
        }

        .app-card {
            background: #1e293b;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #334155;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        @media (min-width: 768px) {
            .app-card {
                flex-direction: row;
                justify-content: space-between;
            }
        }

        .app-info h3 {
            margin: 0 0 0.5rem 0;
            color: #38bdf8;
        }

        .meta {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .message {
            background: #0f172a;
            padding: 1rem;
            border-radius: 6px;
            font-style: italic;
            color: #cbd5e1;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 150px;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .btn-accept {
            background: #22c55e;
            color: #022c22;
        }

        .btn-reject {
            background: #f59e0b;
            color: #451a03;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-Pending {
            background: #f59e0b;
            color: #451a03;
        }

        .status-Accepted {
            background: #22c55e;
            color: #022c22;
        }

        .status-Rejected {
            background: #ef4444;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="index.php" class="btn-back">← Back to Dashboard</a>
        <h1>Rental Applications</h1>

        <?php if (empty($applications)): ?>
            <p style="color: #94a3b8; text-align: center; margin-top: 3rem;">No applications received yet.</p>
        <?php else: ?>
            <?php foreach ($applications as $app): ?>
                <div class="app-card">
                    <div class="app-info">
                        <h3>
                            <?php echo htmlspecialchars($app['RoomTitle']); ?>
                        </h3>
                        <div class="meta">
                            Applicant: <strong>
                                <?php echo htmlspecialchars($app['ApplicantName']); ?>
                            </strong><br>
                            Details:
                            <?php echo $app['AgeGroup']; ?>,
                            <?php echo $app['LivingStatus']; ?><br>
                            Contact:
                            <?php echo htmlspecialchars($app['Email']); ?> |
                            <?php echo htmlspecialchars($app['Phone']); ?>
                        </div>
                        <div class="message">"
                            <?php echo nl2br(htmlspecialchars($app['Message'])); ?>"
                        </div>
                        <div style="margin-top: 0.5rem;">
                            Status: <span class="badge status-<?php echo $app['Status']; ?>">
                                <?php echo $app['Status']; ?>
                            </span>
                            <span style="color: #64748b; font-size: 0.8rem; margin-left: 0.5rem;">
                                <?php echo date('M d, h:i A', strtotime($app['AppliedAt'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="actions">
                        <?php if ($app['Status'] === 'Pending'): ?>
                            <a href="?action=accept&id=<?php echo $app['ApplicationID']; ?>" class="btn btn-accept">Accept</a>
                            <a href="?action=reject&id=<?php echo $app['ApplicationID']; ?>" class="btn btn-reject">Reject</a>
                        <?php else: ?>
                            <!-- Option to delete old processed applications -->
                            <a href="?action=delete&id=<?php echo $app['ApplicationID']; ?>" class="btn btn-delete"
                                onclick="return confirm('Delete this record?');">Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>

</html>