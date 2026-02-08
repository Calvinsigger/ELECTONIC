<?php
session_start();
require_once __DIR__ . "/api/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email    = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    try {
        // Fetch user including status
        $stmt = $conn->prepare("SELECT id, fullname, email, password_hash, role, status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user["password_hash"])) {

            // Check if user is blocked
            if ($user["status"] === "blocked") {
                $error = "Your account has been blocked. Contact admin.";
            } else {
                // Store session
                $_SESSION["user_id"]   = $user["id"];
                $_SESSION["fullname"]  = $user["fullname"];
                $_SESSION["role"]      = $user["role"];

                // Redirect based on role
                if ($user["role"] === "admin") {
                    header("Location: admin/admin_dashboard.php");
                } elseif ($user["role"] === "customer") {
                    header("Location: customer/customer_dashboard.php");
                } else {
                    $error = "User role not recognized!";
                }
                exit;
            }

        } else {
            $error = "Invalid email or password.";
        }

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    background: linear-gradient(to left, #97C2EC, #0a3d62);
    background:url('uploads/abcd.jpg') center/cover no-repeat;
    font-family: 'Poppins', sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.form-container {
    background:green;
    padding: 40px;
    width: 420px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.25);
    position: relative;
    text-align: center;
}

.home-btn {
    display: inline-block;
    padding: 10px 20px;
    background: #620A0E;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    margin-bottom: 20px;
}

.home-btn:hover {
    background: #620A0E;
}

.form-container h2 {
    margin-bottom: 25px;
    color: #ffffff;
    font-size: 28px;
}

.form-container input {
    width: 100%;
    padding: 14px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 15px;
}

.form-container button {
    width: 100%;
    padding: 14px;
    background:blue;
    color: #ffffff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: 0.3s;
    opacity:90%;
}

.form-container button:hover {
    background: #07406b;
}

.error {
    color: red;
    margin-bottom: 15px;
    font-weight: 500;
}

.link {
    margin-top: 12px;
}

.link a {
    text-decoration: none;
    color: #ffffff;
    font-weight: 500;
}

.link a:hover {
    text-decoration: underline;
}
</style>
</head>
<body>

<div class="form-container">

    <a class="home-btn" href="index.php">Home</a>

    <h2>User Login</h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>

    <div class="link">
        Don’t have an account? <a href="register.php">Register</a>
    </div>
</div>

</body>
</html>
