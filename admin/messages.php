<?php
session_start();
require_once __DIR__ . "/../api/db.php";
require_once __DIR__ . "/../api/security.php";

/* ===== ADMIN CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$error = "";
$success = "";

/* ===== HANDLE MARK AS READ ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $message_id = (int)($_POST['message_id'] ?? 0);
        if ($message_id > 0) {
            try {
                $stmt = $conn->prepare("UPDATE customer_messages SET status = 'read' WHERE id = ?");
                $stmt->execute([$message_id]);
                $success = "✓ Message marked as read.";
            } catch (PDOException $e) {
                $error = "Error updating message.";
            }
        }
    }
}

/* ===== HANDLE ADMIN REPLY ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $message_id = (int)($_POST['message_id'] ?? 0);
        $reply_text = trim($_POST['reply_text'] ?? '');

        if ($message_id <= 0 || empty($reply_text)) {
            $error = "Reply message cannot be empty.";
        } elseif (strlen($reply_text) > 5000) {
            $error = "Reply must not exceed 5000 characters.";
        } else {
            try {
                $stmt = $conn->prepare(
                    "UPDATE customer_messages SET admin_reply = ?, replied = 'yes', replied_at = NOW() WHERE id = ?"
                );
                $stmt->execute([$reply_text, $message_id]);
                $success = "✓ Reply sent successfully!";
            } catch (PDOException $e) {
                $error = "Error sending reply.";
            }
        }
    }
}

/* ===== HANDLE DELETE MESSAGE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $message_id = (int)($_POST['message_id'] ?? 0);
        if ($message_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM customer_messages WHERE id = ?");
                $stmt->execute([$message_id]);
                $success = "✓ Message deleted.";
            } catch (PDOException $e) {
                $error = "Error deleting message.";
            }
        }
    }
}

/* ===== MARK ALL NEW MESSAGES AS VIEWED BY ADMIN ===== */
$stmt = $conn->prepare("UPDATE customer_messages SET admin_viewed = 'yes' WHERE admin_viewed = 'no'");
$stmt->execute();

/* ===== COUNT UNVIEWD MESSAGES (FOR SIDEBAR BADGE) ===== */
$new_messages = $conn->query("SELECT COUNT(*) FROM customer_messages WHERE admin_viewed = 'no'")->fetchColumn();

/* ===== FETCH ALL MESSAGES ===== */
$filter = $_GET['filter'] ?? '';
$search = $_GET['search'] ?? '';

$query = "
    SELECT cm.id, cm.user_id, cm.subject, cm.message, cm.status, cm.created_at, u.fullname, u.email
    FROM customer_messages cm
    JOIN users u ON cm.user_id = u.id
    WHERE 1=1
";
$params = [];

if ($filter === 'unread') {
    $query .= " AND cm.status = 'unread'";
} elseif ($filter === 'read') {
    $query .= " AND cm.status = 'read'";
}

if (!empty($search)) {
    $query .= " AND (cm.subject LIKE ? OR cm.message LIKE ? OR u.fullname LIKE ? OR u.email LIKE ?)";
    $search_term = "%$search%";
    $params = array_fill(0, 4, $search_term);
}

