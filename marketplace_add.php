<?php
// marketplace_add.php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']);
    $desc    = trim($_POST['description']);
    $category = $_POST['category'];
    $price   = (float)$_POST['price'];
    $condition = $_POST['condition'];
    $sellerId  = $_SESSION['user_id'];

    if ($title === '' || $desc === '' || $price <= 0) {
        $errors[] = "Please fill all fields correctly.";
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "INSERT INTO MARKETITEM (SellerID, Title, Description, Category, Price, Condition, Status)
             VALUES (?, ?, ?, ?, ?, ?, 'Available')"
        );
        $stmt->bind_param(
            "isssds",
            $sellerId, $title, $desc, $category, $price, $condition
        );
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Error saving item.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post item - Marketplace</title>
</head>
<body>
<h2>Post new marketplace item</h2>

<?php if ($success): ?>
    <p style="color:green;">Item posted successfully.
        <a href="marketplace_list.php">Go back to marketplace</a>
    </p>
<?php endif; ?>

<?php foreach ($errors as $e): ?>
    <p style="color:red;"><?php echo $e; ?></p>
<?php endforeach; ?>

<form method="post">
    <input type="text" name="title" placeholder="Title" required><br><br>
    <textarea name="description" placeholder="Description" required></textarea><br><br>

    <label>Category:</label>
    <select name="category" required>
        <option value="Electronics">Electronics</option>
        <option value="Furniture">Furniture</option>
        <option value="Books">Books</option>
        <option value="Clothing">Clothing</option>
        <option value="Sports">Sports</option>
        <option value="Others">Others</option>
    </select><br><br>

    <label>Price (BDT):</label>
    <input type="number" step="0.01" name="price" required><br><br>

    <label>Condition:</label>
    <select name="condition" required>
        <option value="New">New</option>
        <option value="LikeNew">LikeNew</option>
        <option value="Used">Used</option>
        <option value="VeryUsed">VeryUsed</option>
    </select><br><br>

    <button type="submit">Post item</button>
</form>

<p><a href="marketplace_list.php">Back to marketplace</a></p>
</body>
</html>
<?php
$conn->close();
?>
