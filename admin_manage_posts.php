<?php
include 'config.php';

// Access Control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php");
    exit;
}

$adminId = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Create New Post
    if (isset($_POST['create_post'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category = $_POST['category'];
        $price = (float) $_POST['price'];
        $condition = $_POST['condition'];
        $status = $_POST['status']; // Admin can set status directly

        $stmt = $conn->prepare("INSERT INTO MARKETITEM (SellerID, Title, Description, Category, Price, `Condition`, Status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdss", $adminId, $title, $description, $category, $price, $condition, $status);

        if ($stmt->execute()) {
            $message = "Post created successfully!";
            $messageType = "success";
        } else {
            $message = "Error creating post: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    }

    // Approve Post
    if (isset($_POST['approve_post'])) {
        $itemId = intval($_POST['item_id']);
        $stmt = $conn->prepare("UPDATE MARKETITEM SET Status = 'Available' WHERE ItemID = ?");
        $stmt->bind_param("i", $itemId);

        if ($stmt->execute()) {
            // Notify seller
            $sellerStmt = $conn->prepare("SELECT SellerID, Title FROM MARKETITEM WHERE ItemID = ?");
            $sellerStmt->bind_param("i", $itemId);
            $sellerStmt->execute();
            $result = $sellerStmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $notifStmt = $conn->prepare("INSERT INTO NOTIFICATION (UserID, Title, Message) VALUES (?, 'Item Approved', ?)");
                $msg = "Your item '" . $row['Title'] . "' has been approved and is now visible.";
                $notifStmt->bind_param("is", $row['SellerID'], $msg);
                $notifStmt->execute();
                $notifStmt->close();
            }
            $sellerStmt->close();

            $message = "Post approved!";
            $messageType = "success";
        }
        $stmt->close();
    }

    // Decline Post
    if (isset($_POST['decline_post'])) {
        $itemId = intval($_POST['item_id']);
        $stmt = $conn->prepare("UPDATE MARKETITEM SET Status = 'Declined' WHERE ItemID = ?");
        $stmt->bind_param("i", $itemId);

        if ($stmt->execute()) {
            $message = "Post declined!";
            $messageType = "warning";
        }
        $stmt->close();
    }

    // Delete Post
    if (isset($_POST['delete_post'])) {
        $itemId = intval($_POST['item_id']);
        $stmt = $conn->prepare("DELETE FROM MARKETITEM WHERE ItemID = ?");
        $stmt->bind_param("i", $itemId);

        if ($stmt->execute()) {
            $message = "Post deleted!";
            $messageType = "success";
        }
        $stmt->close();
    }


}

// Fetch filter parameters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($statusFilter !== 'all') {
    $where .= " AND m.Status = ?";
    $types .= "s";
    $params[] = $statusFilter;
}

if ($categoryFilter !== 'all') {
    $where .= " AND m.Category = ?";
    $types .= "s";
    $params[] = $categoryFilter;
}

if ($searchQuery !== '') {
    $where .= " AND (m.Title LIKE ? OR m.Description LIKE ?)";
    $types .= "ss";
    $like = "%$searchQuery%";
    $params[] = $like;
    $params[] = $like;
}

// Fetch all posts
$sql = "SELECT m.*, u.FullName, u.Email 
        FROM MARKETITEM m 
        JOIN USER u ON m.SellerID = u.UserID 
        $where
        ORDER BY m.CreatedAt DESC";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$posts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Count by status
$counts = [
    'all' => 0,
    'Pending' => 0,
    'Available' => 0,
    'Declined' => 0
];

$countResult = $conn->query("SELECT Status, COUNT(*) as count FROM MARKETITEM GROUP BY Status");
while ($row = $countResult->fetch_assoc()) {
    $counts[$row['Status']] = $row['count'];
    $counts['all'] += $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Manage Posts | FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #e5e7eb;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #334155;
        }

        h1 {
            font-size: 2rem;
            background: linear-gradient(90deg, #f43f5e, #fb7185);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: linear-gradient(90deg, #f43f5e, #fb7185);
            color: white;
        }

        .btn-success {
            background: #22c55e;
            color: #022c22;
        }

        .btn-warning {
            background: #f59e0b;
            color: #451a03;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-secondary {
            background: #475569;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid #22c55e;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid #ef4444;
        }

        .alert-warning {
            background: rgba(251, 191, 36, 0.2);
            color: #fbbf24;
            border: 1px solid #f59e0b;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .stat-card:hover {
            border-color: #f43f5e;
            transform: translateY(-2px);
        }

        .stat-card.active {
            border-color: #f43f5e;
            background: rgba(244, 63, 94, 0.1);
        }

        .stat-label {
            font-size: 0.875rem;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #f43f5e;
        }

        .filters {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filters input,
        .filters select {
            padding: 0.6rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: white;
            font-size: 0.9rem;
        }

        .filters input {
            flex: 1;
            min-width: 250px;
        }

        .posts-grid {
            display: grid;
            gap: 1.5rem;
        }

        .post-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 1.5rem;
        }

        .post-info h3 {
            color: #f8fafc;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .post-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .post-desc {
            color: #cbd5e1;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .post-footer {
            display: flex;
            gap: 1rem;
            align-items: center;
            font-size: 0.875rem;
            color: #64748b;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-pending {
            background: #f59e0b;
            color: #451a03;
        }

        .badge-available {
            background: #22c55e;
            color: #022c22;
        }

        .badge-declined {
            background: #ef4444;
            color: white;
        }

        .post-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 150px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: #1e293b;
            border-radius: 12px;
            padding: 2rem;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            color: #f8fafc;
        }

        .close-modal {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-weight: 500;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.6rem;
            border-radius: 8px;
            border: 1px solid #475569;
            background: #0f172a;
            color: white;
            font-size: 0.9rem;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #64748b;
        }

        .empty-state h3 {
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🛍️ Manage Posts</h1>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-primary" onclick="openCreateModal()">+ Create Post</button>
                <a href="index.php" class="btn btn-secondary">← Dashboard</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats">
            <a href="?status=all" class="stat-card <?php echo $statusFilter === 'all' ? 'active' : ''; ?>"
                style="text-decoration: none;">
                <div class="stat-label">Total Posts</div>
                <div class="stat-value">
                    <?php echo $counts['all']; ?>
                </div>
            </a>
            <a href="?status=Pending" class="stat-card <?php echo $statusFilter === 'Pending' ? 'active' : ''; ?>"
                style="text-decoration: none;">
                <div class="stat-label">Pending</div>
                <div class="stat-value">
                    <?php echo $counts['Pending']; ?>
                </div>
            </a>
            <a href="?status=Available" class="stat-card <?php echo $statusFilter === 'Available' ? 'active' : ''; ?>"
                style="text-decoration: none;">
                <div class="stat-label">Approved</div>
                <div class="stat-value">
                    <?php echo $counts['Available']; ?>
                </div>
            </a>
            <a href="?status=Declined" class="stat-card <?php echo $statusFilter === 'Declined' ? 'active' : ''; ?>"
                style="text-decoration: none;">
                <div class="stat-label">Declined</div>
                <div class="stat-value">
                    <?php echo $counts['Declined']; ?>
                </div>
            </a>
        </div>

        <!-- Filters -->
        <form class="filters" method="GET">
            <input type="text" name="search" placeholder="Search posts..."
                value="<?php echo htmlspecialchars($searchQuery); ?>">
            <select name="category">
                <option value="all">All Categories</option>
                <option value="Electronics" <?php echo $categoryFilter === 'Electronics' ? 'selected' : ''; ?>
                    >Electronics</option>
                <option value="Furniture" <?php echo $categoryFilter === 'Furniture' ? 'selected' : ''; ?>>Furniture
                </option>
                <option value="Books" <?php echo $categoryFilter === 'Books' ? 'selected' : ''; ?>>Books</option>
                <option value="Clothing" <?php echo $categoryFilter === 'Clothing' ? 'selected' : ''; ?>>Clothing
                </option>
                <option value="Sports" <?php echo $categoryFilter === 'Sports' ? 'selected' : ''; ?>>Sports</option>
                <option value="Others" <?php echo $categoryFilter === 'Others' ? 'selected' : ''; ?>>Others</option>
            </select>
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
            <button type="submit" class="btn btn-primary">Filter</button>
            <?php if ($searchQuery || $categoryFilter !== 'all'): ?>
                <a href="?status=<?php echo $statusFilter; ?>" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Posts Grid -->
        <div class="posts-grid">
            <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <h3>No posts found</h3>
                    <p>Try adjusting your filters or create a new post.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-info">
                            <h3>
                                <?php echo htmlspecialchars($post['Title']); ?>
                            </h3>
                            <div class="post-meta">
                                <span>BDT
                                    <?php echo number_format($post['Price']); ?>
                                </span>
                                <span>•</span>
                                <span>
                                    <?php echo $post['Category']; ?>
                                </span>
                                <span>•</span>
                                <span>
                                    <?php echo $post['Condition']; ?>
                                </span>
                                <span>•</span>
                                <span class="badge badge-<?php echo strtolower($post['Status']); ?>">
                                    <?php echo $post['Status']; ?>
                                </span>
                            </div>
                            <div class="post-desc">
                                <?php echo nl2br(htmlspecialchars($post['Description'])); ?>
                            </div>
                            <div class="post-footer">
                                <span>Seller: <strong>
                                        <?php echo htmlspecialchars($post['FullName']); ?>
                                    </strong></span>
                                <span>•</span>
                                <span>
                                    <?php echo date('M d, Y h:i A', strtotime($post['CreatedAt'])); ?>
                                </span>
                            </div>
                        </div>
                        <div class="post-actions">
                            <?php if ($post['Status'] === 'Pending'): ?>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="item_id" value="<?php echo $post['ItemID']; ?>">
                                    <button type="submit" name="approve_post" class="btn btn-success" style="width: 100%;">✓
                                        Approve</button>
                                </form>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="item_id" value="<?php echo $post['ItemID']; ?>">
                                    <button type="submit" name="decline_post" class="btn btn-warning" style="width: 100%;">✗
                                        Decline</button>
                                </form>
                            <?php endif; ?>

                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Delete this post?');">
                                <input type="hidden" name="item_id" value="<?php echo $post['ItemID']; ?>">
                                <button type="submit" name="delete_post" class="btn btn-danger" style="width: 100%;">🗑
                                    Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Post Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Post</h2>
                <button class="close-modal" onclick="closeCreateModal()">×</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="Electronics">Electronics</option>
                        <option value="Furniture">Furniture</option>
                        <option value="Books">Books</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Sports">Sports</option>
                        <option value="Others">Others</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price (BDT)</label>
                    <input type="number" step="0.01" name="price" required>
                </div>
                <div class="form-group">
                    <label>Condition</label>
                    <select name="condition" required>
                        <option value="New">New</option>
                        <option value="LikeNew">Like New</option>
                        <option value="Used">Used</option>
                        <option value="VeryUsed">Very Used</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Available">Available (Approved)</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="create_post" class="btn btn-success">Create Post</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>



    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.add('active');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.remove('active');
        }



        // Close modals on outside click
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>

</html>