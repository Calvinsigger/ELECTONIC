<?php
session_start();
require_once "../db.php";

if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

$id = $_POST['id'];

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->execute([$id]);

header("Location: ../../admin/products.php");
