<?php
session_start();
require_once "../api/db.php";
require_once "../api/security.php";

/* ===== SECURITY: CUSTOMER ONLY ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['order'] ?? 0;

/* ===== FETCH ORDER & PAYMENT DETAILS ===== */
$stmt = $conn->prepare("
    SELECT o.id, o.fullname, o.address, o.phone, o.total_amount, o.status, o.created_at,
           p.transaction_id, p.card_last4, p.payment_method, p.created_at as payment_date
    FROM orders o
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.id = ? AND o.user_id = ? AND o.status = 'completed'
");
$stmt->execute([$order_id, $user_id]);
$orderData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$orderData) {
    header("Location: my_orders.php");
    exit;
}

/* ===== FETCH ORDER ITEMS ===== */
$stmt = $conn->prepare("
    SELECT oi.quantity, oi.price, p.product_name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Successful | ElectroStore</title>
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
    max-width:800px;
    margin:40px auto;
    padding:40px;
    background:white;
    border-radius:14px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
}

/* SUCCESS BADGE */
.success-badge{
    text-align:center;
    margin-bottom:30px;
}
.success-badge .icon{
    font-size:80px;
    animation:bounce 0.8s;
}
.success-badge h1{
    color:#28a745;
    font-size:32px;
    margin:15px 0;
    font-weight:700;
}
.success-badge p{
    color:#666;
    font-size:16px;
    margin-bottom:5px;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* SECTIONS */
.section{
    margin-bottom:30px;
    padding-bottom:30px;
    border-bottom:2px solid #f0f0f0;
}
.section:last-child{border-bottom:none;}

.section h2{
    color:#0a3d62;
    font-size:18px;
    margin-bottom:15px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:10px;
}

.info-row{
    display:flex;
    justify-content:space-between;
    padding:10px 0;
    font-size:15px;
}
.info-row .label{color:#666;font-weight:600;}
.info-row .value{color:#0a3d62;font-weight:600;}

.info-box{
    background:#f8f9fa;
    padding:15px;
    border-radius:10px;
    border-left:4px solid #28a745;
    margin-bottom:12px;
}

/* ORDER ITEMS */
.order-items{
    background:#f8f9fa;
    padding:20px;
    border-radius:10px;
}
.order-item{
    display:flex;
    align-items:center;
    gap:15px;
    padding:15px;
    background:white;
    border-radius:8px;
    margin-bottom:10px;
}
.order-item img{width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #e0e0e0;}
.order-item .details{flex:1;}
.order-item .details h4{color:#0a3d62;margin-bottom:5px;}
.order-item .details p{color:#666;font-size:14px;}
.order-item .amount{
    font-size:18px;
    font-weight:700;
    color:#667eea;
    text-align:right;
}

/* TOTAL */
.total-section{
    background:linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color:white;
    padding:20px;
    border-radius:10px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    font-size:20px;
    font-weight:700;
    margin-top:15px;
}

/* BUTTONS */
.buttons{
    display:flex;
    gap:15px;
    margin-top:30px;
}
.btn{
    flex:1;
    padding:14px;
    border:none;
    border-radius:8px;
    font-size:16px;
    font-weight:600;
    cursor:pointer;
    transition:all 0.3s ease;
    text-decoration:none;
    text-align:center;
}
.btn.primary{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
}
.btn.primary:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,0.3);}
.btn.secondary{
    background:#f0f0f0;
    color:#0a3d62;
    border:2px solid #e0e0e0;
}
.btn.secondary:hover{background:#e8e8e8;border-color:#d0d0d0;}

/* PRINT BUTTON */
.print-btn{
    text-align:center;
    margin-top:20px;
}
.print-btn button{
    background:none;
    border:none;
    color:#667eea;
    cursor:pointer;
    font-weight:600;
    text-decoration:underline;
    transition:all 0.3s ease;
}
.print-btn button:hover{color:#764ba2;}

@media(max-width:768px){
    .buttons{flex-direction:column;}
    .order-item{flex-direction:column;}
    .order-item .amount{text-align:left;margin-top:10px;}
    .container{padding:20px;}
}

@media print {
    header, .buttons, .print-btn{display:none;}
    body{background:white;}
    .container{box-shadow:none;margin:0;padding:0;}
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
    <!-- SUCCESS MESSAGE -->
    <div class="success-badge">
        <div class="icon">✅</div>
        <h1>Payment Successful!</h1>
        <p>Your order has been confirmed and payment received.</p>
    </div>

    <!-- TRANSACTION INFO -->
    <div class="section">
        <h2>💳 Transaction Details</h2>
        <div class="info-box">
            <div class="info-row">
                <span class="label">Transaction ID:</span>
                <span class="value"><?= htmlspecialchars($orderData['transaction_id'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="label">Payment Date:</span>
                <span class="value"><?= date('M d, Y H:i A', strtotime($orderData['payment_date'])) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Payment Method:</span>
                <span class="value">
                    <?php 
                    $methods = [
                        'airtel_money' => '📱 AIRTEL MONEY',
                        'mpesa' => '📱 M-PESA',
                        'halotel' => '📱 HALOTEL MONEY',
                        'mixbvy' => '📱 MIX BY YAS',
                        'credit_card' => '💳 Credit Card'
                    ];
                    $method_name = $methods[$orderData['payment_method']] ?? $orderData['payment_method'];
                    echo htmlspecialchars($method_name);
                    ?>
                </span>
            </div>
            <?php if(strpos($orderData['payment_method'], '_money') || $orderData['payment_method'] === 'mixbvy'): ?>
            <div class="info-row">
                <span class="label">Mobile Number:</span>
                <span class="value">•••• •••• •••• <?= htmlspecialchars($orderData['card_last4']) ?></span>
            </div>
            <?php else: ?>
            <div class="info-row">
                <span class="label">Card Used:</span>
                <span class="value">•••• •••• •••• <?= htmlspecialchars($orderData['card_last4'] ?? 'N/A') ?></span>
            </div>
            <?php endif; ?>
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value" style="color:#28a745;">✓ Completed</span>
            </div>
        </div>
    </div>

    <!-- ORDER INFORMATION -->
    <div class="section">
        <h2>📦 Order Information</h2>
        <div class="info-box">
            <div class="info-row">
                <span class="label">Order ID:</span>
                <span class="value">#<?= $orderData['id'] ?></span>
            </div>
            <div class="info-row">
                <span class="label">Order Date:</span>
                <span class="value"><?= date('M d, Y H:i A', strtotime($orderData['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- SHIPPING INFORMATION -->
    <div class="section">
        <h2>📍 Shipping Information</h2>
        <div class="info-box">
            <div class="info-row">
                <span class="label">Name:</span>
                <span class="value"><?= htmlspecialchars($orderData['fullname']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Address:</span>
                <span class="value"><?= htmlspecialchars($orderData['address']) ?></span>
            </div>
            <div class="info-row">
                <span class="label">Phone:</span>
                <span class="value"><?= htmlspecialchars($orderData['phone']) ?></span>
            </div>
        </div>
    </div>

    <!-- ORDER ITEMS -->
    <div class="section">
        <h2>🛒 Order Items</h2>
        <div class="order-items">
            <?php $itemTotal = 0; ?>
            <?php foreach($orderItems as $item): ?>
                <div class="order-item">
                    <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                    <div class="details">
                        <h4><?= htmlspecialchars($item['product_name']) ?></h4>
                        <p>Quantity: <?= $item['quantity'] ?> × TZS <?= number_format($item['price'], 2) ?></p>
                    </div>
                    <div class="amount">
                        TSh <?= number_format($item['quantity'] * $item['price'], 2) ?>
                    </div>
                </div>
                <?php $itemTotal += $item['quantity'] * $item['price']; ?>
            <?php endforeach; ?>

            <div class="total-section">
                <span>Total Amount:</span>
                <span>TZS <?= number_format($orderData['total_amount'], 2) ?></span>
            </div>
        </div>
    </div>

    <!-- ACTION BUTTONS -->
    <div class="buttons">
        <a href="my_orders.php" class="btn primary">📦 View All Orders</a>
        <a href="shop.php" class="btn secondary">🏪 Continue Shopping</a>
    </div>

    <div class="print-btn">
        <button onclick="window.print()">🖨️ Print Receipt</button>
    </div>
</div>

</body>
</html>
