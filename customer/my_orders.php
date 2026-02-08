<?php
session_start();
require_once "../api/db.php";

// ===== ACCESS CONTROL =====
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer'){
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];

/* ===== HANDLE CANCEL ORDER ===== */
if(isset($_POST['cancel_order']) && isset($_POST['order_id'])){
    $order_id = (int)$_POST['order_id'];

    // Only allow cancel if order is pending
    $stmt = $conn->prepare("UPDATE orders SET status='cancelled' WHERE id=? AND user_id=? AND status='pending'");
    $stmt->execute([$order_id, $userId]);
}

/* ===== HANDLE REORDER (ADD TO CART) ===== */
if(isset($_POST['reorder']) && isset($_POST['order_id'])){
    $order_id = (int)$_POST['order_id'];

    // Fetch order items
    $stmt = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id=?");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

    foreach($items as $item){
        $pid = $item['product_id'];
        $qty = $item['quantity'];

        if(isset($_SESSION['cart'][$pid])){
            $_SESSION['cart'][$pid] += $qty;
        } else {
            $_SESSION['cart'][$pid] = $qty;
        }
    }

    header("Location: cart.php");
    exit;
}

/* ===== FETCH ORDERS WITH TOTAL ===== */
$orders = $conn->prepare("
    SELECT o.id, o.created_at, o.status,
           COALESCE(SUM(oi.price * oi.quantity),0) AS total_amount
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$orders->execute([$userId]);
$orders = $orders->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Orders | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; }
header { background:#0a3d62; color:white; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
header .logo { font-size:22px; font-weight:bold; }
nav a { color:white; text-decoration:none; margin-left:15px; }
nav a:hover { text-decoration:underline; }

.container { padding:20px; max-width:900px; margin:auto; }
h1 { color:#0a3d62; margin-bottom:20px; }
table { width:100%; border-collapse: collapse; background:white; box-shadow:0 0 8px rgba(0,0,0,0.1); border-radius:6px; overflow:hidden; }
th, td { padding:12px; text-align:left; border-bottom:1px solid #ddd; }
th { background:#0a3d62; color:white; }
tr:hover { background:#f1f1f1; }
.total { font-weight:bold; color:#0a3d62; }
.status { padding:4px 8px; border-radius:4px; color:white; display:inline-block; }
.status.pending { background:#f39c12; }
.status.completed { background:#27ae60; }
.status.cancelled { background:#e74c3c; }
button.action-btn { padding:6px 10px; margin-right:4px; border:none; border-radius:4px; cursor:pointer; }
button.cancel-btn { background:#e74c3c; color:white; }
button.cancel-btn:hover { background:#c0392b; }
button.reorder-btn { background:#27ae60; color:white; }
button.reorder-btn:hover { background:#1e8449; }
button.details-btn { background:#3498db; color:white; }
button.details-btn:hover { background:#2c80b4; }

.details-row { display:none; background:#f9f9f9; }
.details-row td { border-top:1px solid #ddd; padding:10px; }
</style>
<script>
function toggleDetails(orderId){
    const row = document.getElementById('details-'+orderId);
    row.style.display = row.style.display === 'table-row' ? 'none' : 'table-row';
}
</script>
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
    <h1>My Orders</h1>

    <?php if(count($orders) === 0): ?>
        <p>You have no orders yet. <a href="shop.php">Shop now!</a></p>
    <?php else: ?>
        <table>
            <tr>
                <th>Order ID</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
            <?php foreach($orders as $order): ?>
            <tr>
                <td>#<?= htmlspecialchars($order['id']) ?></td>
                <td class="total">$<?= number_format($order['total_amount'], 2) ?></td>
                <td><span class="status <?= strtolower($order['status']) ?>"><?= ucfirst($order['status']) ?></span></td>
                <td><?= htmlspecialchars($order['created_at']) ?></td>
                <td>
                    <button class="action-btn details-btn" onclick="toggleDetails(<?= $order['id'] ?>)">Details</button>

                    <?php if($order['status']=='pending'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" name="cancel_order" class="action-btn cancel-btn">Cancel</button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                        <button type="submit" name="reorder" class="action-btn reorder-btn">Reorder</button>
                    </form>
                </td>
            </tr>

            <!-- Details row -->
            <tr id="details-<?= $order['id'] ?>" class="details-row">
                <td colspan="5">
                    <?php
                    $stmt = $conn->prepare("SELECT oi.*, p.product_name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
                    $stmt->execute([$order['id']]);
                    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if(count($items) > 0):
                    ?>
                    <ul>
                        <?php foreach($items as $item): ?>
                            <li><?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?> - $<?= number_format($item['price'] * $item['quantity'],2) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                        <p>No items in this order.</p>
                    <?php endif; ?>
                </td>
            </tr>

            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

</body>
</html>
