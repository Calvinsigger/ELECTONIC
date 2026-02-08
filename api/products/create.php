<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['product_name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category_id = $_POST['category_id'] ?? null;
    $description = $_POST['description'] ?? '';

    if (!$name || !$price || !$category_id) {
        die(json_encode(['success' => false, 'message' => 'Please fill all required fields']));
    }

    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
        die(json_encode(['success' => false, 'message' => 'Image upload failed']));
    }

    $imageName = time() . '_' . basename($_FILES['image']['name']);
    $targetPath = __DIR__ . '/../../uploads/' . $imageName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        die(json_encode(['success' => false, 'message' => 'Failed to save image']));
    }

    $stmt = $conn->prepare("
        INSERT INTO products (product_name, price, category_id, image, description)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $price, $category_id, $imageName, $description]);

    echo json_encode(['success' => true, 'message' => 'Product added successfully']);
}
