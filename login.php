<?php
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT UserID, FullName, PasswordHash, Role FROM USER WHERE Email = ? AND Status = 'Active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($userId, $fullName, $hash, $role);
    if ($stmt->fetch()) {
        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $userId;
            $_SESSION['full_name'] = $fullName;
            $_SESSION['role'] = $role;
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Invalid email or password.";
        }
    } else {
        $errors[] = "Invalid email or password.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - FindR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at center, #1e293b 0, #0f172a 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 400px;
            padding: 1.5rem;
        }

        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .brand {
            font-size: 2rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        input {
            width: 100%;
            padding: 0.8rem 1rem;
            border-radius: 10px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        input:focus {
            border-color: #38bdf8;
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 0.9rem;
            background: linear-gradient(135deg, #38bdf8, #2563eb);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 1rem;
            transition: transform 0.1s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .error {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.9rem;
            color: #94a3b8;
        }

        .footer a {
            color: #38bdf8;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card">
            <div class="brand">FindR</div>

            <?php foreach ($errors as $e): ?>
                <div class="error"><?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>

            <?php if (isset($_GET['registered'])): ?>
                <div
                    style="background:rgba(34,197,94,0.1);color:#4ade80;padding:0.8rem;border-radius:8px;margin-bottom:1rem;text-align:center;">
                    Account created! Please login.</div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="you@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                <button type="submit" class="btn">Sign In</button>
            </form>

            <div class="footer">
                Don't have an account? <a href="signup.php">Create one</a>
            </div>
        </div>
    </div>

</body>

</html>