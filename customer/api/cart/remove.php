<?php
session_start();
require_once "../../../api/db.php"; // Adjust path if needed

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===== CHECK IF PRODUCT ID IS PROVIDED ===== */
if (!isset($_GET['product_id'])) {
    header("Location: ../../customer/cart.php"); // Redirect back to cart if no product specified
    exit;
}

$product_id = (int)$_GET['product_id'];

/* ===== DELETE THE PRODUCT FROM CART ===== */
$stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);

/* ===== REDIRECT BACK TO CART ===== */
header("Location: cart.php");
exit;
