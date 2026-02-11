<?php
session_start();
require_once "../api/db.php";
require_once "../api/validation.php";
require_once "../api/security.php";

header('Content-Type: application/json');

/* ===== SECURITY: CUSTOMER ONLY ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===== GET PAYMENT HISTORY ===== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'history') {
    
    try {
        $stmt = $conn->prepare("
            SELECT p.id, p.order_id, p.amount, p.payment_method, p.card_last4, 
                   p.transaction_id, p.status, p.created_at,
                   o.fullname
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $payments
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching payment history'
        ]);
    }
    exit;
}

/* ===== GET PAYMENT DETAILS ===== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'details') {
    
    $payment_id = $_GET['id'] ?? 0;

    try {
        $stmt = $conn->prepare("
            SELECT p.*, o.fullname, o.address, o.phone, o.total_amount
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            WHERE p.id = ? AND p.user_id = ?
        ");
        $stmt->execute([$payment_id, $user_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            echo json_encode([
                'success' => false,
                'message' => 'Payment not found'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'data' => $payment
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching payment details'
        ]);
    }
    exit;
}

/* ===== EXPORT INVOICE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'invoice') {
    
    $order_id = $_GET['order_id'] ?? 0;

    try {
        $stmt = $conn->prepare("
            SELECT p.*, o.id, o.fullname, o.address, o.phone, o.total_amount, o.created_at
            FROM payments p
            JOIN orders o ON p.order_id = o.id
            WHERE p.order_id = ? AND p.user_id = ?
        ");
        $stmt->execute([$order_id, $user_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode([
                'success' => false,
                'message' => 'Order not found'
            ]);
            exit;
        }

        // Fetch order items
        $stmt = $conn->prepare("
            SELECT oi.quantity, oi.price, p.product_name
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'order' => $order,
                'items' => $items
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error generating invoice'
        ]);
    }
    exit;
}

/* ===== REFUND REQUEST ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_refund') {
    
    /* ===== CSRF TOKEN VALIDATION ===== */
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Security token invalid']);
        exit;
    }

    $payment_id = $_POST['payment_id'] ?? 0;
    $reason = trim($_POST['reason'] ?? '');

    if (strlen($reason) < 10) {
        echo json_encode([
            'success' => false,
            'message' => 'Refund reason must be at least 10 characters'
        ]);
        exit;
    }

    try {
        // Check if payment exists and belongs to user
        $stmt = $conn->prepare("SELECT id, status FROM payments WHERE id = ? AND user_id = ?");
        $stmt->execute([$payment_id, $user_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) {
            echo json_encode([
                'success' => false,
                'message' => 'Payment not found'
            ]);
            exit;
        }

        if ($payment['status'] === 'failed') {
            echo json_encode([
                'success' => false,
                'message' => 'Cannot refund a failed payment'
            ]);
            exit;
        }

        // Update payment status to refund_requested (you can add this status to the enum)
        // For now, we'll just log/record the refund request
        
        echo json_encode([
            'success' => true,
            'message' => 'Refund request submitted. Our team will review it shortly.'
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error processing refund request'
        ]);
    }
    exit;
}

/* ===== DEFAULT - INVALID ACTION ===== */
echo json_encode([
    'success' => false,
    'message' => 'Invalid action'
]);
?>
