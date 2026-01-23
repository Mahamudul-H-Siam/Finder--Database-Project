<?php
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'];

// --- ROLE SPECIFIC DATA FETCHING ---

// 1. STUDENT DATA
$myRoomApps = [];
$myServiceBookings = [];
$budgetAlert = false;

if ($role === 'Student') {
    // Room Apps
    $stmt = $conn->prepare("SELECT ra.Status, r.Title, u.FullName as OwnerName FROM ROOMAPPLICATION ra JOIN ROOMLISTING r ON ra.RoomID = r.RoomID JOIN USER u ON r.OwnerID = u.UserID WHERE ra.ApplicantID = ? ORDER BY ra.AppliedAt DESC LIMIT 3");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $myRoomApps = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Service Bookings
    $stmt = $conn->prepare("SELECT sb.BookingID, sb.BookingStatus, sb.Date, sp.BusinessName, sb.ServiceType FROM SERVICEBOOKING sb JOIN SERVICEPROVIDER sp ON sb.ProviderID = sp.ProviderID WHERE sb.UserID = ? ORDER BY sb.Date DESC LIMIT 3");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $myServiceBookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Budget Alert
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    $stmt = $conn->prepare("
        SELECT bc.Name, bc.MonthlyLimit, SUM(bt.Amount) as Spent 
        FROM BUDGETCATEGORY bc 
        JOIN BUDGETTRANSACTION bt ON bc.CategoryID = bt.CategoryID 
        WHERE bc.UserID = ? AND bt.Type = 'Expense' AND bt.Date BETWEEN ? AND ?
        GROUP BY bc.CategoryID HAVING Spent > (bc.MonthlyLimit * 0.9)
    ");
    $stmt->bind_param("iss", $userId, $startDate, $endDate);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0)
        $budgetAlert = true;
    $stmt->close();
}

// 2. OWNER DATA
$pendingAppsCount = 0;
$activeRoomsCount = 0;

