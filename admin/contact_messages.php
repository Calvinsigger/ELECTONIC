<?php
session_start();
require_once "../api/db.php";

// ===== ACCESS CONTROL =====
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: ../login.php");
    exit;
}

/* ===== HANDLE MARK AS READ/RESOLVED ===== */
if (isset($_POST['action']) && isset($_POST['message_id'])) {
    $message_id = (int)$_POST['message_id'];
    $action = $_POST['action'];

    if ($action === 'read') {
        $stmt = $conn->prepare("UPDATE contact_messages SET status='read' WHERE id=?");
        $stmt->execute([$message_id]);
    } elseif ($action === 'resolved') {
        $stmt = $conn->prepare("UPDATE contact_messages SET status='resolved' WHERE id=?");
        $stmt->execute([$message_id]);
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id=?");
        $stmt->execute([$message_id]);
    }
}

/* ===== GET FILTER PARAMETER ===== */
$filter = $_GET['filter'] ?? 'unread';
if (!in_array($filter, ['unread', 'read', 'resolved', 'all'])) {
    $filter = 'unread';
}

/* ===== FETCH CONTACT MESSAGES ===== */
if ($filter === 'all') {
    $stmt = $conn->prepare("
        SELECT * FROM contact_messages 
        ORDER BY created_at DESC
    ");
    $stmt->execute([]);
} else {
    $stmt = $conn->prepare("
        SELECT * FROM contact_messages 
        WHERE status = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$filter]);
}
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== GET MESSAGE COUNTS ===== */
$counts = $conn->prepare("
    SELECT status, COUNT(*) as count
    FROM contact_messages
    GROUP BY status
");
$counts->execute([]);
$countData = $counts->fetchAll(PDO::FETCH_ASSOC);
$statusCounts = [];
foreach ($countData as $row) {
    $statusCounts[$row['status']] = $row['count'];
}
$totalCount = $conn->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Messages | ElectroStore Admin</title>
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
.container{padding:40px 20px;max-width:1200px;margin:auto;}
h1{color:white;margin-bottom:30px;font-size:32px;font-weight:700;}

/* STATS */
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));
    gap:15px;
    margin-bottom:30px;
}
.stat-box{
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
    text-align:center;
    cursor:pointer;
    transition:all 0.3s ease;
    border-left:4px solid #667eea;
}
.stat-box:hover{transform:translateY(-5px);box-shadow:0 8px 25px rgba(0,0,0,0.15);}
.stat-box.unread{border-left-color:#ff9800;}
.stat-box.read{border-left-color:#2196f3;}
.stat-box.resolved{border-left-color:#4caf50;}
.stat-box.all{border-left-color:#9c27b0;}
.stat-box h3{font-size:14px;color:#666;margin-bottom:8px;text-transform:uppercase;}
.stat-box .count{font-size:32px;font-weight:700;color:#667eea;}

/* FILTERS */
.filters{
    margin-bottom:30px;
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.filter-btn{
    padding:10px 20px;
    background:white;
    border:2px solid #e0e0e0;
    border-radius:25px;
    cursor:pointer;
    font-weight:600;
    transition:all 0.3s ease;
    color:#666;
}
.filter-btn:hover, .filter-btn.active{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    border-color:transparent;
}

/* MESSAGES BOX */
.messages-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);}

.message-item{
    padding:20px;
    border:2px solid #f0f0f0;
    border-radius:10px;
    margin-bottom:15px;
    transition:all 0.3s ease;
}
.message-item:hover{border-color:#667eea;background:#f9f9ff;}

.message-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:12px;
    flex-wrap:wrap;
    gap:10px;
}
.message-header h3{color:#0a3d62;font-size:16px;font-weight:600;}
.message-reason{
    background:#f0f0f0;
    padding:4px 12px;
    border-radius:15px;
    font-size:12px;
    color:#666;
    font-weight:600;
}

.message-status{
    padding:6px 14px;
    border-radius:15px;
    font-size:12px;
    font-weight:600;
    text-transform:uppercase;
}
.message-status.unread{background:#ffe0b2;color:#e65100;}
.message-status.read{background:#bbdefb;color:#1565c0;}
.message-status.resolved{background:#c8e6c9;color:#2e7d32;}

.message-info{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
    margin-bottom:12px;
    font-size:14px;
    color:#666;
}
.message-info p{margin-bottom:4px;}
.message-info strong{color:#0a3d62;}

.message-content{
    background:#f8f9fa;
    padding:15px;
    border-radius:8px;
    margin-bottom:15px;
    line-height:1.6;
    color:#555;
}

.message-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
.action-btn{
    padding:8px 14px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:600;
    font-size:12px;
    transition:all 0.3s ease;
}
.action-btn.read{background:#2196f3;color:white;}
.action-btn.resolved{background:#4caf50;color:white;}
.action-btn.delete{background:#f44336;color:white;}
.action-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(0,0,0,0.2);}

.message-date{
    font-size:12px;
    color:#999;
    margin-top:10px;
    padding-top:10px;
    border-top:1px solid #e0e0e0;
}

.no-messages{
    text-align:center;
    padding:40px 20px;
    color:#999;
    font-size:16px;
}

@media(max-width:768px){
    .message-info{grid-template-columns:1fr;}
    .message-actions{flex-direction:column;}
    .action-btn{width:100%;}
    .stat-box{min-width:120px;}
}
</style>
</head>
<body>

<header>
    <div class="logo">🛍️ ElectroStore</div>
    <nav>
        <a href="admin_dashboard.php">📊 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="orders.php">🛒 Orders</a>
        <a href="users.php">👥 Users</a>
        <a href="contact_messages.php">📧 Messages</a>
        <a href="../logout.php">🚪 Logout</a>
    </nav>
</header>

<div class="container">
    <h1>📧 Contact Messages</h1>

    <!-- STATS -->
    <div class="stats">
        <a href="?filter=all" style="text-decoration:none;">
            <div class="stat-box all">
                <h3>Total</h3>
                <div class="count"><?= $totalCount ?></div>
            </div>
        </a>
        <a href="?filter=unread" style="text-decoration:none;">
            <div class="stat-box unread">
                <h3>Unread</h3>
                <div class="count"><?= $statusCounts['unread'] ?? 0 ?></div>
            </div>
        </a>
        <a href="?filter=read" style="text-decoration:none;">
            <div class="stat-box read">
                <h3>Read</h3>
                <div class="count"><?= $statusCounts['read'] ?? 0 ?></div>
            </div>
        </a>
        <a href="?filter=resolved" style="text-decoration:none;">
            <div class="stat-box resolved">
                <h3>Resolved</h3>
                <div class="count"><?= $statusCounts['resolved'] ?? 0 ?></div>
            </div>
        </a>
    </div>

    <!-- FILTERS -->
    <div class="filters">
        <a href="?filter=unread"><button class="filter-btn <?= $filter === 'unread' ? 'active' : '' ?>">⏳ Unread</button></a>
        <a href="?filter=read"><button class="filter-btn <?= $filter === 'read' ? 'active' : '' ?>">👁️ Read</button></a>
        <a href="?filter=resolved"><button class="filter-btn <?= $filter === 'resolved' ? 'active' : '' ?>">✓ Resolved</button></a>
        <a href="?filter=all"><button class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">📋 All Messages</button></a>
    </div>

    <!-- MESSAGES -->
    <div class="messages-box">
        <?php if (count($messages) > 0): ?>
            <?php foreach ($messages as $msg): ?>
                <div class="message-item">
                    <div class="message-header">
                        <h3>📨 <?= htmlspecialchars($msg['fullname']) ?></h3>
                        <span class="message-reason"><?= htmlspecialchars($msg['reason']) ?></span>
                        <span class="message-status <?= $msg['status'] ?>"><?= htmlspecialchars($msg['status']) ?></span>
                    </div>

                    <div class="message-info">
                        <p><strong>📧 Email:</strong> <a href="mailto:<?= htmlspecialchars($msg['email']) ?>"><?= htmlspecialchars($msg['email']) ?></a></p>
                        <p><strong>📱 Phone:</strong> <?= $msg['phone'] ? htmlspecialchars($msg['phone']) : 'Not provided' ?></p>
                    </div>

                    <div class="message-content">
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                    </div>

                    <!-- ACTIONS -->
                    <div class="message-actions">
                        <?php if ($msg['status'] !== 'read'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                            <input type="hidden" name="action" value="read">
                            <button type="submit" class="action-btn read">👁️ Mark as Read</button>
                        </form>
                        <?php endif; ?>

                        <?php if ($msg['status'] !== 'resolved'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                            <input type="hidden" name="action" value="resolved">
                            <button type="submit" class="action-btn resolved">✓ Mark as Resolved</button>
                        </form>
                        <?php endif; ?>

                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message?');">
                            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="action-btn delete">🗑️ Delete</button>
                        </form>
                    </div>

                    <div class="message-date">
                        📅 Sent: <?= date('M d, Y H:i A', strtotime($msg['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="no-messages">
                No messages found in this category.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
