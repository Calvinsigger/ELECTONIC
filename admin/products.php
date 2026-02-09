<?php
session_start();
require_once __DIR__ . "/../api/db.php";
require_once __DIR__ . "/../api/security.php";

/* ===== ADMIN CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ===== FETCH CATEGORIES ===== */
$categories = $conn->query("
    SELECT id, category_name 
    FROM categories 
    ORDER BY category_name ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Products | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;}

.wrapper{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:260px;background:linear-gradient(180deg, #0a3d62 0%, #062d48 100%);color:white;padding:30px 20px;box-shadow:4px 0 15px rgba(0,0,0,0.2);position:sticky;top:0;height:100vh;overflow-y:auto;}
.sidebar h2{text-align:center;margin-bottom:40px;font-size:22px;font-weight:700;letter-spacing:0.5px;}
.sidebar a{display:block;color:white;text-decoration:none;padding:14px 16px;margin-bottom:8px;border-radius:8px;transition:all 0.3s ease;font-weight:500;border-left:4px solid transparent;}
.sidebar a:hover{background:rgba(255,255,255,0.2);border-left:4px solid #ffdd59;padding-left:20px;}

/* MAIN */
.main{flex:1;padding:40px;background:#f8f9fa;overflow-y:auto;}
.main h1{margin-bottom:35px;color:#0a3d62;font-size:32px;font-weight:700;}
.main h3{margin-top:35px;margin-bottom:20px;color:#0a3d62;font-size:20px;font-weight:600;}

/* FORM BOX */
.form-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);margin-bottom:30px;}
.form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:15px;margin-bottom:15px;}
.form-grid textarea{grid-column:1/-1;resize:none;height:100px;}
.form-grid input,.form-grid textarea,.form-grid select{padding:12px 15px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;transition:all 0.3s ease;font-family:inherit;}
.form-grid input:focus,.form-grid textarea:focus,.form-grid select:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}
.btn{padding:12px 24px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:all 0.3s ease;}
.btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4);}
.btn-delete{background:#e74c3c;padding:8px 14px;font-size:13px;}
.btn-delete:hover{background:#c0392b;}

/* TABLE BOX */
.table-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{padding:12px 15px;border-bottom:1px solid #f0f0f0;color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}
img{width:70px;height:70px;object-fit:cover;border-radius:8px;border:2px solid #e0e0e0;}

/* FLASH MESSAGE */
.flash-message{padding:15px;margin-bottom:20px;border-radius:8px;font-weight:500;border-left:4px solid;}
.flash-success{background:#d4edda;color:#155724;border-left-color:#28a745;}
.flash-error{background:#f8d7da;color:#721c24;border-left-color:#dc3545;}

@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{width:100%;height:auto;position:static;}.form-grid{grid-template-columns:1fr;}}
</style>
</head>

<body>

<div class="wrapper">

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar">
        <h2>📊 Admin Panel</h2>
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="products.php">📦 Products</a>
        <a href="categories.php">🏷️ Categories</a>
        <a href="users.php">👥 Users</a>
        <a href="orders.php">📋 Orders</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main">
        <h1>📦 Manage Products</h1>

        <?php displayFlashMessage(); ?>

        <!-- ===== ADD PRODUCT FORM ===== -->
        <h3>➕ Add New Product</h3>
        <div class="form-box">
            <form action="../api/products/create.php" method="POST" enctype="multipart/form-data" class="form-grid">
                <?= getCSRFTokenInput() ?>
                <input type="text" name="product_name" placeholder="Product Name" minlength="3" maxlength="150" required>
                <input type="number" step="0.01" name="price" placeholder="Price (e.g., 29.99)" min="0.01" max="999999.99" required>
                <select name="category_id" required>
                    <option value="">🏷️ Select Category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>">
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                <textarea name="description" placeholder="Product description (optional, max 1000 characters)" maxlength="1000"></textarea>
                <button type="submit" class="btn">✓ Add Product</button>
            </form>
        </div>

        <!-- ===== PRODUCT LIST ===== -->
        <h3>📋 All Products</h3>
        <div class="table-box">
            <table>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>

                <?php
                $stmt = $conn->query("
                    SELECT p.*, c.category_name
                    FROM products p
                    JOIN categories c ON p.category_id = c.id
                    ORDER BY p.id DESC
                ");

                while ($p = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <tr>
                    <td><img src="../uploads/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>"></td>
                    <td><?= htmlspecialchars($p['product_name']) ?></td>
                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                    <td>$<?= number_format($p['price'], 2) ?></td>
                    <td>
                        <form action="../api/products/delete.php" method="POST" style="display:inline;">
                            <?= getCSRFTokenInput() ?>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <button class="btn btn-delete" onclick="return confirm('Delete this product?')">
                                🗑️ Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>

    </div>
</div>

</body>
</html>
