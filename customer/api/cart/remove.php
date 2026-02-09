<?php
session_start();
require_once "../../../api/db.php";
require_once "../../../api/validation.php";

header('Content-Type: application/json');

/* ===== SECURITY: CUSTOMER ONLY ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied']));
}

$user_id = $_SESSION['user_id'];

/* ===== GET CART_ID FROM POST ===== */
$cart_id = $_POST['cart_id'] ?? null;

if (isEmpty($cart_id)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Cart ID is required']));
}

$cart_id = intval($cart_id);

if ($cart_id <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid cart ID']));
}

try {
    /* ===== VERIFY CART ITEM BELONGS TO USER ===== */
    $stmt = $conn->prepare("SELECT id FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cartItem) {
        http_response_code(404);
        die(json_encode(['success' => false, 'message' => 'Cart item not found']));
    }

    /* ===== DELETE CART ITEM ===== */
    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_id, $user_id]);

    /* ===== CALCULATE NEW TOTAL ===== */
    $stmt = $conn->prepare("
        SELECT SUM(c.quantity * p.price) as total
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart',
        'total' => number_format($total, 2, '.', '')
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

