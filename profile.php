<?php
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$msg = "";
$error = "";

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $bio = trim($_POST['bio']);
    $country = trim($_POST['country']);

    $stmt = $conn->prepare("UPDATE USER SET Bio = ?, Country = ? WHERE UserID = ?");
    $stmt->bind_param("ssi", $bio, $country, $userId);
    if ($stmt->execute()) {
        $msg = "Profile updated successfully!";
    } else {
        $error = "Error updating profile.";
    }
    $stmt->close();
}

// Fetch User Data
$stmt = $conn->prepare("SELECT FullName, Email, Role, CreatedAt, Bio, Country FROM USER WHERE UserID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch Points & Stats
$points = ['TotalPoints' => 0, 'TierLevel' => 'Bronze', 'StreakDays' => 0];
$pStmt = $conn->prepare("SELECT TotalPoints, TierLevel, StreakDays FROM USER_POINTS WHERE UserID = ?");
$pStmt->bind_param("i", $userId);
$pStmt->execute();
$res = $pStmt->get_result();
if ($row = $res->fetch_assoc())
    $points = $row;
$pStmt->close();

// Fetch Badges
$badges = [];
$bStmt = $conn->prepare("SELECT BadgeName, BadgeDescription, EarnedAt FROM USER_BADGES WHERE UserID = ? ORDER BY EarnedAt DESC");
$bStmt->bind_param("i", $userId);
$bStmt->execute();
$bRes = $bStmt->get_result();
while ($row = $bRes->fetch_assoc())
    $badges[] = $row;
$bStmt->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Profile - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #fbbf24;
            --border: #334155;
            --success: #22c55e;
            --error: #ef4444;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem 1rem;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .home-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: white;
            text-decoration: none;
            font-size: 1.4rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }

        .profile-header {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            color: #451a03;
        }

        .user-info h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2rem;
        }

        .user-info p {
            margin: 0;
            color: var(--text-muted);
        }

        .role-badge {
            display: inline-block;
            background: rgba(251, 191, 36, 0.1);
            color: var(--accent);
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media(min-width: 900px) {
            .grid {
                grid-template-columns: 2fr 1fr;
            }
        }

        .card {
            background: rgba(30, 41, 59, 0.6);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        h2 {
            margin: 0 0 1.5rem 0;
            font-size: 1.3rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--border);
        }

        .stat-val {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
        }

        .stat-lbl {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        input,
        textarea {
            width: 100%;
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: white;
            padding: 1rem;
            font-family: inherit;
            box-sizing: border-box;
        }

        input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .btn {
            background: var(--accent);
            color: #0f172a;
            font-weight: 700;
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            width: 100%;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .badge-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
            gap: 1rem;
        }

        .badge-item {
            text-align: center;
            background: rgba(255, 255, 255, 0.03);
            padding: 1rem;
            border-radius: 12px;
        }

        .b-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .b-name {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .alert.success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid var(--success);
            color: #4ade80;
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid var(--error);
            color: #f87171;
        }

        .link-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .link-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            color: var(--text);
            text-decoration: none;
            transition: 0.2s;
        }

        .link-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .l-icon {
            margin-right: 1rem;
            font-size: 1.2rem;
        }
    </style>
</head>

<body>

    <div class="container">
        <a href="index.php" class="home-btn">🏠</a>

        <?php if ($msg): ?>
            <div class="alert success">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="avatar">
                <?php echo strtoupper(substr($user['FullName'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <h1>
                    <?php echo htmlspecialchars($user['FullName']); ?>
                </h1>
                <p>
                    <?php echo htmlspecialchars($user['Email']); ?>
                </p>
                <span class="role-badge">
                    <?php echo htmlspecialchars($user['Role']); ?>
                </span>
                <span class="role-badge" style="background:rgba(59,130,246,0.1); color:#60a5fa; margin-left:0.5rem;">
                    ID: <?php echo $userId; ?>
                </span>
                <p style="margin-top:0.5rem; font-size:0.85rem;">Member since
                    <?php echo date('M Y', strtotime($user['CreatedAt'])); ?>
                </p>
            </div>
        </div>

        <div class="grid">
            <div class="main-col">
                <?php if ($user['Role'] === 'Student'): ?>
                    <div class="card">
                        <h2>Your Stats</h2>
                        <div class="stats-row">
                            <div class="stat-box">
                                <div class="stat-val"><?php echo $points['TotalPoints']; ?></div>
                                <div class="stat-lbl">Total Points</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-val"><?php echo ucfirst($points['TierLevel']); ?></div>
                                <div class="stat-lbl">Tier Status</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-val"><?php echo $points['StreakDays']; ?> 🔥</div>
                                <div class="stat-lbl">Day Streak</div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <h2>Edit Profile</h2>
                    <form method="POST">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio"
                                placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['Bio'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Country / Location</label>
                            <input type="text" name="country"
                                value="<?php echo htmlspecialchars($user['Country'] ?? ''); ?>"
                                placeholder="e.g. Bangladesh">
                        </div>
                        <button type="submit" class="btn">Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="side-col">
                <div class="card">
                    <h2>Quick Links</h2>
                    <div class="link-list">
                        <?php if ($user['Role'] === 'Student'): ?>
                            <a href="mood.php" class="link-item">
                                <span class="l-icon">🎭</span> Mood Tracker
                            </a>
                        <?php endif; ?>

                        <a href="notifications.php" class="link-item">
                            <span class="l-icon">🔔</span> Notifications
                        </a>
                    </div>
                </div>

                <?php if ($user['Role'] === 'Student'): ?>
                    <div class="card">
                        <h2>Badges</h2>
                        <?php if (empty($badges)): ?>
                            <p style="color:var(--text-muted); font-size:0.9rem;">No badges earned yet. Keep using FindR!</p>
                        <?php else: ?>
                            <div class="badge-grid">
                                <?php foreach ($badges as $b): ?>
                                    <div class="badge-item" title="<?php echo htmlspecialchars($b['BadgeDescription']); ?>">
                                        <div class="b-icon">🏅</div>
                                        <div class="b-name"><?php echo htmlspecialchars($b['BadgeName']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>

</html>