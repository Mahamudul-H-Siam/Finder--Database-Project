<?php
// config.php
$host = "127.0.0.1";
$db = "findr";
$user = "root";      // change if you set a password
$pass = "";          // set your password if not empty

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Admin Registration Security
// IMPORTANT: Change this passkey to your own secret code!
define('ADMIN_SECRET_PASSKEY', 'siam valo chele');

session_start();
?>