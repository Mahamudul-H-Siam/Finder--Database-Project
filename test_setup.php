<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>FindR System Diagnostic</h1>";

// 1. Check PHP Version
echo "<h2>1. PHP Version</h2>";
echo "Current Version: " . phpversion() . "<br>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "<span style='color:red'>WARNING: PHP version might be too old. Recommend 7.4+ or 8.0+</span><br>";
} else {
    echo "<span style='color:green'>OK</span><br>";
}

// 2. Check Config File
echo "<h2>2. Config File</h2>";
if (file_exists('config.php')) {
    echo "<span style='color:green'>Found config.php</span><br>";
    include 'config.php';
} else {
    die("<span style='color:red'>CRITICAL: config.php NOT FOUND.</span>");
}

// 3. Check Database Connection
echo "<h2>3. Database Connection</h2>";
if (isset($conn) && $conn instanceof mysqli) {
    if ($conn->connect_error) {
        echo "<span style='color:red'>Connection Failed: " . $conn->connect_error . "</span><br>";
        echo "Tip: Check if MySQL is running in XAMPP. Check if password in config.php matches your setup.<br>";
    } else {
        echo "<span style='color:green'>Database Connected Successfully</span><br>";
        echo "Host: " . $host . "<br>";
        echo "User: " . $user . "<br>";
        echo "DB Name: " . $db . "<br>";

        // 4. Check Tables
        echo "<h2>4. Table Check</h2>";
        $tables = [
            'USER',
            'SERVICEPROVIDER',
            'ROOMLISTING',
            'ROOMAPPLICATION',
            'SERVICEBOOKING',
            'MEALPLAN',
            'MARKETITEM',
            'GROCERYPRICE',
            'BUSROUTE',
            'LOSTFOUND',
            'BUDGETCATEGORY',
            'BUDGETTRANSACTION',
            'MOODENTRY'
        ];

        $missing = [];
        echo "<ul>";
        foreach ($tables as $t) {
            $check = $conn->query("SHOW TABLES LIKE '$t'");
            if ($check && $check->num_rows > 0) {
                echo "<li style='color:green'>$t: Found</li>";
            } else {
                echo "<li style='color:red'>$t: MISSING</li>";
                $missing[] = $t;
            }
        }
        echo "</ul>";

        if (!empty($missing)) {
            echo "<h3 style='color:red'>CRITICAL ERROR: Missing Tables</h3>";
            echo "<p>You have not imported the database structure yet.</p>";
            echo "<strong>SOLUTION:</strong><br>";
            echo "1. Go to <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a><br>";
            echo "2. Select 'findr' database.<br>";
            echo "3. Click 'Import'.<br>";
            echo "4. Upload 'findr_full.sql' from your project folder.<br>";
        } else {
            echo "<h3 style='color:green'>Database Structure OK</h3>";
        }
    }
} else {
    echo "<span style='color:red'>CRITICAL: \$conn variable not active. Check config.php code.</span>";
}

// 5. Session Check
echo "<h2>5. Session Status</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<span style='color:green'>Session Active</span><br>";
    echo "Logged in User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'None') . "<br>";
} else {
    echo "<span style='color:orange'>Session Not Started (Normal if not logged in context)</span><br>";
}
?>