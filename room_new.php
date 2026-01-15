<?php
include 'config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Owner') {
    header("Location: index.php");
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = trim($_POST['title']);
    $desc    = trim($_POST['description']);
    $area    = trim($_POST['area']);
    $rent    = (float)$_POST['rent'];
    $gender  = $_POST['gender_pref']; // Any, Male, Female
    $utilInc = isset($_POST['utilities']) ? 1 : 0;
    $ownerId = $_SESSION['user_id'];

    $stmt = $conn->prepare(
        "INSERT INTO ROOMLISTING (OwnerID, ListingType, Title, Description, LocationArea, RentAmount, UtilitiesIncluded, GenderPreference, IsVerified)
         VALUES (?, 'Room', ?, ?, ?, ?, ?, ?, 0)"
    );
    $stmt->bind_param("isssdis",
        $ownerId, $title, $desc, $area, $rent, $utilInc, $gender
    );
    if ($stmt->execute()) {
        header("Location: index.php?section=rooms");
        exit;
    } else {
        $errors[] = "Error saving room.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>New Room</title></head>
<body>
<h2>Post new room</h2>
<?php foreach ($errors as $e): ?>
    <p style="color:red;"><?php echo $e; ?></p>
<?php endforeach; ?>

<form method="post">
    <input type="text" name="title" placeholder="Title" required><br>
    <textarea name="description" placeholder="Description" required></textarea><br>
    <input type="text" name="area" placeholder="Area (e.g., Dhanmondi)" required><br>
    <input type="number" name="rent" placeholder="Rent (BDT)" required><br>
    <label><input type="checkbox" name="utilities"> Utilities included</label><br>
    <select name="gender_pref">
        <option value="Any">Any</option>
        <option value="Male">Male</option>
        <option value="Female">Female</option>
    </select><br>
    <button type="submit">Save</button>
</form>
</body>
</html>
