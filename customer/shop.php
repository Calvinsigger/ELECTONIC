<?php
session_start();
require_once "../api/db.php";

/* SECURITY: CUSTOMER ONLY */
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer'){
    header("Location: ../login.php");
    exit;
}

/* FETCH CATEGORIES FOR FILTER */
$categories = $conn->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* GET PRICE RANGE FROM DATABASE */
$priceStats = $conn->query("SELECT MIN(price) as min_price, MAX(price) as max_price FROM products")->fetch(PDO::FETCH_ASSOC);
$minPrice = floor($priceStats['min_price'] ?? 0);
$maxPrice = ceil($priceStats['max_price'] ?? 1000);
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

/* FILTERS SECTION */
.filters-container{background:white;padding:20px;margin:20px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.1)}
.filter-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:15px}
.filter-group{display:flex;flex-direction:column}
.filter-group label{font-weight:600;margin-bottom:6px;color:#0a3d62;font-size:14px}
.filter-group input,.filter-group select{padding:10px;border-radius:6px;border:1px solid #ccc;font-size:14px}
.price-range-display{font-size:13px;color:#666;margin-top:4px}
.filter-buttons{display:flex;gap:10px;margin-top:15px;flex-wrap:wrap}
.btn-search,.btn-reset{padding:10px 20px;border:none;border-radius:6px;cursor:pointer;font-weight:600;transition:.3s}
.btn-search{background:#0a3d62;color:white}
.btn-search:hover{background:#07406b}
.btn-reset{background:#95a5a6;color:white}
.btn-reset:hover{background:#7f8c8d}

/* RESULTS INFO */
.results-info{padding:15px 20px;background:#f9f9f9;border-radius:6px;margin:0 20px;font-size:14px;color:#666;display:flex;justify-content:space-between;align-items:center}
.results-count{font-weight:600;color:#0a3d62}
.no-results{text-align:center;padding:50px 20px;color:#666}

/* PRODUCTS */
.products{padding:30px 20px;display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:20px}
.product-card{background:white;border-radius:10px;padding:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);transition:.3s;display:flex;flex-direction:column}
.product-card:hover{transform:translateY(-5px);box-shadow:0 8px 20px rgba(0,0,0,.15)}
.product-card img{width:100%;height:160px;object-fit:cover;border-radius:8px}
.product-card h3{margin:10px 0 4px;font-size:16px}
.category{font-size:13px;color:#888}
.price{font-size:18px;font-weight:bold;color:#0a3d62;margin:6px 0 8px}
.stock-status{font-size:12px;margin:4px 0}
.stock-status.in-stock{color:#27ae60}
.stock-status.low-stock{color:#f39c12}
.stock-status.out-of-stock{color:#e74c3c}
.view-btn{margin-top:auto;text-align:center}
.view-btn a{display:block;padding:10px;background:#0a3d62;color:white;text-decoration:none;border-radius:6px;font-weight:500;font-size:14px}
.view-btn a:hover{background:#07406b}

/* PAGINATION */
.pagination{display:flex;justify-content:center;gap:8px;margin:30px 0;flex-wrap:wrap}
.pagination a,.pagination span{padding:8px 12px;border:1px solid #ccc;border-radius:4px;cursor:pointer;text-decoration:none;color:#0a3d62;transition:.3s}
.pagination a:hover{background:#0a3d62;color:white}
.pagination .active{background:#0a3d62;color:white;border-color:#0a3d62}
.pagination .disabled{color:#ccc;cursor:not-allowed}

/* LOADING */
.spinner{display:inline-block;width:20px;height:20px;border:3px solid #f3f3f3;border-top:3px solid #0a3d62;border-radius:50%;animation:spin 1s linear infinite}
@keyframes spin{0%{transform:rotate(0deg)}100%{transform:rotate(360deg)}}

/* RESPONSIVE */
@media(max-width:768px){
    .filter-row{grid-template-columns:1fr}
    .products{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:15px;padding:15px}
    .filter-buttons{flex-direction:column}
    .btn-search,.btn-reset{width:100%}
}

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

<!-- ADVANCED FILTERS -->
<div class="filters-container">
    <h3 style="color:#0a3d62;margin-top:0">Search & Filter Products</h3>
    
    <div class="filter-row">
        <div class="filter-group">
            <label for="searchInput">🔍 Search by Name or Description</label>
            <input type="text" id="searchInput" placeholder="Enter product name..." maxlength="100">
        </div>
    </div>

    <div class="filter-row">
        <div class="filter-group">
            <label for="categoryFilter">📦 Category</label>
            <select id="categoryFilter">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filter-group">
            <label for="sortFilter">➡️ Sort By</label>
            <select id="sortFilter">
                <option value="name_asc">Name (A-Z)</option>
                <option value="name_desc">Name (Z-A)</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="newest">Newest Products</option>
                <option value="stock_desc">Stock Available</option>
            </select>
        </div>

        <div class="filter-group">
            <label for="inStockFilter">📊 Stock Status</label>
            <select id="inStockFilter">
                <option value="0">All Products</option>
                <option value="1">In Stock Only</option>
            </select>
        </div>
    </div>

    <div class="filter-row">
        <div class="filter-group">
            <label for="priceMin">💰 Price Range: $<span id="minDisplay"><?= $minPrice ?></span> - $<span id="maxDisplay"><?= $maxPrice ?></span></label>
            <input type="range" id="priceMin" min="<?= $minPrice ?>" max="<?= $maxPrice ?>" value="<?= $minPrice ?>" step="1" style="width:100%">
            <div class="price-range-display">Min: $<span id="priceMinVal"><?= $minPrice ?></span></div>
        </div>

        <div class="filter-group">
            <label>&nbsp;</label>
            <input type="range" id="priceMax" min="<?= $minPrice ?>" max="<?= $maxPrice ?>" value="<?= $maxPrice ?>" step="1" style="width:100%">
            <div class="price-range-display">Max: $<span id="priceMaxVal"><?= $maxPrice ?></span></div>
        </div>
    </div>

    <div class="filter-buttons">
        <button class="btn-search" onclick="applyFilters()">🔍 Search Products</button>
        <button class="btn-reset" onclick="resetFilters()">🔄 Reset All Filters</button>
    </div>

    <div id="loadingSpinner" style="display:none;text-align:center;margin-top:10px">
        <div class="spinner"></div> Searching...
    </div>
</div>

<!-- RESULTS INFO -->
<div id="resultsInfo" class="results-info" style="display:none">
    <span class="results-count">Found: <span id="resultCount">0</span> products</span>
    <span id="pageInfo"></span>
</div>

<!-- PRODUCTS GRID -->
<section id="productList" class="products"></section>

<!-- PAGINATION -->
<div id="paginationContainer" class="pagination"></div>

<footer>
    &copy; 2026 ElectroStore • Advanced Search & Filtering
</footer>

<script>
/* ===== PRICE RANGE SYNCHRONIZATION ===== */
document.getElementById('priceMin').addEventListener('input', function() {
    const minVal = parseFloat(this.value);
    const maxVal = parseFloat(document.getElementById('priceMax').value);
    
    if (minVal > maxVal) {
        document.getElementById('priceMax').value = minVal;
        document.getElementById('priceMaxVal').textContent = minVal.toFixed(2);
    }
    document.getElementById('priceMinVal').textContent = minVal.toFixed(2);
});

document.getElementById('priceMax').addEventListener('input', function() {
    const maxVal = parseFloat(this.value);
    const minVal = parseFloat(document.getElementById('priceMin').value);
    
    if (maxVal < minVal) {
        document.getElementById('priceMin').value = maxVal;
        document.getElementById('priceMinVal').textContent = maxVal.toFixed(2);
    }
    document.getElementById('priceMaxVal').textContent = maxVal.toFixed(2);
});

/* ===== SEARCH ON ENTER KEY ===== */
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

/* ===== APPLY FILTERS (AJAX) ===== */
function applyFilters(page = 1) {
    const search = document.getElementById('searchInput').value.trim();
    const category = document.getElementById('categoryFilter').value;
    const sort = document.getElementById('sortFilter').value;
    const inStock = document.getElementById('inStockFilter').value;
    const priceMin = parseFloat(document.getElementById('priceMin').value);
    const priceMax = parseFloat(document.getElementById('priceMax').value);

    // Show loading spinner
    document.getElementById('loadingSpinner').style.display = 'block';
    document.getElementById('resultsInfo').style.display = 'none';

    // Build query URL
    const params = new URLSearchParams({
        search: search,
        category: category || 0,
        sort: sort,
        inStock: inStock,
        priceMin: priceMin,
        priceMax: priceMax,
        page: page
    });

    // Fetch results via AJAX
    fetch(`../api/search.php?${params}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('loadingSpinner').style.display = 'none';

            if (data.success) {
                displayProducts(data.products);
                displayPagination(data.pagination, page);
                displayResultsInfo(data.pagination);
            } else {
                document.getElementById('productList').innerHTML = 
                    `<div class="no-results">❌ ${data.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('productList').innerHTML = 
                `<div class="no-results">❌ Error loading products: ${error.message}</div>`;
            console.error('Error:', error);
        });
}

/* ===== DISPLAY PRODUCTS ===== */
function displayProducts(products) {
    const productList = document.getElementById('productList');
    
    if (products.length === 0) {
        productList.innerHTML = '<div class="no-results">😕 No products found matching your filters.</div>';
        return;
    }

    productList.innerHTML = products.map(product => `
        <div class="product-card">
            <img src="../uploads/${product.image}" alt="${product.product_name}" onerror="this.src='../uploads/default.png'">
            
            <h3>${product.product_name}</h3>
            <div class="category">${product.category_name}</div>
            <div class="price">$${parseFloat(product.price).toFixed(2)}</div>
            
            <div class="stock-status ${product.stock > 10 ? 'in-stock' : product.stock > 0 ? 'low-stock' : 'out-of-stock'}">
                ${product.stock > 10 ? '✓ In Stock' : product.stock > 0 ? `⚠ Low Stock (${product.stock})` : '✗ Out of Stock'}
            </div>
            
            <div class="view-btn">
                <a href="product.php?id=${product.id}">View Product</a>
            </div>
        </div>
    `).join('');
}

/* ===== DISPLAY PAGINATION ===== */
function displayPagination(pagination, currentPage) {
    const container = document.getElementById('paginationContainer');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '';

    // Previous button
    if (pagination.has_prev) {
        html += `<a onclick="applyFilters(${currentPage - 1})">← Previous</a>`;
    } else {
        html += `<span class="disabled">← Previous</span>`;
    }

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(pagination.total_pages, currentPage + 2);

    if (startPage > 1) {
        html += `<a onclick="applyFilters(1)">1</a>`;
        if (startPage > 2) {
            html += `<span>...</span>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            html += `<span class="active">${i}</span>`;
        } else {
            html += `<a onclick="applyFilters(${i})">${i}</a>`;
        }
    }

    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            html += `<span>...</span>`;
        }
        html += `<a onclick="applyFilters(${pagination.total_pages})">${pagination.total_pages}</a>`;
    }

    // Next button
    if (pagination.has_next) {
        html += `<a onclick="applyFilters(${currentPage + 1})">Next →</a>`;
    } else {
        html += `<span class="disabled">Next →</span>`;
    }

    container.innerHTML = html;
}

/* ===== DISPLAY RESULTS INFO ===== */
function displayResultsInfo(pagination) {
    const resultsInfo = document.getElementById('resultsInfo');
    const resultCount = document.getElementById('resultCount');
    const pageInfo = document.getElementById('pageInfo');

    resultCount.textContent = pagination.total_count;
    pageInfo.textContent = `Page ${pagination.current_page} of ${pagination.total_pages}`;

    resultsInfo.style.display = 'flex';
}

/* ===== RESET FILTERS ===== */
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('sortFilter').value = 'name_asc';
    document.getElementById('inStockFilter').value = '0';
    
    const minPrice = <?= $minPrice ?>;
    const maxPrice = <?= $maxPrice ?>;
    
    document.getElementById('priceMin').value = minPrice;
    document.getElementById('priceMax').value = maxPrice;
    document.getElementById('priceMinVal').textContent = minPrice.toFixed(2);
    document.getElementById('priceMaxVal').textContent = maxPrice.toFixed(2);

    applyFilters(1);
}

/* ===== LOAD INITIAL PRODUCTS ===== */
window.addEventListener('load', function() {
    applyFilters(1);
});
</script>

</body>
</html>
