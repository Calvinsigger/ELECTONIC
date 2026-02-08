<?php
session_start();
require_once "../api/db.php";

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
    $fullname = trim($_POST['fullname']);
    $address  = trim($_POST['address']);
    $phone    = trim($_POST['phone']);

    if ($cartItems) {
        // Calculate total
        $total = 0;
        foreach ($cartItems as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        // Insert into orders
        $stmt = $conn->prepare("
            INSERT INTO orders (user_id, fullname, address, phone, total_price)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $fullname, $address, $phone, $total]);
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
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Checkout | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif}
body{background:#f4f6f8;color:#333}
header{background:#0a3d62;color:#fff;padding:15px 30px;display:flex;justify-content:space-between;align-items:center}
header .logo{font-size:22px;font-weight:bold}
header nav a{color:#fff;text-decoration:none;margin-left:18px;font-weight:500}
header nav a:hover{color:#ffdd59}
.container{max-width:1100px;margin:30px auto;padding:20px;background:#fff;border-radius:10px;box-shadow:0 8px 20px rgba(0,0,0,.1)}
h1{color:#0a3d62;margin-bottom:20px;text-align:center}
.cart-item{display:flex;align-items:center;border-bottom:1px solid #eee;padding:15px 0}
.cart-item img{width:100px;height:100px;object-fit:cover;border-radius:8px;margin-right:20px}
.cart-item .info{flex:1}
.cart-item .info h3{margin-bottom:5px}
.cart-item .info .qty-price{color:#777;font-size:14px}
.cart-total{text-align:right;font-size:20px;font-weight:bold;margin:20px 0;color:#0a3d62}
form input, form textarea{width:100%;padding:12px;margin-bottom:15px;border-radius:6px;border:1px solid #ccc;font-size:16px}
form button{padding:15px;background:#0a3d62;color:#fff;border:none;border-radius:8px;font-size:16px;width:100%;cursor:pointer}
form button:hover{background:#07406b}
.message{text-align:center;color:green;font-weight:bold;margin-bottom:15px}
.empty{text-align:center;padding:50px;font-size:18px;color:#777}
@media(max-width:768px){.cart-item{flex-direction:column;align-items:flex-start}.cart-item img{margin-bottom:10px}}
</style>
</head>

<body>
<header>
    <div class="logo">ElectroStore</div>
    <nav>
        <a href="shop.php">Shop</a>
        <a href="cart.php">Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">
<h1>Checkout</h1>

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
    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <textarea name="address" placeholder="Shipping Address" rows="3" required></textarea>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <button type="submit" name="place_order">Place Order</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
