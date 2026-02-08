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

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}
body {
    background: #f4f6f8;
}

/* ===== LAYOUT ===== */
.wrapper {
    display: flex;
    min-height: 100vh;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: 230px;
    background: #0a3d62;
    color: white;
    padding: 20px;
}
.sidebar h2 {
    text-align: center;
    margin-bottom: 25px;
}
.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px;
    margin-bottom: 8px;
    border-radius: 4px;
}
.sidebar a:hover {
    background: rgba(255,255,255,0.2);
}

/* ===== MAIN ===== */
.main {
    flex: 1;
    padding: 30px;
}
.main h1 {
    margin-bottom: 20px;
    color: #0a3d62;
}

/* ===== FORM ===== */
.form-box {
    background: white;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 25px;
    box-shadow: 0 0 8px rgba(0,0,0,0.1);
}
.form-box input {
    padding: 10px;
    width: 250px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.form-box button {
    padding: 10px 18px;
    background: #0a3d62;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}
.form-box button:hover {
    background: #07406b;
}

/* ===== TABLE ===== */
.table-box {
    background: white;
    padding: 20px;
    border-radius: 6px;
    box-shadow: 0 0 8px rgba(0,0,0,0.1);
}
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
}
th {
    background: #0a3d62;
    color: white;
    text-align: left;
}
tr:hover {
    background: #f1f1f1;
}
.delete-btn {
    color: red;
    text-decoration: none;
}
.delete-btn:hover {
    text-decoration: underline;
}
</style>
</head>

<body>

<div class="wrapper">

    <!-- ===== SIDEBAR ===== -->
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="users.php">Users</a>
        <a href="orders.php">Orders</a>
        <a href="../logout.php">Logout</a>
    </div>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main">
        <h1>Manage Categories</h1>

        <!-- ===== ADD CATEGORY ===== -->
        <div class="form-box">
            <form method="POST">
                <input type="text" name="category_name" placeholder="New Category Name" required>
                <button type="submit">Add Category</button>
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
                            <td><?= $cat['id'] ?></td>
                            <td><?= htmlspecialchars($cat['category_name']) ?></td>
                            <td><?= $cat['created_at'] ?></td>
                            <td>
                                <a class="delete-btn"
                                   href="?delete=<?= $cat['id'] ?>"
                                   onclick="return confirm('Delete this category?')">
                                   Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No categories found</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>

    </div>
</div>

</body>
</html>
