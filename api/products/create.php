<?php
session_start();
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../validation.php';
require_once __DIR__ . '/../security.php';

/* ===== AUTHORIZATION CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ===== CSRF TOKEN VALIDATION ===== */
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die(json_encode(['success' => false, 'message' => 'Security token is invalid']));
    }

    $name = $_POST['product_name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $category_id = $_POST['category_id'] ?? null;
    $description = $_POST['description'] ?? '';

    // Validate product name
    $nameValidation = validateProductName($name);
    if (!$nameValidation['valid']) {
        die(json_encode(['success' => false, 'message' => $nameValidation['message']]));
    }

    // Validate price
    $priceValidation = validatePrice($price);
    if (!$priceValidation['valid']) {
        die(json_encode(['success' => false, 'message' => $priceValidation['message']]));
    }

    // Validate category
    if (empty($category_id) || !is_numeric($category_id)) {
        die(json_encode(['success' => false, 'message' => 'Please select a valid category']));
    }

    // Validate image file
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
        die(json_encode(['success' => false, 'message' => 'Please upload a product image']));
    }

    $imageValidation = validateImageFile($_FILES['image']);
    if (!$imageValidation['valid']) {
        die(json_encode(['success' => false, 'message' => $imageValidation['message']]));
    }

    // Generate safe filename
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $imageName = time() . '_' . uniqid() . '.' . $ext;
    $targetPath = __DIR__ . '/../../uploads/' . $imageName;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        die(json_encode(['success' => false, 'message' => 'Failed to save image file']));
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO products (product_name, price, category_id, image, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $description = trim($description);
        if (strlen($description) > 1000) {
            $description = substr($description, 0, 1000);
        }

        if ($stmt->execute([$nameValidation['value'], $priceValidation['value'], $category_id, $imageName, $description])) {
            echo json_encode(['success' => true, 'message' => 'Product added successfully']);
        } else {
            unlink($targetPath); // Delete uploaded file if DB insert fails
            die(json_encode(['success' => false, 'message' => 'Failed to add product to database']));
        }
    } catch (PDOException $e) {
        unlink($targetPath); // Delete uploaded file on error
        die(json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]));
    }
} else {
    die(json_encode(['success' => false, 'message' => 'Invalid request method']));
