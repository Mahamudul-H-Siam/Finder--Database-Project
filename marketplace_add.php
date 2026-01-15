<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title     = trim($_POST['title']);
    $desc      = trim($_POST['description']);
    $category  = $_POST['category'];
    $price     = (float)$_POST['price'];
    $condition = $_POST['condition'];
    $sellerId  = $_SESSION['user_id'];

    if ($title === '' || $desc === '' || $price <= 0) {
        $errors[] = "Please fill all fields correctly.";
    }

    if (!$errors) {
        $stmt = $conn->prepare(
            "INSERT INTO MARKETITEM (SellerID, Title, Description, Category, Price, `Condition`, Status)
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
    <title>Post item - Marketplace | FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            margin:0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: radial-gradient(circle at top left, #1e293b 0, #020617 55%);
            color:#e5e7eb;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }
        .card {
            background: rgba(15,23,42,0.96);
            border-radius: 12px;
            border:1px solid #1f2937;
            box-shadow:0 18px 40px rgba(15,23,42,0.85);
            padding:1.5rem 1.75rem;
            width:100%;
            max-width:520px;
        }
        h2 { margin-bottom:0.5rem; }
        .subtitle {
            font-size:0.8rem;
            color:#9ca3af;
            margin-bottom:1rem;
        }
        .top-link {
            font-size:0.75rem;
            color:#9ca3af;
            margin-bottom:0.5rem;
        }
        .top-link a { color:#38bdf8; }
        .form-group {
            margin-bottom:0.75rem;
            font-size:0.8rem;
        }
        .form-group label {
            display:block;
            margin-bottom:0.25rem;
            color:#9ca3af;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width:100%;
            padding:0.4rem 0.55rem;
            border-radius:0.6rem;
            border:1px solid #374151;
            background:#020617;
            color:#e5e7eb;
            font-size:0.85rem;
            outline:none;
        }
        .form-group textarea {
            min-height:80px;
            resize:vertical;
        }
        .form-row {
            display:flex;
            gap:0.75rem;
        }
        .form-row .form-group { flex:1; }
        .btn {
            width:100%;
            margin-top:0.4rem;
            padding:0.5rem 0.7rem;
            border-radius:999px;
            border:none;
            cursor:pointer;
            font-size:0.85rem;
            font-weight:600;
            background:linear-gradient(to right,#22c55e,#16a34a);
            color:#022c22;
            box-shadow:0 10px 24px rgba(22,163,74,0.9);
        }
        .error {
            color:#fecaca;
            font-size:0.8rem;
            margin-bottom:0.3rem;
        }
        .success {
            color:#bbf7d0;
            font-size:0.8rem;
            margin-bottom:0.3rem;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="top-link">
        ← <a href="marketplace_list.php">Back to marketplace</a>
    </div>
    <h2>Post new item</h2>
    <div class="subtitle">Sell books, furniture, electronics and more to nearby students.</div>

    <?php if ($success): ?>
        <div class="success">Item posted successfully. It is now visible in the marketplace.</div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <div class="error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <form method="post">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" placeholder="e.g., Study table with shelf" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Condition, size, location, pickup details" required></textarea>
        </div>
        <div class="form-row">
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
                <input type="number" step="0.01" name="price" placeholder="e.g., 2300" required>
            </div>
        </div>
        <div class="form-group">
            <label>Condition</label>
            <select name="condition" required>
                <option value="New">New</option>
                <option value="LikeNew">Like new</option>
                <option value="Used">Used</option>
                <option value="VeryUsed">Very used</option>
            </select>
        </div>

        <button type="submit" class="btn">Post item</button>
    </form>
</div>
</body>
</html>
