<?php
session_start();
require_once __DIR__ . "/../api/db.php";

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
<title>Admin | Manage Products</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:#f4f6f9;}

header{
    background:#0a3d62;
    color:white;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
header h2{font-size:24px;}
header a{color:white;text-decoration:none;font-weight:500;}

.container{
    max-width:1150px;
    margin:30px auto;
    background:white;
    padding:25px;
    border-radius:12px;
    box-shadow:0 8px 25px rgba(0,0,0,.1);
}

h3{margin-bottom:15px;color:#0a3d62;}

form{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:15px;
    margin-bottom:30px;
}

form input, form textarea, form select, form button{
    padding:12px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:14px;
}

form textarea{
    grid-column:1/-1;
    resize:none;
    height:90px;
}

form button{
    background:#0a3d62;
    color:white;
    border:none;
    cursor:pointer;
    font-weight:600;
}
form button:hover{background:#07406b;}

table{
    width:100%;
    border-collapse:collapse;
}

table th, table td{
    padding:12px;
    border-bottom:1px solid #ddd;
    text-align:center;
}

table th{
    background:#0a3d62;
    color:white;
}

img{
    width:65px;
    height:65px;
    object-fit:cover;
    border-radius:8px;
}

.delete-btn{
    background:#e74c3c;
    color:white;
    border:none;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
}
.delete-btn:hover{background:#c0392b;}
</style>
</head>

<body>

<header>
    <h2>Admin – Product Management</h2>
    <a href="admin_dashboard.php">← Back to Dashboard</a>
</header>

<div class="container">

<h3>Add New Product</h3>

<form action="../api/products/create.php" method="POST" enctype="multipart/form-data">

    <input type="text" name="product_name" placeholder="Product Name" required>

    <input type="number" step="0.01" name="price" placeholder="Price" required>

    <!-- CATEGORY SELECT -->
    <select name="category_id" required>
        <option value="">Select Category</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>">
                <?= htmlspecialchars($cat['category_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="file" name="image" accept="image/*" required>

    <textarea name="description" placeholder="Product description (optional)"></textarea>

    <button type="submit">Add Product</button>
</form>

<h3>All Products</h3>

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
    <td><img src="../uploads/<?= htmlspecialchars($p['image']) ?>"></td>
    <td><?= htmlspecialchars($p['product_name']) ?></td>
    <td><?= htmlspecialchars($p['category_name']) ?></td>
    <td>$<?= number_format($p['price'], 2) ?></td>
    <td>
        <form action="../api/products/delete.php" method="POST" style="display:inline;">
            <input type="hidden" name="id" value="<?= $p['id'] ?>">
            <button class="delete-btn"
                    onclick="return confirm('Delete this product?')">
                Delete
            </button>
        </form>
    </td>
</tr>
<?php endwhile; ?>

</table>

</div>

</body>
</html>
