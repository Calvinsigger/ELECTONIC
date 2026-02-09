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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;}

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
nav a{color:white;text-decoration:none;margin-left:20px;font-weight:500;transition:all 0.3s ease;}
nav a:hover{color:#ffdd59;transform:translateY(-2px);}

/* CONTAINER */
.container{padding:40px 20px;max-width:1000px;margin:auto;}
h1{color:white;margin-bottom:30px;font-size:32px;font-weight:700;}

/* TABLE BOX */
.table-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{padding:12px 15px;border-bottom:1px solid #f0f0f0;color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}

/* STATUS BADGE */
.status{display:inline-block;padding:6px 14px;border-radius:20px;color:white;font-size:12px;font-weight:600;text-transform:uppercase;}
.status.pending{background:#f39c12;}
.status.completed{background:#27ae60;}
.status.cancelled{background:#e74c3c;}

/* BUTTONS */
.action-btn{padding:8px 14px;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;transition:all 0.3s ease;margin-right:6px;}
.cancel-btn{background:#e74c3c;color:white;}
.cancel-btn:hover{background:#c0392b;transform:translateY(-2px);}
.reorder-btn{background:#27ae60;color:white;}
.reorder-btn:hover{background:#1e8449;transform:translateY(-2px);}
.details-btn{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;}
.details-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.3);}

.details-row{display:none;background:#f8f9fa;border-top:2px solid #e0e0e0;}
.details-row td{padding:20px 15px;}
.details-row ul{margin-left:20px;list-style:none;}
.details-row li{padding:8px 0;color:#666;border-bottom:1px solid #e0e0e0;}
.details-row li:last-child{border-bottom:none;}

.no-orders{text-align:center;padding:40px 20px;color:white;font-size:18px;}
.no-orders a{color:#ffdd59;font-weight:600;text-decoration:none;}
.no-orders a:hover{text-decoration:underline;}
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
    <div class="logo">🛍️ ElectroStore</div>
    <nav>
        <a href="customer_dashboard.php">📊 Dashboard</a>
        <a href="shop.php">🏪 Shop</a>
        <a href="cart.php">🛒 Cart</a>
        <a href="../logout.php">🚪 Logout</a>
    </nav>
</header>

<div class="container">
    <h1>📋 My Orders</h1>

    <?php if(count($orders) === 0): ?>
        <div class="no-orders">
            <p>You haven't placed any orders yet.</p>
            <p><a href="shop.php">Start shopping now →</a></p>
        </div>
    <?php else: ?>
        <div class="table-box">
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
                    <td style="font-weight:600;color:#667eea;">$<?= number_format($order['total_amount'], 2) ?></td>
                    <td>
                        <?php if($order['status'] === 'pending'): ?>
                            <span class="status pending">⏳ Pending</span>
                        <?php elseif($order['status'] === 'completed'): ?>
                            <span class="status completed">✅ Completed</span>
                        <?php else: ?>
                            <span class="status cancelled">❌ Cancelled</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                    <td>
                        <button class="action-btn details-btn" onclick="toggleDetails(<?= $order['id'] ?>)">👁️ Details</button>

                        <?php if($order['status']=='pending'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button type="submit" name="cancel_order" class="action-btn cancel-btn">❌ Cancel</button>
                        </form>
                        <?php endif; ?>

                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <button type="submit" name="reorder" class="action-btn reorder-btn">🔄 Reorder</button>
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
                                <li>📦 <strong><?= htmlspecialchars($item['product_name']) ?></strong> × <?= $item['quantity'] ?> = <strong>$<?= number_format($item['price'] * $item['quantity'],2) ?></strong></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php else: ?>
                            <p style="color:#999;">No items in this order.</p>
                        <?php endif; ?>
                    </td>
                </tr>

                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

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
