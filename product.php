<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);

// Redirect admins to their dashboard
if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin/admin_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Our Products | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    background: #f8f9ff;
    color: #333;
}

/* ===== HEADER ===== */
header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 18px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    flex-wrap: wrap;
    gap: 20px;
}

header h1 {
    font-size: 28px;
    font-weight: 700;
}

header nav {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

header a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    padding: 6px 12px;
    border-radius: 6px;
}

header a:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

/* ===== HERO SECTION ===== */
.hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 20px;
    text-align: center;
    margin-bottom: 40px;
}

.hero h2 {
    font-size: 42px;
    margin-bottom: 15px;
    font-weight: 700;
}

.hero p {
    font-size: 18px;
    opacity: 0.95;
    font-weight: 500;
}

/* ===== FILTER BAR ===== */
.filters {
    display: flex;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
    margin: 0 30px 40px;
    padding: 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.filters button {
    padding: 10px 22px;
    border: 2px solid #e0e0e0;
    border-radius: 20px;
    background: white;
    color: #666;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
}

.filters button:hover {
    border-color: #667eea;
    color: #667eea;
}

.filters button.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

/* ===== PRODUCTS GRID ===== */
.products {
    padding: 20px 30px 50px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 30px;
}

/* ===== CARD ===== */
.card {
    background: white;
    border-radius: 14px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    border-left: 5px solid #667eea;
}

.card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 35px rgba(0, 0, 0, 0.12);
}

