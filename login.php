<?php
session_start();
require_once __DIR__ . "/api/db.php";
require_once __DIR__ . "/api/validation.php";
require_once __DIR__ . "/api/security.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* ===== CSRF TOKEN VALIDATION ===== */
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = "Security token is invalid. Please try again.";
    } else {

        $email    = $_POST["email"] ?? '';
        $password = $_POST["password"] ?? '';

        // Validate email format
        if (!validateEmail($email)) {
            $error = "Invalid email format.";
        } else if (isEmpty($password)) {
            $error = "Password is required.";
        } else {

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
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: url('uploads/abcd.jpg') center/cover no-repeat fixed;
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
        max-width: 420px;
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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #ffffff;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-bottom: 30px;
        transition: all 0.3s ease;
        cursor: pointer;
        font-size: 14px;
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
    }

    .form-container input {
        width: 100%;
        padding: 14px 16px;
        margin-bottom: 18px;
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

    .error {
        background: #fee;
        color: #c33;
        padding: 14px 16px;
        margin-bottom: 20px;
        font-weight: 600;
        border-radius: 10px;
        border-left: 4px solid #c33;
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
        <?= getCSRFTokenInput() ?>
        <input type="email" name="email" placeholder="Email Address" maxlength="100" required value="<?= sanitizeOutput($_POST['email'] ?? '') ?>">
        <input type="password" name="password" placeholder="Password" maxlength="100" required>
        <button type="submit">Login</button>
    </form>

    <div class="link">
        Don’t have an account? <a href="register.php">Register</a>
    </div>
</div>

</body>
</html>
