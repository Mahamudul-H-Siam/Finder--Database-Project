<?php
// marketplace_list.php
include 'config.php';

// user must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// read search + category from GET
$search   = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'All';

// base SQL: only available items
$sql = "SELECT mi.ItemID, mi.Title, mi.Description, mi.Category, mi.Price,
               mi.Condition, mi.Status, mi.CreatedAt,
               u.FullName AS SellerName
        FROM MARKETITEM mi
        JOIN USER u ON mi.SellerID = u.UserID
        WHERE mi.Status = 'Available'";

$params = [];
$types  = "";

// category filter
if ($category !== 'All' && $category !== '') {
    $sql .= " AND mi.Category = ?";
    $types  .= "s";
    $params[] = $category;
}

// text search in title/description
if ($search !== '') {
    $sql .= " AND (mi.Title LIKE ? OR mi.Description LIKE ?)";
    $types  .= "ss";
    $like = "%".$search."%";
    $params[] = $like;
    $params[] = $like;
}

$sql .= " ORDER BY mi.CreatedAt DESC";

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Marketplace - FindR</title>
</head>
<body>
<h2>Marketplace items</h2>

<form method="get" style="margin-bottom:10px;">
    <input type="text" name="q" placeholder="Search items..."
           value="<?php echo htmlspecialchars($search); ?>">
    <select name="category">
        <option value="All" <?php if ($category==='All') echo 'selected'; ?>>All</option>
        <option value="Electronics" <?php if ($category==='Electronics') echo 'selected'; ?>>Electronics</option>
        <option value="Furniture" <?php if ($category==='Furniture') echo 'selected'; ?>>Furniture</option>
        <option value="Books" <?php if ($category==='Books') echo 'selected'; ?>>Books</option>
        <option value="Clothing" <?php if ($category==='Clothing') echo 'selected'; ?>>Clothing</option>
        <option value="Sports" <?php if ($category==='Sports') echo 'selected'; ?>>Sports</option>
        <option value="Others" <?php if ($category==='Others') echo 'selected'; ?>>Others</option>
    </select>
    <button type="submit">Filter</button>
</form>

<p><a href="marketplace_add.php">Post new item</a></p>

<?php if ($result->num_rows === 0): ?>
    <p>No items found.</p>
<?php else: ?>
    <?php while ($row = $result->fetch_assoc()): ?>
        <div style="border:1px solid #ccc; padding:8px; margin-bottom:8px;">
            <strong><?php echo htmlspecialchars($row['Title']); ?></strong>
            <span>(<?php echo htmlspecialchars($row['Category']); ?>)</span><br>
            BDT <?php echo number_format($row['Price'], 2); ?> •
            <?php echo htmlspecialchars($row['Condition']); ?><br>
            Seller: <?php echo htmlspecialchars($row['SellerName']); ?><br>
            <small>Posted: <?php echo htmlspecialchars($row['CreatedAt']); ?></small><br>
            <p><?php echo nl2br(htmlspecialchars($row['Description'])); ?></p>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
