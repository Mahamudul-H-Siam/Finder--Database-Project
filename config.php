<?php
// config.php
$host = "localhost";
$db   = "findr";
$user = "root";      // change if you set a password
$pass = "";          // set your password if not empty

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
?>
