<?php
session_start();
require_once "../api/db.php";

// Access control: only logged-in customers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $conn->prepare("SELECT fullname, email, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch recent orders (latest 5)
$stmt = $conn->prepare("
    SELECT o.id, o.total_amount, o.status, o.created_at
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$recentOrders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Dashboard | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* ===== GENERAL ===== */
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Arial, sans-serif; }
body { background:#f4f6f8; color:#333; }

/* ===== HEADER ===== */
header {
    background:#0a3d62; color:#fff;
    padding:15px 30px;
    display:flex; justify-content: space-between; align-items:center;
    box-shadow:0 2px 5px rgba(0,0,0,0.2);
}
header .logo { font-size:24px; font-weight:bold; }
nav a { color:white; text-decoration:none; margin-left:20px; font-weight:500; transition:0.3s; }
nav a:hover { color:#1e90ff; }

/* ===== DASHBOARD LAYOUT ===== */
.wrapper { display:flex; flex-wrap:wrap; padding:20px; gap:20px; justify-content:center; }

/* ===== SIDEBAR ===== */
.sidebar {
    flex:0 0 220px; background:#0a3d62; color:white;
    padding:20px; border-radius:8px;
}
.sidebar h2 { text-align:center; margin-bottom:20px; }
.sidebar a {
    display:block; color:white; text-decoration:none; padding:10px;
    margin-bottom:8px; border-radius:5px; transition:0.3s;
}
.sidebar a:hover { background:rgba(255,255,255,0.2); }

/* ===== MAIN ===== */
.main { flex:1; min-width:300px; padding:20px; }
.main h1 { color:#0a3d62; margin-bottom:20px; }

/* ===== CARDS ===== */
.cards { display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap:20px; margin-bottom:30px; }
.card { background:white; border-radius:10px; padding:20px; box-shadow:0 4px 12px rgba(0,0,0,0.1); text-align:center; }
.card h3 { margin-bottom:10px; color:#555; }
.card p { font-size:20px; font-weight:bold; color:#0a3d62; }

/* ===== TABLE ===== */
.table-box { background:white; padding:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
table { width:100%; border-collapse:collapse; }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#0a3d62; color:white; }
tr:hover { background:#f1f1f1; }

/* ===== BUTTONS ===== */
button { padding:10px 16px; border:none; border-radius:6px; background:#1e90ff; color:white; cursor:pointer; transition:0.3s; }
button:hover { background:#0d74d1; }

/* ===== RESPONSIVE ===== */
@media(max-width:768px){ .wrapper { flex-direction:column; } .sidebar { width:100%; } }
</style>
</head>
<body>

<header>
    <div class="logo">ElectroStore</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="shop.php">shop</a>
        <a href="cart.php">Cart</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="wrapper">

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar">
        <h2>My Account</h2>
        <a href="customer_dashboard.php">Dashboard</a>
        <a href="profile.php">Profile</a>
        <a href="shop.php">shop</a>
         <a href="profile.php">Profile</a>
        <a href="my_orders.php">My Orders</a>
    </div>

    <!-- ===== MAIN ===== -->
    <div class="main">
        <h1>Welcome, <?= htmlspecialchars($user['fullname']) ?></h1>

        <!-- ===== INFO CARDS ===== -->
        <div class="cards">
            <div class="card">
                <h3>Email</h3>
                <p><?= htmlspecialchars($user['email']) ?></p>
            </div>
            <div class="card">
                <h3>Member Since</h3>
                <p><?= date("F d, Y", strtotime($user['created_at'])) ?></p>
            </div>
            <div class="card">
                <h3>Recent Orders</h3>
                <p><?= count($recentOrders) ?></p>
            </div>
        </div>

        <!-- ===== RECENT ORDERS TABLE ===== -->
        <div class="table-box">
            <h2 style="margin-bottom:15px;color:#0a3d62;">Recent Orders</h2>
            <?php if(count($recentOrders) > 0): ?>
            <table>
                <tr>
                    <th>Order ID</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
                <?php foreach($recentOrders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td>$<?= $order['total_amount'] ?></td>
                    <td><?= ucfirst($order['status']) ?></td>
                    <td><?= date("F d, Y", strtotime($order['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?>
                <p>No orders yet.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

</body>
</html>
