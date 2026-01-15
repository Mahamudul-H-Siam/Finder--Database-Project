<?php
include 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>FindR - Bachelor Assistant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg: #0f172a;
            --bg-soft: #111827;
            --card: #020617;
            --accent: #22c55e;
            --accent-soft: rgba(34, 197, 94, 0.15);
            --accent-secondary: #38bdf8;
            --text: #e5e7eb;
            --muted: #9ca3af;
            --border: #1f2937;
            --danger: #f97373;
            --radius-lg: 12px;
            --radius: 8px;
            --shadow-soft: 0 18px 40px rgba(15, 23, 42, 0.85);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        a {
            color: inherit;
            text-decoration: none;
        }
        /* Navbar */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(20px);
            background: linear-gradient(to right, rgba(15,23,42,0.95), rgba(15,23,42,0.8));
            border-bottom: 1px solid rgba(55,65,81,0.7);
            padding: 0.7rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            font-size: 1.1rem;
        }
        .brand-logo {
            width: 30px;
            height: 30px;
            border-radius: 100%;
            background: radial-gradient(circle at 30% 20%, #bbf7d0, #22c55e 55%, #16a34a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #022c22;
            font-size: 0.95rem;
            box-shadow: 0 0 0 2px rgba(34,197,94,0.3), 0 12px 25px rgba(34,197,94,0.45);
        }
        .brand-subtitle {
            font-size: 0.7rem;
            font-weight: 500;
            color: var(--muted);
            text-transform: uppercase;
        }
        .nav-links {
            display: flex;
            gap: 0.4rem;
            flex: 1;
        }
        .nav-link {
            padding: 0.45rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 999px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 0.3rem;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.18s ease-out;
        }
        .nav-link span.icon {
            font-size: 0.9rem;
            opacity: 0.9;
        }
        .nav-link:hover {
            color: #f9fafb;
            background: rgba(31,41,55,0.9);
            border-color: rgba(55,65,81,0.8);
        }
        .nav-link.active {
            color: #ecfdf5;
            background: rgba(22,163,74,0.12);
            border-color: rgba(34,197,94,0.65);
            box-shadow: 0 0 0 1px rgba(22,163,74,0.35);
        }
        .nav-search {
            flex: 1.2;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            background: rgba(15,23,42,0.9);
            border-radius: 999px;
            border: 1px solid rgba(55,65,81,0.9);
            padding: 0.35rem 0.75rem;
            box-shadow: 0 12px 30px rgba(15,23,42,0.7);
        }
        .nav-search input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            color: var(--text);
            font-size: 0.8rem;
        }
        .nav-search input::placeholder {
            color: #6b7280;
        }
        .nav-search-btn {
            font-size: 0.75rem;
            border-radius: 999px;
            padding: 0.3rem 0.7rem;
            border: none;
            cursor: pointer;
            background: linear-gradient(to right, #22c55e, #16a34a);
            color: #022c22;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            transition: transform 0.08s ease-out, box-shadow 0.08s ease-out;
            box-shadow: 0 8px 18px rgba(22,163,74,0.6);
        }
        .nav-search-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 24px rgba(22,163,74,0.8);
        }
        .nav-right {
            display: flex;
            align-items: center;
            gap: 0.9rem;
        }
        .pill {
            font-size: 0.7rem;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(55,65,81,0.9);
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }
        .avatar {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            background: radial-gradient(circle at 20% 0, #38bdf8, #1d4ed8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #eff6ff;
            box-shadow: 0 0 0 2px rgba(30,64,175,0.6), 0 10px 20px rgba(30,64,175,0.8);
        }
        .badge-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #22c55e;
            box-shadow: 0 0 0 3px rgba(21,128,61,0.7);
        }

        /* Layout */
        .layout {
            display: grid;
            grid-template-columns: 220px minmax(0, 1fr);
            gap: 1.5rem;
            padding: 1.3rem 1.5rem 2rem;
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }
        .sidebar {
            background: rgba(15,23,42,0.9);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(31,41,55,0.9);
            padding: 0.9rem;
            box-shadow: 0 18px 48px rgba(15,23,42,0.8);
            height: fit-content;
        }
        .sidebar-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted);
            margin-bottom: 0.25rem;
        }
        .sidebar-subtitle {
            font-size: 0.75rem;
            color: #e5e7eb;
            margin-bottom: 0.7rem;
        }
        .sidebar-section {
            margin-bottom: 0.9rem;
        }
        .sidebar-label {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .sidebar-links {
            display: grid;
            gap: 0.35rem;
        }
        .sidebar-link {
            font-size: 0.78rem;
            padding: 0.4rem 0.55rem;
            border-radius: 0.6rem;
            border: 1px solid transparent;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.15s ease-out;
            background: rgba(15,23,42,0.75);
        }
        .sidebar-link span {
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .sidebar-link:hover {
            border-color: rgba(55,65,81,0.9);
            color: #e5e7eb;
            background: rgba(17,24,39,0.95);
        }
        .sidebar-link.active {
            background: linear-gradient(to right, rgba(34,197,94,0.18), rgba(16,185,129,0.05));
            border-color: rgba(34,197,94,0.65);
            color: #bbf7d0;
            box-shadow: 0 0 0 1px rgba(16,185,129,0.4);
        }
        .badge-soft {
            font-size: 0.65rem;
            border-radius: 999px;
            padding: 0.15rem 0.35rem;
            background: rgba(31,41,55,0.9);
            color: #9ca3af;
            border: 1px solid rgba(55,65,81,0.9);
        }
        .badge-soft.accent {
            background: rgba(22,163,74,0.22);
            color: #bbf7d0;
            border-color: rgba(34,197,94,0.8);
        }
        .sidebar-footer {
            margin-top: 0.7rem;
            padding-top: 0.7rem;
            border-top: 1px dashed rgba(55,65,81,0.7);
            font-size: 0.72rem;
            color: #9ca3af;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .main {
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
        }

        .hero {
            background: radial-gradient(circle at top left, rgba(34,197,94,0.18), rgba(15,23,42,0.96));
            border-radius: var(--radius-lg);
            border: 1px solid rgba(55,65,81,0.95);
            padding: 1rem 1.1rem;
            box-shadow: var(--shadow-soft);
            display: grid;
            grid-template-columns: minmax(0, 2.1fr) minmax(0, 1.5fr);
            gap: 1rem;
            align-items: center;
        }
        .hero-title {
            font-size: 1.2rem;
            margin-bottom: 0.35rem;
        }
        .gradient-text {
            background: linear-gradient(to right, #bbf7d0, #a5b4fc, #38bdf8);
            -webkit-background-clip: text;
            color: transparent;
        }
        .hero-subtitle {
            font-size: 0.88rem;
            color: #cbd5f5;
            margin-bottom: 0.75rem;
            max-width: 460px;
        }
        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.7rem;
        }
        .btn {
            padding: 0.45rem 0.85rem;
            border-radius: 999px;
            font-size: 0.8rem;
            border: 1px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.12s ease-out;
        }
        .btn-primary {
            background: linear-gradient(to right, #22c55e, #16a34a);
            color: #022c22;
            border-color: rgba(22,163,74,0.5);
            box-shadow: 0 10px 24px rgba(22,163,74,0.9);
            font-weight: 600;
        }
        .btn-outline {
            background: rgba(15,23,42,0.94);
            color: var(--text);
            border-color: rgba(55,65,81,0.95);
        }
        .hero-metrics {
            display: flex;
            gap: 0.6rem;
            flex-wrap: wrap;
            font-size: 0.7rem;
            color: #9ca3af;
        }
        .metric-pill {
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: rgba(15,23,42,0.92);
            border: 1px solid rgba(55,65,81,0.85);
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }
        .hero-right {
            border-radius: 0.95rem;
            border: 1px solid rgba(55,65,81,0.95);
            background: radial-gradient(circle at top, rgba(56,189,248,0.16), rgba(15,23,42,0.96));
            padding: 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .hero-right-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.75rem;
            color: #e5e7eb;
        }
        .hero-right-body {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.45rem;
        }
        .quick-card {
            background: rgba(15,23,42,0.94);
            border-radius: 0.7rem;
            padding: 0.45rem;
            border: 1px solid rgba(55,65,81,0.9);
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            cursor: pointer;
            font-size: 0.7rem;
            transition: all 0.12s ease-out;
        }
        .quick-card-label {
            color: #e5e7eb;
        }
        .quick-card-meta {
            color: #9ca3af;
            font-size: 0.68rem;
        }

        .section {
            background: rgba(15,23,42,0.96);
            border-radius: var(--radius-lg);
            border: 1px solid rgba(31,41,55,0.9);
            padding: 0.9rem 1rem;
            box-shadow: 0 18px 40px rgba(15,23,42,0.85);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.55rem;
        }
        .section-title {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .section-subtitle {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-bottom: 0.45rem;
        }
        .section-actions {
            display: flex;
            gap: 0.4rem;
            font-size: 0.7rem;
        }
        .pill-ghost {
            border-radius: 999px;
            padding: 0.2rem 0.55rem;
            border: 1px solid rgba(55,65,81,0.9);
            color: #9ca3af;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background: rgba(15,23,42,0.9);
            transition: all 0.12s ease-out;
        }

        .grid {
            display: grid;
            gap: 0.65rem;
        }
        .grid-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .grid-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .card {
            background: rgba(15,23,42,0.94);
            border-radius: var(--radius);
            border: 1px solid rgba(31,41,55,0.95);
            padding: 0.6rem;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            font-size: 0.78rem;
            position: relative;
            overflow: hidden;
        }
        .card-title {
            font-size: 0.8rem;
            color: #e5e7eb;
        }
        .card-meta {
            font-size: 0.7rem;
            color: #9ca3af;
        }

        footer {
            padding: 0.85rem 1.5rem 1.3rem;
            font-size: 0.7rem;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
            max-width: 1280px;
            margin: 0 auto;
            width: 100%;
        }

        @media (max-width: 900px) {
            .layout {
                grid-template-columns: minmax(0, 1fr);
            }
        }
    </style>
</head>
<body>
<header class="navbar">
    <div class="brand">
        <div class="brand-logo">F</div>
        <div>
            <div>FindR</div>
            <div class="brand-subtitle">Bachelor assistant</div>
        </div>
    </div>
    <nav class="nav-links">
        <div class="nav-link active" data-section="dashboard"><span class="icon">🏠</span><span>Home</span></div>
        <div class="nav-link" data-section="rooms"><span class="icon">🛏️</span><span>Rooms</span></div>
        <div class="nav-link" data-section="services"><span class="icon">🧹</span><span>Services</span></div>
        <div class="nav-link" data-section="market"><span class="icon">🛒</span><span>Market</span></div>
        <div class="nav-link" data-section="budget"><span class="icon">💰</span><span>Budget</span></div>
        <div class="nav-link" data-section="more"><span class="icon">➕</span><span>More</span></div>
    </nav>
    <div class="nav-search">
        <span class="icon" style="font-size:0.8rem;color:#6b7280;">🔍</span>
        <input id="globalSearch" type="text" placeholder="Search rooms, services, items...">
        <button class="nav-search-btn" id="searchBtn">SEARCH</button>
    </div>
    <div class="nav-right">
        <div class="pill">
            <span class="badge-dot"></span>
            <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
        </div>
        <div class="avatar" title="Profile">
            AS
        </div>
        <a href="logout.php" style="font-size:0.75rem;">Logout</a>
    </div>
</header>

<main class="layout">
    <aside class="sidebar">
        <div class="sidebar-title">Overview</div>
        <div class="sidebar-subtitle">Quick access for student role</div>

        <div class="sidebar-section">
            <div class="sidebar-label">Quick actions</div>
            <div class="sidebar-links">
                <div class="sidebar-link active" data-section="rooms">
                    <span><span>🛏️</span><span>Find room & roommates</span></span>
                    <span class="badge-soft accent">Live</span>
                </div>
                <div class="sidebar-link" data-section="services">
                    <span><span>🧹</span><span>Book services</span></span>
                    <span class="badge-soft">Today 2</span>
                </div>
                <div class="sidebar-link" data-section="market">
                    <span><span>🛒</span><span>Marketplace</span></span>
                    <span class="badge-soft">New</span>
                </div>
                <div class="sidebar-link" data-section="budget">
                    <span><span>💰</span><span>Budget tracker</span></span>
                    <span class="badge-soft">This month</span>
                </div>
                <div class="sidebar-link" data-section="mood">
                    <span><span>😊</span><span>Mood log</span></span>
                    <span class="badge-soft">Daily</span>
                </div>
            </div>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-label">Modules</div>
            <div class="sidebar-links">
                <div class="sidebar-link" data-section="lostfound">
                    <span><span>📦</span><span>Lost & found</span></span>
                </div>
                <div class="sidebar-link" data-section="utilities">
                    <span><span>🚌</span><span>Utilities & routes</span></span>
                </div>
            </div>
        </div>

        <div class="sidebar-footer">
            <span>Phase 1 tables USER, ROOMLISTING, SERVICEBOOKING, MARKETITEM, BUDGET, MOOD</span>
            <span>v0.1</span>
        </div>
    </aside>

    <section class="main">
        <!-- HERO -->
        <section class="hero" id="dashboard">
            <div>
                <h1 class="hero-title">
                    Welcome back, <span class="gradient-text">Student</span>
                </h1>
                <p class="hero-subtitle">
                    One place to manage your room, services, expenses, and daily mood while studying in the city.
                </p>
                <div class="hero-actions">
                    <button class="btn btn-primary" data-section="rooms"><span>Find a room now</span></button>
                    <button class="btn btn-outline" data-section="services"><span>Book cleaner / van</span></button>
                    <button class="btn btn-outline" data-section="market"><span>Browse used items</span></button>
                </div>
                <div class="hero-metrics">
                    <div class="metric-pill"><span>📄</span><span>3 active applications</span></div>
                    <div class="metric-pill"><span>💳</span><span>BDT 12,500 spent this month</span></div>
                    <div class="metric-pill"><span>📈</span><span>Mood trending up last 7 days</span></div>
                </div>
            </div>
            <div class="hero-right">
                <div class="hero-right-header">
                    <span>Today’s shortcuts</span>
                    <span class="badge-soft">Click any to jump</span>
                </div>
                <div class="hero-right-body">
                    <div class="quick-card" data-section="rooms">
                        <div class="quick-card-label">Rooms near campus</div>
                        <div class="quick-card-meta">8 listings under BDT 5k</div>
                    </div>
                    <div class="quick-card" data-section="services">
                        <div class="quick-card-label">Cleaner for weekend</div>
                        <div class="quick-card-meta">From BDT 500</div>
                    </div>
                    <div class="quick-card" data-section="market">
                        <div class="quick-card-label">Used study desk</div>
                        <div class="quick-card-meta">4 nearby offers</div>
                    </div>
                    <div class="quick-card" data-section="budget">
                        <div class="quick-card-label">Update expenses</div>
                        <div class="quick-card-meta">2 bills pending</div>
                    </div>
                    <div class="quick-card" data-section="mood">
                        <div class="quick-card-label">Log today’s mood</div>
                        <div class="quick-card-meta">Takes 10 seconds</div>
                    </div>
                    <div class="quick-card" data-section="lostfound">
                        <div class="quick-card-label">Lost ID card</div>
                        <div class="quick-card-meta">Check new posts</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ROOMS SECTION -->
        <section class="section" id="rooms">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <span class="icon">🛏️</span>
                        <span>Rooms & roommates</span>
                    </div>
                    <p class="section-subtitle">
                        Browse tolet listings, hostel beds, and roommate wanted posts in your area.
                    </p>
                </div>
                <div class="section-actions">
                    <button class="pill-ghost"><span>Post listing</span></button>
                    <button class="pill-ghost"><span>My applications</span></button>
                </div>
            </div>

            <div class="grid grid-3" id="roomCards">
                <?php
                $areaFilter = isset($_GET['area']) ? $_GET['area'] : '';
                $sqlRooms = "SELECT r.RoomID, r.Title, r.LocationArea, r.RentAmount, r.GenderPreference,
                                    r.UtilitiesIncluded, r.Status
                             FROM ROOMLISTING r
                             WHERE r.Status = 'Available'";
                if ($areaFilter !== '') {
                    $sqlRooms .= " AND r.LocationArea LIKE ?";
                    $stmt = $conn->prepare($sqlRooms);
                    $like = "%".$areaFilter."%";
                    $stmt->bind_param("s", $like);
                } else {
                    $stmt = $conn->prepare($sqlRooms);
                }
                if ($stmt && $stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($result) {
                        while ($row = $result->fetch_assoc()):
                ?>
                <div class="card">
                    <div class="card-title"><?php echo htmlspecialchars($row['Title']); ?></div>
                    <div class="card-meta">
                        BDT <?php echo $row['RentAmount']; ?> •
                        <?php echo htmlspecialchars($row['LocationArea']); ?> •
                        <?php echo $row['GenderPreference']; ?>
                        <?php if ($row['UtilitiesIncluded']): ?> • Utilities included<?php endif; ?>
                    </div>
                </div>
                <?php
                        endwhile;
                    }
                    $stmt->close();
                } else {
                    echo "<p>Could not load room listings.</p>";
                }
                ?>
            </div>
        </section>

        <!-- SERVICES SECTION (still frontend only for now) -->
        <section class="section" id="services" style="display:none;">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <span class="icon">🧹</span>
                        <span>Services cleaner, van, tuition</span>
                    </div>
                    <p class="section-subtitle">
                        Book trusted service providers for cleaning, shifting, and private tuition.
                    </p>
                </div>
                <div class="section-actions">
                    <button class="pill-ghost"><span>My bookings</span></button>
                </div>
            </div>
            <!-- keep your original service form/cards here if needed -->
            <p style="font-size:0.8rem;color:#9ca3af;">(Service booking backend to be added.)</p>
        </section>

        <!-- MARKETPLACE SECTION -->
        <section class="section" id="market" style="display:none;">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <span class="icon">🛒</span>
                        <span>Marketplace secondhand</span>
                    </div>
                    <p class="section-subtitle">
                        Buy and sell books, furniture, electronics, and more with nearby students.
                    </p>
                </div>
                <div class="section-actions">
                    <a class="pill-ghost" href="marketplace_add.php"><span>Post item</span></a>
                    <a class="pill-ghost" href="marketplace_list.php"><span>View all</span></a>
                </div>
            </div>

            <div class="grid grid-3">
                <div class="card">
                    <div class="card-title">Sample item</div>
                    <div class="card-meta">Connect with marketplace pages to see real data.</div>
                </div>
            </div>
        </section>

        <!-- BUDGET, MOOD, LOSTFOUND, UTILITIES, MORE -->
        <section class="section" id="budget" style="display:none;">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <span class="icon">💰</span>
                        <span>Budget & expenses</span>
                    </div>
                    <p class="section-subtitle">
                        Track monthly spending by category and keep room, food, and transport under control.
                    </p>
                </div>
            </div>
            <p style="font-size:0.8rem;color:#9ca3af;">(Budget backend will use BUDGETCATEGORY and BUDGETTRANSACTION.)</p>
        </section>

        <section class="section" id="mood" style="display:none;">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <span class="icon">😊</span>
                        <span>Mood & mental health</span>
                    </div>
                    <p class="section-subtitle">
                        Track daily mood in a simple way and reflect on patterns across busy weeks.
                    </p>
                </div>
            </div>
            <p style="font-size:0.8rem;color:#9ca3af;">(Mood backend will use MOODENTRY.)</p>
        </section>

        <section class="section" id="lostfound" style="display:none;">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <span class="icon">📦</span>
                        <span>Lost & found</span>
                    </div>
                    <p class="section-subtitle">
                        Report lost items or check found posts from other students around campus.
                    </p>
                </div>
            </div>
            <p style="font-size:0.8rem;color:#9ca3af;">(Lost & found backend will use LOSTFOUND.)</p>
        </section>

        <section class="section" id="utilities" style="display:none;">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <span class="icon">🚌</span>
                        <span>Utilities & routes</span>
                    </div>
                    <p class="section-subtitle">
                        Check daily grocery prices and local bus routes and fares for better planning.
                    </p>
                </div>
            </div>
            <p style="font-size:0.8rem;color:#9ca3af;">(Utilities backend will use GROCERYPRICE and BUSROUTE.)</p>
        </section>

        <section class="section" id="more" style="display:none;">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <span class="icon">➕</span>
                        <span>More modules</span>
                    </div>
                    <p class="section-subtitle">
                        Jump directly to any available module of the FindR bachelor assistant platform.
                    </p>
                </div>
            </div>
            <p style="font-size:0.8rem;color:#9ca3af;">Modules will expand as backend is implemented.</p>
        </section>
    </section>
</main>

<footer>
    <span>FindR bachelor assistant UI hooked to backend tables.</span>
    <span>Design mapped from schema document.</span>
</footer>

<script>
const sectionIds = ["dashboard","rooms","services","market","budget","mood","lostfound","utilities","more"];

function showSection(id) {
    sectionIds.forEach(sec => {
        const el = document.getElementById(sec);
        if (el) {
            el.style.display = (sec === id || (id === "dashboard" && sec === "dashboard")) ? "block" : "none";
            if (sec === "rooms" && id === "dashboard") {
                el.style.display = "none";
            }
        }
    });

    document.querySelectorAll(".nav-link").forEach(link => {
        link.classList.toggle("active", link.dataset.section === id || (id === "dashboard" && link.dataset.section === "dashboard"));
    });

    document.querySelectorAll("[data-section]").forEach(el => {
        el.addEventListener("click", () => {
            const target = el.dataset.section;
            if (sectionIds.includes(target)) showSection(target);
        });
    });
}

document.querySelectorAll(".nav-link").forEach(link => {
    link.addEventListener("click", () => {
        const target = link.dataset.section;
        if (sectionIds.includes(target)) showSection(target);
    });
});

const searchBtn = document.getElementById("searchBtn");
const globalSearch = document.getElementById("globalSearch");
if (searchBtn && globalSearch) {
    searchBtn.addEventListener("click", () => {
        const value = globalSearch.value.trim().toLowerCase();
        if (!value) return;
        if (value.includes("room") || value.includes("hostel")) showSection("rooms");
        else if (value.includes("clean") || value.includes("van") || value.includes("tuition")) showSection("services");
        else if (value.includes("book") || value.includes("table") || value.includes("monitor")) showSection("market");
        else if (value.includes("budget") || value.includes("expense")) showSection("budget");
        else if (value.includes("mood") || value.includes("sad") || value.includes("happy")) showSection("mood");
        else showSection("dashboard");
    });
}

showSection("dashboard");
</script>
</body>
</html>
