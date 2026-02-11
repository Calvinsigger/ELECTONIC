<?php
session_start();
require_once __DIR__ . "/../api/db.php";

/* ===== CHECK LOGIN & ROLE ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===== MARK ALL UNSEEN REPLIES AS SEEN ===== */
$stmt = $conn->prepare(
    "UPDATE customer_messages SET seen_reply = 'yes' WHERE user_id = ? AND replied = 'yes' AND seen_reply = 'no'"
);
$stmt->execute([$user_id]);

/* ===== FETCH USER'S MESSAGES ===== */
$stmt = $conn->prepare(
    "SELECT id, subject, message, admin_reply, status, replied, created_at, replied_at 
     FROM customer_messages 
     WHERE user_id = ? 
     ORDER BY created_at DESC"
);
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Messages | Customer</title>
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
    padding: 20px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.header {
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.header h1 {
    color: #0a3d62;
    font-size: 28px;
    font-weight: 700;
}

.header a {
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.header a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.info-box {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #667eea;
}

.info-box p {
    color: #555;
    font-size: 14px;
    line-height: 1.6;
}

.message-item {
    background: white;
    padding: 25px;
    border-radius: 12px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border-left: 5px solid #667eea;
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.message-subject {
    font-weight: 600;
    color: #0a3d62;
    font-size: 16px;
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

.reply-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    background: #e8f5e9;
    color: #2e7d32;
}

.message-body {
    background: #f8f9ff;
    padding: 15px;
    border-radius: 8px;
    color: #555;
    font-size: 14px;
    line-height: 1.6;
    margin: 15px 0;
    max-height: 150px;
    overflow-y: auto;
}

.message-date {
    font-size: 12px;
    color: #999;
    margin: 10px 0;
}

.reply-section {
    background: #e8f5e9;
    border-left: 4px solid #4caf50;
    padding: 15px;
    margin-top: 15px;
    border-radius: 6px;
}

.reply-header {
    font-weight: 600;
    color: #2e7d32;
    margin-bottom: 10px;
}

.reply-text {
    color: #555;
    font-size: 14px;
    line-height: 1.6;
    margin: 10px 0;
}

.reply-date {
    font-size: 11px;
    color: #999;
}

.no-messages {
    background: white;
    padding: 40px;
    border-radius: 12px;
    text-align: center;
    color: #999;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
}

.no-messages p {
    font-size: 16px;
    margin-bottom: 15px;
}

.no-messages a {
    display: inline-block;
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.no-messages a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        text-align: center;
    }

    .message-header {
        flex-direction: column;
    }

    .header h1 {
        font-size: 24px;
    }
}
</style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <h1>💬 My Messages</h1>
        <a href="customer_dashboard.php">← Back to Dashboard</a>
    </div>

    <!-- INFO -->
    <div class="info-box">
        <p>📧 View all messages you've sent to our support team and their replies. Admin responses will appear below your message once they've reviewed your request.</p>
    </div>

    <!-- MESSAGES -->
    <?php if (count($messages) > 0): ?>
        <?php foreach ($messages as $msg): ?>
            <div class="message-item">
                <div class="message-header">
                    <div class="message-subject"><?= htmlspecialchars($msg['subject']) ?></div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        <span class="message-status <?= $msg['status'] === 'read' ? 'status-read' : 'status-unread' ?>">
                            <?= ucfirst($msg['status']) ?>
                        </span>
                        <?php if (isset($msg['replied']) && $msg['replied'] === 'yes'): ?>
                            <span class="reply-status">✓ Replied
                                <?php if (isset($msg['seen_reply']) && $msg['seen_reply'] === 'no'): ?>
                                    <span style="margin-left:5px;background:#ff5722;color:white;padding:2px 6px;border-radius:3px;font-size:10px;font-weight:700;">NEW</span>
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span style="padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;background:#f0f0f0;color:#666;">⏳ Pending</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="message-date">📅 Sent: <?= date('M j, Y H:i', strtotime($msg['created_at'])) ?></div>

                <div class="message-body">
                    <strong>Your Message:</strong><br>
                    <?= htmlspecialchars($msg['message']) ?>
                </div>

                <!-- ADMIN REPLY -->
                <?php if (isset($msg['admin_reply']) && !empty($msg['admin_reply'])): ?>
                    <div class="reply-section">
                        <div class="reply-header">✓ Admin Response</div>
                        <div class="reply-text">
                            <?= htmlspecialchars($msg['admin_reply']) ?>
                        </div>
                        <div class="reply-date">📅 Replied: <?= date('M j, Y H:i', strtotime($msg['replied_at'])) ?></div>
                    </div>
                <?php else: ?>
                    <div style="background:#fffaee;border-left:4px solid #ff9800;padding:12px;margin-top:15px;border-radius:6px;font-size:13px;color:#e65100;">
                        ⏳ Waiting for admin response...
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="no-messages">
            <p>No messages sent yet.</p>
            <a href="contact_admin.php">Send a Message to Admin</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
