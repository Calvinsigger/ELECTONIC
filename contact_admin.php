<?php
session_start();
require_once "api/db.php";
require_once "api/validation.php";
require_once "api/security.php";

$messageMsg = "";
$messageError = false;
$isLoggedIn = isset($_SESSION['user_id']);
$userEmail = $_SESSION['email'] ?? '';

/* ===== PROCESS CONTACT FORM ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {

    /* ===== CSRF TOKEN VALIDATION ===== */
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $messageMsg = "Security token is invalid. Please try again.";
        $messageError = true;
    } else {

        $fullname = $_POST['fullname'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $reason = $_POST['reason'] ?? '';
        $message = $_POST['message'] ?? '';
        $user_id = $isLoggedIn ? $_SESSION['user_id'] : NULL;

        // Validate fullname
        $nameValidation = validateFullname($fullname);
        if (!$nameValidation['valid']) {
            $messageMsg = $nameValidation['message'];
            $messageError = true;
        }
        // Validate email
        else if (!validateEmail($email)) {
            $messageMsg = "Invalid email format.";
            $messageError = true;
        }
        // Validate phone (optional but if provided, validate format)
        else if (!empty($phone)) {
            $phoneValidation = validatePhone($phone);
            if (!$phoneValidation['valid']) {
                $messageMsg = $phoneValidation['message'];
                $messageError = true;
            }
        }
        // Validate reason
        else if (empty($reason)) {
            $messageMsg = "Please select a reason for contacting us.";
            $messageError = true;
        }
        // Validate message
        else if (strlen($message) < 10) {
            $messageMsg = "Message must be at least 10 characters long.";
            $messageError = true;
        }
        else if (strlen($message) > 1000) {
            $messageMsg = "Message cannot exceed 1000 characters.";
            $messageError = true;
        }
        else {

            try {
                // Insert contact message
                $stmt = $conn->prepare("
                    INSERT INTO contact_messages (user_id, fullname, email, phone, reason, message, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'unread')
                ");
                $stmt->execute([
                    $user_id,
                    $nameValidation['value'],
                    $email,
                    (!empty($phone) ? $phoneValidation['value'] : NULL),
                    $reason,
                    trim($message)
                ]);

                $messageMsg = "✅ Your message has been sent successfully! Our admin team will review it shortly.";
                $messageError = false;

                // Clear form
                $_POST = [];

            } catch (PDOException $e) {
                $messageMsg = "Error sending message: " . $e->getMessage();
                $messageError = true;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Admin | ElectroStore</title>
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
header nav a{color:white;text-decoration:none;margin-left:20px;font-weight:500;transition:all 0.3s ease;}
header nav a:hover{color:#ffdd59;transform:translateY(-2px);}

/* CONTAINER */
.container{
    max-width:700px;
    margin:40px auto;
    padding:40px;
    background:white;
    border-radius:14px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
}

h1{color:#0a3d62;margin-bottom:10px;font-size:32px;font-weight:700;text-align:center;}
.subtitle{text-align:center;color:#666;margin-bottom:30px;font-size:15px;}

/* MESSAGE */
.message{
    text-align:center;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
    font-weight:600;
}
.message.error{
    color:#721c24;
    background:#f8d7da;
    border:2px solid #f5c6cb;
    border-left:4px solid #f5c6cb;
}
.message.success{
    color:#155724;
    background:#d4edda;
    border:2px solid #c3e6cb;
    border-left:4px solid #c3e6cb;
}

/* INFO BOX */
.info-box{
    background:#f8f9fa;
    padding:15px;
    border-radius:10px;
    margin-bottom:25px;
    border-left:4px solid #667eea;
}
.info-box p{color:#666;font-size:14px;margin-bottom:8px;line-height:1.6;}
.info-box p:last-child{margin-bottom:0;}

/* FORM */
form{padding:10px 0;}
form .form-group{margin-bottom:20px;}
form label{
    display:block;
    margin-bottom:8px;
    font-weight:600;
    color:#0a3d62;
    font-size:15px;
}
form input, form select, form textarea{
    width:100%;
    padding:14px 16px;
    border-radius:8px;
    border:2px solid #e0e0e0;
    font-size:16px;
    transition:all 0.3s ease;
    font-family:inherit;
}
form input:focus, form select:focus, form textarea:focus{
    outline:none;
    border-color:#667eea;
    box-shadow:0 0 0 3px rgba(102,126,234,0.1);
}
form select{
    cursor:pointer;
    appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 15px center;
    padding-right:40px;
}

form textarea{
    resize:vertical;
    min-height:150px;
    max-height:400px;
}

.char-count{
    font-size:13px;
    color:#999;
    margin-top:5px;
    text-align:right;
}

/* BUTTON */
form button{
    padding:14px 28px;
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    border:none;
    border-radius:8px;
    font-size:16px;
    width:100%;
    cursor:pointer;
    font-weight:600;
    transition:all 0.3s ease;
    margin-top:10px;
}
form button:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,0.3);}
form button:active{transform:translateY(0);}

/* BACK LINK */
.back-link{
    text-align:center;
    margin-top:20px;
}
.back-link a{color:#667eea;text-decoration:none;font-weight:600;transition:all 0.3s ease;}
.back-link a:hover{color:#764ba2;text-decoration:underline;}

/* REASONS */
.reasons-list{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-top:8px;
}
.reasons-list option{padding:8px;}

@media(max-width:768px){
    .container{padding:20px;margin:20px;}
    .reasons-list{grid-template-columns:1fr;}
}
</style>
</head>

<body>
<header>
    <div class="logo">🛍️ ElectroStore</div>
    <nav>
        <?php if($isLoggedIn): ?>
            <?php if($_SESSION['role'] === 'customer'): ?>
            <a href="customer/customer_dashboard.php">📊 Dashboard</a>
            <a href="customer/shop.php">🏪 Shop</a>
            <a href="customer/cart.php">🛒 Cart</a>
            <?php else: ?>
            <a href="admin/admin_dashboard.php">📊 Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php">🚪 Logout</a>
        <?php else: ?>
            <a href="login.php">🔑 Login</a>
            <a href="register.php">📝 Register</a>
        <?php endif; ?>
    </nav>
</header>

<div class="container">
    <h1>📧 Contact Admin</h1>
    <p class="subtitle">Send us a message and our admin team will get back to you soon</p>

    <?php if($messageMsg): ?>
        <p class="message <?= $messageError ? 'error' : 'success' ?>">
            <?= htmlspecialchars($messageMsg) ?>
        </p>
    <?php endif; ?>

    <!-- INFO BOX -->
    <div class="info-box">
        <p><strong>⏱️ Response Time:</strong> We typically respond within 24-48 hours</p>
        <p><strong>📞 Urgent Issues?</strong> For immediate assistance, please call our support team</p>
    </div>

    <!-- CONTACT FORM -->
    <form method="POST" autocomplete="off">
        <?= getCSRFTokenInput() ?>

        <!-- FULL NAME -->
        <div class="form-group">
            <label for="fullname">👤 Full Name *</label>
            <input type="text" id="fullname" name="fullname" placeholder="Your full name" maxlength="100" required value="<?= sanitizeOutput($_POST['fullname'] ?? '') ?>">
        </div>

        <!-- EMAIL -->
        <div class="form-group">
            <label for="email">📧 Email Address *</label>
            <input type="email" id="email" name="email" placeholder="your.email@example.com" maxlength="100" required value="<?= sanitizeOutput($_POST['email'] ?? $userEmail) ?>">
        </div>

        <!-- PHONE (Optional) -->
        <div class="form-group">
            <label for="phone">📱 Phone Number (Optional)</label>
            <input type="tel" id="phone" name="phone" placeholder="Your phone number" maxlength="20" value="<?= sanitizeOutput($_POST['phone'] ?? '') ?>">
        </div>

        <!-- REASON -->
        <div class="form-group">
            <label for="reason">🏷️ Reason for Contact *</label>
            <select id="reason" name="reason" required style="appearance: none;">
                <option value="">-- Select a reason --</option>
                <option value="account_blocked">Account Blocked</option>
                <option value="order_issue">Order Issue</option>
                <option value="payment_issue">Payment Issue</option>
                <option value="product_quality">Product Quality</option>
                <option value="refund_request">Refund Request</option>
                <option value="technical_issue">Technical Issue</option>
                <option value="general_inquiry">General Inquiry</option>
                <option value="other">Other</option>
            </select>
        </div>

        <!-- MESSAGE -->
        <div class="form-group">
            <label for="message">💬 Message *</label>
            <textarea id="message" name="message" placeholder="Please describe your issue or inquiry in detail..." maxlength="1000" required><?= sanitizeOutput($_POST['message'] ?? '') ?></textarea>
            <div class="char-count">
                <span id="char-count">0</span>/1000
            </div>
        </div>

        <button type="submit" name="send_message">✓ Send Message</button>
    </form>

    <div class="back-link">
        <?php if($isLoggedIn): ?>
            <a href="<?= $_SESSION['role'] === 'customer' ? 'customer/customer_dashboard.php' : 'admin/admin_dashboard.php' ?>">← Back to Dashboard</a>
        <?php else: ?>
            <a href="login.php">← Back to Login</a>
        <?php endif; ?>
    </div>
</div>

<script>
// Character counter
document.getElementById('message').addEventListener('input', function() {
    document.getElementById('char-count').textContent = this.value.length;
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const message = document.getElementById('message').value.trim();
    if (message.length < 10) {
        e.preventDefault();
        alert('Message must be at least 10 characters long.');
        return false;
    }
});
</script>
</body>
</html>
