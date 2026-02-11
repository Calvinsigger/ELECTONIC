<?php
session_start();
require_once "../api/db.php";
require_once "../api/validation.php";
require_once "../api/security.php";

/* ===== SECURITY: CUSTOMER ONLY ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===== CHECK IF ORDER EXISTS ===== */
if (!isset($_SESSION['order_id'])) {
    header("Location: checkout.php");
    exit;
}

$order_id = $_SESSION['order_id'];
$order_amount = $_SESSION['order_amount'] ?? 0;

/* ===== FETCH ORDER DETAILS ===== */
$stmt = $conn->prepare("
    SELECT id, fullname, address, phone, total_amount, status 
    FROM orders 
    WHERE id = ? AND user_id = ? AND status = 'pending'
");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: my_orders.php");
    exit;
}

/* ===== PROCESS PAYMENT ===== */
$paymentMsg = "";
$paymentError = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {

    /* ===== CSRF TOKEN VALIDATION ===== */
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $paymentMsg = "Security token is invalid. Please try again.";
        $paymentError = true;
    } else {

        $paymentMethod = $_POST['payment_method'] ?? '';
        $phoneNumber = $_POST['phone_number'] ?? '';

        // Validate payment method selection
        if (empty($paymentMethod)) {
            $paymentMsg = "Please select a payment method.";
            $paymentError = true;
        } 
        // Validate phone number
        else {
            $phoneValidation = validateMobileNumber($phoneNumber);
            if (!$phoneValidation['valid']) {
                $paymentMsg = $phoneValidation['message'];
                $paymentError = true;
            } else {

                    try {
                        // Generate transaction ID
                        $transactionId = "TXN-" . $order_id . "-" . strtoupper(substr($paymentMethod, 0, 3)) . "-" . time() . "-" . bin2hex(random_bytes(4));

                        // Create payment record with mobile payment method
                        $stmt = $conn->prepare("
                            INSERT INTO payments (order_id, user_id, amount, payment_method, card_last4, transaction_id, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $order_id,
                            $user_id,
                            $order['total_amount'],
                            $paymentMethod,
                            substr($phoneNumber, -4), // Store last 4 digits of phone
                            $transactionId,
                            'completed'
                        ]);

                        // Update order status
                        $stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
                        $stmt->execute([$order_id]);

                        // Clear session vars
                        unset($_SESSION['order_id']);
                        unset($_SESSION['order_amount']);

                        // Redirect to success page
                        $_SESSION['payment_success'] = true;
                        $_SESSION['transaction_id'] = $transactionId;
                        $_SESSION['payment_method'] = $paymentMethod;
                        $_SESSION['payment_phone'] = $phoneNumber;
                        header("Location: payment_success.php?order=$order_id");
                        exit;

                    } catch (PDOException $e) {
                        $paymentMsg = "Payment processing error: " . $e->getMessage();
                        $paymentError = true;
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
<title>Mobile Payment | ElectroStore</title>
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
h1{color:#0a3d62;margin-bottom:30px;font-size:32px;font-weight:700;text-align:center;}

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

/* ORDER SUMMARY */
.order-summary{
    background:#f8f9fa;
    padding:20px;
    border-radius:10px;
    margin-bottom:30px;
    border-left:4px solid #667eea;
}
.order-summary h3{color:#0a3d62;margin-bottom:12px;font-size:18px;}
.order-summary p{color:#666;font-size:14px;margin-bottom:8px;display:flex;justify-content:space-between;}
.order-summary .amount{
    font-size:24px;
    font-weight:700;
    color:#667eea;
    margin-top:15px;
    border-top:2px solid #e0e0e0;
    padding-top:15px;
    display:flex;
    justify-content:space-between;
}

/* PAYMENT METHODS */
.payment-methods{
    margin-bottom:30px;
}
.payment-methods h2{
    color:#0a3d62;
    font-size:16px;
    margin-bottom:15px;
    font-weight:700;
}

.payment-option{
    display:grid;
    grid-template-columns:auto 1fr;
    gap:15px;
    padding:15px;
    border:2px solid #e0e0e0;
    border-radius:10px;
    margin-bottom:10px;
    cursor:pointer;
    transition:all 0.3s ease;
    align-items:center;
}
.payment-option:hover{
    border-color:#667eea;
    background:#f9f9ff;
}
.payment-option input[type="radio"]{
    width:24px;
    height:24px;
    cursor:pointer;
    accent-color:#667eea;
}
.payment-option input[type="radio"]:checked ~ .method-info{
    color:#667eea;
    font-weight:600;
}

.method-info{
    display:flex;
    flex-direction:column;
    gap:3px;
}
.method-info .name{
    font-size:16px;
    font-weight:600;
    color:#0a3d62;
}
.method-info .code{
    font-size:13px;
    color:#999;
}

.payment-option input[type="radio"]:checked{
    accent-color:#28a745;
}
.payment-option input[type="radio"]:checked ~ .method-info .name{
    color:#28a745;
}

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
form input{
    width:100%;
    padding:14px 16px;
    border-radius:8px;
    border:2px solid #e0e0e0;
    font-size:16px;
    transition:all 0.3s ease;
    font-family:inherit;
}
form input:focus{
    outline:none;
    border-color:#667eea;
    box-shadow:0 0 0 3px rgba(102,126,234,0.1);
}

.phone-format-hint{
    font-size:13px;
    color:#999;
    margin-top:8px;
    font-style:italic;
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

/* SECURITY INFO */
.security-info{
    text-align:center;
    color:#666;
    font-size:13px;
    margin-top:20px;
    padding-top:20px;
    border-top:2px solid #f0f0f0;
}

/* BACK LINK */
.back-link{
    text-align:center;
    margin-top:20px;
}
.back-link a{color:#667eea;text-decoration:none;font-weight:600;transition:all 0.3s ease;}
.back-link a:hover{color:#764ba2;text-decoration:underline;}

/* PAYMENT LOGOS */
.payment-logo{
    font-size:24px;
    margin-right:10px;
}

@media(max-width:768px){
    .container{padding:20px;margin:20px;}
    .payment-option{grid-template-columns:20px 1fr;}
}
</style>
</head>

<body>
<header>
    <div class="logo">🛍️ ElectroStore</div>
    <nav>
        <a href="customer_dashboard.php">📊 Dashboard</a>
        <a href="shop.php">🏪 Shop</a>
        <a href="cart.php">🛒 Cart</a>
        <a href="../logout.php">🚪 Logout</a>
    </nav>
</header>

<div class="container">
    <h1>📱 Mobile Payment</h1>

    <?php if($paymentMsg): ?>
        <p class="message <?= $paymentError ? 'error' : 'success' ?>">
            <?= htmlspecialchars($paymentMsg) ?>
        </p>
    <?php endif; ?>

    <!-- ORDER SUMMARY -->
    <div class="order-summary">
        <h3>📦 Order Summary</h3>
        <p>
            <strong>Order ID:</strong>
            <span>#<?= $order['id'] ?></span>
        </p>
        <p>
            <strong>Name:</strong>
            <span><?= htmlspecialchars($order['fullname']) ?></span>
        </p>
        <p>
            <strong>Address:</strong>
            <span><?= htmlspecialchars(substr($order['address'], 0, 40)) ?>...</span>
        </p>
        <div class="amount">
            <strong>Amount Due:</strong>
            <span style="color:#28a745;">TZS <?= number_format($order['total_amount'], 2) ?></span>
        </div>
    </div>

    <!-- PAYMENT FORM -->
    <form method="POST" onsubmit="return validatePaymentForm()" autocomplete="off">
        <?= getCSRFTokenInput() ?>

        <!-- PAYMENT METHODS SELECTION -->
        <div class="payment-methods">
            <h2>🇹🇿 Select Payment Method</h2>

            <label class="payment-option" onclick="selectPayment('airtel_money')">
                <input type="radio" name="payment_method" value="airtel_money" required>
                <div class="method-info">
                    <span class="name">📱 AIRTEL MONEY</span>
                    <span class="code">Pay via Airtel Money (Airtel TZ)</span>
                </div>
            </label>

            <label class="payment-option" onclick="selectPayment('mpesa')">
                <input type="radio" name="payment_method" value="mpesa" required>
                <div class="method-info">
                    <span class="name">📱 M-PESA</span>
                    <span class="code">Pay via Safaricom M-Pesa</span>
                </div>
            </label>

            <label class="payment-option" onclick="selectPayment('halotel')">
                <input type="radio" name="payment_method" value="halotel" required>
                <div class="method-info">
                    <span class="name">📱 HALOTEL MONEY</span>
                    <span class="code">Pay via Halotel Money (Halotel TZ)</span>
                </div>
            </label>

            <label class="payment-option" onclick="selectPayment('mixbvy')">
                <input type="radio" name="payment_method" value="mixbvy" required>
                <div class="method-info">
                    <span class="name">📱 MIX BY YAS</span>
                    <span class="code">Pay via Mix by Yas</span>
                </div>
            </label>
        </div>

        <!-- PHONE NUMBER -->
        <div class="form-group">
            <label for="phone_number">📱 Mobile Number *</label>
            <input type="tel" id="phone_number" name="phone_number" placeholder="Enter your mobile number (e.g., 0654321098)" maxlength="15" required autocomplete="off" value="<?= sanitizeOutput($_POST['phone_number'] ?? '') ?>">
            <div class="phone-format-hint">Format: 07XXXXXXXX or +255XXXXXXXXX</div>
        </div>

        <button type="submit" name="process_payment">✓ Complete Payment (TZS <?= number_format($order['total_amount'], 2) ?>)</button>
    </form>

    <!-- SECURITY INFO -->
    <div class="security-info">
        🔒 Your transaction is secure. Payment processed via Tanzanian mobile money networks.
    </div>

    <div class="back-link">
        <a href="checkout.php">← Back to checkout</a>
    </div>
</div>

<script>
function selectPayment(method) {
    const radio = document.querySelector(`input[name="payment_method"][value="${method}"]`);
    if (radio) {
        radio.checked = true;
    }
}

function validatePaymentForm() {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    const phoneNumber = document.getElementById('phone_number').value.trim();

    if (!paymentMethod) {
        alert("Please select a payment method.");
        return false;
    }

    if (!phoneNumber) {
        alert("Please enter your mobile number.");
        return false;
    }

    // Validate phone format
    const formatPhoneRegex = /^\+?255[67]\d{8}$|^0[67]\d{8}$/;

    if (!formatPhoneRegex.test(phoneNumber.replace(/\s|-/g, ''))) {
        alert("Please enter a valid Tanzanian mobile number.\nFormat: 0XXXXXXXXX or +255XXXXXXXXX");
        return false;
    }

    // Show confirmation
    const methods = {
        'airtel_money': 'AIRTEL MONEY',
        'mpesa': 'M-PESA',
        'halotel': 'HALOTEL MONEY',
        'mixbvy': 'MIX BY YAS'
    };

    const confirm_msg = `Confirm Payment:\n\nMethod: ${methods[paymentMethod.value]}\nPhone: ${phoneNumber}\n\nPress OK to continue.`;
    return confirm(confirm_msg);
}

// Auto-format phone number as user types
document.getElementById('phone_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    
    // Limit to reasonable length
    if (value.length > 12) {
        value = value.slice(0, 12);
    }
    
    e.target.value = value;
});


</script>
</body>
</html>
