<?php
session_start();
require_once __DIR__ . "/../api/db.php";

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ===== HANDLE STATUS UPDATE ===== */
if (isset($_POST['update_status']) && isset($_POST['order_id'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];

    $allowed_status = ['pending', 'completed', 'cancelled'];
    if (in_array($status, $allowed_status)) {
        $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $stmt->execute([$status, $order_id]);
    }
}

/* ===== FETCH ALL ORDERS WITH CUSTOMER INFO FROM ORDER TABLE ===== */
$orders = $conn->query("
    SELECT o.id, o.status, o.created_at, o.phone, o.address, u.fullname, u.email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Orders | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* {margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif;}
body {background:#f4f6f8;}
.wrapper {display:flex;min-height:100vh;}
.sidebar {width:240px;background:#0a3d62;color:white;padding:20px;}
.sidebar h2 {text-align:center;margin-bottom:30px;}
.sidebar a {display:block;color:white;text-decoration:none;padding:10px;margin-bottom:8px;border-radius:4px;}
.sidebar a:hover {background: rgba(255,255,255,0.2);}
.main {flex:1;padding:30px;}
.main h1 {margin-bottom:20px;color:#0a3d62;}
.table-box {background:white;padding:20px;border-radius:6px;box-shadow:0 0 8px rgba(0,0,0,0.1);overflow-x:auto;margin-bottom:30px;}
table {width:100%;border-collapse:collapse;}
th, td {padding:12px;border-bottom:1px solid #ddd;text-align:left;vertical-align:top;}
th {background:#0a3d62;color:white;}
tr:hover {background:#f1f1f1;}
.status {padding:4px 8px;border-radius:4px;color:white;display:inline-block;}
.status.pending {background:#f39c12;}
.status.completed {background:#27ae60;}
.status.cancelled {background:#e74c3c;}
form.status-form {display:inline;}
select.status-select {padding:4px 8px;border-radius:4px;border:1px solid #ccc;}
button.update-btn {padding:4px 8px;margin-left:4px;background:#1e90ff;color:white;border:none;border-radius:4px;cursor:pointer;}
button.update-btn:hover {background:#0d74d1;}
.order-items {margin-top:10px;border-top:1px dashed #ccc;padding-top:8px;}
.order-items table {width:100%;border-collapse:collapse;}
.order-items th, .order-items td {padding:6px;border-bottom:1px solid #eee;text-align:left;}
.order-items th {background:#f1f1f1;color:#0a3d62;}
.total-amount {text-align:right;font-weight:bold;margin-top:5px;}
@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{width:100%;}}
</style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="users.php">Users</a>
        <a href="orders.php">Orders</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- Main content -->
    <div class="main">
        <h1>Manage Orders</h1>

        <?php foreach($orders as $order): ?>
        <div class="table-box">
            <h3>Order #<?= $order['id'] ?> - <?= htmlspecialchars($order['fullname']) ?></h3>
            <p>
                Email: <?= htmlspecialchars($order['email']) ?> |
                Phone: <?= htmlspecialchars($order['phone']) ?> |
                Address: <?= htmlspecialchars($order['address']) ?>
            </p>
            <p>Order Date: <?= $order['created_at'] ?> | Status: 
                <span class="status <?= strtolower($order['status']) ?>"><?= ucfirst($order['status']) ?></span>
            </p>

            <!-- Status update form -->
            <form method="POST" class="status-form">
                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                <select name="status" class="status-select">
                    <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>Pending</option>
                    <option value="completed" <?= $order['status']=='completed'?'selected':'' ?>>Completed</option>
                    <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>Cancelled</option>
                </select>
                <button type="submit" name="update_status" class="update-btn">Update</button>
            </form>

            <!-- Fetch order items -->
            <?php
            $stmtItems = $conn->prepare("
                SELECT oi.quantity, oi.price, p.product_name
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmtItems->execute([$order['id']]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            $totalAmount = 0;
            ?>
            <div class="order-items">
                <table>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                    <?php foreach($items as $item): 
                        $subtotal = $item['price'] * $item['quantity'];
                        $totalAmount += $subtotal;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td>$<?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p class="total-amount">Total Amount: $<?= number_format($totalAmount, 2) ?></p>
            </div>
        </div>
        <?php endforeach; ?>

    </div>
</div>

</body>
</html>
