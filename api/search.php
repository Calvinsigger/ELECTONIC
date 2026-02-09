<?php
/* ===============================================
   ADVANCED SEARCH & FILTER API
   File: api/search.php
   =============================== */

session_start();
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/validation.php";

// Security check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$priceMin = isset($_GET['priceMin']) ? floatval($_GET['priceMin']) : 0;
$priceMax = isset($_GET['priceMax']) ? floatval($_GET['priceMax']) : 999999;
$inStockOnly = isset($_GET['inStock']) && $_GET['inStock'] === '1' ? true : false;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'name_asc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 12; // Items per page

// Validate inputs
if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}

// Build WHERE clause
$where_conditions = [];
$params = [];

// Search by name or description
if (!empty($search)) {
    $search_term = "%{$search}%";
    $where_conditions[] = "(p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = $search_term;
    $params[] = $search_term;
}

// Filter by category
if ($category > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category;
}

// Filter by price range
if ($priceMin >= 0 && $priceMax > 0) {
    $where_conditions[] = "p.price BETWEEN ? AND ?";
    $params[] = $priceMin;
    $params[] = $priceMax;
}

// Filter by stock
if ($inStockOnly) {
    $where_conditions[] = "p.stock > 0";
}

// Build WHERE clause
$where = '';
if (!empty($where_conditions)) {
    $where = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Determine ORDER BY
$order_by = '';
switch ($sort) {
    case 'price_asc':
        $order_by = 'ORDER BY p.price ASC';
        break;
    case 'price_desc':
        $order_by = 'ORDER BY p.price DESC';
        break;
    case 'name_asc':
        $order_by = 'ORDER BY p.product_name ASC';
        break;
    case 'name_desc':
        $order_by = 'ORDER BY p.product_name DESC';
        break;
    case 'newest':
        $order_by = 'ORDER BY p.created_at DESC';
        break;
    case 'stock_desc':
        $order_by = 'ORDER BY p.stock DESC';
        break;
    default:
        $order_by = 'ORDER BY p.product_name ASC';
}

try {
    // Count total results
    $count_query = "SELECT COUNT(*) as total FROM products p JOIN categories c ON p.category_id = c.id {$where}";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate pagination
    $total_pages = ceil($total_count / $per_page);
    $offset = ($page - 1) * $per_page;

    // Fetch paginated results
    $query = "
        SELECT 
            p.id,
            p.product_name,
            p.price,
            p.image,
            p.description,
            p.stock,
            p.category_id,
            c.category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        {$where}
        {$order_by}
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($query);
    
    // Add LIMIT and OFFSET to params
    $stmt->bindValue(count($params) + 1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    
    // Bind other parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key + 1, $value);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sanitize output
    foreach ($products as &$product) {
        $product['product_name'] = htmlspecialchars($product['product_name'], ENT_QUOTES, 'UTF-8');
        $product['description'] = htmlspecialchars($product['description'], ENT_QUOTES, 'UTF-8');
        $product['image'] = htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8');
        $product['category_name'] = htmlspecialchars($product['category_name'], ENT_QUOTES, 'UTF-8');
    }

    // Return results
    echo json_encode([
        'success' => true,
        'products' => $products,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_count' => $total_count,
            'per_page' => $per_page,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages
        ],
        'filters' => [
            'search' => htmlspecialchars($search),
            'category' => $category,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'inStock' => $inStockOnly,
            'sort' => htmlspecialchars($sort)
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