$query .= " ORDER BY cm.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== COUNT UNREAD ===== */
$unread_stmt = $conn->query("SELECT COUNT(*) as count FROM customer_messages WHERE status = 'unread'");
$unread_count = $unread_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Customer Messages | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.wrapper {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */
.sidebar {
    width: 260px;
    background: linear-gradient(180deg, #0a3d62 0%, #062d48 100%);
    color: white;
    padding: 30px 20px;
    box-shadow: 4px 0 15px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
}

.sidebar h2 {
    text-align: center;
    margin-bottom: 40px;
    font-size: 22px;
    font-weight: 700;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 14px 16px;
    margin-bottom: 8px;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
    border-left: 4px solid transparent;
}

.sidebar a:hover {
    background: rgba(255,255,255,0.2);
    border-left: 4px solid #ffdd59;
    padding-left: 20px;
}

.sidebar a.active {
    background: rgba(255,255,255,0.3);
    border-left: 4px solid #667eea;
}

/* MAIN */
.main {
    flex: 1;
    padding: 40px;
    background: #f8f9fa;
    overflow-y: auto;
}

.main h1 {
    margin-bottom: 10px;
    color: #0a3d62;
    font-size: 32px;
    font-weight: 700;
}

.subtitle {
    color: #666;
    margin-bottom: 30px;
    font-size: 14px;
}

/* ALERTS */
.alert {
    padding: 15px 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    font-weight: 500;
    border-left: 4px solid;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left-color: #28a745;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
    border-left-color: #dc3545;
}

/* FILTERS & SEARCH */
.filters {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.filter-input,
.filter-select {
    padding: 10px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.3s ease;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #667eea;
}

.filter-btn {
    padding: 10px 20px;
    background: #667eea;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* STATS */
.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    text-align: center;
}

.stat-number {
    font-size: 32px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 5px;
}

.stat-label {
    color: #666;
    font-size: 14px;
    font-weight: 500;
}

/* MESSAGES TABLE */
.messages-box {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.messages-box h3 {
    color: #0a3d62;
    margin-bottom: 20px;
    font-size: 18px;
    font-weight: 600;
}

.message-item {
    border-left: 5px solid #667eea;
    background: #f8f9ff;
    padding: 15px;
    margin-bottom: 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.message-item:hover {
    background: #f0f2ff;
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 10px;
    flex-wrap: wrap;
    gap: 10px;
}

.message-meta {
    flex: 1;
}

.message-from {
    font-weight: 600;
    color: #0a3d62;
    font-size: 15px;
}

.message-email {
    font-size: 12px;
    color: #999;
}

.message-subject {
    font-weight: 600;
    color: #0a3d62;
    margin: 8px 0;
    font-size: 14px;
}

.message-body {
    background: white;
    padding: 10px;
    border-radius: 6px;
    color: #555;
    font-size: 13px;
    line-height: 1.5;
    margin: 8px 0;
    max-height: 100px;
    overflow-y: auto;
}

.message-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
}

.message-date {
    font-size: 12px;
    color: #999;
}

.message-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-unread {
    background: #fff3cd;
    color: #856404;
}

.status-read {
    background: #d4edda;
    color: #155724;
}

.action-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    margin-left: 5px;
}

.btn-read {
    background: #2196f3;
    color: white;
}

.btn-read:hover {
    background: #1976d2;
}

.btn-delete {
    background: #f44336;
    color: white;
}

.btn-delete:hover {
    background: #da190b;
}

.no-messages {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 16px;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    padding: 30px;
    border-radius: 12px;
    max-width: 500px;
    width: 90%;
}

.modal-content h3 {
    color: #0a3d62;
    margin-bottom: 15px;
    font-size: 18px;
}

.modal-buttons {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 20px;
}

.modal-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.modal-btn-confirm {
    background: #f44336;
    color: white;
}

.modal-btn-confirm:hover {
    background: #da190b;
}

.modal-btn-cancel {
    background: #999;
    color: white;
}

.modal-btn-cancel:hover {
    background: #777;
}

@media (max-width: 1024px) {
    .wrapper {
        flex-direction: column;
    }
    
    .sidebar {
        width: 100%;
        height: auto;
        position: static;
    }
}

@media (max-width: 768px) {
    .main {
        padding: 20px;
    }

    .main h1 {
        font-size: 24px;
    }

    .message-header {
        flex-direction: column;
    }

    .stats {
        grid-template-columns: 1fr;
    }

    .filters {
        flex-direction: column;
    }
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="manage_products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="users.php">Users</a>
        <a href="orders.php">Orders</a>
        <a href="messages.php" class="active">Messages</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- MAIN -->
    <div class="main">
        <h1>Customer Messages</h1>
        <p class="subtitle">View and manage customer messages and support requests</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- NEW MESSAGES NOTIFICATION -->
        <?php 
        $unread_msg = $conn->query("SELECT COUNT(*) FROM customer_messages WHERE status = 'unread' AND admin_viewed = 'yes'")->fetchColumn();
        if ($unread_msg > 0): 
        ?>
            <div style="background:#e3f2fd;border-left:5px solid #2196f3;padding:15px 20px;border-radius:8px;margin-bottom:25px;color:#1565c0;font-weight:500;display:flex;justify-content:space-between;align-items:center;">
                <span>🔔 You have <strong><?= $unread_msg ?></strong> unread customer message<?= $unread_msg === 1 ? '' : 's' ?>!</span>
            </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($messages) ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $unread_count ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
        </div>

        <!-- FILTERS & SEARCH -->
        <div class="filters">
            <form method="GET" style="display:flex;gap:10px;width:100%;flex-wrap:wrap;">
                <input type="text" name="search" class="filter-input" placeholder="Search by subject, message, customer name..." 
                    value="<?= htmlspecialchars($search) ?>" style="flex:1;min-width:250px;">
                <select name="filter" class="filter-select">
                    <option value="">All Messages</option>
                    <option value="unread" <?= $filter === 'unread' ? 'selected' : '' ?>>Unread Only</option>
                    <option value="read" <?= $filter === 'read' ? 'selected' : '' ?>>Read Only</option>
                </select>
                <button type="submit" class="filter-btn">Filter</button>
            </form>
        </div>

        <!-- MESSAGES -->
        <div class="messages-box">
            <h3><?= count($messages) ?> Message<?= count($messages) !== 1 ? 's' : '' ?></h3>

            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message-item">
                        <div class="message-header">
                            <div class="message-meta">
                                <div class="message-from">👤 <?= htmlspecialchars($msg['fullname']) ?></div>
                                <div class="message-email">📧 <?= htmlspecialchars($msg['email']) ?></div>
                            </div>
                            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                <?php if (isset($msg['admin_viewed']) && $msg['admin_viewed'] === 'no'): ?>
                                    <span style="background:#ff5722;color:white;padding:4px 10px;border-radius:20px;font-size:10px;font-weight:700;">NEW</span>
                                <?php endif; ?>
                                <span class="message-status <?= $msg['status'] === 'read' ? 'status-read' : 'status-unread' ?>">
                                    <?= ucfirst($msg['status']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="message-subject">📌 <?= htmlspecialchars($msg['subject']) ?></div>
                        
                        <div class="message-body">
                            <?= htmlspecialchars($msg['message']) ?>
                        </div>

                        <div class="message-footer">
                            <div class="message-date">🕐 <?= date('M j, Y H:i', strtotime($msg['created_at'])) ?></div>
                            <div>
                                <?php if ($msg['status'] === 'unread'): ?>
                                    <form method="POST" style="display:inline;">
                                        <?= getCSRFTokenInput() ?>
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                        <button type="submit" class="action-btn btn-read">✓ Mark as Read</button>
                                    </form>
                                <?php endif; ?>
                                <button type="button" class="action-btn btn-delete" onclick="openDeleteModal(<?= $msg['id'] ?>)">🗑 Delete</button>
                            </div>
                        </div>

                        <!-- ADMIN REPLY SECTION -->
                        <?php if (isset($msg['admin_reply']) && !empty($msg['admin_reply'])): ?>
                            <div style="background:#e8f5e9;border-left:4px solid #4caf50;padding:15px;margin-top:15px;border-radius:6px;">
                                <div style="font-weight:600;color:#2e7d32;margin-bottom:8px;">✓ Admin Reply</div>
                                <div style="color:#555;font-size:13px;line-height:1.5;"><?= htmlspecialchars($msg['admin_reply']) ?></div>
                                <div style="font-size:11px;color:#999;margin-top:8px;">📅 <?= date('M j, Y H:i', strtotime($msg['replied_at'])) ?></div>
                            </div>
                        <?php else: ?>
                            <!-- REPLY FORM -->
                            <div style="background:#f8f9ff;border-left:4px solid #667eea;padding:15px;margin-top:15px;border-radius:6px;">
                                <div style="font-weight:600;color:#0a3d62;margin-bottom:10px;">💬 Send Reply</div>
                                <form method="POST">
                                    <?= getCSRFTokenInput() ?>
                                    <input type="hidden" name="action" value="reply">
                                    <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
                                    <textarea name="reply_text" placeholder="Type your reply to the customer..." style="width:100%;padding:10px;border:2px solid #e0e0e0;border-radius:6px;font-size:13px;min-height:80px;font-family:inherit;margin-bottom:10px;" minlength="5" maxlength="5000" required></textarea>
                                    <button type="submit" style="padding:8px 16px;background:#4caf50;color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:13px;transition:all 0.3s ease;" onmouseover="this.style.background='#45a049'" onmouseout="this.style.background='#4caf50'">Send Reply</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-messages">
                    <?php if (!empty($search) || !empty($filter)): ?>
                        No messages found matching your filters.
                    <?php else: ?>
                        No customer messages yet.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- DELETE MODAL -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h3>Delete Message?</h3>
        <p>Are you sure you want to delete this message? This action cannot be undone.</p>
        <form id="deleteForm" method="POST" style="display:none;">
            <?= getCSRFTokenInput() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="message_id" id="deleteMessageId">
        </form>
        <div class="modal-buttons">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button type="submit" form="deleteForm" class="modal-btn modal-btn-confirm">Delete</button>
        </div>
    </div>
</div>

<script>
function openDeleteModal(messageId) {
    document.getElementById('deleteMessageId').value = messageId;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}

window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        modal.classList.remove('active');
    }
}
</script>

</body>
</html>
