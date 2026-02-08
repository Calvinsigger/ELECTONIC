<?php
session_start();
require_once "../api/db.php";

/* ===== SECURITY: CUSTOMER ONLY ===== */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer'){
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$message = "";

/* ===== FETCH CUSTOMER ===== */
$stmt = $conn->prepare("SELECT fullname, email, profile_image FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===== UPDATE PROFILE ===== */
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name  = trim($_POST['fullname']);
    $email = trim($_POST['email']);

    /* IMAGE UPLOAD */
    $imageName = $user['profile_image'];
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0){
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $imageName = uniqid("profile_") . "." . $ext;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], "../uploads/" . $imageName);
    }

    $update = $conn->prepare("
        UPDATE users 
        SET fullname = ?, email = ?, profile_image = ?
        WHERE id = ?
    ");
    $update->execute([$name, $email, $imageName, $userId]);

    $message = "✅ Profile updated successfully";

    // Refresh data
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{box-sizing:border-box}
body{
    margin:0;
    font-family:'Segoe UI',Arial,sans-serif;
    background:#f4f6f8;
}

/* HEADER */
header{
    background:#0a3d62;
    color:white;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.logo{font-size:22px;font-weight:bold}
nav a{
    color:white;
    text-decoration:none;
    margin-left:18px;
}
nav a:hover{color:#ffdd59}

/* CONTAINER */
.container{
    max-width:900px;
    margin:40px auto;
    background:white;
    border-radius:14px;
    padding:30px;
    box-shadow:0 12px 25px rgba(0,0,0,.15);
}

/* TITLE */
h1{
    margin-top:0;
    color:#0a3d62;
}

/* PROFILE GRID */
.profile-grid{
    display:grid;
    grid-template-columns:280px 1fr;
    gap:30px;
}

/* IMAGE */
.avatar-box{
    text-align:center;
}
.avatar{
    width:200px;
    height:200px;
    border-radius:50%;
    object-fit:cover;
    border:6px solid #0a3d62;
}
.upload-label{
    display:inline-block;
    margin-top:12px;
    color:#1e90ff;
    cursor:pointer;
    font-weight:500;
}

/* FORM */
.form-group{
    margin-bottom:18px;
}
.form-group label{
    display:block;
    margin-bottom:6px;
    font-weight:600;
}
.form-group input{
    width:100%;
    padding:12px;
    border-radius:6px;
    border:1px solid #ccc;
    font-size:15px;
}

/* BUTTON */
button{
    background:#0a3d62;
    color:white;
    border:none;
    padding:14px;
    width:100%;
    font-size:16px;
    border-radius:8px;
    cursor:pointer;
}
button:hover{background:#07406b}

/* MESSAGE */
.message{
    background:#e8f8f5;
    color:#0a3d62;
    padding:12px;
    border-radius:6px;
    margin-bottom:15px;
    text-align:center;
    font-weight:500;
}

/* RESPONSIVE */
@media(max-width:800px){
    .profile-grid{
        grid-template-columns:1fr;
        text-align:center;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">ElectroStore</div>
    <nav>
        <a href="customer_dashboard.php">Dashboard</a>
        <a href="shop.php">Shop</a>
        <a href="cart.php">Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">
    <h1>My Profile</h1>

    <?php if($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="profile-grid">

            <!-- PROFILE IMAGE -->
            <div class="avatar-box">
                <img src="../uploads/<?= $user['profile_image'] ?: 'default.png' ?>" class="avatar">
                <br>
                <label class="upload-label">
                    Change Photo
                    <input type="file" name="profile_image" hidden accept="image/*">
                </label>
            </div>

            <!-- PROFILE INFO -->
            <div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" value="<?= htmlspecialchars($user['fullname']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <button type="submit">Update Profile</button>
            </div>

        </div>
    </form>
</div>

</body>
</html>
