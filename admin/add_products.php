<?php
session_start();
require_once "../api/db.php";

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ===== MESSAGE ===== */
$message = "";

/* ===== ADD PRODUCT ===== */
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $name     = trim($_POST['name']);
    $price    = (float) $_POST['price'];
    $stock    = (int) $_POST['stock'];
    $category = (int) $_POST['category'];

    $image = null;
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
    }

    $stmt = $conn->prepare("
        INSERT INTO products (product_name, price, stock, category_id, image)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$name, $price, $stock, $category, $image]);

    $message = "✅ Product added successfully!";
}

/* ===== UPDATE PRODUCT ===== */
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id       = (int) $_POST['id'];
    $name     = trim($_POST['name']);
    $price    = (float) $_POST['price'];
    $stock    = (int) $_POST['stock'];
    $category = (int) $_POST['category'];

    // Keep old image if none uploaded
    $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
    $stmt->execute([$id]);
    $old = $stmt->fetch(PDO::FETCH_ASSOC);
    $image = $old['image'];

    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image);
    }

    $stmt = $conn->prepare("
        UPDATE products 
        SET product_name=?, price=?, stock=?, category_id=?, image=?
        WHERE id=?
    ");
    $stmt->execute([$name, $price, $stock, $category, $image, $id]);

    $message = "✅ Product updated successfully!";
}

/* ===== DELETE PRODUCT ===== */
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $conn->prepare("SELECT image FROM products WHERE id=?");
    $stmt->execute([$id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($p && $p['image'] && file_exists("../uploads/" . $p['image'])) {
        unlink("../uploads/" . $p['image']);
    }

    $conn->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
    $message = "🗑 Product deleted!";
}

/* ===== FETCH DATA ===== */
$products = $conn->query("
    SELECT p.*, c.category_name 
    FROM products p
    JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$categories = $conn->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Products | Admin</title>
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
.form-grid input,.form-grid select{padding:12px 15px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;transition:all 0.3s ease;font-family:inherit;}
.form-grid input:focus,.form-grid select:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}
.btn{padding:12px 24px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:all 0.3s ease;}
.btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4);}
.btn-small{padding:8px 16px;font-size:13px;}
.btn-delete{background:#e74c3c;}
.btn-delete:hover{background:#c0392b;}

/* MESSAGE */
.message{padding:15px 20px;margin-bottom:20px;border-radius:8px;border-left:4px solid;font-weight:600;}
.message-success{background:#d4edda;color:#155724;border-left-color:#28a745;}

/* TABLE BOX */
.table-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{padding:12px 15px;border-bottom:1px solid #f0f0f0;color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}
img{width:70px;height:70px;object-fit:cover;border-radius:8px;border:2px solid #e0e0e0;}

@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{width:100%;height:auto;position:static;}.form-grid{grid-template-columns:1fr;}}
</style>
</head>

<body>
<div class="wrapper">

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar">
        <h2>📊 Admin Panel</h2>
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="manage_products.php">📦 Products</a>
        <a href="categories.php">🏷️ Categories</a>
        <a href="users.php">👥 Users</a>
        <a href="orders.php">📋 Orders</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main">
        <h1>📦 Product Management</h1>

        <?php if($message): ?>
            <div class="message message-success">✅ <?= $message ?></div>
        <?php endif; ?>

        <h3>➕ Add New Product</h3>
        <div class="form-box">
            <form method="POST" enctype="multipart/form-data" class="form-grid">
                <input type="hidden" name="action" value="add">
                <input type="text" name="name" placeholder="Product Name" required>
                <input type="number" step="0.01" name="price" placeholder="Price (e.g., 49.99)" required>
                <input type="number" name="stock" placeholder="Stock Quantity" min="0" required>
                <select name="category" required>
                    <option value="">🏷️ Select Category</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                <button type="submit" class="btn">✓ Add Product</button>
            </form>
        </div>

        <h3>📋 All Products</h3>
        <div class="table-box">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
                <?php foreach($products as $p): ?>
                <tr>
                    <td>#<?= $p['id'] ?></td>
                    <td><?= $p['image'] ? "<img src='../uploads/{$p['image']}' alt='{$p['product_name']}'>" : "—" ?></td>
                    <td><?= htmlspecialchars($p['product_name']) ?></td>
                    <td>TZS <?= number_format($p['price'],2) ?></td>
                    <td><?= $p['stock'] ?></td>
                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                    <td>
                        <a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete this product?');" style="text-decoration:none;">
                            <button class="btn btn-small btn-delete">🗑️ Delete</button>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </div>
</div>

</body>
</html>
