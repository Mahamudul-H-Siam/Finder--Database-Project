<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$refId = isset($_GET['ref_id']) ? (int)$_GET['ref_id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';
$userId = $_SESSION['user_id'];

$reportedId = 0;
$targetName = "Unknown";
$error = "";
$success = "";

if ($refId && $type) {
    if ($type === 'Booking') {
        // Fetch Provider info
        $stmt = $conn->prepare("
            SELECT sb.ProviderID, sp.BusinessName 
            FROM SERVICEBOOKING sb 
            JOIN SERVICEPROVIDER sp ON sb.ProviderID = sp.ProviderID 
            WHERE sb.BookingID = ? AND sb.UserID = ?
        ");
        $stmt->bind_param("ii", $refId, $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $reportedId = $row['ProviderID'];
            $targetName = $row['BusinessName'];
        } else {
            $error = "Booking not found or access denied.";
        }
        $stmt->close();
    } elseif ($type === 'Application') {
        // Fetch Owner info
        // Assuming ApplicaitonID is passed, but index.php lists logic slightly differently.
        // Let's rely on passed ID being ApplicationID.
        // Wait, index.php query: SELECT ra.Status, r.Title, u.FullName... FROM ROOMAPPLICATION ra ...
        // I need to assume we pass ApplicationID.
        
        // Wait, ROOMAPPLICATION has ApplicationID.
        $stmt = $conn->prepare("
            SELECT r.OwnerID, u.FullName 
            FROM ROOMAPPLICATION ra 
            JOIN ROOMLISTING r ON ra.RoomID = r.RoomID 
            JOIN USER u ON r.OwnerID = u.UserID 
            WHERE ra.ApplicationID = ? AND ra.ApplicantID = ?
        ");
        $stmt->bind_param("ii", $refId, $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $reportedId = $row['OwnerID'];
            $targetName = $row['FullName'];
        } else {
            $error = "Application not found or access denied.";
        }
        $stmt->close();
    } else {
        $error = "Invalid report type.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $reason = $_POST['reason'];
    $description = trim($_POST['description']);
    
    if (empty($description)) {
        $error = "Description is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO REPORTS (ReporterID, ReportedID, ReferenceID, ReferenceType, Reason, Description) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisss", $userId, $reportedId, $refId, $type, $reason, $description);
        if ($stmt->execute()) {
            $success = "Report submitted successfully. Admins will review it shortly.";
        } else {
            $error = "Failed to submit report: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Issue - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 16px; width: 100%; max-width: 500px; border: 1px solid #334155; }
        h2 { margin-top: 0; color: #ef4444; }
        .info { background: #334155; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.9rem; }
        label { display: block; margin-bottom: 0.5rem; color: #cbd5e1; }
        select, textarea { width: 100%; padding: 0.8rem; background: #0f172a; border: 1px solid #475569; color: white; border-radius: 8px; margin-bottom: 1rem; font-family: inherit; box-sizing: border-box;}
        button { width: 100%; padding: 0.8rem; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        button:hover { background: #dc2626; }
        .back-link { display: block; text-align: center; margin-top: 1rem; color: #94a3b8; text-decoration: none; font-size: 0.9rem; }
        .msg { padding: 1rem; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
        .error { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }
        .success { background: rgba(34, 197, 94, 0.2); color: #86efac; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Report Issue</h2>
        
        <?php if ($error): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="msg success"><?php echo htmlspecialchars($success); ?></div>
            <a href="index.php" class="back-link">Return to Dashboard</a>
        <?php elseif ($reportedId): ?>
            <div class="info">
                You are reporting <strong><?php echo htmlspecialchars($targetName); ?></strong> regarding a <?php echo htmlspecialchars($type); ?>.
            </div>
            
            <form method="post">
                <label>Reason</label>
                <select name="reason">
                    <option value="PoorService">Poor Service / Condition</option>
                    <option value="Fraud">Fraud / Scam</option>
                    <option value="Harassment">Harassment / Rude Behavior</option>
                    <option value="InappropriateBehavior">Inappropriate Behavior</option>
                    <option value="Other">Other</option>
                </select>
                
                <label>Description (Please provide details)</label>
                <textarea name="description" rows="5" required></textarea>
                
                <button type="submit">Submit Report</button>
            </form>
            <a href="index.php" class="back-link">Cancel</a>
        <?php else: ?>
            <a href="index.php" class="back-link">Return to Dashboard</a>
        <?php endif; ?>
    </div>
</body>
</html>