if ($role === 'Owner') {
    // Pending Apps
    $stmt = $conn->prepare("SELECT COUNT(*) FROM ROOMAPPLICATION ra JOIN ROOMLISTING r ON ra.RoomID = r.RoomID WHERE r.OwnerID = ? AND ra.Status = 'Pending'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($pendingAppsCount);
    $stmt->fetch();
    $stmt->close();

    // Active Rooms
    $stmt = $conn->prepare("SELECT COUNT(*) FROM ROOMLISTING WHERE OwnerID = ? AND Status = 'Available'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($activeRoomsCount);
    $stmt->fetch();
    $stmt->close();
}

// 3. SERVICE PROVIDER DATA
$pendingJobsCount = 0;
$avgRating = 0;
$totalReviews = 0;
$activeServicesCount = 0;

if ($role === 'ServiceProvider') {
    // Pending Jobs
    $stmt = $conn->prepare("SELECT COUNT(*) FROM SERVICEBOOKING WHERE ProviderID = ? AND BookingStatus = 'Pending'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($pendingJobsCount);
    $stmt->fetch();
    $stmt->close();

    // Rating
    $stmt = $conn->prepare("SELECT AverageRating, TotalReviews FROM SERVICEPROVIDER WHERE ProviderID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($avgRating, $totalReviews);
    $stmt->fetch();
    $stmt->close();

    // Active Services Count
    $stmt = $conn->prepare("SELECT COUNT(*) FROM PROVIDER_SERVICES WHERE ProviderID = ? AND IsActive = 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->bind_result($activeServicesCount);
        $stmt->fetch();
        $stmt->close();
    }
}

// 4. ADMIN DATA
$pendingItemsCount = 0;
$totalUsersCount = 0;
$pendingProvidersCount = 0;

if ($role === 'Admin') {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM MARKETITEM WHERE Status = 'Pending'");
    $stmt->execute();
    $stmt->bind_result($pendingItemsCount);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM USER");
    $stmt->execute();
    $stmt->bind_result($totalUsersCount);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) FROM SERVICEPROVIDER WHERE IsApproved = 0");
    $stmt->execute();
    $stmt->bind_result($pendingProvidersCount);
    $stmt->fetch();
    $stmt->close();
}

// --- GENERAL DATA (Recent Rooms, Mood, etc) ---
// Only for students/others, not needed for providers if simplified
$budgetSummary = [];
if ($role === 'Student') {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    $stmt = $conn->prepare("SELECT bc.Name, bc.MonthlyLimit, COALESCE(SUM(bt.Amount),0) as Spent FROM BUDGETCATEGORY bc LEFT JOIN BUDGETTRANSACTION bt ON bc.CategoryID = bt.CategoryID AND bt.Type='Expense' AND bt.Date BETWEEN ? AND ? WHERE bc.UserID = ? GROUP BY bc.CategoryID LIMIT 4");
    $stmt->bind_param("ssi", $startDate, $endDate, $userId);
    $stmt->execute();
    $budgetSummary = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$moodHistory = [];
if ($role === 'Student') {
    $stmt = $conn->prepare("SELECT MoodLevel, MoodLabel, Note, CreatedAt FROM MOODENTRY WHERE UserID = ? ORDER BY CreatedAt DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc())
            $moodHistory[] = $row;
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --primary: #3b82f6;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --accent-rose: #f43f5e;
            --accent-purple: #8b5cf6;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* Navbar */
        .navbar {
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-right {
            display: flex;
            align-items: center;
        }

        .nav-right a {
            color: var(--text-muted);
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-right a:hover {
            color: white;
        }

        .btn-logout {
            background: #334155;
            padding: 0.4rem 1rem;
            border-radius: 99px;
            color: white !important;
        }

        /* Hero */
        .hero {
            padding: 2.5rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .welcome {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .role-badge {
            display: inline-block;
            font-size: 0.8rem;
            background: #334155;
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
            vertical-align: middle;
            margin-left: 0.5rem;
            color: #cbd5e1;
        }

        /* Role Specific Banner */
        .role-banner {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.8));
            border: 1px solid var(--border);
            padding: 1.5rem;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
        }

        .stat-val {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.2rem;
        }

        .stat-lbl {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .stat-link {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
        }

        /* Main Grid */
        .grid-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            padding: 0 2rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        @media(max-width:900px) {
            .grid-container {
                grid-template-columns: 1fr;
            }
        }

        .section-header {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.2rem;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-3px);
            border-color: #64748b;
        }

        .c-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: inline-block;
            padding: 0.5rem;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
        }

        .c-title {
            font-weight: 600;
            font-size: 1.05rem;
            margin-bottom: 0.3rem;
        }

        .c-sub {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.4;
            flex: 1;
        }

        .c-btn {
            display: inline-block;
            margin-top: 1rem;
            text-decoration: none;
            color: white;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-align: center;
            background: #334155;
            transition: background 0.2s;
        }

        .c-btn:hover {
            background: #475569;
        }

        /* Listing/Item Styles */
        .list-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .list-item:last-child {
            border: none;
        }

        .li-main {
            font-weight: 500;
            font-size: 0.95rem;
        }

        .li-sub {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        .status {
            font-size: 0.75rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }

        .st-green {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
        }

        .st-yellow {
            background: rgba(251, 191, 36, 0.15);
            color: #fbbf24;
        }

        .st-blue {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
        }

        .alert-box {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #fca5a5;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="brand">FindR</div>
        <div class="nav-right">
            <?php if ($role === 'Admin'): ?>
                <a href="admin_manage_posts.php" style="color:#fbbf24">★ Manage Posts</a>
                <a href="admin_manage_services.php" style="color:#fbbf24">★ Manage Services</a>
                <a href="admin_manage_homes.php" style="color:#fbbf24">★ Manage Homes</a>
            <?php endif; ?>

            <a href="notifications.php" title="Notifications" class="nav-link-notif" style="position:relative;">
                🔔 Alerts
                <span id="notif-badge" style="
                    display:none;
                    position:absolute;
                    top:-5px;
                    right:-8px;
                    background:#ef4444;
                    color:white;
                    font-size:0.7rem;
                    padding:2px 6px;
                    border-radius:99px;
                    border:2px solid #0f172a;
                    font-weight:700;
                ">0</span>
            </a>

            <a href="messages.php" title="Messages">💬 Messages</a>
            <a href="profile.php" title="Profile">👤 Profile</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <script>
        function updateNotifCount() {
            fetch('api_notifications.php')
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notif-badge');
                    if (data.count > 0) {
                        badge.style.display = 'block';
                        badge.innerText = data.count > 9 ? '9+' : data.count;
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(err => console.error(err));
        }

        // Check every 10 seconds
        setInterval(updateNotifCount, 10000);
        // Check immediately
        updateNotifCount();
    </script>

    <div class="hero">
        <div class="welcome">
            Hello, <?php echo htmlspecialchars($fullName); ?>
            <span class="role-badge"><?php echo $role; ?></span>
        </div>
        <div style="color:#94a3b8; margin-top:0.5rem;">Here is your personalized overview.</div>
    </div>

    <div class="grid-container">

        <!-- LEFT COLUMN (Main Content) -->
        <div>

            <!-- ROLE SPECIFIC SECTION -->
            <?php if ($role === 'Student'): ?>
                <?php if ($budgetAlert): ?>
                    <div class="alert-box">
                        <div style="font-size:1.5rem">⚠️</div>
                        <div>
                            <strong>Budget Alert!</strong><br>
                            You have exceeded 90% of your limit in one or more categories.
                            <a href="budget.php"
                                style="color:white; text-decoration:underline; display:block; margin-top:0.3rem">Check
                                Budget</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="section-header">🔍 My Activities</div>
                <div class="role-banner">
                    <div class="stat-box">
                        <div style="font-weight:600; color:#cbd5e1; margin-bottom:1rem;">Recent Room Applications</div>
                        <?php if (empty($myRoomApps)): ?>
                            <div style="color:#64748b; font-style:italic;">No applications sent.</div>
                        <?php else: ?>
                            <?php foreach ($myRoomApps as $app): ?>
                                <div class="list-item">
                                    <div>
                                        <div class="li-main"><?php echo htmlspecialchars($app['Title']); ?></div>
                                        <div class="li-sub"><?php echo htmlspecialchars($app['OwnerName']); ?></div>
                                    </div>
                                    <div
                                        class="status <?php echo ($app['Status'] == 'Accepted' ? 'st-green' : ($app['Status'] == 'Rejected' ? 'st-red' : 'st-yellow')); ?>">
                                        <?php echo ($app['Status'] == 'Pending' ? 'Processing' : $app['Status']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <a href="room_list.php" class="stat-link">Browse More Rooms →</a>
                    </div>

                    <div class="stat-box">
                        <div style="font-weight:600; color:#cbd5e1; margin-bottom:1rem;">Service Bookings</div>
                        <?php if (empty($myServiceBookings)): ?>
                            <div style="color:#64748b; font-style:italic;">No services booked.</div>
                        <?php else: ?>
                            <?php foreach ($myServiceBookings as $bk): ?>
                                <div class="list-item">
                                    <div>
                                        <div class="li-main"><?php echo htmlspecialchars($bk['ServiceType']); ?></div>
                                        <div class="li-sub"><?php echo htmlspecialchars($bk['BusinessName']); ?></div>
                                    </div>
                                    <div
                                        class="status <?php echo ($bk['BookingStatus'] == 'Confirmed' ? 'st-green' : 'st-yellow'); ?>">
                                        <?php echo ($bk['BookingStatus'] == 'Pending' ? 'Processing' : $bk['BookingStatus']); ?>
                                    </div>
                                    <?php if ($bk['BookingStatus'] === 'Completed'): ?>
                                        <a href="rate_service.php?booking_id=<?php echo $bk['BookingID']; ?>"
                                            style="font-size:0.75rem; background:#fbbf24; color:#451a03; padding:0.2rem 0.5rem; border-radius:4px; text-decoration:none; margin-left:0.5rem;">Rate</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <a href="service_list.php" class="stat-link">Find Services →</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'Owner'): ?>
                <div class="section-header">🏢 Property Management</div>
                <div class="role-banner">
                    <div class="stat-box">
                        <div class="stat-lbl">Pending Applications</div>
                        <div class="stat-val" style="color:#fbbf24"><?php echo $pendingAppsCount; ?></div>
                        <a href="owner_applications.php" class="stat-link">Review Applications →</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-lbl">Active Listings</div>
                        <div class="stat-val" style="color:#4ade80"><?php echo $activeRoomsCount; ?></div>
                        <a href="room_new.php" class="stat-link">Post New Listing →</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'ServiceProvider'): ?>
                <div class="section-header">🛠️ Business Overview</div>
                <div class="role-banner">
                    <div class="stat-box">
                        <div class="stat-lbl">Job Requests</div>
                        <div class="stat-val" style="color:#fbbf24"><?php echo $pendingJobsCount; ?></div>
                        <a href="provider_bookings.php" class="stat-link">Manage Jobs →</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-lbl">Active Services</div>
                        <div class="stat-val" style="color:#4ade80"><?php echo $activeServicesCount; ?></div>
                        <a href="service_add.php" class="stat-link">Manage Services →</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-lbl">Average Rating</div>
                        <div class="stat-val" style="color:#f43f5e">★ <?php echo number_format($avgRating, 1); ?></div>
                        <div class="li-sub"><?php echo $totalReviews; ?> reviews</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'Admin'): ?>
                <div class="section-header">🛡️ Admin Dashboard</div>
                <div class="role-banner">
                    <div class="stat-box">
                        <div class="stat-lbl">Pending Items</div>
                        <div class="stat-val" style="color:#fbbf24"><?php echo $pendingItemsCount; ?></div>
                        <a href="admin_manage_posts.php?status=Pending" class="stat-link">Review Items →</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-lbl">Total Users</div>
                        <div class="stat-val" style="color:#4ade80"><?php echo $totalUsersCount; ?></div>
                        <a href="admin_portal.php" class="stat-link">View All →</a>
                    </div>
                    <div class="stat-box">
                        <div class="stat-lbl">Pending Providers</div>
                        <div class="stat-val" style="color:#f43f5e"><?php echo $pendingProvidersCount; ?></div>
                        <a href="admin_portal.php" class="stat-link">Approve Providers →</a>
                    </div>
                </div>

                <div class="section-header" style="margin-top:2rem">⚡ Quick Actions</div>
                <div class="card-grid">
                    <div class="card">
                        <div class="c-icon">🛍️</div>
                        <div class="c-title">Manage Posts</div>
                        <div class="c-sub">Approve, decline, or delete marketplace items posted by students.</div>
                        <a href="admin_manage_posts.php" class="c-btn" style="background:rgba(244,63,94,0.2); color:#f43f5e;">Manage Posts</a>
                    </div>

                    <div class="card">
                        <div class="c-icon">🛠️</div>
                        <div class="c-title">Manage Services</div>
                        <div class="c-sub">Approve service providers and their individual service offerings.</div>
                        <a href="admin_manage_services.php" class="c-btn" style="background:rgba(59,130,246,0.2); color:#60a5fa;">Manage Services</a>
                    </div>

                    <div class="card">
                        <div class="c-icon">🏠</div>
                        <div class="c-title">Manage Homes</div>
                        <div class="c-sub">Review and approve room listings posted by homeowners.</div>
                        <a href="admin_manage_homes.php" class="c-btn" style="background:rgba(34,197,94,0.2); color:#4ade80;">Manage Homes</a>
                    </div>

                    <div class="card">
                        <div class="c-icon">👥</div>
                        <div class="c-title">User Management</div>
                        <div class="c-sub">View all users, block/unblock accounts, and manage permissions.</div>
                        <a href="admin_portal.php" class="c-btn" style="background:rgba(139,92,246,0.2); color:#a78bfa;">Admin Portal</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ALL CATEGORIES / MODULES (HIDDEN FOR SERVICE PROVIDER, OWNER & ADMIN) -->
            <?php if ($role === 'Student'): ?>
                <div class="section-header">🚀 Explore FindR</div>
                <div class="card-grid">
                    <div class="card">
                        <div class="c-title">🏠 Rooms & Housing</div>
                        <div class="c-sub">Find availabl rooms and hostel beds near you.</div>
                        <a href="room_list.php" class="c-btn" style="background:rgba(59,130,246,0.2); color:#60a5fa;">Browse
                            Rooms</a>
                    </div>

                    <div class="card">
                        <div class="c-title">🛍️ Marketplace</div>
                        <div class="c-sub">Buy and sell furniture, books, and gadgets.</div>
                        <a href="marketplace_list.php" class="c-btn"
                            style="background:rgba(244,63,94,0.2); color:#f43f5e;">Go to Market</a>
                    </div>

                    <div class="card">
                        <div class="c-title">🛠️ Services</div>
                        <div class="c-sub">Book cleaners, vans, and tutors easily.</div>
                        <a href="service_list.php" class="c-btn" style="background:rgba(34,197,94,0.2); color:#4ade80;">Find
                            Services</a>
                    </div>

                    <div class="card">
                        <div class="c-title">🚌 Utilities</div>
                        <div class="c-sub">Check bus routes, ticket prices, and lost & found.</div>
                        <div style="margin-top:auto; display:flex; gap:0.5rem; flex-wrap:wrap;">
                            <a href="utilities.php" class="c-btn"
                                style="font-size:0.75rem; padding:0.3rem 0.6rem;">Bus/Prices</a>
                            <a href="lostfound_list.php" class="c-btn"
                                style="font-size:0.75rem; padding:0.3rem 0.6rem;">Lost&Found</a>
                            <a href="meal_list.php" class="c-btn"
                                style="font-size:0.75rem; padding:0.3rem 0.6rem;">Meals</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- RIGHT COLUMN (Sidebar Stats) -->
        <div>
            <!-- Budget -->
            <?php if ($role === 'Student'): ?>
                <div class="card" style="margin-bottom:1.5rem;">
                    <div class="c-title" style="display:flex; justify-content:space-between;">
                        <span>💰 Budget</span>
                        <span style="font-size:0.8rem; color:#94a3b8; font-weight:400">This Month</span>
                    </div>
                    <div style="margin-top:1rem;">
                        <?php if (empty($budgetSummary)): ?>
                            <div style="color:#64748b; font-size:0.9rem;">No data yet.</div>
                        <?php else: ?>
                            <?php foreach ($budgetSummary as $b): ?>
                                <div style="margin-bottom:0.8rem;">
                                    <div
                                        style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.2rem;">
                                        <span><?php echo $b['Name']; ?></span>
                                        <span><?php echo number_format($b['Spent']); ?> / <?php echo $b['MonthlyLimit']; ?></span>
                                    </div>
                                    <div style="height:6px; background:#334155; border-radius:4px; overflow:hidden;">
                                        <?php $pct = ($b['MonthlyLimit'] > 0) ? ($b['Spent'] / $b['MonthlyLimit']) * 100 : 0; ?>
                                        <div
                                            style="height:100%; width:<?php echo min($pct, 100); ?>%; background:<?php echo ($pct > 90 ? '#ef4444' : '#8b5cf6'); ?>">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="budget.php" class="c-btn" style="background:#8b5cf6; margin-top:1rem;">Manage Budget</a>
                </div>

                <!-- Mood (STUDENT ONLY) -->
                <div class="card">
                    <div class="c-title">😊 Mood Tracker</div>
                    <div style="margin-top:0.5rem;">
                        <?php if (empty($moodHistory)): ?>
                            <div style="color:#64748b; font-size:0.9rem;">How are you feeling?</div>
                        <?php else: ?>
                            <?php
                            $latest = $moodHistory[0];
                            $emojis = [1 => '😠', 2 => '😢', 3 => '😂', 4 => '🙂', 5 => '🤩', 6 => '🙂', 7 => '😌', 8 => '😎', 9 => '🥳', 10 => '😇'];
                            // Simple mapping or fallback
                            $displayEmoji = $latest['MoodLabel'] ? '📝' : ($emojis[$latest['MoodLevel']] ?? '😐');
                            ?>
                            <div style="text-align:center; padding:1rem;">
                                <div style="font-size:2rem; margin-bottom:0.5rem;">Mood Logged</div>
                                <div style="color:#cbd5e1; font-style:italic;">
                                    "<?php echo htmlspecialchars($latest['Note'] ?: $latest['MoodLabel']); ?>"
                                </div>
                                <div style="font-size:0.75rem; color:#64748b; margin-top:0.5rem;">
                                    <?php echo date('M d', strtotime($latest['CreatedAt'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="mood.php" class="c-btn" style="background:#fbbf24; color:#451a03;">Log Mood</a>
                </div>
            <?php endif; ?>

        </div>

    </div>

</body>

</html>