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
<html>
<head>
<meta charset="UTF-8">
<title>Admin Products | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body{margin:0;font-family:Segoe UI;background:#f4f6f8}
.wrapper{display:flex;min-height:100vh}
.sidebar{width:240px;background:#0a3d62;color:#fff;padding:20px}
.sidebar a{display:block;color:#fff;text-decoration:none;padding:10px;margin-bottom:8px;border-radius:4px}
.sidebar a:hover{background:rgba(255,255,255,.2)}
.main{flex:1;padding:30px}
h1{color:#0a3d62}
.box{background:#fff;padding:20px;border-radius:6px;margin-bottom:25px;box-shadow:0 0 8px rgba(0,0,0,.1)}
input,select{width:100%;padding:10px;margin-bottom:10px}
button{padding:10px 15px;border:none;border-radius:4px;cursor:pointer}
.add{background:#1e90ff;color:#fff}
.edit{background:#28a745;color:#fff}
.del{background:#dc3545;color:#fff}
table{width:100%;border-collapse:collapse}
th,td{padding:10px;border-bottom:1px solid #ddd}
th{background:#0a3d62;color:#fff}
img{width:70px;height:70px;object-fit:cover;border-radius:4px}
.msg{text-align:center;color:green;margin-bottom:10px}
</style>
</head>

<body>
<div class="wrapper">

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="../logout.php">Logout</a>
</div>

<div class="main">
<h1>Manage Products</h1>

<?php if($message): ?><p class="msg"><?= $message ?></p><?php endif; ?>

<div class="box">
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="action" value="add">
<input name="name" placeholder="Product Name" required>
<input type="number" step="0.01" name="price" placeholder="Price" required>
<input type="number" name="stock" placeholder="Stock Quantity" min="0" required>
<select name="category" required>
<option value="">Select Category</option>
<?php foreach($categories as $c): ?>
<option value="<?= $c['id'] ?>"><?= $c['category_name'] ?></option>
<?php endforeach; ?>
</select>
<input type="file" name="image">
<button class="add">Add Product</button>
</form>
</div>

<div class="box">
<table>
<tr>
<th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Stock</th><th>Category</th><th>Action</th>
</tr>
<?php foreach($products as $p): ?>
<tr>
<td><?= $p['id'] ?></td>
<td><?= $p['image'] ? "<img src='../uploads/{$p['image']}'>" : "—" ?></td>
<td><?= htmlspecialchars($p['product_name']) ?></td>
<td>$<?= number_format($p['price'],2) ?></td>
<td><?= $p['stock'] ?></td>
<td><?= $p['category_name'] ?></td>
<td>
<a href="?delete=<?= $p['id'] ?>" onclick="return confirm('Delete?')">
<button class="del">Delete</button>
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
