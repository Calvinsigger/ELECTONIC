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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;}

.wrapper{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{
    width:260px;
    background:linear-gradient(180deg, #0a3d62 0%, #062d48 100%);
    color:white;
    padding:30px 20px;
    box-shadow:4px 0 15px rgba(0,0,0,0.2);
    position:sticky;
    top:0;
    height:100vh;
    overflow-y:auto;
}
.sidebar h2{
    text-align:center;
    margin-bottom:40px;
    font-size:22px;
    font-weight:700;
    letter-spacing:0.5px;
}
.sidebar a{
    display:block;
    color:white;
    text-decoration:none;
    padding:14px 16px;
    margin-bottom:8px;
    border-radius:8px;
    transition:all 0.3s ease;
    font-weight:500;
    border-left:4px solid transparent;
}
.sidebar a:hover{
    background:rgba(255,255,255,0.2);
    border-left:4px solid #ffdd59;
    padding-left:20px;
}

/* MAIN CONTENT */
.main{
    flex:1;
    padding:40px;
    background:#f8f9fa;
}
.main h1{
    margin-bottom:35px;
    color:#0a3d62;
    font-size:32px;
    font-weight:700;
}

/* ORDER CARD */
.order-card{
    background:white;
    margin-bottom:25px;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    transition:all 0.3s ease;
    border-left:5px solid #667eea;
}
.order-card:hover{
    box-shadow:0 8px 30px rgba(0,0,0,0.12);
    transform:translateY(-2px);
}

/* ORDER HEADER */
.order-header{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    padding:20px 25px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:15px;
}
.order-header h3{
    font-size:20px;
    font-weight:600;
}
.order-info{
    display:flex;
    gap:30px;
    flex-wrap:wrap;
}
.info-item{
    font-size:13px;
    opacity:0.9;
}
.info-item strong{
    display:block;
    font-size:14px;
    margin-bottom:2px;
}

/* ORDER BODY */
.order-body{
    padding:25px;
}
.customer-info{
    background:#f8f9fa;
    padding:15px;
    border-radius:8px;
    margin-bottom:20px;
}
.customer-info p{
    margin:6px 0;
    color:#333;
    font-size:14px;
}
.customer-info strong{
    color:#0a3d62;
    font-weight:600;
}

/* STATUS SECTION */
.status-section{
    display:flex;
    align-items:center;
    gap:15px;
    margin-bottom:20px;
    padding:15px;
    background:#f0f7ff;
    border-radius:8px;
    border-left:4px solid #1e90ff;
}
.status-badge{
    padding:8px 16px;
    border-radius:20px;
    color:white;
    display:inline-block;
    font-weight:600;
    font-size:13px;
}
.status-badge.pending{
    background:#f39c12;
    box-shadow:0 2px 8px rgba(243,156,18,0.3);
}
.status-badge.completed{
    background:#27ae60;
    box-shadow:0 2px 8px rgba(39,174,96,0.3);
}
.status-badge.cancelled{
    background:#e74c3c;
    box-shadow:0 2px 8px rgba(231,76,60,0.3);
}

/* FORM CONTROLS */
.status-form{
    display:flex;
    align-items:center;
    gap:10px;
}
.status-select{
    padding:10px 14px;
    border-radius:6px;
    border:2px solid #e0e0e0;
    background:white;
    color:#333;
    font-weight:500;
    cursor:pointer;
    transition:all 0.3s ease;
}
.status-select:hover{
    border-color:#667eea;
}
.status-select:focus{
    outline:none;
    border-color:#667eea;
    box-shadow:0 0 0 3px rgba(102,126,234,0.1);
}
.update-btn{
    padding:10px 20px;
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
    transition:all 0.3s ease;
}
.update-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(102,126,234,0.4);
}

/* ITEMS TABLE */
.items-container{
    margin-top:20px;
}
.items-container table{
    width:100%;
    border-collapse:collapse;
}
.items-container th{
    background:#f0f7ff;
    padding:12px 15px;
    text-align:left;
    font-weight:600;
    color:#0a3d62;
    border-bottom:2px solid #e0e0e0;
    font-size:13px;
    text-transform:uppercase;
    letter-spacing:0.5px;
}
.items-container td{
    padding:12px 15px;
    border-bottom:1px solid #f0f0f0;
    color:#555;
    font-size:14px;
}
.items-container tr:hover{
    background:#f8f9fa;
}
.items-container tr:last-child td{
    border-bottom:none;
}

/* TOTAL SECTION */
.total-section{
    display:flex;
    justify-content:flex-end;
    margin-top:20px;
    padding-top:15px;
    border-top:2px solid #f0f0f0;
}
.total-amount{
    font-size:18px;
    font-weight:700;
    color:#0a3d62;
}
.total-amount .label{
    color:#666;
    font-weight:500;
    margin-right:10px;
}
.total-amount .value{
    color:#667eea;
    font-size:22px;
}

/* EMPTY STATE */
.empty-state{
    text-align:center;
    padding:60px 20px;
    color:#999;
}
.empty-state h3{
    font-size:20px;
    margin-bottom:10px;
    color:#0a3d62;
}

/* RESPONSIVE */
@media(max-width:768px){
    .wrapper{flex-direction:column;}
    .sidebar{width:100%;height:auto;position:static;}
    .main{padding:20px;}
    .order-header{flex-direction:column;align-items:flex-start;}
    .order-info{flex-direction:column;gap:10px;}
    .status-form{flex-direction:column;}
    .status-form select, .status-form button{width:100%;}
}
</style>
</head>
<body>

<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>📊 Admin Panel</h2>
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="manage_products.php">📦 Products</a>
        <a href="categories.php">🏷️ Categories</a>
        <a href="users.php">👥 Users</a>
        <a href="orders.php">📋 Orders</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <!-- Main content -->
    <div class="main">
        <h1>📋 Manage Orders</h1>

        <?php if(empty($orders)): ?>
        <div class="empty-state">
            <h3>No orders yet</h3>
            <p>When customers place orders, they will appear here.</p>
        </div>
        <?php else: ?>
            <?php foreach($orders as $order): ?>
            <div class="order-card">
                <!-- Order Header -->
                <div class="order-header">
                    <div>
                        <h3>#<?= $order['id'] ?> - <?= htmlspecialchars($order['fullname']) ?></h3>
                    </div>
                    <div class="order-info">
                        <div class="info-item">
                            <strong>📅 Date</strong>
                            <?= date('M d, Y H:i', strtotime($order['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <!-- Order Body -->
                <div class="order-body">
                    <!-- Customer Info -->
                    <div class="customer-info">
                        <p><strong>📧 Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                        <p><strong>📱 Phone:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                        <p><strong>📍 Address:</strong> <?= htmlspecialchars($order['address']) ?></p>
                    </div>

                    <!-- Status Section -->
                    <div class="status-section">
                        <strong>Status:</strong>
                        <span class="status-badge <?= strtolower($order['status']) ?>">
                            <?php 
                            $status_icon = [
                                'pending' => '⏳',
                                'completed' => '✅',
                                'cancelled' => '❌'
                            ];
                            echo $status_icon[$order['status']] . ' ' . ucfirst($order['status']);
                            ?>
                        </span>
                        
                        <form method="POST" class="status-form" style="margin-left:auto;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" class="status-select">
                                <option value="pending" <?= $order['status']=='pending'?'selected':'' ?>>⏳ Pending</option>
                                <option value="completed" <?= $order['status']=='completed'?'selected':'' ?>>✅ Completed</option>
                                <option value="cancelled" <?= $order['status']=='cancelled'?'selected':'' ?>>❌ Cancelled</option>
                            </select>
                            <button type="submit" name="update_status" class="update-btn">Update Status</button>
                        </form>
                    </div>

                    <!-- Order Items -->
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
                    
                    <div class="items-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($items as $item): 
                                    $subtotal = $item['price'] * $item['quantity'];
                                    $totalAmount += $subtotal;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>TZS <?= number_format($item['price'], 2) ?></td>
                                    <td>TZS <?= number_format($subtotal, 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Total Amount -->
                    <div class="total-section">
                        <div class="total-amount">
                            <span class="label">Total Order Amount:</span>
                            <span class="value">TZS <?= number_format($totalAmount, 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
