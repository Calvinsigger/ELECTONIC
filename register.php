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
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: url('uploads/zz.jpg') center/cover no-repeat fixed;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #333;
        padding: 20px;
    }

    .form-container {
        background: white;
        padding: 45px;
        border-radius: 16px;
        width: 100%;
        max-width: 480px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
        border-left: 5px solid #667eea;
        animation: slideIn 0.5s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .home-btn {
        display: inline-block;
        width: 100%;
        padding: 12px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 25px;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 15px;
        text-align: center;
    }

    .home-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    .form-container h2 {
        margin-bottom: 30px;
        color: #333;
        font-size: 28px;
        font-weight: 700;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        text-align: center;
    }

    .form-container input {
        width: 100%;
        padding: 14px 16px;
        margin-bottom: 15px;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
        font-size: 15px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        background: #f8f9ff;
    }

    .form-container input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-container input::placeholder {
        color: #999;
        font-weight: 500;
    }

    .form-container small {
        color: #666;
        font-size: 13px;
        display: block;
        margin-top: -10px;
        margin-bottom: 12px;
        font-weight: 500;
    }

    .form-container button {
        width: 100%;
        padding: 14px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        font-size: 16px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s ease;
        margin-top: 8px;
    }

    .form-container button:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 30px rgba(102, 126, 234, 0.4);
    }

    .form-container button:active {
        transform: translateY(-1px);
    }

    .message {
        background: #eef;
        color: #338833;
        padding: 14px 16px;
        margin-bottom: 20px;
        font-weight: 600;
        border-radius: 10px;
        border-left: 4px solid #338833;
        text-align: center;
        animation: slideIn 0.3s ease;
    }

    .message a {
        color: #667eea;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .message a:hover {
        text-decoration: underline;
    }

    .error {
        background: #fee;
        color: #c33;
        padding: 14px 16px;
        margin-bottom: 20px;
        font-weight: 600;
        border-radius: 10px;
        border-left: 4px solid #c33;
        text-align: center;
        animation: shake 0.3s ease;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .link {
        margin-top: 20px;
        text-align: center;
        color: #666;
        font-size: 15px;
    }

    .link a {
        text-decoration: none;
        color: #667eea;
        font-weight: 700;
        transition: all 0.3s ease;
    }

    .link a:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    .validation-message {
        font-size: 13px;
        margin-top: -10px;
        margin-bottom: 12px;
        padding: 8px 12px;
        border-radius: 6px;
        display: none;
        font-weight: 500;
    }

    .validation-message.error {
        background: #fee;
        color: #c33;
        border-left: 3px solid #c33;
        display: block;
        animation: slideIn 0.2s ease;
    }

    .validation-message.success {
        background: #efe;
        color: #338833;
        border-left: 3px solid #338833;
        display: block;
    }

    .validation-message.info {
        background: #eef;
        color: #336;
        border-left: 3px solid #667eea;
        display: block;
    }

    @media (max-width: 480px) {
        .form-container {
            padding: 30px 20px;
            border-radius: 12px;
        }

        .form-container h2 {
            font-size: 24px;
        }

        .form-container input {
            padding: 12px 14px;
            font-size: 14px;
        }

        .form-container button {
            padding: 12px 14px;
            font-size: 15px;
        }
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
        <input type="text" name="fullname" id="fullname" placeholder="Full Name" maxlength="100" required value="<?= sanitizeOutput($_POST['fullname'] ?? '') ?>" oninput="validateFullnameField()">
        <div id="fullnameMsg" class="validation-message info">Full Name must be at least 2 characters</div>

        <input type="email" name="email" id="email" placeholder="Email Address" maxlength="100" required value="<?= sanitizeOutput($_POST['email'] ?? '') ?>" oninput="validateEmailField()">
        <div id="emailMsg" class="validation-message info">Enter a valid email address (example@domain.com)</div>

        <input type="password" name="password" id="password" placeholder="Password (min 8 chars, uppercase, lowercase, number)" minlength="8" required oninput="validatePasswordField()">
        <div id="passwordMsg" class="validation-message info">Password must contain: uppercase, lowercase, number (min 8 characters)</div>

        <input type="password" name="password_confirm" id="password_confirm" placeholder="Confirm Password" minlength="8" required oninput="validatePasswordConfirmField()">
        <div id="passwordConfirmMsg" class="validation-message"></div>

        <button type="submit">Register</button>
    </form>

    <div class="link">
        Already have an account? <a href="login.php">Login</a>
    </div>
</div>

<script>
// Real-time validation functions
function validateFullnameField() {
    const fullname = document.getElementById("fullname").value.trim();
    const msg = document.getElementById("fullnameMsg");
    
    if (fullname.length === 0) {
        msg.textContent = "Full Name is required";
        msg.className = "validation-message error";
        return false;
    } else if (fullname.length < 2) {
        msg.textContent = "Full Name must be at least 2 characters";
        msg.className = "validation-message error";
        return false;
    } else if (fullname.length > 100) {
        msg.textContent = "Full Name cannot exceed 100 characters";
        msg.className = "validation-message error";
        return false;
    } else {
        msg.textContent = "✓ Full Name looks good!";
        msg.className = "validation-message success";
        return true;
    }
}

function validateEmailField() {
    const email = document.getElementById("email").value.trim();
    const msg = document.getElementById("emailMsg");
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email.length === 0) {
        msg.textContent = "Email is required";
        msg.className = "validation-message error";
        return false;
    } else if (!emailRegex.test(email)) {
        msg.textContent = "Please enter a valid email address (example@domain.com)";
        msg.className = "validation-message error";
        return false;
    } else {
        msg.textContent = "✓ Email is valid!";
        msg.className = "validation-message success";
        return true;
    }
}

function validatePasswordField() {
    const password = document.getElementById("password").value;
    const msg = document.getElementById("passwordMsg");
    let issues = [];
    
    if (password.length === 0) {
        msg.textContent = "Password is required";
        msg.className = "validation-message error";
        return false;
    }
    
    if (password.length < 8) issues.push("at least 8 characters");
    if (!/[A-Z]/.test(password)) issues.push("1 uppercase letter");
    if (!/[a-z]/.test(password)) issues.push("1 lowercase letter");
    if (!/[0-9]/.test(password)) issues.push("1 number");
    
    if (issues.length > 0) {
        msg.textContent = "Missing: " + issues.join(", ");
        msg.className = "validation-message error";
        return false;
    } else {
        msg.textContent = "✓ Password is strong!";
        msg.className = "validation-message success";
        return true;
    }
}

function validatePasswordConfirmField() {
    const password = document.getElementById("password").value;
    const passwordConfirm = document.getElementById("password_confirm").value;
    const msg = document.getElementById("passwordConfirmMsg");
    
    if (passwordConfirm.length === 0) {
        msg.textContent = "Please confirm your password";
        msg.className = "validation-message error";
        return false;
    } else if (password !== passwordConfirm) {
        msg.textContent = "Passwords do not match";
        msg.className = "validation-message error";
        return false;
    } else {
        msg.textContent = "✓ Passwords match!";
        msg.className = "validation-message success";
        return true;
    }
}

function validateForm() {
    // Run all validations
    const isFullnameValid = validateFullnameField();
    const isEmailValid = validateEmailField();
    const isPasswordValid = validatePasswordField();
    const isPasswordConfirmValid = validatePasswordConfirmField();
    
    // Return true only if all fields are valid
    return isFullnameValid && isEmailValid && isPasswordValid && isPasswordConfirmValid;
}
</script>

</body>
</html>
