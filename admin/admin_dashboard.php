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
$unreadMessages = $conn->query("SELECT COUNT(*) FROM customer_messages WHERE status = 'unread'")->fetchColumn();
$newMessages = $conn->query("SELECT COUNT(*) FROM customer_messages WHERE admin_viewed = 'no'")->fetchColumn();

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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;}

.wrapper{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:260px;background:linear-gradient(180deg, #0a3d62 0%, #062d48 100%);color:white;padding:30px 20px;box-shadow:4px 0 15px rgba(0,0,0,0.2);position:sticky;top:0;height:100vh;overflow-y:auto;}
.sidebar h2{text-align:center;margin-bottom:40px;font-size:22px;font-weight:700;letter-spacing:0.5px;}
.sidebar a{display:block;color:white;text-decoration:none;padding:14px 16px;margin-bottom:8px;border-radius:8px;transition:all 0.3s ease;font-weight:500;border-left:4px solid transparent;}
.sidebar a:hover{background:rgba(255,255,255,0.2);border-left:4px solid #ffdd59;padding-left:20px;}

/* MAIN */
.main{flex:1;padding:40px;background:#f8f9fa;}
.main h1{margin-bottom:35px;color:#0a3d62;font-size:32px;font-weight:700;}

/* CARDS */
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px;margin-bottom:30px;}
.card{background:white;padding:25px;border-radius:12px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.08);transition:all 0.3s ease;border-left:5px solid #667eea;}
.card:hover{transform:translateY(-5px);box-shadow:0 8px 30px rgba(0,0,0,0.12);}
.card h3{margin-bottom:15px;color:#666;font-weight:600;font-size:14px;text-transform:uppercase;letter-spacing:0.5px;}
.card p{font-size:36px;font-weight:700;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}

/* TABLE BOX */
.table-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow-x:auto;}
.table-box h2{margin-bottom:20px;color:#0a3d62;font-size:20px;}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{padding:12px 15px;border-bottom:1px solid #f0f0f0;color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}

button.view-items{padding:8px 16px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;transition:all 0.3s ease;}
button.view-items:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4);}

.details-row{display:none;background:#f8f9fa;}
.details-row td{padding:15px;border-top:2px solid #e0e0e0;}
.details-row ul{margin-left:20px;}
.details-row li{margin:8px 0;color:#666;}

/* RESPONSIVE */
@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{width:100%;height:auto;position:static;}.cards{grid-template-columns:1fr;}}
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
        <h2>📊 Admin Panel</h2>
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="manage_products.php">📦 Products</a>
        <a href="categories.php">🏷️ Categories</a>
        <a href="users.php">👥 Users</a>
        <a href="orders.php">📋 Orders</a>
        <a href="messages.php">💬 Messages<?= $newMessages > 0 ? ' <span style="background:#ff5722;color:white;padding:2px 8px;border-radius:12px;font-size:11px;margin-left:5px;font-weight:700;">' . $newMessages . '</span>' : '' ?></a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <!-- ===== MAIN ===== -->
    <div class="main">
        <h1>Dashboard Overview</h1>

        <?php if ($newMessages > 0): ?>
            <div style="background:#ffe0b2;border-left:5px solid #ff9800;padding:15px 20px;border-radius:8px;margin-bottom:25px;color:#e65100;font-weight:500;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:15px;">
                <span>🔔 <strong><?= $newMessages ?></strong> new customer message<?= $newMessages === 1 ? '' : 's' ?>!</span>
                <a href="messages.php" style="background:#ff9800;color:white;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;font-size:13px;transition:all 0.3s ease;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">View Messages →</a>
            </div>
        <?php endif; ?>

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
            <div class="card" style="border-left-color:#ff6b6b;">
                <h3>Unread Messages</h3>
                <p style="color:#ff6b6b;"><?= $unreadMessages ?></p>
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
                                        <li><?= htmlspecialchars($item['product_name']) ?> x <?= $item['quantity'] ?> - TZS <?= number_format($item['price'] * $item['quantity'],2) ?></li>
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
