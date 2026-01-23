<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM NOTIFICATION WHERE UserID = ? AND IsRead = 0");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

echo json_encode(['count' => $row['count']]);
?>