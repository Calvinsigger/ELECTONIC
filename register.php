<?php
session_start();
require_once "api/db.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullname = trim($_POST["fullname"]);
    $email    = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];
    $role     = "customer"; // Automatically assign role as 'customer'

    /* ===== BASIC VALIDATION ===== */
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {

        /* ===== CHECK EMAIL ===== */
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            $error = "Email already registered.";
        } else {

            /* ===== INSERT USER ===== */
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (fullname, email, password_hash, role)
                 VALUES (?, ?, ?, ?)"
            );

            if ($stmt->execute([$fullname, $email, $password_hash, $role])) {
                $message = "Registration successful! You can now <a href='login.php'>login</a>.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    background: linear-gradient(to right, #1e90ff, #0a3d62);
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.form-container {
    background: white;
    padding: 35px;
    width: 420px;
    border-radius: 10px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
}

.form-container h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #0a3d62;
}

.form-container input {
    width: 100%;
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 5px;
    border: 1px solid #ccc;
}

.form-container button {
    width: 100%;
    padding: 12px;
    background: #0a3d62;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

.form-container button:hover {
    background: #07406b;
}

.message {
    text-align: center;
    margin-bottom: 15px;
    color: green;
}

.error {
    text-align: center;
    margin-bottom: 15px;
    color: red;
}

.link {
    text-align: center;
    margin-top: 10px;
}

.link a {
    text-decoration: none;
    color: #1e90ff;
}
</style>
</head>

<body>

<div class="form-container">
    <h2>Create Account</h2>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm()">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" id="password" placeholder="Password" required>
        <button type="submit">Register</button>
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

<script>
function validateForm() {
    const password = document.getElementById("password").value;
    if (password.length < 6) {
        alert("Password must be at least 6 characters.");
        return false;
    }
    return true;
}
</script>

</body>
</html>
