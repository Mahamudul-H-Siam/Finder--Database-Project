<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postType = $_POST['post_type'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $contactInfo = trim($_POST['contact_info']);

    if (empty($title) || empty($description) || empty($location) || empty($contactInfo) || !in_array($postType, ['Lost', 'Found'])) {
        $errors[] = "Fill all fields correctly.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO LOSTFOUND (ReporterID, PostType, Title, Description, Location, ContactInfo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("isssss", $_SESSION['user_id'], $postType, $title, $description, $location, $contactInfo);
        if ($stmt->execute()) {
            $success = true;
        } else {
            $errors[] = "Error posting.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post Lost/Found - FindR</title>
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
        .subtitle { font-size:0.8rem; color:#9ca3af; margin-bottom:1rem; }
        .form-group { margin-bottom:0.75rem; font-size:0.8rem; }
        .form-group label { display:block; margin-bottom:0.25rem; color:#9ca3af; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.4rem 0.55rem; border-radius:0.6rem; border:1px solid #374151; background:#020617; color:#e5e7eb; font-size:0.85rem; outline:none; }
        .form-group textarea { min-height:80px; resize:vertical; }
        .btn { width:100%; margin-top:0.4rem; padding:0.5rem 0.7rem; border-radius:999px; border:none; cursor:pointer; font-size:0.85rem; font-weight:600; background:linear-gradient(to right,#22c55e,#16a34a); color:#022c22; box-shadow:0 10px 24px rgba(22,163,74,0.9); }
        .error { color:#fecaca; font-size:0.8rem; margin-bottom:0.3rem; }
        .success { color:#bbf7d0; font-size:0.8rem; margin-bottom:0.3rem; }
        .top-link { font-size:0.75rem; color:#9ca3af; margin-bottom:0.5rem; }
        .top-link a { color:#38bdf8; }
    </style>
</head>
<body>
<div class="card">
    <div class="top-link">← <a href="lostfound_list.php">Back</a></div>
    <h2>Post Lost or Found Item</h2>

    <?php if ($success): ?>
        <div class="success">Posted successfully.</div>
    <?php endif; ?>

    <?php foreach ($errors as $e): ?>
        <div class="error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <form method="post">
        <div class="form-group">
            <label>Type</label>
            <select name="post_type" required>
                <option value="Lost">Lost</option>
                <option value="Found">Found</option>
            </select>
        </div>
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" required>
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" required></textarea>
        </div>
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" required>
        </div>
        <div class="form-group">
            <label>Contact Info</label>
            <input type="text" name="contact_info" required placeholder="Phone or email">
        </div>
        <button type="submit" class="btn">Post</button>
    </form>
</div>
</body>
</html>