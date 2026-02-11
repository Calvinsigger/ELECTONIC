<?php
session_start();
require_once __DIR__ . "/../api/db.php";
require_once __DIR__ . "/../api/security.php";
require_once __DIR__ . "/../api/validation.php";

/* ===== ADMIN CHECK ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$error = "";
$success = "";
$edit_product = null;

/* ===== HANDLE ADD PRODUCT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $product_name = trim($_POST['product_name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category_id = (int)($_POST['category_id'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 10);

        // Prepare upload handling
        $uploadDir = __DIR__ . '/../uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $imageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Image upload failed.';
            } else {
                // Validate size (5MB) and mime
                if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                    $error = 'Image exceeds 5MB size limit.';
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
                    finfo_close($finfo);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                    if (!array_key_exists($mime, $allowed)) {
                        $error = 'Unsupported image format.';
                    } else {
                        $ext = $allowed[$mime];
                        $imageName = time() . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $dest = $uploadDir . $imageName;
                        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                            $error = 'Failed to move uploaded image.';
                            $imageName = null;
                        }
                    }
                }
            }
        }

        if (empty($product_name) || $price <= 0 || $category_id <= 0) {
            $error = "All required fields must be filled correctly.";
            // If an image was uploaded but other data invalid, remove uploaded file to avoid orphan
            if ($imageName && file_exists($uploadDir . $imageName)) {
                @unlink($uploadDir . $imageName);
            }
        } else {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO products (product_name, price, category_id, stock, image) VALUES (?, ?, ?, ?, ?)"
                );
                $stmt->execute([$product_name, $price, $category_id, $stock, $imageName]);
                $success = "✓ Product added successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
                if ($imageName && file_exists($uploadDir . $imageName)) {
                    @unlink($uploadDir . $imageName);
                }
            }
        }
    }
}

/* ===== HANDLE UPDATE PRODUCT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $product_name = trim($_POST['product_name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 10);

        if ($product_id <= 0 || empty($product_name) || $price <= 0) {
            $error = "Invalid product data.";
        } else {
            try {
                // Handle optional image upload for update
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }
                $newImageName = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                        throw new Exception('Image upload error.');
                    }
                    if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                        throw new Exception('Image exceeds 5MB size limit.');
                    }
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['image']['tmp_name']);
                    finfo_close($finfo);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                    if (!array_key_exists($mime, $allowed)) {
                        throw new Exception('Unsupported image format.');
                    }
                    $ext = $allowed[$mime];
                    $newImageName = time() . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
                    $dest = $uploadDir . $newImageName;
                    if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                        throw new Exception('Failed to move uploaded image.');
                    }
                }

                if ($newImageName) {
                    // If new image uploaded, update image column and delete old file
                    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $old = $stmt->fetchColumn();

                    $stmt = $conn->prepare(
                        "UPDATE products SET product_name = ?, price = ?, stock = ?, image = ? WHERE id = ?"
                    );
                    $stmt->execute([$product_name, $price, $stock, $newImageName, $product_id]);

                    if ($old) {
                        $oldPath = $uploadDir . $old;
                        if (file_exists($oldPath)) { @unlink($oldPath); }
                    }
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE products SET product_name = ?, price = ?, stock = ? WHERE id = ?"
                    );
                    $stmt->execute([$product_name, $price, $stock, $product_id]);
                }
                $success = " Product updated successfully!";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            } catch (Exception $ex) {
                $error = $ex->getMessage();
            }
        }
    }
}

/* ===== HANDLE DELETE PRODUCT ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $product_id = (int)($_POST['product_id'] ?? 0);
        if ($product_id > 0) {
            try {
                $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $success = " Product deleted successfully!";
            } catch (PDOException $e) {
                $error = "Error deleting product.";
            }
        }
    }
}

/* ===== FETCH CATEGORIES ===== */
$categories = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

/* ===== CHECK FOR EDIT REQUEST ===== */
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
}

/* ===== FETCH ALL PRODUCTS ===== */
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND p.product_name LIKE ?";
    $params[] = "%$search%";
}

if (!empty($category_filter)) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY p.id DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Products Management | Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;}

