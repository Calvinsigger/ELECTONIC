<?php
session_start();
require_once "../../../api/db.php"; // Adjust path if needed

header('Content-Type: text/plain');

// Security: Customer only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(403);
    exit("Access denied");
}

$userId = $_SESSION['user_id'];
$productId = $_POST['product_id'] ?? null;

if (!$productId) {
    http_response_code(400);
    exit("No product specified");
}

try {
    // Check if product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) exit("Product does not exist");

    // Check if already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $cartItem = $stmt->fetch();

    if ($cartItem) {
        // Increment quantity by 1
        $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
        $stmt->execute([$cartItem['id']]);
    } else {
        // Insert new cart item
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->execute([$userId, $productId]);
    }

    echo "Product added to cart ✅";

} catch(PDOException $e){
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
