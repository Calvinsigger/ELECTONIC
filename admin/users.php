<?php
session_start();
require_once __DIR__ . "/../api/db.php";

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ===== HANDLE ROLE UPDATE ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'], $_POST['role'])) {
        $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$_POST['role'], $_POST['user_id']]);
        header("Location: users.php");
        exit;
    }
    if (isset($_POST['block_id'])) {
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['block_id']]);
        header("Location: users.php");
        exit;
    }
}

/* ===== HANDLE DELETE USER ===== */
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: users.php");
    exit;
}

/* ===== GET ALL USERS ===== */
$users = $conn->query("SELECT id, fullname, email, role, status, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Users | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
* {margin:0; padding:0; box-sizing:border-box; font-family: Arial,sans-serif;}
body {background:#f4f6f8;}
.wrapper {display:flex; min-height:100vh;}
.sidebar {width:240px; background:#0a3d62; color:white; padding:20px;}
.sidebar h2 {text-align:center; margin-bottom:30px;}
.sidebar a {display:block; color:white; text-decoration:none; padding:10px; margin-bottom:8px; border-radius:4px;}
.sidebar a:hover {background: rgba(255,255,255,0.2);}
.main {flex:1; padding:30px;}
.main h1 {margin-bottom:20px; color:#0a3d62;}
.table-box {background:white; padding:20px; border-radius:6px; box-shadow:0 0 8px rgba(0,0,0,0.1);}
table {width:100%; border-collapse:collapse;}
th, td {padding:12px; border-bottom:1px solid #ddd; text-align:left;}
th {background:#0a3d62; color:white;}
tr:hover {background:#f1f1f1;}
button {padding:6px 10px; border:none; border-radius:4px; cursor:pointer;}
button.update {background:#1e90ff; color:white;}
button.delete {background:#e74c3c; color:white;}
button.block {background:#f39c12; color:white;}
button.unblock {background:#27ae60; color:white;}
button.update:hover {background:#0d74d1;}
button.delete:hover {background:#c0392b;}
button.block:hover {background:#d68910;}
button.unblock:hover {background:#1e8449;}
select {padding:6px; border-radius:4px; border:1px solid #ccc;}
@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{width:100%;}}
</style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <a href="admin_dashboard.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="categories.php">Categories</a>
        <a href="users.php">Users</a>
        <a href="orders.php">Orders</a>
        <a href="../logout.php">Logout</a>
    </div>

    <div class="main">
        <h1>Manage Users</h1>
        <div class="table-box">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered At</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['fullname']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="customer" <?= $user['role']=='customer'?'selected':'' ?>>Customer</option>
                                <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="block_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="status" value="<?= $user['status']=='blocked'?'active':'blocked' ?>">
                            <button type="submit" class="<?= $user['status']=='blocked'?'unblock':'block' ?>">
                                <?= $user['status']=='blocked'?'Unblock':'Block' ?>
                            </button>
                        </form>
                    </td>
                    <td><?= $user['created_at'] ?></td>
                    <td>
                        <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Delete this user?')">
                            <button class="delete">Delete</button>
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
