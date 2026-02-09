<?php
session_start();
require_once __DIR__ . '/../db.php';

/* ===== NOTE: PUBLIC - Anyone can view products ===== */
/* Authentication required for: Adding to cart, checkout, orders */

$stmt = $conn->query("
    SELECT p.id, p.product_name, p.price, p.image, p.description, c.category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
");

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($products);

