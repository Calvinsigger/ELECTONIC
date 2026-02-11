<?php
session_start();
require_once __DIR__ . "/../api/db.php";
require_once __DIR__ . "/../api/security.php";
require_once __DIR__ . "/../api/validation.php";

/* ===== CHECK LOGIN & ROLE ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

/* ===== HANDLE MESSAGE SUBMISSION ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($subject) || empty($message)) {
            $error = "Subject and message are required.";
        } elseif (strlen($subject) > 255) {
            $error = "Subject must not exceed 255 characters.";
        } elseif (strlen($message) > 5000) {
            $error = "Message must not exceed 5000 characters.";
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO customer_messages (user_id, subject, message, status) VALUES (?, ?, ?, 'unread')"
                );
                $stmt->execute([$user_id, $subject, $message]);
                $success = "✓ Message sent successfully! Admin will review it shortly.";
            } catch (PDOException $e) {
                $error = "Error sending message. Please try again.";
            }
        }
    }
}

/* ===== FETCH SENT MESSAGES ===== */
$stmt = $conn->prepare(
    "SELECT id, subject, message, status, created_at FROM customer_messages WHERE user_id = ? ORDER BY created_at DESC"
);
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contact Admin | Customer</title>
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
    max-width: 1000px;
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

.content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

/* FORM BOX */
.form-box {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.form-box h2 {
    color: #0a3d62;
    margin-bottom: 20px;
    font-size: 20px;
    font-weight: 600;
}

.form-group {
    margin-bottom: 18px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #333;
    font-weight: 600;
    font-size: 14px;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.char-count {
    font-size: 12px;
    color: #999;
    margin-top: 5px;
}

.btn-submit {
    width: 100%;
    padding: 12px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    margin-top: 10px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* MESSAGES BOX */
.messages-box {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.messages-box h2 {
    color: #0a3d62;
    margin-bottom: 20px;
    font-size: 20px;
    font-weight: 600;
}

.message-item {
    padding: 15px;
    border-left: 4px solid #667eea;
    background: #f8f9ff;
    margin-bottom: 12px;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.message-item:hover {
    background: #f0f2ff;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
}

.message-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    flex-wrap: wrap;
    gap: 10px;
}

.message-subject {
    font-weight: 600;
    color: #0a3d62;
    font-size: 15px;
}

.message-status {
    padding: 4px 10px;
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

.message-text {
    color: #555;
    font-size: 13px;
    margin: 8px 0;
    line-height: 1.5;
    max-height: 80px;
    overflow-y: auto;
}

.message-date {
    font-size: 12px;
    color: #999;
}

.no-messages {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

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

@media (max-width: 768px) {
    .header {
        flex-direction: column;
        text-align: center;
    }

    .content {
        grid-template-columns: 1fr;
    }

    .header h1 {
        font-size: 22px;
    }
}
</style>
</head>

<body>

<div class="container">

    <!-- HEADER -->
    <div class="header">
        <h1>Contact Admin</h1>
        <a href="customer_dashboard.php">← Back to Dashboard</a>
    </div>

    <!-- ALERTS -->
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- CONTENT -->
    <div class="content">

        <!-- SEND MESSAGE FORM -->
        <div class="form-box">
            <h2>Send Message to Admin</h2>
            
            <form method="POST">
                <?= getCSRFTokenInput() ?>
                <input type="hidden" name="action" value="send">

                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" 
                        placeholder="What is your message about?" 
                        minlength="5" maxlength="255" required>
                    <div class="char-count"><span id="subject-count">0</span>/255</div>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" 
                        placeholder="Please describe your issue or concern in detail..." 
                        minlength="10" maxlength="5000" required></textarea>
                    <div class="char-count"><span id="message-count">0</span>/5000</div>
                </div>

                <button type="submit" class="btn-submit">Send Message</button>
            </form>
        </div>

        <!-- MESSAGES HISTORY -->
        <div class="messages-box">
            <h2>Your Messages</h2>

            <?php if (count($messages) > 0): ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="message-item">
                        <div class="message-header">
                            <span class="message-subject"><?= htmlspecialchars($msg['subject']) ?></span>
                            <span class="message-status <?= $msg['status'] === 'read' ? 'status-read' : 'status-unread' ?>">
                                <?= ucfirst($msg['status']) ?>
                            </span>
                        </div>
                        <div class="message-text"><?= htmlspecialchars($msg['message']) ?></div>
                        <div class="message-date"><?= date('M j, Y H:i', strtotime($msg['created_at'])) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-messages">
                    No messages sent yet. Send your first message above!
                </div>
            <?php endif; ?>
        </div>

    </div>

</div>

<script>
// Real-time character counting
document.getElementById('subject').addEventListener('input', function() {
    document.getElementById('subject-count').innerText = this.value.length;
});

document.getElementById('message').addEventListener('input', function() {
    document.getElementById('message-count').innerText = this.value.length;
});
</script>

</body>
</html>
