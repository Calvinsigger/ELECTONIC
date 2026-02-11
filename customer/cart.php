<?php
session_start();
require_once "../api/db.php";

/* SECURITY: CUSTOMER ONLY */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

/* ===== AUTO-ADD PRODUCT AFTER LOGIN ===== */
if (isset($_SESSION['add_to_cart_product_id'])) {
    $product_id = $_SESSION['add_to_cart_product_id'];
    unset($_SESSION['add_to_cart_product_id']); // Remove after using

    try {
        // Check if product exists
        $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if ($product) {
            // Check if already in cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $cartItem = $stmt->fetch();

            if ($cartItem) {
                // Increment quantity
                $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
                $stmt->execute([$cartItem['id']]);
            } else {
                // Insert new cart item
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
                $stmt->execute([$user_id, $product_id]);
            }
        }
    } catch (PDOException $e) {
        // Silently continue if there's an error
    }
}

/* FETCH CART ITEMS */
$stmt = $conn->prepare("
    SELECT 
        c.id AS cart_id,
        c.quantity,
        p.product_name,
        p.price,
        p.image,
        p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* TOTAL */
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Cart | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Cart | ElectroStore</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);min-height:100vh;}

/* HEADER */
header{
    background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color:white;
    padding:18px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 15px rgba(0,0,0,0.15);
}
header .logo{font-size:26px;font-weight:700;letter-spacing:0.5px;}
nav a{color:white;text-decoration:none;margin-left:20px;font-weight:500;transition:all 0.3s ease;}
nav a:hover{color:#ffdd59;transform:translateY(-2px);}

/* CONTAINER */
.container{max-width:1100px;margin:40px auto;padding:0 20px;}
h1{color:white;margin-bottom:30px;font-size:32px;font-weight:700;}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
}
th,td{padding:16px 18px;border-bottom:1px solid #f0f0f0;text-align:center;}
th{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);color:white;font-weight:600;font-size:13px;text-transform:uppercase;letter-spacing:0.5px;}
td{color:#555;}
tr:hover{background:#f8f9fa;}
tr:last-child td{border-bottom:none;}

td img{width:70px;height:70px;object-fit:cover;border-radius:8px;border:2px solid #e0e0e0;}
.product{display:flex;align-items:center;gap:14px;text-align:left;}

/* INPUT */
input[type=number]{width:70px;padding:10px;border-radius:6px;border:2px solid #e0e0e0;text-align:center;transition:all 0.3s ease;}
input[type=number]:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,0.1);}

/* BUTTONS */
button{border:none;padding:10px 16px;border-radius:6px;cursor:pointer;color:white;font-weight:600;transition:all 0.3s ease;}
.remove{background:#e74c3c;}
.remove:hover{background:#c0392b;transform:translateY(-2px);}

/* SUMMARY */
.summary{
    margin-top:25px;
    background:white;
    padding:25px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    font-size:18px;
    font-weight:600;
}
.total{font-size:28px;color:linear-gradient(135deg, #667eea, #764ba2);font-weight:700;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}

/* CHECKOUT */
.checkout{margin-top:25px;text-align:right;}
.checkout button{background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);padding:14px 32px;font-size:16px;}
.checkout button:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(102,126,234,0.3);}

/* EMPTY CART */
.empty{background:white;padding:50px 30px;border-radius:12px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.08);}
.empty h2{color:#0a3d62;margin-bottom:15px;font-size:24px;}
.empty p{color:#666;margin-bottom:20px;}
.empty a{text-decoration:none;color:white;background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);padding:12px 24px;border-radius:8px;display:inline-block;font-weight:600;transition:all 0.3s ease;}
.empty a:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(102,126,234,0.3);}

@media(max-width:800px){table,thead,tbody,th,td,tr{display:block;}thead{display:none}tr{margin-bottom:15px;border-bottom:2px solid #ddd;background:white;border-radius:10px;padding:15px;}td{text-align:right;padding-left:50%;position:relative;}td::before{content:attr(data-label);position:absolute;left:15px;font-weight:bold;}.summary{flex-direction:column;gap:10px;text-align:center;}}
</style>
</head>

<body>

<header>
    <div class="logo">ElectroStore</div>
    <nav>
        <a href="shop.php">Shop</a>
        <a href="cart.php">Cart</a>
        <a href="my_orders.php">My Orders</a>
        <a href="../logout.php">Logout</a>
    </nav>
</header>

<div class="container">
<h1>🛒 My Shopping Cart</h1>

<?php if(empty($cartItems)): ?>
    <div class="empty">
        <h2>Your cart is empty</h2>
        <p><a href="shop.php">Continue shopping</a></p>
    </div>
<?php else: ?>

<table>
<thead>
<tr>
    <th>Product</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Subtotal</th>
    <th>Action</th>
</tr>
</thead>
<tbody>

<?php foreach($cartItems as $item): ?>
<tr id="row<?= $item['cart_id'] ?>">
    <td data-label="Product">
        <div class="product">
            <img src="../uploads/<?= htmlspecialchars($item['image']) ?>">
            <?= htmlspecialchars($item['product_name']) ?>
        </div>
    </td>
    <td data-label="Price">TZS <?= number_format($item['price'],2) ?></td>
    <td data-label="Quantity">
        <input type="number"
               min="1"
               max="<?= $item['stock'] ?>"
               value="<?= $item['quantity'] ?>"
               onchange="updateQty(<?= $item['cart_id'] ?>, this.value)">
    </td>
    <td data-label="Subtotal">
        TZS<span id="sub<?= $item['cart_id'] ?>">
        <?= number_format($item['price']*$item['quantity'],2) ?>
        </span>
    </td>
    <td data-label="Action">
        <button class="remove" onclick="removeItem(<?= $item['cart_id'] ?>)">Remove</button>
    </td>
</tr>
<?php endforeach; ?>

</tbody>
</table>

<div class="summary">
    <span>Total:</span>
    <span class="total">TZS<span id="total"><?= number_format($total,2) ?></span></span>
</div>

<div class="checkout">
    <button onclick="checkout()">Proceed to Checkout</button>
</div>

<?php endif; ?>
</div>

<script>
function updateQty(cartId, qty){
    fetch("api/cart/update.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`cart_id=${cartId}&quantity=${qty}`
    })
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("sub"+cartId).innerText = data.subtotal;
        document.getElementById("total").innerText = data.total;
    });
}

function removeItem(cartId){
    if(!confirm("Remove item from cart?")) return;
    fetch("api/cart/remove.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:`cart_id=${cartId}`
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            document.getElementById("row"+cartId).remove();
            document.getElementById("total").innerText = data.total;
        }
    });
}

function checkout(){
    window.location = "checkout.php";
}
</script>

</body>
</html>
