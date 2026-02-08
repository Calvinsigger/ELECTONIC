<?php
session_start();
require_once "../api/db.php";

/* ===== SECURITY: CUSTOMER ONLY ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

/* ===== VALIDATE PRODUCT ID ===== */
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: shop.php");
    exit;
}

$product_id = (int)$_GET['id'];

/* ===== FETCH PRODUCT (STRICT) ===== */
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.product_name,
        p.description,
        p.price,
        CAST(p.stock AS SIGNED) AS stock,
        p.image,
        c.category_name
    FROM products p
    INNER JOIN categories c ON p.category_id = c.id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: shop.php");
    exit;
}

/* ===== FORCE STOCK INT ===== */
$stock = isset($product['stock']) ? (int)$product['stock'] : 0;
$inStock = $stock > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($product['product_name']) ?> | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f8}

header{
    background:#0a3d62;
    color:#fff;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center
}
.logo{font-size:22px;font-weight:bold}
nav a{color:#fff;text-decoration:none;margin-left:18px}
nav a:hover{color:#ffdd59}

.container{
    max-width:1100px;
    margin:40px auto;
    background:#fff;
    border-radius:14px;
    padding:30px;
    box-shadow:0 12px 25px rgba(0,0,0,.15);
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:30px
}

.product-image img{
    width:100%;
    height:420px;
    object-fit:cover;
    border-radius:12px
}

.product-info h1{margin-top:0;font-size:30px}
.category{color:#777;margin-bottom:6px}
.price{font-size:26px;font-weight:bold;color:#0a3d62;margin:15px 0}
.description{line-height:1.6;margin-bottom:20px}

.stock{
    font-weight:bold;
    margin-bottom:15px;
    font-size:16px
}
.stock.in{color:#28a745}
.stock.out{color:#dc3545}

.quantity{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:20px
}
.quantity input{
    width:80px;
    padding:10px;
    border-radius:6px;
    border:1px solid #ccc;
    text-align:center
}

.add-btn{
    background:#0a3d62;
    color:#fff;
    border:none;
    padding:14px;
    width:100%;
    font-size:16px;
    border-radius:8px;
    cursor:pointer
}
.add-btn:hover{background:#07406b}
.add-btn:disabled{background:#999;cursor:not-allowed}

.back{margin-top:20px}
.back a{text-decoration:none;color:#1e90ff}

footer{
    background:#0a3d62;
    color:#fff;
    text-align:center;
    padding:15px;
    margin-top:30px
}

@media(max-width:900px){
    .container{grid-template-columns:1fr}
    .product-image img{height:300px}
}
</style>
</head>

<body>

<header>
    <div class="logo">ElectroStore</div>
    <nav>
        <a href="shop.php">Shop</a>
        <a href="cart.php">Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">

    <div class="product-image">
        <img src="../uploads/<?= htmlspecialchars($product['image'] ?: 'no-image.png') ?>" alt="Product Image">
    </div>

    <div class="product-info">
        <h1><?= htmlspecialchars($product['product_name']) ?></h1>
        <div class="category"><?= htmlspecialchars($product['category_name']) ?></div>
        <div class="price">$<?= number_format($product['price'], 2) ?></div>
        <div class="description"><?= nl2br(htmlspecialchars($product['description'])) ?></div>

        <div class="stock <?= $inStock ? 'in' : 'out' ?>">
            <?= $inStock ? "✔ In Stock ({$stock} available)" : "❌ Out of Stock" ?>
        </div>

        <div class="quantity">
            <label>Quantity:</label>
            <input type="number"
                   id="qty"
                   min="1"
                   max="<?= $stock ?>"
                   value="1"
                   <?= !$inStock ? 'disabled' : '' ?>>
        </div>

        <button class="add-btn"
                id="addBtn"
                <?= !$inStock ? 'disabled' : '' ?>>
            🛒 Add to Cart
        </button>

        <div class="back">
            <a href="shop.php">← Continue Shopping</a>
        </div>
    </div>

</div>

<footer>
    &copy; 2026 ElectroStore • Secure Shopping Experience
</footer>

<script>
document.getElementById('addBtn')?.addEventListener('click', () => {
    const qtyInput = document.getElementById('qty');
    const qty = parseInt(qtyInput.value);
    const max = parseInt(qtyInput.max);

    if (qty < 1 || qty > max) {
        alert("❌ Invalid quantity selected");
        return;
    }

    fetch("api/cart/add.php", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "product_id=<?= $product_id ?>&quantity=" + qty
    })
    .then(res => res.text())
    .then(msg => alert(msg))
    .catch(() => alert("❌ Failed to add product to cart"));
});
</script>

</body>
</html>
