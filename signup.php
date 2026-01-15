<?php
include 'config.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $phone     = trim($_POST['phone']);
    $password  = $_POST['password'];
    $password2 = $_POST['password2'];
    $role      = $_POST['role'];
    $ageGroup  = $_POST['age_group'];
    $living    = $_POST['living_status'];

    if ($password !== $password2) {
        $errors[] = "Passwords do not match.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("SELECT UserID FROM USER WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        }
        $stmt->close();
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare(
            "INSERT INTO USER (FullName, Email, Phone, PasswordHash, Role, AgeGroup, LivingStatus)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssss",
            $fullName, $email, $phone, $hash, $role, $ageGroup, $living
        );
        if ($stmt->execute()) {
            header("Location: login.php?registered=1");
            exit;
        } else {
            $errors[] = "Error creating account.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sign up - FindR</title>
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
            max-width:420px;
        }
        h2 {
            margin-bottom:0.5rem;
        }
        .subtitle {
            font-size:0.8rem;
            color:#9ca3af;
            margin-bottom:1rem;
        }
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
        .form-group select {
            width:100%;
            padding:0.4rem 0.55rem;
            border-radius:0.6rem;
            border:1px solid #374151;
            background:#020617;
            color:#e5e7eb;
            font-size:0.85rem;
            outline:none;
        }
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
        .bottom-text {
            margin-top:0.8rem;
            font-size:0.8rem;
            color:#9ca3af;
            text-align:center;
        }
        .bottom-text a {
            color:#38bdf8;
        }
    </style>
</head>
<body>
<div class="card">
    <h2>Sign up</h2>
    <div class="subtitle">Create your FindR account.</div>

    <?php foreach ($errors as $e): ?>
        <div class="error"><?php echo htmlspecialchars($e); ?></div>
    <?php endforeach; ?>

    <form method="post">
        <div class="form-group">
            <label>Full name</label>
            <input type="text" name="full_name" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Phone</label>
            <input type="text" name="phone" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="form-group">
            <label>Confirm password</label>
            <input type="password" name="password2" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role" required>
                <option value="Student">Student</option>
                <option value="Owner">Owner</option>
                <option value="ServiceProvider">ServiceProvider</option>
            </select>
        </div>
        <div class="form-group">
            <label>Age group</label>
            <select name="age_group" required>
                <option value="18-21">18-21</option>
                <option value="22-25">22-25</option>
                <option value="26-30">26-30</option>
                <option value="Over30">Over30</option>
            </select>
        </div>
        <div class="form-group">
            <label>Living status</label>
            <select name="living_status" required>
                <option value="Alone">Alone</option>
                <option value="Roommates">Roommates</option>
                <option value="Family">Family</option>
                <option value="Hostel">Hostel</option>
            </select>
        </div>

        <button type="submit" class="btn">Create account</button>
    </form>

    <div class="bottom-text">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>
</body>
</html>
