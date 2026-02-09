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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#f8f9fa;color:#333;}

/* ===== NAVBAR ===== */
header{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    padding:18px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:sticky;
    top:0;
    z-index:1000;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
}
.logo{font-size:28px;font-weight:700;letter-spacing:0.5px;}
nav{display:flex;align-items:center;gap:25px;}
nav a{color:white;text-decoration:none;font-weight:500;transition:all 0.3s ease;}
nav a:hover{color:#ffdd59;transform:translateY(-2px);}
.user-greet{font-size:14px;font-weight:600;padding:8px 16px;background:rgba(255,255,255,0.2);border-radius:20px;}

/* ===== HERO ===== */
.hero{
    background: url('uploads/kks.jfif') center/cover no-repeat fixed;
    padding:100px 20px;
    text-align:center;
    color:white;
}
.hero-content{max-width:800px;margin:0 auto;}
.hero h1{font-size:48px;font-weight:700;margin-bottom:20px;letter-spacing:-1px;text-shadow:2px 2px 8px rgba(0,0,0,0.5);}
.hero p{font-size:18px;margin-bottom:40px;opacity:0.95;line-height:1.6;text-shadow:1px 1px 4px rgba(0,0,0,0.5);}
.cta-btn{
    display:inline-block;
    background:white;
    color:#667eea;
    padding:14px 40px;
    border-radius:50px;
    font-weight:700;
    text-decoration:none;
    transition:all 0.3s ease;
    box-shadow:0 8px 20px rgba(0,0,0,0.2);
}
.cta-btn:hover{transform:translateY(-3px);box-shadow:0 12px 30px rgba(0,0,0,0.3);color:#764ba2;}

/* ===== ABOUT US ===== */
.about{
    padding:80px 20px;
    max-width:1100px;
    margin:0 auto;
    text-align:center;
}
.about h2{font-size:40px;color:#0a3d62;margin-bottom:25px;font-weight:700;}
.about-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:30px;margin-top:40px;}
.about-card{
    background:white;
    padding:30px;
    border-radius:12px;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    transition:all 0.3s ease;
    border-left:5px solid #667eea;
}
.about-card:hover{transform:translateY(-5px);box-shadow:0 12px 35px rgba(0,0,0,0.12);}
.about-card h3{font-size:20px;color:#667eea;margin-bottom:15px;font-weight:700;}
.about-card p{color:#666;line-height:1.8;font-size:15px;}

/* ===== CONTACT SECTION ===== */
.contact{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding:80px 20px;
    color:white;
}
.contact .container{max-width:1100px;margin:0 auto;}
.contact h2{font-size:40px;text-align:center;margin-bottom:50px;font-weight:700;}
.contact-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:35px;}
.contact-card{
    background:rgba(255,255,255,0.15);
    padding:30px;
    border-radius:12px;
    border:2px solid rgba(255,255,255,0.3);
    backdrop-filter:blur(10px);
    transition:all 0.3s ease;
}
.contact-card:hover{background:rgba(255,255,255,0.25);border-color:rgba(255,255,255,0.6);}
.contact-card h3{font-size:22px;margin-bottom:15px;font-weight:700;}
.contact-card p{margin-bottom:10px;opacity:0.95;line-height:1.6;}
.contact-card strong{font-weight:600;}

/* ===== PRODUCTS SECTION ===== */
.products-section{padding:80px 20px;background:#f8f9fa;}
.products-section .container{max-width:1100px;margin:0 auto;}
.section-header{text-align:center;margin-bottom:50px;}
.section-header h2{font-size:40px;color:#0a3d62;font-weight:700;margin-bottom:15px;}
.section-header p{font-size:16px;color:#666;max-width:600px;margin:0 auto;}

.products{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
    gap:25px;
}
.product-card{
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    transition:all 0.3s ease;
    border-left:5px solid #667eea;
    display:flex;
    flex-direction:column;
}
.product-card:hover{transform:translateY(-8px);box-shadow:0 12px 35px rgba(102,126,234,0.25);}
.product-card img{width:100%;height:180px;object-fit:cover;}
.product-card-content{padding:15px;}
.product-card h3{font-size:16px;color:#0a3d62;font-weight:600;margin-bottom:8px;height:45px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.product-card p{font-weight:700;color:#667eea;font-size:18px;margin-bottom:12px;}
.product-card button{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    border:none;
    padding:10px 16px;
    border-radius:8px;
    cursor:pointer;
    font-weight:600;
    transition:all 0.3s ease;
    width:100%;
}
.product-card button:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4);}

/* ===== FEATURES SECTION ===== */
.features{
    background:white;
    padding:60px 20px;
    margin:40px 0;
}
.features .container{max-width:1100px;margin:0 auto;}
.features h2{text-align:center;font-size:36px;color:#0a3d62;margin-bottom:40px;font-weight:700;}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:30px;}
.feature{text-align:center;}
.feature-icon{font-size:48px;margin-bottom:15px;}
.feature h3{font-size:20px;color:#667eea;margin-bottom:12px;font-weight:700;}
.feature p{color:#666;line-height:1.6;}

/* ===== FOOTER ===== */
footer{
    background:linear-gradient(180deg, #0a3d62 0%, #062d48 100%);
    color:white;
    padding:30px 20px;
    text-align:center;
    margin-top:50px;
}
footer p{font-weight:500;}

@media(max-width:768px){
    .hero h1{font-size:32px;}
    .hero p{font-size:16px;}
    header{flex-direction:column;gap:15px;}
    nav{flex-wrap:wrap;justify-content:center;gap:15px;}
    .about h2, .section-header h2, .section-header h2{font-size:28px;}
    .products{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;}
}
</style>
</head>

<body>

<header>
    <div class="logo">🛍️ ElectroStore</div>
    <nav>
        <a href="index.php">🏠 Home</a>
        <a href="product.php">📦 Products</a>

        <?php if($userLoggedIn): ?>
            <span class="user-greet">✓ Welcome, <?= htmlspecialchars($fullname) ?></span>
            <?php if($role==='admin'): ?>
                <a href="admin/admin_dashboard.php">⚙️ Admin</a>
            <?php elseif($role==='customer'): ?>
                <a href="customer/customer_dashboard.php">📊 Dashboard</a>
            <?php endif; ?>
            <a href="logout.php">🚪 Logout</a>
        <?php else: ?>
            <a href="login.php">🔐 Login</a>
            <a href="register.php">📝 Register</a>
        <?php endif; ?>
    </nav>
</header>

<!-- ===== HERO ===== -->
<section class="hero">
    <div class="hero-content">
        <h1>⚡ Power Your Life With Smart Electronics</h1>
        <p>Discover the latest phones, laptops, accessories, and gadgets at unbeatable prices</p>
        <a href="product.php" class="cta-btn">🛒 Shop Now</a>
    </div>
</section>

<!-- ===== ABOUT US ===== -->
<section class="about">
    <h2>✨ Why Choose ElectroStore?</h2>
    <div class="about-cards">
        <div class="about-card">
            <h3>🎯 Quality Products</h3>
            <p>We offer only the latest and most reliable electronics from trusted brands worldwide.</p>
        </div>
        <div class="about-card">
            <h3>💰 Best Prices</h3>
            <p>Competitive pricing with regular deals and discounts to help you save more on every purchase.</p>
        </div>
        <div class="about-card">
            <h3>🚚 Fast Delivery</h3>
            <p>Quick and secure shipping to get your products delivered to your doorstep on time.</p>
        </div>
    </div>
</section>

<!-- ===== FEATURES ===== -->
<section class="features">
    <div class="container">
        <h2>🌟 Our Commitment</h2>
        <div class="features-grid">
            <div class="feature">
                <div class="feature-icon">✓</div>
                <h3>100% Authentic</h3>
                <p>All products are 100% genuine and come with manufacturer warranty.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">🔒</div>
                <h3>Secure Shopping</h3>
                <p>Your payment and personal information are safe and protected.</p>
            </div>
            <div class="feature">
                <div class="feature-icon">💬</div>
                <h3>Expert Support</h3>
                <p>24/7 customer support to help you with any questions or concerns.</p>
            </div>
        </div>
    </div>
</section>

<!-- ===== PRODUCTS ===== -->
<section class="products-section">
    <div class="container">
        <div class="section-header">
            <h2>🔍 Featured Products</h2>
            <p>Browse our wide selection of high-quality electronics and gadgets</p>
        </div>
        <div class="products" id="productsContainer">
            <!-- Products loaded dynamically -->
        </div>
    </div>
</section>

<!-- ===== CONTACT INFORMATION ===== -->
<section class="contact">
    <div class="container">
        <h2>📞 Get In Touch</h2>
        <div class="contact-grid">
            <div class="contact-card">
                <h3>📱 Phone</h3>
                <p><strong>Support:</strong> +255787718748</p>
                <p><strong>Hours:</strong> Mon-Fri 9:00am - 6:00pm</p>
            </div>
            <div class="contact-card">
                <h3>✉️ Email</h3>
                <p><strong>Email:</strong> calvinsigger2@gmail.com</p>
                <p>We'll respond within 24 hours</p>
            </div>
            <div class="contact-card">
                <h3>📍 Location</h3>
                <p><strong>Address:</strong> Dar es Salaam, Tanzania</p>
                <p><strong>Country:</strong> TZ</p>
            </div>
        </div>
    </div>
</section>

<footer>
    <p>&copy; 2026 ElectroStore. All rights reserved. 🛍️</p>
</footer>

<script>
document.addEventListener("DOMContentLoaded", ()=>{
    fetch("api/products/read.php")
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById("productsContainer");
        container.innerHTML = "";
        data.forEach(p=>{
            const clickHandler = <?= $userLoggedIn ? "''\"'" : "'window.location.href=\\'login.php\\';'" ?>;
            container.innerHTML+=`
            <div class="product-card">
                <img src="uploads/${p.image}" alt="${p.product_name}">
                <div class="product-card-content">
                    <h3>${p.product_name}</h3>
                    <p>$${p.price}</p>
                    <button onclick="${<?= $userLoggedIn ? 'true' : 'false' ?> ? `alert('Add to cart: ${p.product_name}')` : `window.location.href='login.php'`}">🛒 Add to Cart</button>
                </div>
            </div>`;
        });
    })
    .catch(err => {
        document.getElementById("productsContainer").innerHTML = '<p style="grid-column:1/-1;text-align:center;color:#999;">Failed to load products</p>';
    });
});
</script>

</body>
</html>
