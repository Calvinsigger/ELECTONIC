<?php
session_start();
require_once "api/db.php";
require_once "api/validation.php";
require_once "api/security.php";

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ===== CSRF TOKEN VALIDATION ===== */
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security token is invalid. Please try again.";
    } else {

        /* ===== INPUT VALIDATION ===== */
        $fullname = $_POST["fullname"] ?? '';
        $email    = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';
        $password_confirm = $_POST["password_confirm"] ?? '';
        $role     = "customer";

        // Validate fullname
        $nameValidation = validateFullname($fullname);
        if (!$nameValidation['valid']) {
            $error = $nameValidation['message'];
        }
        // Validate email
        elseif (!validateEmail($email)) {
            $error = "Invalid email format.";
        }
        // Check password match
        elseif ($password !== $password_confirm) {
            $error = "Passwords do not match.";
        }
        // Validate password strength
        else {
            $passwordValidation = validatePassword($password);
            if (!$passwordValidation['valid']) {
                $error = $passwordValidation['message'];
            } else {

                /* ===== CHECK EMAIL UNIQUENESS ===== */
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);

                if ($stmt->rowCount() > 0) {
                    $error = "Email already registered.";
                } else {

                    /* ===== INSERT USER ===== */
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);

                    try {
                        $stmt = $conn->prepare(
                            "INSERT INTO users (fullname, email, password_hash, role)
                             VALUES (?, ?, ?, ?)"
                        );

                        if ($stmt->execute([$fullname, $email, $password_hash, $role])) {
                            $message = "✅ Registration successful! You can now <a href='login.php'>login</a>.";
                            // Clear form
                            $_POST = [];
                        } else {
                            $error = "Registration failed. Please try again.";
                        }
                    } catch (PDOException $e) {
                        $error = "Database error: " . $e->getMessage();
                    }
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
<title>Register | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    background: linear-gradient(to right, #1e90ff, #0a3d62);
     background:url('uploads/zz.jpg') center/cover no-repeat;
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

.form-container {
    background: #1e90ff;
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
    margin-top: 15px;
}

.link a {
    text-decoration: none;
    color: #1307F9;
}

.home-btn {
    display: inline-block;
    width: 100%;
    padding: 12px;
    background: #0a3d62;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    margin-bottom: 15px;
    transition: 0.3s;
}

.home-btn:hover {
    background: #07406b;
}
</style>
</head>

<body>

<div class="form-container">
    <a href="index.php" class="home-btn">← Home</a>
    <h2>Create Account</h2>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" onsubmit="return validateForm()">
        <?= getCSRFTokenInput() ?>
        <input type="text" name="fullname" placeholder="Full Name" maxlength="100" required value="<?= sanitizeOutput($_POST['fullname'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email Address" maxlength="100" required value="<?= sanitizeOutput($_POST['email'] ?? '') ?>">
        <input type="password" name="password" id="password" placeholder="Password (min 8 chars, uppercase, lowercase, number)" minlength="8" required>
        <input type="password" name="password_confirm" id="password_confirm" placeholder="Confirm Password" minlength="8" required>
        <small style="color: #666; margin-top: -12px; margin-bottom: 10px; display: block;">Password must contain: uppercase, lowercase, number (min 8 characters)</small>
        <button type="submit">Register</button>
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

<script>
function validateForm() {
    const fullname = document.querySelector('input[name="fullname"]').value.trim();
    const email = document.querySelector('input[name="email"]').value.trim();
    const password = document.getElementById("password").value;
    const passwordConfirm = document.getElementById("password_confirm").value;

    // Fullname validation
    if (fullname.length < 2) {
        alert("Name must be at least 2 characters.");
        return false;
    }

    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }

    // Password validation
    if (password.length < 8) {
        alert("Password must be at least 8 characters.");
        return false;
    }
    if (!/[A-Z]/.test(password)) {
        alert("Password must contain at least one uppercase letter.");
        return false;
    }
    if (!/[a-z]/.test(password)) {
        alert("Password must contain at least one lowercase letter.");
        return false;
    }
    if (!/[0-9]/.test(password)) {
        alert("Password must contain at least one number.");
        return false;
    }

    // Password confirmation
    if (password !== passwordConfirm) {
        alert("Passwords do not match.");
        return false;
    }

    return true;
}
</script>

</body>
</html>
