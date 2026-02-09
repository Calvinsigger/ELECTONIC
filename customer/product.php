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
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;color:#333;}

/* HEADER */
header{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    padding:18px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
}
header .logo{font-size:26px;font-weight:700;letter-spacing:0.5px;}
nav a{color:white;text-decoration:none;margin-left:20px;font-weight:500;transition:all 0.3s ease;}
nav a:hover{color:#ffdd59;transform:translateY(-2px);}

/* CONTAINER */
.container{
    max-width:1100px;
    margin:40px auto;
    background:white;
    border-radius:14px;
    padding:40px;
    box-shadow:0 12px 30px rgba(0,0,0,0.12);
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:40px;
}

.product-image img{
    width:100%;
    height:450px;
    object-fit:cover;
    border-radius:12px;
    border:3px solid #e0e0e0;
}

.product-info h1{margin-top:0;font-size:32px;color:#0a3d62;font-weight:700;margin-bottom:12px;}
.category{color:#999;margin-bottom:15px;font-size:14px;text-transform:uppercase;letter-spacing:0.5px;font-weight:600;}
.price{font-size:36px;font-weight:700;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin:20px 0;}
.description{line-height:1.8;margin-bottom:25px;color:#666;font-size:15px;}

/* STOCK */
.stock{font-weight:700;margin-bottom:20px;font-size:16px;padding:12px 16px;border-radius:8px;display:inline-block;}
.stock.in{color:white;background:#27ae60;}
.stock.out{color:white;background:#e74c3c;}

/* QUANTITY */
.quantity{display:flex;align-items:center;gap:15px;margin-bottom:25px;}
.quantity label{font-weight:600;color:#0a3d62;}
.quantity input{width:90px;padding:12px 14px;border-radius:8px;border:2px solid #e0e0e0;text-align:center;font-size:15px;transition:all 0.3s ease;font-family:inherit;}
.quantity input:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}

/* BUTTON */
.add-btn{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    border:none;
    padding:14px 28px;
    width:100%;
    font-size:16px;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    transition:all 0.3s ease;
}
.add-btn:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,0.3);}
.add-btn:disabled{background:#ccc;cursor:not-allowed;transform:none;box-shadow:none;}

/* BACK */
.back{margin-top:25px;}
.back a{text-decoration:none;color:#667eea;font-weight:600;transition:all 0.3s ease;}
.back a:hover{color:#764ba2;}

/* FOOTER */
footer{background:linear-gradient(180deg, #0a3d62 0%, #062d48 100%);color:white;text-align:center;padding:20px;margin-top:30px;font-weight:500;}

@media(max-width:900px){.container{grid-template-columns:1fr;gap:30px;}.product-image img{height:350px;}}
</style>
</head>

<body>

<header>
    <div class="logo">🛍️ ElectroStore</div>
    <nav>
        <a href="customer_dashboard.php">📊 Dashboard</a>
        <a href="shop.php">🏪 Shop</a>
        <a href="cart.php">🛒 Cart</a>
        <a href="../logout.php">🚪 Logout</a>
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
