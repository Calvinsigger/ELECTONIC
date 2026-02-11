<?php
session_start();
require_once __DIR__ . "/api/db.php";
require_once __DIR__ . "/api/security.php";
require_once __DIR__ . "/api/validation.php";

$error = "";
$success = "";
$email = trim($_GET['email'] ?? '');

/* ===== HANDLE MESSAGE SUBMISSION (NO LOGIN REQUIRED) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $contact_email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($contact_email) || empty($subject) || empty($message)) {
            $error = "All fields are required.";
        } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address.";
        } elseif (strlen($subject) > 255) {
            $error = "Subject must not exceed 255 characters.";
        } elseif (strlen($message) > 5000) {
            $error = "Message must not exceed 5000 characters.";
        } else {
            // Check if user account exists with this email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$contact_email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                try {
                    // Store message linked to user ID
                    $stmt = $conn->prepare(
                        "INSERT INTO customer_messages (user_id, subject, message, status) VALUES (?, ?, ?, 'unread')"
                    );
                    $stmt->execute([$user['id'], $subject, $message]);
                    $success = "✓ Your message has been sent successfully! Admin will review it shortly.";
                    $email = '';
                } catch (PDOException $e) {
                    $error = "Database error: The messaging system may not be set up. Please contact admin directly or try again later.";
                    // Log the actual error for debugging
                    error_log("Message DB Error: " . $e->getMessage());
                }
            } else {
                $error = "No account found with this email address.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Admin | Account Support</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}

.container {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 100%;
    animation: slideIn 0.4s ease;
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

.header {
    text-align: center;
    margin-bottom: 30px;
}

.header h1 {
    color: #0a3d62;
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 10px;
}

.header p {
    color: #666;
    font-size: 14px;
}

.alert {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
    border-left: 4px solid;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left-color: #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

.alert-info {
    background: #cfe8ff;
    color: #084298;
    border-left-color: #0d6efd;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-size: 14px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.char-count {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.form-buttons {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn {
    flex: 1;
    padding: 12px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-back {
    background: #999;
    flex: 0.5;
}

.btn-back:hover {
    background: #777;
}

.info-box {
    background: #f0f2ff;
    border-left: 4px solid #667eea;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #555;
    line-height: 1.6;
}

@media (max-width: 480px) {
    .container {
        padding: 25px;
    }

    .header h1 {
        font-size: 24px;
    }

    .form-buttons {
        flex-direction: column;
    }

    .btn-back {
        flex: 1;
    }
}
</style>
</head>

<body>

<div class="container">

    <div class="header">
        <h1>📧 Contact Admin</h1>
        <p>Send a message to our support team</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <div class="alert alert-info">
            ✓ Your message has been submitted. Our admin team will review it and contact you as soon as possible.
        </div>
        <div style="text-align:center;margin-top:30px;">
            <a href="login.php" class="btn" style="text-decoration:none;display:inline-block;width:auto;padding:12px 30px;">← Return to Login</a>
        </div>
    <?php else: ?>

        <div class="info-box">
            💡 If your account has been blocked, please use this form to explain your situation or request an appeal. Our admin team reviews all messages and will respond promptly.
        </div>

        <form method="POST">
            <?= getCSRFTokenInput() ?>
            <input type="hidden" name="action" value="send">

            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" 
                    placeholder="your@email.com"
                    value="<?= htmlspecialchars($email) ?>"
                    required>
                <div class="char-count">Account email associated with your profile</div>
            </div>

            <div class="form-group">
                <label for="subject">Subject *</label>
                <input type="text" id="subject" name="subject" 
                    placeholder="e.g., Account Appeal, Account Issue..."
                    minlength="5" maxlength="255" required>
                <div class="char-count"><span id="subject-count">0</span>/255</div>
            </div>

            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" 
                    placeholder="Please provide details about your situation..."
                    minlength="10" maxlength="5000" required></textarea>
                <div class="char-count"><span id="message-count">0</span>/5000</div>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn">Send Message</button>
                <a href="login.php" class="btn btn-back" style="display:flex;align-items:center;justify-content:center;text-decoration:none;">Back</a>
            </div>
        </form>

    <?php endif; ?>

</div>

<script>
// Real-time character counting
document.getElementById('subject').addEventListener('input', function() {
    document.getElementById('subject-count').innerText = this.value.length;
});

document.getElementById('message').addEventListener('input', function() {
    document.getElementById('message-count').innerText = this.value.length;
});
</script>

</body>
</html>
