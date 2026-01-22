<?php
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== 'Student') {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$errors = [];
$successMessage = "";
$motivation = "";
$pointsEarned = 0;

// Handle mood submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_mood'])) {
    $moodLevel = (int) ($_POST['mood_level'] ?? 5);
    $energyLevel = (int) ($_POST['energy_level'] ?? 3);
    $stressLevel = (int) ($_POST['stress_level'] ?? 5);
    $note = trim($_POST['mood_notes'] ?? '');
    $activities = isset($_POST['activities']) ? json_encode($_POST['activities']) : null;
    $medicationTaken = isset($_POST['medication_taken']) ? 1 : 0;

    $moodLabels = [
        1 => 'Very Bad',
        2 => 'Bad',
        3 => 'Poor',
        4 => 'Below Average',
        5 => 'Okay',
        6 => 'Good',
        7 => 'Very Good',
        8 => 'Great',
        9 => 'Excellent',
        10 => 'Perfect'
    ];
    $moodLabel = $moodLabels[$moodLevel] ?? 'Okay';

    // Insert Mood
    $stmt = $conn->prepare("INSERT INTO MOODENTRY (UserID, MoodLevel, MoodLabel, EnergyLevel, StressLevel, Activities, MedicationTaken, Note) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisiisis", $userId, $moodLevel, $moodLabel, $energyLevel, $stressLevel, $activities, $medicationTaken, $note);

    if ($stmt->execute()) {
        $successMessage = "Mood logged successfully!";

        // Gamification: Award Points
        $pointsEarned = 5;
        // Check if USER_POINTS entry exists
        $checkPoints = $conn->prepare("SELECT PointID FROM USER_POINTS WHERE UserID = ?");
        $checkPoints->bind_param("i", $userId);
        $checkPoints->execute();
        if ($checkPoints->get_result()->num_rows === 0) {
            $conn->query("INSERT INTO USER_POINTS (UserID, TotalPoints, TierLevel, StreakDays, LastActivityDate) VALUES ($userId, 5, 'Bronze', 1, CURDATE())");
        } else {
            // Update points and streak
            $conn->query("UPDATE USER_POINTS SET TotalPoints = TotalPoints + 5, LastActivityDate = CURDATE() WHERE UserID = $userId");
            // Simple streak logic can be added here (check if DATEDIFF is 1)
        }
        $checkPoints->close();

        // Motivational Quote Map (expanded)
        $quotes = [
            1 => "Take it one breath at a time. You are stronger than you know.",
            2 => "It’s okay to not be okay. Tomorrow is a fresh start.",
            3 => "Be gentle with yourself today.",
            4 => "Storms don't last forever.",
            5 => "Keep going, you're doing fine.",
            6 => "Good job! Keep that momentum.",
            7 => "You're doing great! Keep shining.",
            8 => "Awesome energy! Share a smile.",
            9 => "Fantastic! Ride this wave.",
            10 => "Perfect! Save this feeling."
        ];
        $motivation = $quotes[$moodLevel] ?? "Keep going!";

    } else {
        $errors[] = "Error logging mood: " . $conn->error;
    }
    $stmt->close();
}

// Fetch Stats & History
$weekAgo = date('Y-m-d H:i:s', strtotime('-7 days'));

// History
$history = [];
$hStmt = $conn->prepare("SELECT * FROM MOODENTRY WHERE UserID = ? AND CreatedAt >= ? ORDER BY CreatedAt DESC LIMIT 10");
$hStmt->bind_param("is", $userId, $weekAgo);
$hStmt->execute();
$hRes = $hStmt->get_result();
while ($row = $hRes->fetch_assoc())
    $history[] = $row;
$hStmt->close();

