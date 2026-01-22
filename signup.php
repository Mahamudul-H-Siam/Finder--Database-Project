<?php
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $password2 = $_POST['password2'];
    $role = $_POST['role'];
    $ageGroup = $_POST['age_group'];
    $living = $_POST['living_status'];

    // Service Provider specific fields
    $businessName = trim($_POST['business_name'] ?? '');
    $serviceType = $_POST['service_type'] ?? '';
    $area = trim($_POST['area'] ?? '');

    if ($password !== $password2) {
        $errors[] = "Passwords do not match.";
    }

    if ($role === 'ServiceProvider' && (empty($businessName) || empty($serviceType) || empty($area))) {
        $errors[] = "Service Providers must fill in Business Name, Service Type, and Area.";
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
        $conn->begin_transaction();
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare(
                "INSERT INTO USER (FullName, Email, Phone, PasswordHash, Role, AgeGroup, LivingStatus)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "sssssss",
                $fullName,
                $email,
                $phone,
                $hash,
                $role,
                $ageGroup,
                $living
            );

            if (!$stmt->execute()) {
                throw new Exception("Error creating user account.");
            }
            $newUserId = $stmt->insert_id;
            $stmt->close();

            if ($role === 'ServiceProvider') {
                $stmtSp = $conn->prepare(
                    "INSERT INTO SERVICEPROVIDER (ProviderID, ServiceType, BusinessName, Area, IsApproved)
                     VALUES (?, ?, ?, ?, 1)"
                );
                $stmtSp->bind_param("isss", $newUserId, $serviceType, $businessName, $area);
                if (!$stmtSp->execute()) {
                    throw new Exception("Error creating service provider profile.");
                }
                $stmtSp->close();
            }

            $conn->commit();
            header("Location: login.php?registered=1");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Account - FindR</title>
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
            padding: 2rem 1rem;
        }

        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 500px;
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        h1 {
            margin: 0 0 0.5rem 0;
            font-size: 1.8rem;
        }

        .sub {
            color: #94a3b8;
            font-size: 0.95rem;
        }

        .form-grid {
            display: grid;
            gap: 1rem;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.4rem;
            color: #cbd5e1;
            font-size: 0.85rem;
        }

        input,
        select {
            width: 100%;
            padding: 0.7rem;
            border-radius: 8px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            font-size: 0.95rem;
            box-sizing: border-box;
        }

        input:focus,
        select:focus {
            border-color: #38bdf8;
            outline: none;
        }

        .section-break {
            margin: 1rem 0 0.5rem;
            color: #38bdf8;
            font-size: 0.9rem;
            font-weight: 600;
            border-bottom: 1px solid #334155;
            padding-bottom: 0.5rem;
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
            margin-top: 1.5rem;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .error {
            color: #f87171;
            background: rgba(239, 68, 68, 0.1);
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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

        .hidden {
            display: none;
        }
    </style>
    <script>
        function toggleServiceFields() {
            const role = document.getElementById('role').value;
            const fields = document.getElementById('serviceFields');
            if (role === 'ServiceProvider') {
                fields.classList.remove('hidden');
                document.getElementById('business_name').required = true;
                document.getElementById('service_type').required = true;
                document.getElementById('area').required = true;
            } else {
                fields.classList.add('hidden');
                document.getElementById('business_name').required = false;
                document.getElementById('service_type').required = false;
                document.getElementById('area').required = false;
            }
        }
    </script>
</head>

<body>

    <div class="card">
        <div class="header">
            <h1>Create Account</h1>
            <div class="sub">Join FindR today</div>
        </div>

        <?php foreach ($errors as $e): ?>
            <div class="error"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>

        <form method="post">
            <div class="form-grid">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="full_name" required placeholder="John Doe">
                </div>
                <div>
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="john@example.com">
                </div>

                <div class="row">
                    <div>
                        <label>Password</label>
                        <input type="password" name="password" required>
                    </div>
                    <div>
                        <label>Confirm Password</label>
                        <input type="password" name="password2" required>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label>Phone</label>
                        <input type="text" name="phone" required placeholder="017...">
                    </div>
                    <div>
                        <label>Role</label>
                        <select name="role" id="role" onchange="toggleServiceFields()" required>
                            <option value="Student">Student</option>
                            <option value="Owner">House Owner</option>
                            <option value="ServiceProvider">Service Provider</option>
                            <option value="Admin">Admin</option>
                        </select>
                    </div>
                </div>

                <div class="row" id="commonFields">
                    <div>
                        <label>Age Group</label>
                        <select name="age_group">
                            <option value="18-22">18-22</option>
                            <option value="23-26">23-26</option>
                            <option value="27+">27+</option>
                        </select>
                    </div>
                    <div>
                        <label>Living Status</label>
                        <select name="living_status">
                            <option value="Single">Single</option>
                            <option value="Shared">Shared</option>
                        </select>
                    </div>
                </div>

                <div id="serviceFields" class="hidden">
                    <div class="section-break">Service Details</div>
                    <div style="margin-bottom:1rem">
                        <label>Business Name</label>
                        <input type="text" name="business_name" id="business_name" placeholder="e.g. Fast Cleaners">
                    </div>
                    <div class="row">
                        <div>
                            <label>Service Type</label>
                            <select name="service_type" id="service_type">
                                <option value="Cleaner">Cleaner</option>
                                <option value="Van">Moving Van</option>
                                <option value="Mess">Mess Manager</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label>Area</label>
                            <input type="text" name="area" id="area" placeholder="e.g. Dhanmondi">
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn">Create Account</button>
        </form>

        <div class="footer">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>

</body>

</html>