<?php
include 'config.php';
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logged out - FindR</title>
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
            max-width:360px;
            text-align:center;
        }
        h2 {
            margin-bottom:0.5rem;
        }
        p {
            font-size:0.85rem;
            color:#9ca3af;
            margin-bottom:1rem;
        }
        a.btn {
            display:inline-block;
            padding:0.45rem 0.9rem;
            border-radius:999px;
            border:none;
            cursor:pointer;
            font-size:0.85rem;
            font-weight:600;
            background:linear-gradient(to right,#22c55e,#16a34a);
            color:#022c22;
            box-shadow:0 10px 24px rgba(22,163,74,0.9);
        }
    </style>
</head>
<body>
<div class="card">
    <h2>Logged out</h2>
    <p>You have been signed out of FindR.</p>
    <a href="login.php" class="btn">Back to login</a>
</div>
</body>
</html>
