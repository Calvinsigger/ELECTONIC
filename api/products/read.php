<?php
session_start();
require_once __DIR__ . '/../db.php';

/* ===== SECURITY: CUSTOMER LOGIN REQUIRED ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(403);
    header('Content-Type: application/json');
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$stmt = $conn->query("
    SELECT p.id, p.product_name, p.price, p.image, p.description, c.category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($products);

