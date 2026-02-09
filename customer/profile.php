<?php
session_start();
require_once "../api/db.php";
require_once "../api/validation.php";
require_once "../api/security.php";

/* ===== SECURITY: CUSTOMER ONLY ===== */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer'){
    header("Location: ../login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$message = "";
$errorMsg = "";

/* ===== FETCH CUSTOMER ===== */
$stmt = $conn->prepare("SELECT fullname, email, profile_image FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

/* ===== UPDATE PROFILE ===== */
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    /* ===== CSRF TOKEN VALIDATION ===== */
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errorMsg = "Security token is invalid. Please try again.";
    } else {

        $name  = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';

        // Validate fullname
        $nameValidation = validateFullname($name);
        if (!$nameValidation['valid']) {
            $errorMsg = $nameValidation['message'];
        }
        // Validate email
        elseif (!validateEmail($email)) {
            $errorMsg = "Invalid email format.";
        } else {

            /* IMAGE UPLOAD */
            $imageName = $user['profile_image'];
            if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE){
                
                // Validate image file
                $imageValidation = validateImageFile($_FILES['profile_image']);
                if (!$imageValidation['valid']) {
                    $errorMsg = $imageValidation['message'];
                } else {
                    $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $imageName = uniqid("profile_") . "." . $ext;
                    $uploadPath = "../uploads/" . $imageName;
                    
                    if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                        $errorMsg = "Failed to upload image.";
                    }
                }
            }

            // If no errors, update profile
            if (empty($errorMsg)) {
                try {
                    $update = $conn->prepare("
                        UPDATE users 
                        SET fullname = ?, email = ?, profile_image = ?
                        WHERE id = ?
                    ");
                    $update->execute([$name, $email, $imageName, $userId]);

                    $message = "✅ Profile updated successfully!";

                    // Refresh data
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $errorMsg = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;color:#333;}

/* HEADER */
header{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    padding:18px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
}
header .logo{font-size:26px;font-weight:700;letter-spacing:0.5px;}
nav a{color:white;text-decoration:none;margin-left:20px;font-weight:500;transition:all 0.3s ease;}
nav a:hover{color:#ffdd59;transform:translateY(-2px);}

/* CONTAINER */
.container{
    max-width:900px;
    margin:40px auto;
    background:white;
    border-radius:14px;
    padding:40px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
}
h1{color:#0a3d62;margin-bottom:30px;font-size:28px;font-weight:700;}

/* PROFILE GRID */
.profile-grid{display:grid;grid-template-columns:300px 1fr;gap:40px;align-items:start;}

/* AVATAR BOX */
.avatar-box{text-align:center;}
.avatar{width:200px;height:200px;border-radius:50%;object-fit:cover;border:6px solid;border-image:linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;box-shadow:0 8px 20px rgba(102,126,234,0.3);}
.upload-label{display:inline-block;margin-top:18px;color:#667eea;cursor:pointer;font-weight:600;transition:all 0.3s ease;}
.upload-label:hover{color:#764ba2;transform:translateY(-2px);}
.upload-label input{display:none;}
small{color:#999;display:block;margin-top:10px;font-size:12px;}

/* FORM */
.form-group{margin-bottom:22px;}
.form-group label{display:block;margin-bottom:8px;font-weight:600;color:#0a3d62;text-transform:uppercase;font-size:12px;letter-spacing:0.5px;}
.form-group input{
    width:100%;
    padding:14px 16px;
    border:2px solid #e0e0e0;
    border-radius:8px;
    font-size:15px;
    transition:all 0.3s ease;
    font-family:inherit;
}
.form-group input:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}

/* MESSAGE */
.message{padding:15px 20px;border-radius:8px;margin-bottom:20px;border-left:4px solid;font-weight:600;}
.message[style*="background:#e8f8f5"]{background:#d4edda !important;color:#155724 !important;border-left-color:#28a745 !important;}
.message[style*="background:#fadbd8"]{background:#f8d7da !important;color:#721c24 !important;border-left-color:#dc3545 !important;}

/* BUTTON */
button{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    border:none;
    padding:14px 28px;
    width:100%;
    font-size:16px;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    transition:all 0.3s ease;
}
button:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,0.3);}

@media(max-width:800px){.profile-grid{grid-template-columns:1fr;}.avatar-box{text-align:center;}.avatar{width:150px;height:150px;}}
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
        <div class="message" style="background:#e8f8f5;color:#27ae60;"><?= sanitizeOutput($message) ?></div>
    <?php endif; ?>

    <?php if($errorMsg): ?>
        <div class="message" style="background:#fadbd8;color:#e74c3c;"><?= sanitizeOutput($errorMsg) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <?= getCSRFTokenInput() ?>
        <div class="profile-grid">

            <!-- PROFILE IMAGE -->
            <div class="avatar-box">
                <img src="../uploads/<?= sanitizeOutput($user['profile_image'] ?: 'default.png') ?>" class="avatar" alt="Profile">
                <br>
                <label class="upload-label">
                    Change Photo
                    <input type="file" name="profile_image" hidden accept="image/jpeg,image/png,image/gif,image/webp">
                </label>
                <small style="color:#666;display:block;margin-top:8px;">Max 5MB • JPG, PNG, GIF, WebP</small>
            </div>

            <!-- PROFILE INFO -->
            <div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" minlength="2" maxlength="100" value="<?= sanitizeOutput($user['fullname']) ?>" required>
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" maxlength="100" value="<?= sanitizeOutput($user['email']) ?>" required>
                </div>

                <button type="submit">Update Profile</button>
            </div>

        </div>
    </form>
</div>

</body>
</html>