// Chart Data
$chartData = [];
$cStmt = $conn->prepare("
    SELECT DATE(CreatedAt) as mDate, AVG(MoodLevel) as avgMood 
    FROM MOODENTRY 
    WHERE UserID = ? AND CreatedAt >= ? 
    GROUP BY DATE(CreatedAt) 
    ORDER BY mDate ASC
");
$cStmt->bind_param("is", $userId, $weekAgo);
$cStmt->execute();
$cRes = $cStmt->get_result();
while ($row = $cRes->fetch_assoc())
    $chartData[] = $row;
$cStmt->close();

// User Points
$uPoints = 0;
$uTier = 'Bronze';
$pStmt = $conn->prepare("SELECT TotalPoints, TierLevel FROM USER_POINTS WHERE UserID = ?");
$pStmt->bind_param("i", $userId);
$pStmt->execute();
$pRes = $pStmt->get_result();
if ($r = $pRes->fetch_assoc()) {
    $uPoints = $r['TotalPoints'];
    $uTier = $r['TierLevel'];
}
$pStmt->close();

function getEmoji($level)
{
    $emojis = [
        1 => '😭',
        2 => '😢',
        3 => '😟',
        4 => '😕',
        5 => '😐',
        6 => '🙂',
        7 => '😊',
        8 => '😄',
        9 => '😃',
        10 => '😍'
    ];
    return $emojis[$level] ?? '😐';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Mood Tracker - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #fbbf24;
            --success: #22c55e;
            --border: #334155;
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

        /* Header Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        .stat-val {
            font-size: 2rem;
            font-weight: 700;
            color: var(--accent);
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
        }

        /* Main Grid */
        .grid-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 900px) {
            .grid-layout {
                grid-template-columns: 2fr 1fr;
            }
        }

        .card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 2rem;
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }

        h2 {
            margin: 0 0 1.5rem 0;
            font-size: 1.4rem;
            color: white;
        }

        /* Mood Selector */
        .mood-selector {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 0.5rem;
            margin-bottom: 2rem;
        }

        @media (min-width: 600px) {
            .mood-selector {
                grid-template-columns: repeat(10, 1fr);
            }
        }

        .mood-option {
            cursor: pointer;
            text-align: center;
            padding: 0.5rem 0;
            border-radius: 12px;
            transition: all 0.2s;
            border: 2px solid transparent;
        }

        .mood-option:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .mood-option.active {
            background: rgba(251, 191, 36, 0.15);
            border-color: var(--accent);
        }

        .m-emoji {
            font-size: 1.8rem;
            display: block;
            margin-bottom: 0.3rem;
        }

        .m-label {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* Sliders */
        .slider-group {
            margin-bottom: 1.5rem;
        }

        .slider-head {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        input[type=range] {
            width: 100%;
            height: 6px;
            background: #334155;
            border-radius: 5px;
            outline: none;
            appearance: none;
        }

        input[type=range]::-webkit-slider-thumb {
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent);
            cursor: pointer;
        }

        /* Activities */
        .activity-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-bottom: 2rem;
        }

        .act-tag {
            background: #334155;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            cursor: pointer;
            user-select: none;
            transition: 0.2s;
        }

        .act-tag.selected {
            background: var(--accent);
            color: #0f172a;
            font-weight: 600;
        }

        .act-tag input {
            display: none;
        }

        /* Notes */
        textarea {
            width: 100%;
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: 12px;
            color: white;
            padding: 1rem;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
            box-sizing: border-box;
            margin-bottom: 1rem;
        }

        .btn-save {
            width: 100%;
            background: var(--accent);
            color: #0f172a;
            font-weight: 700;
            padding: 1rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .btn-save:hover {
            opacity: 0.9;
        }

        /* Chart */
        .chart-bars {
            display: flex;
            align-items: flex-end;
            gap: 10px;
            height: 150px;
            padding-top: 2rem;
            padding-bottom: 1rem;
        }

        .bar-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .bar {
            width: 100%;
            background: linear-gradient(to top, var(--accent), #fef3c7);
            border-radius: 4px 4px 0 0;
            transition: height 0.5s;
        }

        .bar-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* History */
        .history-item {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .h-emoji {
            font-size: 1.5rem;
        }

        .h-info {
            flex: 1;
        }

        .h-time {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .h-badges {
            display: flex;
            gap: 10px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .badge {
            background: #334155;
            padding: 2px 8px;
            border-radius: 4px;
        }

        .success-box {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid var(--success);
            color: #4ade80;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
</head>

<body>

    <div class="container">
        <a href="index.php" class="home-btn">🏠</a>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-val"><?php echo $uPoints; ?></div>
                <div class="stat-label">Total Points</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo ucfirst($uTier); ?></div>
                <div class="stat-label">Current Tier</div>
            </div>
            <div class="stat-card">
                <div class="stat-val"><?php echo count($chartData); ?></div>
                <div class="stat-label">Days Tracked</div>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="success-box">
                <?php echo $successMessage; ?> (+<?php echo $pointsEarned; ?> pts)<br>
                <i>"<?php echo $motivation; ?>"</i>
            </div>
        <?php endif; ?>

        <div class="grid-layout">
            <div class="entry-col">
                <div class="card">
                    <h2>How are you feeling today?</h2>
                    <form method="POST">
                        <input type="hidden" name="save_mood" value="1">
                        <input type="hidden" name="mood_level" id="moodLevel" value="5">

                        <div class="mood-selector">
                            <?php
                            $emojis = ['😭', '😢', '😟', '😕', '😐', '🙂', '😊', '😄', '😃', '😍'];
                            $labels = ['Very Bad', 'Bad', 'Poor', 'Below Avg', 'Okay', 'Good', 'Very Good', 'Great', 'Excellent', 'Perfect'];
                            for ($i = 1; $i <= 10; $i++): ?>
                                <div class="mood-option <?php echo $i === 5 ? 'active' : ''; ?>"
                                    onclick="selectMood(<?php echo $i; ?>, this)">
                                    <span class="m-emoji"><?php echo $emojis[$i - 1]; ?></span>
                                    <span class="m-label"><?php echo $labels[$i - 1]; ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>

                        <div class="slider-group">
                            <div class="slider-head">
                                <span>Energy Level ⚡</span>
                                <span id="energyVal">3/5</span>
                            </div>
                            <input type="range" name="energy_level" min="1" max="5" value="3"
                                oninput="document.getElementById('energyVal').innerText = this.value + '/5'">
                        </div>

                        <div class="slider-group">
                            <div class="slider-head">
                                <span>Stress Level 🤯</span>
                                <span id="stressVal">5/10</span>
                            </div>
                            <input type="range" name="stress_level" min="1" max="10" value="5"
                                oninput="document.getElementById('stressVal').innerText = this.value + '/10'">
                        </div>

                        <div class="slider-head">Today's Activities</div>
                        <div class="activity-grid">
                            <?php
                            $acts = ['🏃 Exercise', '🧘 Meditation', '😴 Good Sleep', '📚 Reading', '👥 Socializing', '🥗 Healthy Eat', '🎮 Gaming', '🎵 Music'];
                            foreach ($acts as $a): ?>
                                <label class="act-tag">
                                    <input type="checkbox" name="activities[]" value="<?php echo $a; ?>">
                                    <?php echo $a; ?>
                                </label>
                            <?php endforeach; ?>
                        </div>

                        <label class="act-tag" style="display:block; width:fit-content; margin-bottom:1.5rem;">
                            <input type="checkbox" name="medication_taken" value="1"
                                style="display:inline-block; width:auto;">
                            💊 I took my medication
                        </label>

                        <textarea name="mood_notes" placeholder="Any thoughts or triggers?"></textarea>

                        <button type="submit" class="btn-save">Log Mood</button>
                    </form>
                </div>
            </div>

            <div class="side-col">
                <div class="card">
                    <h2>Weekly Trend</h2>
                    <div class="chart-bars">
                        <?php if (empty($chartData)): ?>
                            <div style="width:100%; text-align:center; color:var(--text-muted);">No data yet</div>
                        <?php else: ?>
                            <?php foreach ($chartData as $d):
                                $h = ($d['avgMood'] / 10) * 100;
                                ?>
                                <div class="bar-col">
                                    <div class="bar" style="height: <?php echo $h; ?>%;"></div>
                                    <div class="bar-label"><?php echo date('D', strtotime($d['mDate'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h2>Recent Entries</h2>
                    <?php foreach ($history as $h): ?>
                        <div class="history-item">
                            <div class="h-emoji"><?php echo getEmoji($h['MoodLevel']); ?></div>
                            <div class="h-info">
                                <div style="font-weight:600;"><?php echo htmlspecialchars($h['MoodLabel'] ?? 'Mood'); ?>
                                </div>
                                <div class="h-time"><?php echo date('M d, h:i A', strtotime($h['CreatedAt'])); ?></div>
                                <?php if ($h['Note']): ?>
                                    <div style="font-size:0.8rem; color: #cbd5e1; margin-top:0.2rem;">
                                        "<?php echo htmlspecialchars(substr($h['Note'], 0, 50)); ?>..."</div>
                                <?php endif; ?>
                                <div class="h-badges">
                                    <span class="badge">⚡ <?php echo $h['EnergyLevel']; ?>/5</span>
                                    <span class="badge">🤯 <?php echo $h['StressLevel']; ?>/10</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectMood(level, el) {
            document.getElementById('moodLevel').value = level;
            document.querySelectorAll('.mood-option').forEach(o => o.classList.remove('active'));
            el.classList.add('active');
        }

        // Activity Tag Toggle Visuals
        document.querySelectorAll('.act-tag input').forEach(inp => {
            inp.addEventListener('change', function () {
                if (this.parentNode.classList.contains('act-tag')) { // Only for the styled tags
                    this.parentNode.classList.toggle('selected', this.checked);
                }
            });
        });
    </script>
</body>

</html>