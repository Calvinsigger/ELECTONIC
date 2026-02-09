<?php
session_start();
require_once __DIR__ . "/../db.php";
require_once __DIR__ . "/../validation.php";
require_once __DIR__ . "/../security.php";

/* ===== SECURITY CHECK: ADMIN ONLY ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['flash_message'] = 'Unauthorized access';
    $_SESSION['flash_type'] = 'error';
    header("Location: ../../admin/products.php");
    exit;
}

/* ===== VERIFY CSRF TOKEN ===== */
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    $_SESSION['flash_message'] = 'Invalid request. Security token mismatch.';
    $_SESSION['flash_type'] = 'error';
    header("Location: ../../admin/products.php");
    exit;
}

/* ===== GET AND VALIDATE PRODUCT ID ===== */
$product_id = $_POST['id'] ?? null;

if (isEmpty($product_id)) {
    $_SESSION['flash_message'] = 'Product ID is required';
    $_SESSION['flash_type'] = 'error';
    header("Location: ../../admin/products.php");
    exit;
}

$product_id = intval($product_id);

if ($product_id <= 0) {
    $_SESSION['flash_message'] = 'Invalid product ID';
    $_SESSION['flash_type'] = 'error';
    header("Location: ../../admin/products.php");
    exit;
}

try {
    /* ===== FETCH PRODUCT DETAILS ===== */
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['flash_message'] = 'Product not found';
        $_SESSION['flash_type'] = 'error';
        header("Location: ../../admin/products.php");
        exit;
    }

    /* ===== DELETE PRODUCT FROM DATABASE ===== */
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);

    /* ===== DELETE IMAGE FILE IF EXISTS ===== */
    if (!isEmpty($product['image'])) {
        $imagePath = __DIR__ . '/../../uploads/' . $product['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    $_SESSION['flash_message'] = 'Product deleted successfully ✅';
    $_SESSION['flash_type'] = 'success';

} catch(PDOException $e) {
    $_SESSION['flash_message'] = 'Database error: ' . $e->getMessage();
    $_SESSION['flash_type'] = 'error';
}

/* ===== REDIRECT BACK TO PRODUCTS PAGE ===== */
header("Location: ../../admin/products.php");
