<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'All';
$view = isset($_GET['view']) ? $_GET['view'] : 'public'; // public or my

// Build Query
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($view === 'my') {
    $where .= " AND m.SellerID = ?";
    $types .= "i";
    $params[] = $userId;
} else {
    // Public view only shows Available
    $where .= " AND m.Status = 'Available'";
}

if ($category !== 'All' && $category !== '') {
    $where .= " AND m.Category = ?";
    $types .= "s";
    $params[] = $category;
}

if ($search !== '') {
    $where .= " AND (m.Title LIKE ? OR m.Description LIKE ?)";
    $types .= "ss";
    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
}

$stmt = $conn->prepare("
    SELECT m.*, u.FullName 
    FROM MARKETITEM m 
    JOIN USER u ON m.SellerID = u.UserID 
    $where
    ORDER BY m.CreatedAt DESC
");

if ($types) {
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
}

$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Marketplace - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --accent: #f43f5e;
        }

        .home-btn {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid #334155;
            color: #f8fafc;
            text-decoration: none;
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            transition: transform 0.2s, background 0.2s;
            margin-right: 1rem;
        }

        .home-btn:hover {
            transform: scale(1.05);
            background: #334155;
        }

        body {
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1.5rem;
        }

        /* Header & Tabs */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #334155;
        }

        h1 {
            margin: 0;
            font-size: 1.8rem;
            background: linear-gradient(90deg, #f43f5e, #fb7185);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .tab-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .tab {
            text-decoration: none;
            color: var(--muted);
            font-weight: 500;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid transparent;
        }

        .tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        .btn-post {
            background: var(--accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 99px;
            font-weight: 600;
            text-decoration: none;
        }

        /* Search Bar */
        .search-bar {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            border: 1px solid #334155;
        }

        input,
        select {
            flex: 1;
            padding: 0.6rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: white;
        }

        button.apply {
            padding: 0 1.5rem;
            background: #334155;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        /* Grid */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .item-card {
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid #334155;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
            position: relative;
        }

        .item-img-placeholder {
            height: 160px;
            background: #020617;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            font-size: 2rem;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.6rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }

        .st-available {
            background: #22c55e;
            color: white;
        }

        .st-pending {
            background: #fbbf24;
            color: #451a03;
        }

        .st-declined {
            background: #ef4444;
            color: white;
        }

        .st-sold {
            background: #64748b;
            color: white;
        }

        .item-body {
            padding: 1.2rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #bef264;
            margin-bottom: 0.2rem;
        }

        .title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .tags {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.75rem;
        }

        .tag {
            padding: 0.2rem 0.5rem;
            background: #334155;
            border-radius: 4px;
        }

        .desc {
            font-size: 0.9rem;
            color: var(--muted);
            margin-bottom: 1rem;
            flex: 1;
            line-height: 1.5;
        }

        .seller-info {
            font-size: 0.8rem;
            color: var(--muted);
            border-top: 1px solid #334155;
            padding-top: 0.8rem;
            margin-top: auto;
        }

        .contact-btn {
            display: block;
            text-align: center;
            background: #334155;
            color: white;
            padding: 0.6rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-weight: 600;
            text-decoration: none;
        }

        .contact-btn:hover {
            background: var(--accent);
            color: white;
        }

        .empty {
            text-align: center;
            color: var(--muted);
            margin-top: 3rem;
            grid-column: 1 / -1;
        }
    </style>
</head>

<body>
    <div class="container">

        <div class="header">
            <div style="display:flex;align-items:center;">
                <a href="index.php" class="home-btn" title="Back to Dashboard">🏠</a>
                <h1>Marketplace</h1>
            </div>
            <a href="marketplace_add.php" class="btn-post">+ Sell Item</a>
        </div>

        <div class="tab-group">
            <a href="?view=public" class="tab <?php echo $view === 'public' ? 'active' : ''; ?>">All Items</a>
            <a href="?view=my" class="tab <?php echo $view === 'my' ? 'active' : ''; ?>">My Items</a>
        </div>

        <form class="search-bar">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="text" name="q" placeholder="Search for items..."
                value="<?php echo htmlspecialchars($search); ?>">
            <select name="category">
                <option value="All">All Categories</option>
                <option value="Electronics" <?php if ($category == 'Electronics')
                    echo 'selected'; ?>>Electronics</option>
                <option value="Furniture" <?php if ($category == 'Furniture')
                    echo 'selected'; ?>>Furniture</option>
                <option value="Books" <?php if ($category == 'Books')
                    echo 'selected'; ?>>Books</option>
                <option value="Clothing" <?php if ($category == 'Clothing')
                    echo 'selected'; ?>>Clothing</option>
                <option value="Sports" <?php if ($category == 'Sports')
                    echo 'selected'; ?>>Sports</option>
                <option value="Others" <?php if ($category == 'Others')
                    echo 'selected'; ?>>Others</option>
            </select>
            <button type="submit" class="apply">Search</button>
        </form>

        <div class="grid">
            <?php if (empty($items)): ?>
                <div class="empty">
                    <h3>No items found</h3>
                    <p>Try adjusting your search criteria.</p>
                </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                    <div class="item-card">

                        <?php if ($view === 'my'): ?>
                            <div class="status-badge st-<?php echo strtolower($item['Status']); ?>">
                                <?php echo htmlspecialchars($item['Status']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="item-img-placeholder">📷</div>
                        <div class="item-body">
                            <div class="price">BDT <?php echo number_format($item['Price'], 0); ?></div>
                            <div class="title"><?php echo htmlspecialchars($item['Title']); ?></div>
                            <div class="tags">
                                <div class="tag"><?php echo htmlspecialchars($item['Category']); ?></div>
                                <div class="tag"><?php echo htmlspecialchars($item['Condition']); ?></div>
                            </div>
                            <div class="desc">
                                <?php echo nl2br(htmlspecialchars(substr($item['Description'], 0, 100))); ?>...
                            </div>
                            <div class="seller-info">
                                Seller: <strong><?php echo htmlspecialchars($item['FullName']); ?></strong><br>
                                Posted: <?php echo date('M d', strtotime($item['CreatedAt'])); ?>
                            </div>
                            <?php if ($item['Status'] === 'Available'): ?>
                                <a href="chat.php?user_id=<?php echo $item['SellerID']; ?>" class="contact-btn">
                                    Message Seller
                                </a>
                            <?php else: ?>
                                <div style="text-align:center; margin-top:1rem; color:#94a3b8; font-size:0.9rem;">
                                    <?php echo $item['Status']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>