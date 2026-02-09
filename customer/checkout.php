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

/* ===== FETCH CART ITEMS ===== */
$stmt = $conn->prepare("
    SELECT c.*, p.product_name, p.price, p.image 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$cartItems) {
    $emptyCart = true;
}

/* ===== PLACE ORDER ===== */
$orderMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {

    /* ===== CSRF TOKEN VALIDATION ===== */
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $orderMsg = "Security token is invalid. Please try again.";
    } else {

        $fullname = $_POST['fullname'] ?? '';
        $address  = $_POST['address'] ?? '';
        $phone    = $_POST['phone'] ?? '';

        // Validate fullname
        $nameValidation = validateFullname($fullname);
        if (!$nameValidation['valid']) {
            $orderMsg = $nameValidation['message'];
        }
        // Validate address
        else {
            $addressValidation = validateAddress($address);
            if (!$addressValidation['valid']) {
                $orderMsg = $addressValidation['message'];
            } else {
                // Validate phone
                $phoneValidation = validatePhone($phone);
                if (!$phoneValidation['valid']) {
                    $orderMsg = $phoneValidation['message'];
                } else if (!$cartItems) {
                    $orderMsg = "Your cart is empty.";
                } else {

                    try {
                        // Calculate total
                        $total = 0;
                        foreach ($cartItems as $item) {
                            $total += $item['price'] * $item['quantity'];
                        }

                        // Insert into orders
                        $stmt = $conn->prepare("
                            INSERT INTO orders (user_id, fullname, address, phone, total_amount)
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$user_id, $fullname, $addressValidation['value'], $phoneValidation['value'], $total]);
                        $order_id = $conn->lastInsertId();

                        // Insert order items
                        $stmt = $conn->prepare("
                            INSERT INTO order_items (order_id, product_id, quantity, price)
                            VALUES (?, ?, ?, ?)
                        ");
                        foreach ($cartItems as $item) {
                            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

                            // Reduce product stock
                            $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                                ->execute([$item['quantity'], $item['product_id']]);
                        }

                        // Clear cart
                        $conn->prepare("DELETE FROM cart WHERE user_id=?")->execute([$user_id]);

                        $orderMsg = "✅ Your order has been placed successfully! Order ID: #$order_id";
                        $emptyCart = true;
                        $cartItems = [];

                    } catch (PDOException $e) {
                        $orderMsg = "Error placing order: " . $e->getMessage();
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
<title>Checkout | ElectroStore</title>
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
    max-width:900px;
    margin:40px auto;
    padding:30px;
    background:white;
    border-radius:14px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
}
h1{color:#0a3d62;margin-bottom:30px;font-size:32px;font-weight:700;text-align:center;}

/* MESSAGE */
.message{
    text-align:center;
    color:#155724;
    background:#d4edda;
    border:2px solid #28a745;
    border-left:4px solid #28a745;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
    font-weight:600;
}

/* CART ITEMS */
.cart-items{background:#f8f9fa;padding:20px;border-radius:10px;margin-bottom:25px;}
.cart-item{
    display:flex;
    align-items:center;
    gap:20px;
    padding:15px;
    background:white;
    border-radius:10px;
    margin-bottom:12px;
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}
.cart-item img{width:120px;height:120px;object-fit:cover;border-radius:10px;border:2px solid #e0e0e0;}
.cart-item .info{flex:1;}
.cart-item .info h3{color:#0a3d62;margin-bottom:8px;font-weight:600;}
.cart-item .qty-price{color:#666;font-size:14px;margin-bottom:5px;}
.cart-item .price{font-size:18px;font-weight:700;color:#667eea;}

/* GRAND TOTAL */
.cart-total{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    padding:20px;
    border-radius:10px;
    text-align:right;
    font-size:24px;
    font-weight:700;
    margin-bottom:30px;
}

/* FORM */
form input, form textarea{
    width:100%;
    padding:14px 16px;
    margin-bottom:15px;
    border-radius:8px;
    border:2px solid #e0e0e0;
    font-size:16px;
    transition:all 0.3s ease;
    font-family:inherit;
}
form input:focus, form textarea:focus{
    outline:none;
    border-color:#667eea;
    box-shadow:0 0 0 3px rgba(102,126,234,0.1);
}
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
}
form button:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,0.3);}

/* EMPTY */
.empty{
    text-align:center;
    padding:50px;
    background:#f8f9fa;
    border-radius:12px;
    color:#999;
    font-size:18px;
}
.empty a{color:#667eea;font-weight:600;text-decoration:none;transition:all 0.3s ease;}
.empty a:hover{color:#764ba2;text-decoration:underline;}

@media(max-width:768px){.cart-item{flex-direction:column;}.cart-item img{width:100%;}}
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
    <h1>💳 Checkout</h1>

    <?php if($orderMsg): ?>
        <p class="message"><?= $orderMsg ?></p>
    <?php endif; ?>

    <?php if(!empty($emptyCart)): ?>
        <div class="empty">
            🛒 Your cart is empty! <br><br>
            <a href="shop.php">← Continue shopping</a>
        </div>
    <?php else: ?>
        <!-- CART ITEMS -->
        <div class="cart-items">
            <?php $grandTotal=0; ?>
            <?php foreach($cartItems as $item): ?>
                <div class="cart-item">
                    <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                    <div class="info">
                        <h3>📦 <?= htmlspecialchars($item['product_name']) ?></h3>
                        <div class="qty-price">Quantity: <?= $item['quantity'] ?> × $<?= number_format($item['price'],2) ?></div>
                    </div>
                    <div class="price">
                        $<?= number_format($item['price']*$item['quantity'],2) ?>
                    </div>
                </div>
                <?php $grandTotal += $item['price'] * $item['quantity']; ?>
            <?php endforeach; ?>
        </div>

        <div class="cart-total">💰 Grand Total: $<?= number_format($grandTotal,2) ?></div>

        <!-- SHIPPING FORM -->
        <form method="POST" onsubmit="return validateCheckoutForm()">
            <?= getCSRFTokenInput() ?>
            <input type="text" name="fullname" placeholder="👤 Full Name (min 2 characters)" minlength="2" maxlength="100" required value="<?= sanitizeOutput($_POST['fullname'] ?? '') ?>">
            <textarea name="address" placeholder="📍 Shipping Address (min 5 characters)" minlength="5" maxlength="255" rows="3" required><?= sanitizeOutput($_POST['address'] ?? '') ?></textarea>
            <input type="tel" name="phone" placeholder="📱 Phone Number (7-20 digits)" pattern="[0-9\+\-\(\)\s]{7,20}" maxlength="20" required value="<?= sanitizeOutput($_POST['phone'] ?? '') ?>">
            <button type="submit" name="place_order">✓ Place Order</button>
        </form>
    <?php endif; ?>
</div>

<script>
function validateCheckoutForm() {
    const fullname = document.querySelector('input[name="fullname"]').value.trim();
    const address = document.querySelector('textarea[name="address"]').value.trim();
    const phone = document.querySelector('input[name="phone"]').value.trim();

    if (fullname.length < 2) {
        alert("Name must be at least 2 characters.");
        return false;
    }

    if (address.length < 5) {
        alert("Address must be at least 5 characters.");
        return false;
    }

    if (phone.length < 7 || phone.length > 20) {
        alert("Phone number must be between 7-20 characters.");
        return false;
    }

    return true;
}
</script>
</body>
</html>

<?php if($orderMsg): ?>
    <p class="message"><?= $orderMsg ?></p>
<?php endif; ?>

<?php if(!empty($emptyCart)): ?>
    <p class="empty">🛒 Your cart is empty! <a href="shop.php">Continue shopping</a></p>
<?php else: ?>
    <!-- CART ITEMS -->
    <?php $grandTotal=0; ?>
    <?php foreach($cartItems as $item): ?>
        <div class="cart-item">
            <img src="../uploads/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
            <div class="info">
                <h3><?= htmlspecialchars($item['product_name']) ?></h3>
                <div class="qty-price">Quantity: <?= $item['quantity'] ?> × $<?= number_format($item['price'],2) ?></div>
            </div>
            <div class="price">
                $<?= number_format($item['price']*$item['quantity'],2) ?>
            </div>
        </div>
        <?php $grandTotal += $item['price'] * $item['quantity']; ?>
    <?php endforeach; ?>

    <div class="cart-total">Grand Total: $<?= number_format($grandTotal,2) ?></div>

    <!-- SHIPPING FORM -->
    <form method="POST" onsubmit="return validateCheckoutForm()">
        <?= getCSRFTokenInput() ?>
        <input type="text" name="fullname" placeholder="Full Name (min 2 characters)" minlength="2" maxlength="100" required value="<?= sanitizeOutput($_POST['fullname'] ?? '') ?>">
        <textarea name="address" placeholder="Shipping Address (min 5 characters)" minlength="5" maxlength="255" rows="3" required><?= sanitizeOutput($_POST['address'] ?? '') ?></textarea>
        <input type="tel" name="phone" placeholder="Phone Number (7-20 digits)" pattern="[0-9\+\-\(\)\s]{7,20}" maxlength="20" required value="<?= sanitizeOutput($_POST['phone'] ?? '') ?>">
        <button type="submit" name="place_order">Place Order</button>
    </form>
<?php endif; ?>
</div>

<script>
function validateCheckoutForm() {
    const fullname = document.querySelector('input[name="fullname"]').value.trim();
    const address = document.querySelector('textarea[name="address"]').value.trim();
    const phone = document.querySelector('input[name="phone"]').value.trim();

    if (fullname.length < 2) {
        alert("Name must be at least 2 characters.");
        return false;
    }

    if (address.length < 5) {
        alert("Address must be at least 5 characters.");
        return false;
    }

    if (phone.length < 7 || phone.length > 20) {
        alert("Phone number must be between 7-20 characters.");
        return false;
    }

    return true;
}
</script>
</body>
</html>
