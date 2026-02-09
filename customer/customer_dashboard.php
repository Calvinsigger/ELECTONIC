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
nav a{color:white;text-decoration:none;margin-left:20px;font-weight:500;transition:all 0.3s ease;}
nav a:hover{color:#ffdd59;transform:translateY(-2px);}

/* WRAPPER */
.wrapper{display:flex;flex-wrap:wrap;padding:30px 20px;gap:25px;justify-content:center;min-height:calc(100vh - 70px);}

/* SIDEBAR */
.sidebar{
    flex:0 0 220px;
    background:white;
    color:#333;
    padding:25px 20px;
    border-radius:12px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    height:fit-content;
}
.sidebar h2{text-align:center;margin-bottom:25px;color:#0a3d62;font-size:18px;font-weight:700;}
.sidebar a{
    display:block;
    color:#333;
    text-decoration:none;
    padding:12px 14px;
    margin-bottom:8px;
    border-radius:8px;
    transition:all 0.3s ease;
    font-weight:500;
    border-left:4px solid transparent;
}
.sidebar a:hover{background:linear-gradient(135deg, #667eea15 0%, #764ba215 100%);border-left:4px solid #667eea;padding-left:18px;}

/* MAIN */
.main{flex:1;min-width:300px;}
.main h1{color:white;margin-bottom:30px;font-size:32px;font-weight:700;}

/* CARDS */
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:25px;margin-bottom:30px;}
.card{
    background:white;
    border-radius:12px;
    padding:25px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    text-align:center;
    transition:all 0.3s ease;
    border-left:5px solid #667eea;
}
.card:hover{transform:translateY(-5px);box-shadow:0 8px 30px rgba(0,0,0,0.12);}
.card h3{margin-bottom:15px;color:#666;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
.card p{font-size:24px;font-weight:700;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}

/* TABLE BOX */
.table-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);}
.table-box h2{margin-bottom:20px;color:#0a3d62;font-size:20px;font-weight:600;}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{padding:12px 15px;border-bottom:1px solid #f0f0f0;color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}

button{padding:10px 16px;border:none;border-radius:6px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;cursor:pointer;font-weight:600;transition:all 0.3s ease;}
button:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4);}

@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{flex:0 0 100%;}nav{display:none;}}
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
