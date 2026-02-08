<?php
session_start();
require_once "../../db.php"; 

header('Content-Type: application/json');

// Security: Customer only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$userId = $_SESSION['user_id'];
$cartId = $_POST['cart_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;

if (!$cartId || !$quantity) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing parameters']);
    exit;
}

$quantity = (int)$quantity;
if ($quantity < 1) $quantity = 1;

try {
    // Check if cart item belongs to this user
    $stmt = $conn->prepare("
        SELECT c.id, c.product_id, c.quantity, p.price
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cartId, $userId]);
    $cartItem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cartItem) {
        echo json_encode(['error' => 'Cart item not found']);
        exit;
    }

    // Update quantity
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$quantity, $cartId]);

    // Calculate new subtotal & total
    $subtotal = $cartItem['price'] * $quantity;
    $totalStmt = $conn->prepare("
        SELECT SUM(p.price * c.quantity) as total
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $totalStmt->execute([$userId]);
    $total = $totalStmt->fetchColumn();

    echo json_encode([
        'subtotal' => number_format($subtotal,2),
        'total' => number_format($total,2)
    ]);

} catch(PDOException $e){
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
