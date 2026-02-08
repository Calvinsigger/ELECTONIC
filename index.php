<?php
session_start();

/* ===== SESSION CHECK ===== */
$userLoggedIn = isset($_SESSION['user_id']);
$fullname = $userLoggedIn ? ($_SESSION['fullname'] ?? 'User') : 'Guest';
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ElectroStore | Home</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Roboto',sans-serif;}
body{background:#f4f6f8;}

/* ===== NAVBAR ===== */
header{
    background:#0a3d62;
    color:white;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:sticky;
    top:0;
    z-index:1000;
    box-shadow:0 4px 6px rgba(0,0,0,.1);
}
.logo{font-size:28px;font-weight:700;}
nav a{
    color:white;
    text-decoration:none;
    margin-left:20px;
    font-weight:500;
    transition:.3s;
}
nav a:hover{color:#ffdd59;}
.user-greet{margin-left:20px;font-size:14px;}

/* ===== HERO / ADVERTISEMENT ===== */
.hero{
    background:url('uploads/new.png') center/cover no-repeat;
    height:70vh;
    position:relative;
    display:flex;
    align-items:center;
    justify-content:center;
    text-align:center;
    color:white;
}
.hero::before{
    content:'';
    position:absolute;
    inset:0;
    background:rgba(0,0,0,0.55);
}
.hero-content{
    position:relative;
    z-index:2;
    max-width:700px;
    padding:20px;
}
.hero h1{
    font-size:44px;
    margin-bottom:15px;
    text-shadow:2px 2px 8px rgba(0,0,0,.6);
}
.hero p{
    font-size:20px;
    margin-bottom:30px;
}
.hero .cta-btn{
    background:#ffdd59;
    color:#0a3d62;
    padding:14px 30px;
    border-radius:50px;
    font-weight:600;
    text-decoration:none;
    transition:.3s;
}
.hero .cta-btn:hover{
    background:#ffc107;
    transform:translateY(-3px);
}

/* ===== ABOUT US ===== */
.about{
    padding:60px 20px;
    background:#ffffff;
    text-align:center;
}
.about h2{
    color:#0a3d62;
    font-size:36px;
    margin-bottom:20px;
}
.about p{
    font-size:18px;
    color:#555;
    max-width:800px;
    margin:0 auto;
    line-height:1.6;
}

/* ===== CONTACT & LOCATION ===== */
.contact{
    background:#f1f5f9;
    padding:60px 20px;
    display:flex;
    flex-wrap:wrap;
    justify-content:space-around;
    text-align:left;
}
.contact-info{
    max-width:400px;
    margin:15px;
}
.contact-info h3{
    color:#0a3d62;
    font-size:24px;
    margin-bottom:12px;
}
.contact-info p{
    font-size:16px;
    color:#555;
    margin-bottom:8px;
    line-height:1.5;
}
.map{
    max-width:500px;
    margin:15px;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
}

/* ===== PRODUCTS ===== */
.products-section{
    padding:50px 20px;
    text-align:center;
}
.products-section h2{
    color:#0a3d62;
    font-size:36px;
    margin-bottom:30px;
}
.products{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:25px;
}
.product-card{
    background:white;
    padding:15px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 4px 12px rgba(0,0,0,.1);
    transition:.3s;
}
.product-card:hover{
    transform:translateY(-5px);
}
.product-card img{
    width:100%;
    height:180px;
    object-fit:cover;
    border-radius:8px;
    margin-bottom:10px;
}
.product-card h3{color:#0a3d62;margin-bottom:5px;}
.product-card p{
    font-weight:bold;
    color:#1e90ff;
    margin-bottom:10px;
}
.product-card button{
    background:#0a3d62;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    transition:.3s;
}
.product-card button:hover{background:#07406b;}

/* ===== FOOTER ===== */
footer{
    background:#0a3d62;
    color:white;
    text-align:center;
    padding:25px;
    margin-top:50px;
}

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    .hero h1{font-size:32px;}
    .hero p{font-size:16px;}
    header{flex-direction:column; gap:10px;}
    .contact{flex-direction:column;align-items:center;}
    .map{width:100%;}
}
</style>
</head>

<body>

<header>
    <div class="logo">Siggerit ElectroStore</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="product.php">Products</a>

        <?php if($userLoggedIn): ?>
            <span class="user-greet">Welcome, <?= htmlspecialchars($fullname) ?></span>
            <?php if($role==='admin'): ?>
                <a href="admin/dashboard.php">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>

<!-- ===== HERO ===== -->
<section class="hero">
    <div class="hero-content">
        <h1>Power Your Life With Smart Electronics</h1>
        <p>Phones • Laptops • Accessories • Home Electronics</p>
        <a href="product.php" class="cta-btn">Shop Now</a>
    </div>
</section>

<!-- ===== ABOUT US ===== -->
<section class="about">
    <h2>About Us</h2>
    <p>ElectroStore is your trusted destination for the latest electronics. 
       We provide high-quality phones, laptops, accessories, and home gadgets 
       at competitive prices. Our mission is to empower our customers with 
       smart technology solutions that simplify and enhance everyday life.</p>
</section>

<!-- ===== CONTACT INFORMATION & LOCATION ===== -->
<section class="contact">
    <div class="contact-info">
        <h3>Contact Us</h3>
        <p><strong>Phone:</strong> +255 123 456 789</p>
        <p><strong>Email:</strong> support@electrostore.com</p>
        <p><strong>Address:</strong> 123 Tech Avenue, Dar es Salaam, Tanzania</p>
        <p><strong>Working Hours:</strong> Mon - Fri: 9:00am - 6:00pm</p>
    </div>
   
</section><

<!-- ===== PRODUCTS ===== -->
<section class="products-section">
    <h2>WELCOME</h2>
    <div class="products" id="productsContainer">
        <!-- Products loaded dynamically -->
    </div>
</section>

<footer>
    <p>&copy; 2026 ElectroStore. All rights reserved.</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", ()=>{
    const loggedIn = <?= $userLoggedIn ? 'true':'false' ?>;
    fetch("api/products/read.php")
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById("productsContainer");
        container.innerHTML = "";
        data.forEach(p=>{
            container.innerHTML+=`
            <div class="product-card" onclick="${!$userLoggedIn?'window.location.href=\'login.php\'':''}">
                <img src="uploads/${p.image}" alt="${p.product_name}">
                <h3>${p.product_name}</h3>
                <p>$${p.price}</p>
                <button>Add to Cart</button>
            </div>`;
        });
    });
});
</script>

</body>
</html>