.sale {
    position: absolute;
    top: 15px;
    left: 15px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    z-index: 10;
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.card img {
    width: 100%;
    height: 240px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.card:hover img {
    transform: scale(1.05);
}

.card-body {
    padding: 22px;
    text-align: center;
}

.card-body h3 {
    font-size: 18px;
    color: #333;
    margin-bottom: 8px;
    font-weight: 700;
}

.category {
    font-size: 13px;
    color: #999;
    margin-bottom: 12px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.price {
    font-size: 22px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    font-weight: 700;
    margin-bottom: 15px;
}

.card-body button {
    margin-top: 8px;
    width: 100%;
    padding: 12px 16px;
    border: none;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    font-family: 'Poppins', sans-serif;
    cursor: pointer;
    transition: all 0.3s ease;
}

.card-body button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.card-body button:active {
    transform: translateY(0);
}

/* ===== FOOTER ===== */
footer {
    background: linear-gradient(180deg, #0a3d62 0%, #062d48 100%);
    color: white;
    text-align: center;
    padding: 30px 20px;
    margin-top: 50px;
    font-weight: 500;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    header {
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    header nav {
        justify-content: center;
    }

    .hero h2 {
        font-size: 32px;
    }

    .hero p {
        font-size: 16px;
    }

    .products {
        padding: 15px 15px 40px;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .filters {
        margin: 0 15px 30px;
    }
}

@media (max-width: 480px) {
    header h1 {
        font-size: 22px;
    }

    header nav {
        gap: 10px;
    }

    header a {
        padding: 5px 8px;
        font-size: 14px;
    }

    .hero h2 {
        font-size: 26px;
    }

    .products {
        grid-template-columns: 1fr;
    }

    .filters {
        flex-direction: column;
    }

    .filters button {
        width: 100%;
    }
}

/* ===== AUTH MODAL ===== */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    padding: 40px 30px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 450px;
    width: 90%;
    text-align: center;
    animation: slideUp 0.3s ease;
    position: relative;
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-content h2 {
    font-size: 28px;
    color: #0a3d62;
    margin-bottom: 15px;
    font-weight: 700;
}

.modal-content p {
    color: #666;
    margin-bottom: 30px;
    font-size: 16px;
    line-height: 1.6;
}

.modal-buttons {
    display: flex;
    gap: 15px;
}

.modal-btn {
    flex: 1;
    padding: 14px 20px;
    border: none;
    border-radius: 10px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;
}

.modal-btn-login {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.modal-btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.modal-btn-register {
    background: #f0f0f0;
    color: #0a3d62;
    border: 2px solid #667eea;
}

.modal-btn-register:hover {
    background: #667eea;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    position: absolute;
    top: 15px;
    right: 15px;
    transition: all 0.3s ease;
}

.modal-close:hover {
    color: #0a3d62;
    transform: rotate(90deg);
}

</style>
</head>

<body>

<header>
    <h1>ElectroStore</h1>
    <nav>
        <a href="index.php">Home</a>
        <a href="product.php">Products</a>
        <?php if($isLoggedIn): ?>
            <a href="customer/customer_dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<section class="hero">
    <h2>Explore Our Products</h2>
    <p>Filter by category and shop smart</p>
</section>

<!-- CATEGORY FILTER -->
<div class="filters" id="categoryFilters">
    <button class="active" onclick="filterCategory('all')">All</button>
</div>

<section class="products" id="productList"></section>

<footer>
    &copy; 2026 ElectroStore | Quality Electronics
</footer>

<!-- ===== AUTH MODAL ===== -->
<div id="authModal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closeAuthModal()">&times;</button>
        <h2>🔐 Access Required</h2>
        <p>To add items to your cart, please log in or create an account</p>
        <div class="modal-buttons">
            <a href="login.php?add_to_cart=" id="loginLink" class="modal-btn modal-btn-login">🔑 Log In</a>
            <a href="register.php" class="modal-btn modal-btn-register">📝 Register</a>
        </div>
    </div>
</div>

<script>
const loggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
let allProducts = [];

/* ===== FETCH PRODUCTS WITH CATEGORIES ===== */
fetch("api/products/read.php")
.then(res => res.json())
.then(data => {
    allProducts = data;
    renderCategories(data);
    renderProducts(data);
});

/* ===== RENDER CATEGORY BUTTONS ===== */
function renderCategories(products){
    const filters = document.getElementById("categoryFilters");
    const categories = [...new Set(products.map(p => p.category_name))];

    categories.forEach(cat => {
        const btn = document.createElement("button");
        btn.innerText = cat;
        btn.onclick = () => filterCategory(cat);
        filters.appendChild(btn);
    });
}

/* ===== FILTER PRODUCTS ===== */
function filterCategory(category){
    document.querySelectorAll(".filters button").forEach(b => b.classList.remove("active"));
    event.target.classList.add("active");

    if(category === "all"){
        renderProducts(allProducts);
    }else{
        renderProducts(allProducts.filter(p => p.category_name === category));
    }
}

/* ===== RENDER PRODUCTS ===== */
function renderProducts(products){
    const list = document.getElementById("productList");
    list.innerHTML = "";
    products.forEach(p => {
        const imageUrl = p.image ? ('uploads/' + encodeURIComponent(p.image)) : 'uploads/new.png';
        list.innerHTML += `
        <div class="card">
            <span class="sale">OFFER</span>
            <img src="${imageUrl}" alt="${p.product_name}" onerror="this.onerror=null;this.src='uploads/new.png'">
            <div class="card-body">
                <h3>${p.product_name}</h3>
                <div class="category">${p.category_name}</div>
                <div class="price">TZS ${p.price}</div>
                <button onclick="handleClick(${p.id})">Add to Cart</button>
            </div>
        </div>`;
    });
}

/* ===== LOGIN CHECK - PASS PRODUCT ID IF ADDING TO CART ===== */
function handleClick(productId){
    if(!loggedIn){
        // Show auth modal with login and register options
        const loginLink = document.getElementById('loginLink');
        loginLink.href = "login.php?add_to_cart=" + productId;
        openAuthModal();
    }else{
        window.location.href = "customer/cart.php";
    }
}

/* ===== AUTH MODAL FUNCTIONS ===== */
function openAuthModal(){
    document.getElementById('authModal').classList.add('active');
}

function closeAuthModal(){
    document.getElementById('authModal').classList.remove('active');
}

// Close modal when clicking outside of it
window.onclick = function(event){
    const modal = document.getElementById('authModal');
    if(event.target == modal){
        closeAuthModal();
    }
}
</script>

</body>
</html>
