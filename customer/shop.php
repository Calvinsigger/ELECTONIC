<?php
session_start();
require_once "../api/db.php";

/* SECURITY: CUSTOMER ONLY */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer'){
    header("Location: ../login.php");
    exit;
}

/* FETCH PRODUCTS */
$products = $conn->query("
    SELECT p.*, c.category_name 
    FROM products p
    JOIN categories c ON p.category_id = c.id
")->fetchAll(PDO::FETCH_ASSOC);

/* FETCH CATEGORIES */
$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Shop | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
*{box-sizing:border-box}
body{margin:0;font-family:'Segoe UI',Arial,sans-serif;background:#f4f6f8;}
header{background:#0a3d62;color:white;padding:15px 30px;display:flex;justify-content:space-between;align-items:center}
.logo{font-size:22px;font-weight:bold}
nav a{color:white;text-decoration:none;margin-left:18px;font-weight:500}
nav a:hover{color:#ffdd59}
.hero{background:linear-gradient(to right,#1e90ff,#0a3d62);color:white;padding:45px 20px;text-align:center}
.hero h1{font-size:36px;margin-bottom:8px}
.hero p{opacity:.9}
.search-section{padding:20px;display:flex;justify-content:center;gap:15px;flex-wrap:wrap}
.search-section input,.search-section select{padding:12px;width:230px;border-radius:6px;border:1px solid #ccc;font-size:15px}
.products{padding:30px;display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:25px}
.product-card{background:white;border-radius:12px;padding:15px;box-shadow:0 8px 18px rgba(0,0,0,.12);transition:.3s;display:flex;flex-direction:column}
.product-card:hover{transform:translateY(-6px)}
.product-card img{width:100%;height:190px;object-fit:cover;border-radius:10px}
.product-card h3{margin:12px 0 4px}
.category{font-size:14px;color:#777}
.price{font-size:20px;font-weight:bold;color:#0a3d62;margin:8px 0 15px}
.view-btn{margin-top:auto;text-align:center}
.view-btn a{display:block;padding:11px;background:#0a3d62;color:white;text-decoration:none;border-radius:6px;font-weight:500}
.view-btn a:hover{background:#07406b}
footer{background:#0a3d62;color:white;text-align:center;padding:15px;margin-top:25px}
</style>
</head>
<body>

<header>
    <div class="logo">ElectroStore</div>
    <nav>
        <a href="customer_dashboard.php">Dashboard</a>
        <a href="shop.php">Shop</a>
        <a href="cart.php">Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<section class="hero">
    <h1>Explore Our Products</h1>
    <p>High-quality electronics at unbeatable prices</p>
</section>

<section class="search-section">
    <input type="text" id="searchInput" placeholder="Search product name...">
    <select id="categoryFilter">
        <option value="">All Categories</option>
        <?php foreach($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
        <?php endforeach; ?>
    </select>
</section>

<section class="products" id="productList">
<?php foreach($products as $p): ?>
    <div class="product-card"
         data-name="<?= strtolower($p['product_name']) ?>"
         data-category="<?= $p['category_id'] ?>">

        <img src="../uploads/<?= htmlspecialchars($p['image']) ?>" alt="Product">

        <h3><?= htmlspecialchars($p['product_name']) ?></h3>
        <div class="category"><?= htmlspecialchars($p['category_name']) ?></div>
        <div class="price">$<?= number_format($p['price'],2) ?></div>

        <div class="view-btn">
            <a href="product.php?id=<?= $p['id'] ?>">View Product</a>
        </div>
    </div>
<?php endforeach; ?>
</section>

<footer>
    &copy; 2026 ElectroStore • Secure Customer Shopping
</footer>

<script>
/* SEARCH + FILTER */
document.getElementById("searchInput").addEventListener("keyup", filterProducts);
document.getElementById("categoryFilter").addEventListener("change", filterProducts);

function filterProducts(){
    let search = document.getElementById("searchInput").value.toLowerCase();
    let category = document.getElementById("categoryFilter").value;

    document.querySelectorAll(".product-card").forEach(card=>{
        let matchName = card.dataset.name.includes(search);
        let matchCat = category === "" || card.dataset.category === category;
        card.style.display = (matchName && matchCat) ? "flex" : "none";
    });
}
</script>

</body>
</html>
