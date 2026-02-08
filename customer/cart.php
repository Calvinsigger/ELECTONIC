<?php
session_start();
require_once "../api/db.php";

/* SECURITY: CUSTOMER ONLY */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

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

<style>
*{box-sizing:border-box}
body{
    margin:0;
    font-family:'Segoe UI', Arial, sans-serif;
    background:#f4f6f8;
}

/* HEADER */
header{
    background:#0a3d62;
    color:white;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.logo{font-size:22px;font-weight:bold}
nav a{
    color:white;
    text-decoration:none;
    margin-left:18px;
}
nav a:hover{color:#ffdd59}

/* CONTAINER */
.container{
    max-width:1100px;
    margin:40px auto;
    padding:0 20px;
}
h1{
    color:#0a3d62;
    margin-bottom:20px;
}

/* TABLE */
table{
    width:100%;
    border-collapse:collapse;
    background:white;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 10px 22px rgba(0,0,0,.15);
}
th,td{
    padding:15px;
    border-bottom:1px solid #eee;
    text-align:center;
}
th{
    background:#0a3d62;
    color:white;
}
td img{
    width:70px;
    height:70px;
    object-fit:cover;
    border-radius:8px;
}
.product{
    display:flex;
    align-items:center;
    gap:12px;
    text-align:left;
}

/* INPUT */
input[type=number]{
    width:70px;
    padding:8px;
    border-radius:6px;
    border:1px solid #ccc;
    text-align:center;
}

/* BUTTONS */
button{
    border:none;
    padding:8px 14px;
    border-radius:6px;
    cursor:pointer;
    color:white;
}
.update{
    background:#27ae60;
}
.update:hover{background:#1e8449}
.remove{
    background:#c0392b;
}
.remove:hover{background:#922b21}

/* SUMMARY */
.summary{
    margin-top:20px;
    background:white;
    padding:20px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 8px 18px rgba(0,0,0,.15);
    font-size:18px;
}
.total{
    font-weight:bold;
    font-size:22px;
}

/* CHECKOUT */
.checkout{
    margin-top:20px;
    text-align:right;
}
.checkout button{
    background:#0a3d62;
    padding:12px 22px;
    font-size:16px;
}
.checkout button:hover{
    background:#07406b;
}

/* EMPTY */
.empty{
    background:white;
    padding:40px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 8px 18px rgba(0,0,0,.15);
}
.empty a{
    text-decoration:none;
    color:#1e90ff;
    font-weight:bold;
}

/* RESPONSIVE */
@media(max-width:800px){
    table, thead, tbody, th, td, tr{
        display:block;
    }
    thead{display:none}
    tr{
        margin-bottom:15px;
        border-bottom:2px solid #ddd;
    }
    td{
        text-align:right;
        padding-left:50%;
        position:relative;
    }
    td::before{
        content:attr(data-label);
        position:absolute;
        left:15px;
        font-weight:bold;
    }
}
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
    <td data-label="Price">$<?= number_format($item['price'],2) ?></td>
    <td data-label="Quantity">
        <input type="number"
               min="1"
               max="<?= $item['stock'] ?>"
               value="<?= $item['quantity'] ?>"
               onchange="updateQty(<?= $item['cart_id'] ?>, this.value)">
    </td>
    <td data-label="Subtotal">
        $<span id="sub<?= $item['cart_id'] ?>">
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
    <span class="total">$<span id="total"><?= number_format($total,2) ?></span></span>
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
