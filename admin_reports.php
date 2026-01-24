<?php
include 'config.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$message = '';
$messageType = '';

// Handle Actions
if (isset($_GET['dismiss_report'])) {
    $reportId = (int)$_GET['dismiss_report'];
    $stmt = $conn->prepare("UPDATE REPORTS SET Status = 'Dismissed' WHERE ReportID = ?");
    $stmt->bind_param("i", $reportId);
    if ($stmt->execute()) {
        $message = "Report dismissed.";
        $messageType = "success";
    }
    $stmt->close();
}

if (isset($_GET['block_user_report'])) {
    $reportId = (int)$_GET['block_user_report']; // Pass ReportID to verify and update status
    $userId = (int)$_GET['uid'];
    
    // Block User
    $stmt = $conn->prepare("UPDATE USER SET Status = 'Blocked' WHERE UserID = ? AND Role != 'Admin'");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        // Mark report as Reviewed
        $conn->query("UPDATE REPORTS SET Status = 'Reviewed' WHERE ReportID = $reportId");
        
        $message = "User blocked and report marked as reviewed.";
        $messageType = "warning";
    }
    $stmt->close();
}

// Fetch Pending Reports
$reports = [];
$res = $conn->query("
    SELECT r.ReportID, r.Reason, r.Description, r.CreatedAt, r.ReferenceType, r.Status,
           u1.FullName as ReporterName, u1.Email as ReporterEmail,
           u2.UserID as ReportedID, u2.FullName as ReportedName, u2.Email as ReportedEmail, u2.Role as ReportedRole
    FROM REPORTS r
    JOIN USER u1 ON r.ReporterID = u1.UserID
    JOIN USER u2 ON r.ReportedID = u2.UserID
    WHERE r.Status = 'Pending'
    ORDER BY r.CreatedAt DESC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Reports - FindR Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #f8fafc; margin: 0; padding: 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #334155; padding-bottom: 1rem; }
        h1 { margin: 0; color: #f8fafc; }
        .btn { padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 0.9rem; }
        .btn-back { background: #334155; color: white; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; color: #94a3b8; padding: 0.8rem; border-bottom: 1px solid #334155; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 0.8rem; border-bottom: 1px solid #334155; vertical-align: top; }
        .user-info { font-size: 0.9rem; }
        .user-email { color: #94a3b8; font-size: 0.8rem; display: block; }
        .reason-badge { background: rgba(239,68,68,0.2); color: #fca5a5; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-bottom: 0.3rem; display: inline-block; }
        .desc { font-size: 0.9rem; line-height: 1.5; color: #cbd5e1; margin-top: 0.5rem; }
        .actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .btn-dismiss { background: #334155; color: #cbd5e1; }
        .btn-block { background: #ef4444; color: white; }
        .alert { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; background: rgba(34,197,94,0.1); color: #86efac; border: 1px solid #22c55e; }
        .empty { text-align: center; color: #64748b; padding: 3rem; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ User Reports</h1>
            <a href="admin_portal.php" class="btn btn-back">Back to Portal</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <?php if (empty($reports)): ?>
                <div class="empty">No pending reports. Great!</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Reported User</th>
                            <th>Reported By</th>
                            <th>Issue</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td>
                                    <div class="user-info"><strong><?php echo htmlspecialchars($r['ReportedName']); ?></strong></div>
                                    <span class="user-email"><?php echo htmlspecialchars($r['ReportedEmail']); ?></span>
                                    <div style="font-size:0.75rem; color:#94a3b8; margin-top:0.2rem;">Role: <?php echo $r['ReportedRole']; ?></div>
                                </td>
                                <td>
                                    <div class="user-info"><?php echo htmlspecialchars($r['ReporterName']); ?></div>
                                    <span class="user-email"><?php echo htmlspecialchars($r['ReporterEmail']); ?></span>
                                </td>
                                <td style="width: 40%;">
                                    <span class="reason-badge"><?php echo htmlspecialchars($r['Reason']); ?></span>
                                    <span style="font-size:0.75rem; color:#94a3b8;">(<?php echo $r['ReferenceType']; ?>)</span>
                                    <div class="desc"><?php echo nl2br(htmlspecialchars($r['Description'])); ?></div>
                                </td>
                                <td style="font-size:0.85rem; color:#94a3b8;"><?php echo date('M d, Y', strtotime($r['CreatedAt'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="?dismiss_report=<?php echo $r['ReportID']; ?>" class="btn btn-dismiss">Dismiss</a>
                                        <a href="?block_user_report=<?php echo $r['ReportID']; ?>&uid=<?php echo $r['ReportedID']; ?>" class="btn btn-block" onclick="return confirm('Are you sure you want to BLOCK this user?');">Block User</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
