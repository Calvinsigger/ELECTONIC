<?php
session_start();
require_once __DIR__ . "/../api/db.php";

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ===== ADD CATEGORY ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['category_name'])) {
    $category_name = trim($_POST['category_name']);

    if ($category_name !== '') {
        $stmt = $conn->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->execute([$category_name]);
        header("Location: categories.php");
        exit;
    }
}

/* ===== DELETE CATEGORY ===== */
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: categories.php");
    exit;
}

/* ===== FETCH CATEGORIES ===== */
$categories = $conn->query("
    SELECT id, category_name, created_at 
    FROM categories 
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Categories | Admin</title>
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
.main{flex:1;padding:40px;background:#f8f9fa;}
.main h1{margin-bottom:35px;color:#0a3d62;font-size:32px;font-weight:700;}

/* FORM BOX */
.form-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);margin-bottom:25px;}
.form-box input{padding:12px 15px;width:250px;border:2px solid #e0e0e0;border-radius:6px;font-size:14px;transition:all 0.3s ease;}
.form-box input:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}
.form-box button{padding:12px 24px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;border:none;border-radius:6px;cursor:pointer;font-weight:600;transition:all 0.3s ease;margin-left:10px;}
.form-box button:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4);}

/* TABLE BOX */
.table-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{padding:12px 15px;border-bottom:1px solid #f0f0f0;color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}

.delete-btn{color:#e74c3c;text-decoration:none;font-weight:600;transition:all 0.3s ease;}
.delete-btn:hover{color:#c0392b;text-decoration:underline;}

@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{width:100%;height:auto;position:static;}.form-box input{width:100%;}}
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
        <h1>🏷️ Manage Categories</h1>

        <!-- ===== ADD CATEGORY ===== -->
        <div class="form-box">
            <form method="POST" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <input type="text" name="category_name" placeholder="Enter new category name" required>
                <button type="submit">➕ Add Category</button>
            </form>
        </div>

        <!-- ===== CATEGORY LIST ===== -->
        <div class="table-box">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Category Name</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>

                <?php if ($categories): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td>#<?= $cat['id'] ?></td>
                            <td><?= htmlspecialchars($cat['category_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($cat['created_at'])) ?></td>
                            <td>
                                <a class="delete-btn"
                                   href="?delete=<?= $cat['id'] ?>"
                                   onclick="return confirm('Delete this category?')">
                                   🗑️ Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align:center;color:#999;">No categories found</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

    </div>
</div>

</body>
</html>
