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
<title>Manage Users | Admin</title>
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

/* TABLE BOX */
.table-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow-x:auto;}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{padding:12px 15px;border-bottom:1px solid #f0f0f0;color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}

/* ROLE BADGE */
.role-badge{display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;text-transform:uppercase;}
.admin-badge{background:linear-gradient(135deg, #f093fb 0%, #f5576c 100%);color:white;}
.customer-badge{background:linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);color:white;}

/* STATUS BADGE */
.status-active{display:inline-block;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;background:#27ae60;color:white;}
.status-blocked{display:inline-block;padding:4px 10px;border-radius:12px;font-size:11px;font-weight:600;background:#e74c3c;color:white;}

/* BUTTONS */
select{padding:8px 12px;border:2px solid #e0e0e0;border-radius:6px;font-size:13px;cursor:pointer;transition:all 0.3s ease;}
select:focus{outline:none;border-color:#667eea;}

.action-btn{padding:6px 12px;border:none;border-radius:6px;cursor:pointer;font-weight:600;font-size:12px;transition:all 0.3s ease;}
.delete-btn{background:#e74c3c;color:white;}
.delete-btn:hover{background:#c0392b;transform:translateY(-2px);}
.block-btn{background:#f39c12;color:white;}
.block-btn:hover{background:#d68910;transform:translateY(-2px);}
.unblock-btn{background:#27ae60;color:white;}
.unblock-btn:hover{background:#1e8449;transform:translateY(-2px);}

@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{width:100%;height:auto;position:static;}}
</style>
</head>
<body>

<div class="wrapper">
    <div class="sidebar">
        <h2>📊 Admin Panel</h2>
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="manage_products.php">📦 Products</a>
        <a href="categories.php">🏷️ Categories</a>
        <a href="users.php">👥 Users</a>
        <a href="orders.php">📋 Orders</a>
        <a href="../logout.php">🚪 Logout</a>
    </div>

    <div class="main">
        <h1>👥 Manage Users</h1>
        <div class="table-box">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>#<?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['fullname']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="role" onchange="this.form.submit()">
                                <option value="customer" <?= $user['role']=='customer'?'selected':'' ?>>🛍️ Customer</option>
                                <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>⚙️ Admin</option>
                            </select>
                        </form>
                    </td>
                    <td>
                        <?php if ($user['status'] == 'blocked'): ?>
                            <span class="status-blocked">🔒 Blocked</span>
                        <?php else: ?>
                            <span class="status-active">✓ Active</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                    <td>
                        <form method="POST" style="display:inline-block;margin-right:8px;">
                            <input type="hidden" name="block_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="status" value="<?= $user['status']=='blocked'?'active':'blocked' ?>">
                            <button type="submit" class="action-btn <?= $user['status']=='blocked'?'unblock-btn':'block-btn' ?>">
                                <?= $user['status']=='blocked'?'🔓 Unblock':'🔒 Block' ?>
                            </button>
                        </form>
                        <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Delete this user? This action is irreversible.');" style="text-decoration:none;">
                            <button class="action-btn delete-btn">🗑️ Delete</button>
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
