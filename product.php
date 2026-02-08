<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Our Products | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#f4f6f9;}

/* ===== HEADER ===== */
header{
    background:#0a3d62;
    color:white;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
header h1{font-size:26px;}
header a{
    color:white;
    text-decoration:none;
    margin-left:18px;
    font-weight:500;
}
header a:hover{color:#ffdd59;}

/* ===== HERO ===== */
.hero{
    background:linear-gradient(to right,#1e90ff,#0a3d62);
    color:white;
    padding:70px 20px;
    text-align:center;
}
.hero h2{font-size:42px;}
.hero p{font-size:18px;opacity:.95;}

/* ===== FILTER BAR ===== */
.filters{
    display:flex;
    justify-content:center;
    gap:15px;
    flex-wrap:wrap;
    margin:30px;
}
.filters button{
    padding:10px 18px;
    border:none;
    border-radius:20px;
    background:#ddd;
    cursor:pointer;
    font-weight:500;
}
.filters button.active{
    background:#0a3d62;
    color:white;
}

/* ===== PRODUCTS GRID ===== */
.products{
    padding:20px 30px 50px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:30px;
}

/* ===== CARD ===== */
.card{
    background:white;
    border-radius:16px;
    box-shadow:0 10px 25px rgba(0,0,0,.1);
    overflow:hidden;
    transition:.3s;
    cursor:pointer;
    position:relative;
}
.card:hover{transform:translateY(-8px);}

.sale{
    position:absolute;
    top:15px;
    left:15px;
    background:#e74c3c;
    color:white;
    padding:6px 14px;
    border-radius:20px;
    font-size:13px;
    font-weight:600;
}

.card img{
    width:100%;
    height:220px;
    object-fit:cover;
}

.card-body{
    padding:18px;
    text-align:center;
}
.card-body h3{
    font-size:18px;
    color:#0a3d62;
}
.category{
    font-size:14px;
    color:#777;
    margin-bottom:6px;
}

.price{
    font-size:20px;
    color:#1e90ff;
    font-weight:700;
}

.card-body button{
    margin-top:15px;
    width:100%;
    padding:12px;
    border:none;
    border-radius:10px;
    background:#0a3d62;
    color:white;
    font-weight:600;
    cursor:pointer;
}
.card-body button:hover{background:#07406b;}

footer{
    background:#0a3d62;
    color:white;
    text-align:center;
    padding:25px;
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
        list.innerHTML += `
        <div class="card" onclick="handleClick()">
            <span class="sale">OFFER</span>
            <img src="uploads/${p.image}">
            <div class="card-body">
                <h3>${p.product_name}</h3>
                <div class="category">${p.category_name}</div>
                <div class="price">$${p.price}</div>
                <button>Add to Cart</button>
            </div>
        </div>`;
    });
}

/* ===== LOGIN CHECK ===== */
function handleClick(){
    if(!loggedIn){
        window.location.href = "login.php";
    }else{
        window.location.href = "customer/cart.php";
    }
}
</script>

</body>
</html>