.wrapper{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{
    width:260px;background:linear-gradient(180deg, #0a3d62 0%, #062d48 100%);
    color:white;padding:30px 20px;box-shadow:4px 0 15px rgba(0,0,0,0.2);
    position:sticky;top:0;height:100vh;overflow-y:auto;
}
.sidebar h2{text-align:center;margin-bottom:40px;font-size:22px;font-weight:700;}
.sidebar a{display:block;color:white;text-decoration:none;padding:14px 16px;margin-bottom:8px;border-radius:8px;transition:all 0.3s ease;font-weight:500;border-left:4px solid transparent;}
.sidebar a:hover{background:rgba(255,255,255,0.2);border-left:4px solid #ffdd59;padding-left:20px;}

/* MAIN */
.main{flex:1;padding:40px;background:#f8f9fa;overflow-y:auto;}
.main h1{margin-bottom:10px;color:#0a3d62;font-size:32px;font-weight:700;}
.subtitle{color:#666;margin-bottom:30px;font-size:14px;}

/* ALERTS */
.alert{padding:15px 20px;margin-bottom:20px;border-radius:8px;font-weight:500;border-left:4px solid;}
.alert-success{background:#d4edda;color:#155724;border-left-color:#28a745;}
.alert-error{background:#f8d7da;color:#721c24;border-left-color:#dc3545;}

/* CONTAINER */
.container{display:grid;grid-template-columns:1fr 1.2fr;gap:30px;margin-bottom:30px;}

/* FORM BOX */
.form-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);}
.form-box h3{color:#0a3d62;margin-bottom:20px;font-size:18px;font-weight:600;display:flex;align-items:center;gap:8px;}
.form-group{margin-bottom:15px;}
.form-group label{display:block;margin-bottom:8px;color:#333;font-weight:600;font-size:14px;}
.form-group input, .form-group select, .form-group textarea{
    width:100%;padding:12px 15px;border:2px solid #e0e0e0;border-radius:8px;
    font-size:14px;font-family:inherit;transition:all 0.3s ease;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus{
    outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);
}
.form-group textarea{resize:vertical;min-height:80px;}

.form-buttons{display:flex;gap:10px;margin-top:20px;}
.btn{padding:12px 24px;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;
    border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:all 0.3s ease;flex:1;text-align:center;}
.btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4);}
.btn-cancel{background:#999;flex:0;}
.btn-cancel:hover{background:#777;}

/* FILTERS */
.filters{background:white;padding:20px;border-radius:12px;margin-bottom:20px;display:flex;gap:15px;flex-wrap:wrap;}
.filter-input{padding:10px 15px;border:2px solid #e0e0e0;border-radius:8px;font-size:14px;flex:1;min-width:200px;}
.filter-btn{padding:10px 20px;background:#667eea;color:white;border:none;border-radius:8px;cursor:pointer;font-weight:600;transition:all 0.3s ease;}
.filter-btn:hover{transform:translateY(-2px);box-shadow:0 4px 12px rgba(102,126,234,0.4);}

/* TABLE */
.table-box{background:white;padding:25px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.08);overflow-x:auto;}
.table-box h3{color:#0a3d62;margin-bottom:20px;font-size:18px;font-weight:600;}
table{width:100%;border-collapse:collapse;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;padding:15px;text-align:left;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{padding:12px 15px;border-bottom:1px solid #f0f0f0;color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}

.product-name{font-weight:600;color:#0a3d62;}
.stock-badge{padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;}
.stock-low{background:#fff3cd;color:#856404;}
.stock-ok{background:#d4edda;color:#155724;}
.stock-out{background:#f8d7da;color:#721c24;}

.action-btn{padding:6px 12px;border:none;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;transition:all 0.3s ease;margin-right:5px;}
.btn-edit{background:#2196f3;color:white;}
.btn-edit:hover{background:#1976d2;}
.btn-delete{background:#f44336;color:white;}
.btn-delete:hover{background:#da190b;}

.no-products{text-align:center;padding:40px;color:#999;font-size:16px;}

.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;align-items:center;justify-content:center;}
.modal.active{display:flex;}
.modal-content{background:white;padding:30px;border-radius:12px;max-width:500px;width:90%;}
.modal-content h3{color:#0a3d62;margin-bottom:20px;}

@media(max-width:1024px){.container{grid-template-columns:1fr;};}
@media(max-width:768px){.wrapper{flex-direction:column;}.sidebar{width:100%;height:auto;position:static;}}
</style>
</head>

<body>

<div class="wrapper">

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2> Admin Panel</h2>
        <a href="admin_dashboard.php"> Dashboard</a>
        <a href="manage_products.php"> Products</a>
        <a href="categories.php"> Categories</a>
        <a href="users.php"> Users</a>
        <a href="orders.php"> Orders</a>
        <a href="../logout.php"> Logout</a>
    </div>

    <!-- MAIN -->
    <div class="main">
        <h1> Products Management</h1>
        <p class="subtitle">Add, edit, and manage all products in your store</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- FORM & TABLE -->
        <div class="container">

            <!-- ADD/EDIT FORM -->
            <div class="form-box">
                <h3><?= $edit_product ? ' Edit Product' : ' Add New Product' ?></h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <?= getCSRFTokenInput() ?>
                    <input type="hidden" name="action" value="<?= $edit_product ? 'update' : 'add' ?>">
                    
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="product_name">Product Name *</label>
                        <input type="text" id="product_name" name="product_name" 
                            value="<?= $edit_product ? htmlspecialchars($edit_product['product_name']) : '' ?>"
                            placeholder="e.g., DELL PC" minlength="3" maxlength="150" required>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" 
                                    <?= $edit_product && $edit_product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price">Price (TZS) *</label>
                        <input type="number" id="price" name="price" step="0.01"
                            value="<?= $edit_product ? $edit_product['price'] : '' ?>"
                            placeholder="29.99" min="0.01" max="999999.99" required>
                    </div>

                    <div class="form-group">
                        <label for="stock">Stock Quantity *</label>
                        <input type="number" id="stock" name="stock"
                            value="<?= $edit_product ? $edit_product['stock'] : '10' ?>"
                            placeholder="10" min="0" max="99999" required>
                    </div>



                    <hr style="margin:20px 0;border:1px solid #e0e0e0;">
                    <h4 style="color:#0a3d62;margin:15px 0 10px 0;font-size:16px;font-weight:600;"> Product Image</h4>

                    <div class="form-group">
                        <label for="image">Upload Product Image *</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp" <?= $edit_product ? '' : 'required' ?> >
                        <div style="font-size:12px;color:#666;margin-top:8px;"> Supported formats: JPG, PNG, GIF, WebP (Max 5MB)</div>
                    </div>

                    <div class="image-preview-box" style="background:#f5f5f5;padding:15px;border-radius:8px;margin-bottom:15px;text-align:center;display:none;" id="preview-box">
                        <img id="preview-image" src="" alt="Preview" style="max-width:100%;max-height:200px;border-radius:8px;">
                        <p style="font-size:12px;color:#666;margin-top:10px;">Preview of selected image</p>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn">
                            <?= $edit_product ? ' Update Product' : ' Add Product' ?>
                        </button>
                        <?php if ($edit_product): ?>
                            <a href="manage_products.php" class="btn btn-cancel">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- PRODUCTS LIST -->
            <div>
                <!-- FILTERS -->
                <div class="filters">
                    <form method="GET" style="display:flex;gap:10px;width:100%;flex-wrap:wrap;">
                        <input type="text" name="search" class="filter-input" placeholder="Search products..." 
                            value="<?= htmlspecialchars($search) ?>">
                        <select name="category" class="filter-input" style="flex:0.5;min-width:150px;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['category_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="filter-btn"> Filter</button>
                    </form>
                </div>

                <!-- TABLE -->
                <div class="table-box">
                    <h3><?= count($products) ?> Product<?= count($products) !== 1 ? 's' : '' ?> Found</h3>
                    
                    <?php if (count($products) > 0): ?>
                        <table>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                            
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td class="product-name"><?= htmlspecialchars($p['product_name']) ?></td>
                                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                                    <td>TZS <?= number_format($p['price'], 2) ?></td>
                                    <td>
                                        <?php
                                        if ($p['stock'] <= 0) {
                                            $badge_class = 'stock-out';
                                            $stock_text = 'Out of Stock';
                                        } elseif ($p['stock'] < 5) {
                                            $badge_class = 'stock-low';
                                            $stock_text = $p['stock'] . ' Left';
                                        } else {
                                            $badge_class = 'stock-ok';
                                            $stock_text = $p['stock'] . ' in Stock';
                                        }
                                        ?>
                                        <span class="stock-badge <?= $badge_class ?>"><?= $stock_text ?></span>
                                    </td>
                                    <td>
                                        <a href="manage_products.php?edit=<?= $p['id'] ?>" class="action-btn btn-edit"> Edit</a>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete <?= htmlspecialchars($p['product_name']) ?>?');">
                                            <?= getCSRFTokenInput() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="action-btn btn-delete"> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <div class="no-products">
                            <?php if (!empty($search) || !empty($category_filter)): ?>
                                No products found matching your filters.
                            <?php else: ?>
                                No products yet. Add one to get started!
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Image preview functionality
document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewBox = document.getElementById('preview-box');
    const previewImage = document.getElementById('preview-image');
    
    if (file) {
        // Check file size (5MB max)
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit. Please choose a smaller image.');
            e.target.value = '';
            previewBox.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(event) {
            previewImage.src = event.target.result;
            previewBox.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        previewBox.style.display = 'none';
    }
});
</script>

</body>
</html>
