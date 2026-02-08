<?php
session_start();

/* ===== SAFE DB INCLUDE ===== */
require_once __DIR__ . "/../api/db.php";

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ===== DASHBOARD DATA ===== */
$totalUsers = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProducts = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();

/* ===== RECENT ORDERS WITH DYNAMIC TOTAL ===== */
$recentOrders = $conn->query("
    SELECT o.id, u.fullname, o.created_at,
           COALESCE(SUM(oi.price * oi.quantity), 0) AS total_amount
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    GROUP BY o.id
    ORDER BY o.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
* {margin:0;padding:0;box-sizing:border-box;font-family:Arial,sans-serif;}
body {background:#f4f6f8;}

/* ===== LAYOUT ===== */
.wrapper {display:flex;min-height:100vh;}

/* ===== SIDEBAR ===== */
.sidebar {width:240px;background:#0a3d62;color:white;padding:20px;}
.sidebar h2 {text-align:center;margin-bottom:30px;}
.sidebar a {display:block;color:white;text-decoration:none;padding:10px;margin-bottom:8px;border-radius:4px;}
.sidebar a:hover {background: rgba(255,255,255,0.2);}

/* ===== MAIN ===== */
.main {flex:1;padding:30px;}
.main h1 {margin-bottom:20px;color:#0a3d62;}

/* ===== CARDS ===== */
.cards {display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin-bottom:30px;}
.card {background:white;padding:20px;border-radius:6px;text-align:center;box-shadow:0 0 8px rgba(0,0,0,0.1);}
.card h3 {margin-bottom:10px;color:#555;}
.card p {font-size:28px;font-weight:bold;color:#0a3d62;}

/* ===== TABLE ===== */
.table-box {background:white;padding:20px;border-radius:6px;box-shadow:0 0 8px rgba(0,0,0,0.1);overflow-x:auto;}
table {width:100%;border-collapse:collapse;}
th, td {padding:12px;border-bottom:1px solid #ddd;text-align:left;white-space:nowrap;}
th {background:#0a3d62;color:white;}
tr:hover {background:#f1f1f1;}
button.view-items {padding:4px 8px;background:#3498db;color:white;border:none;border-radius:4px;cursor:pointer;}
button.view-items:hover {background:#2c80b4;}
.details-row {display:none;background:#f9f9f9;}
.details-row td {border-top:1px solid #ddd;padding:10px;}

/* ===== RESPONSIVE ===== */
@media(max-width:768px) {.wrapper{flex-direction:column;}.sidebar{width:100%;}}
</style>

<script>
function toggleDetails(orderId){
    const row = document.getElementById('details-'+orderId);
    row.style.display = row.style.display === 'table-row' ? 'none' : 'table-row';
}
</script>
</head>

<body>

<div class="wrapper">

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="add_products.php">Manage Products</a>
        <a href="categories.php">Categories</a>
        <a href="users.php">Users</a>
        <a href="orders.php">Orders</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- ===== MAIN ===== -->
    <div class="main">
        <h1>Dashboard Overview</h1>

        <!-- ===== STATS ===== -->
        <div class="cards">
            <div class="card">
                <h3>Total Users</h3>
                <p><?= $totalUsers ?></p>
            </div>
            <div class="card">
                <h3>Total Products</h3>
                <p><?= $totalProducts ?></p>
            </div>
            <div class="card">
                <h3>Total Orders</h3>
                <p><?= $totalOrders ?></p>
            </div>
        </div>

        <!-- ===== RECENT ORDERS ===== -->
        <div class="table-box">
            <h2 style="margin-bottom:15px;color:#0a3d62;">Recent Orders</h2>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>

                <?php if ($recentOrders): ?>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['fullname']) ?></td>
                            <td>$<?= number_format($order['total_amount'], 2) ?></td>
                            <td><?= $order['created_at'] ?></td>
                            <td>
                                <button class="view-items" onclick="toggleDetails(<?= $order['id'] ?>)">View Items</button>
                            </td>
                        </tr>
                        <!-- Details Row -->
                        <tr id="details-<?= $order['id'] ?>" class="details-row">
                            <td colspan="5">
                                <?php
                                $stmt = $conn->prepare("SELECT oi.*, p.product_name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
                                $stmt->execute([$order['id']]);
                                $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php if($items): ?>
                                    <ul>
                                    <?php foreach($items as $item): ?>
                                        <li><?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?> - $<?= number_format($item['price'] * $item['quantity'],2) ?></li>
                                    <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p>No items found for this order.</p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No recent orders found</td></tr>
                <?php endif; ?>
            </table>
        </div>

    </div>
</div>

</body>
</html>
