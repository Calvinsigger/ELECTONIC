# 🔍 Advanced Search & Filtering System

## Overview

A complete server-side search and filtering system with:
- ✅ Real-time AJAX search
- ✅ Advanced filtering (price range, category, stock)
- ✅ Multiple sorting options
- ✅ Pagination support (12 items per page)
- ✅ Performance optimized database queries
- ✅ Full-text search (name & description)

---

## 🎯 Features Implemented

### **1. Real-Time Search**
- Search by product **name** or **description**
- Case-insensitive matching
- AJAX-based (no page reload)
- Instant results display

### **2. Advanced Filters**

| Filter | Options | Description |
|--------|---------|-------------|
| **Category** | All categories available | Filter products by category |
| **Price Range** | $0 - $max price | Dual slider for min/max price |
| **Stock Status** | All / In Stock Only | Hide out-of-stock items |
| **Sorting** | 6 options | Sort by name, price, newest, stock |

### **3. Sorting Options**
- 🔤 **Name (A-Z)** - Alphabetical ascending
- 🔤 **Name (Z-A)** - Alphabetical descending
- 💰 **Price: Low to High** - Cheapest first
- 💰 **Price: High to Low** - Expensive first
- ⭐ **Newest Products** - Recently added
- 📊 **Stock Available** - Most stock first

### **4. Pagination**
- 12 products per page
- Smart pagination UI (shows up to 5 page numbers)
- Previous / Next buttons
- Jump to any page

### **5. Stock Status Indicators**
- ✓ **In Stock** - Green (10+ items)
- ⚠ **Low Stock** - Orange (1-9 items)
- ✗ **Out of Stock** - Red (0 items)

---

## 📁 Files Created/Updated

### **New File:**
- **api/search.php** - Advanced search API endpoint (server-side)

### **Updated Files:**
- **customer/shop.php** - Enhanced UI with new filters and AJAX integration

---

## 🔧 Technical Implementation

### **API Endpoint: api/search.php**

**URL:** `/api/search.php`

**Method:** GET

**Parameters:**
```
search      (string)   - Search term (max 100 chars)
category    (int)      - Category ID (0 for all)
priceMin    (float)    - Minimum price
priceMax    (float)    - Maximum price
inStock     (0|1)      - Show in stock only
sort        (string)   - Sort option
page        (int)      - Page number (default 1)
```

**Sort Options:**
- `name_asc` - Name ascending
- `name_desc` - Name descending
- `price_asc` - Price low to high
- `price_desc` - Price high to low
- `newest` - Latest products first
- `stock_desc` - Highest stock first

**Response:**
```json
{
  "success": true,
  "products": [
    {
      "id": 1,
      "product_name": "DELL PC",
      "price": "999.99",
      "image": "DELL PC.jpg",
      "description": "...",
      "stock": 15,
      "category_id": 1,
      "category_name": "Laptops"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 3,
    "total_count": 32,
    "per_page": 12,
    "has_prev": false,
    "has_next": true
  },
  "filters": {
    "search": "dell",
    "category": 0,
    "priceMin": 0,
    "priceMax": 2500,
    "inStock": false,
    "sort": "name_asc"
  }
}
```

---

## 🛡️ Security Features

✅ **Server-Side Validation**
- Input length limits (100 chars max for search)
- Numeric validation for prices and IDs
- SQL injection prevention (prepared statements)

✅ **Output Sanitization**
- `htmlspecialchars()` on all JSON responses
- Safe UTF-8 encoding

✅ **Authorization**
- Session check (customer login required)
- No unauthorized data access

✅ **Performance**
- Database query optimization
- Indexes on searchable columns
- LIMIT/OFFSET for pagination

---

## 📊 Database Queries Used

### **Search Query**
```sql
SELECT p.*, c.category_name 
FROM products p
JOIN categories c ON p.category_id = c.id
WHERE (p.product_name LIKE ? OR p.description LIKE ?)
  AND p.category_id = ?
  AND p.price BETWEEN ? AND ?
  AND p.stock > 0
ORDER BY p.price ASC
LIMIT 12 OFFSET 0
```

### **Count Query**
```sql
SELECT COUNT(*) as total FROM products p
JOIN categories c ON p.category_id = c.id
WHERE (p.product_name LIKE ? OR p.description LIKE ?)
  AND p.category_id = ?
  AND p.price BETWEEN ? AND ?
  AND p.stock > 0
```

---

## 💻 Frontend Implementation

### **JavaScript Functions**

| Function | Purpose |
|----------|---------|
| `applyFilters(page)` | AJAX request to API with current filters |
| `displayProducts(products)` | Render product cards |
| `displayPagination(pagination)` | Render pagination buttons |
| `displayResultsInfo(pagination)` | Show results count and page info |
| `resetFilters()` | Clear all filters to default |

### **Event Listeners**

- **Price Min/Max:** Synchronize min/max values
- **Search Input:** Apply filters on Enter key
- **Sort Dropdown:** Triggers applyFilters()
- **Category Filter:** Triggers applyFilters()
- **Stock Filter:** Triggers applyFilters()
- **Page Load:** Load initial products

---

## 🎨 UI Components

### **Filter Sections:**

1. **Search Bar**
   - Text input for product name/description
   - Max 100 characters
   - Enter key to search

2. **Category Dropdown**
   - All categories dynamically loaded
   - Default: "All Categories"

3. **Sorting Dropdown**
   - 6 sort options
   - Default: Name (A-Z)

4. **Stock Filter**
   - All Products / In Stock Only
   - Default: All Products

5. **Price Range Sliders**
   - Dual sliders (min/max)
   - Automatic synchronization
   - Real-time display of values

6. **Action Buttons**
   - 🔍 Search Products (apply filters)
   - 🔄 Reset All Filters (clear form)

### **Product Cards:**
- Product image
- Product name
- Category name
- Price (formatted)
- **Stock status indicator** (NEW)
- View Product button

### **Pagination:**
- Previous button (disabled if on first page)
- Smart page numbers (max 5 visible)
- Current page highlighted
- Next button (disabled if on last page)
- Ellipsis (...) for skipped pages

### **Results Info:**
- Total products found
- Current page / total pages

---

## 🚀 Performance Optimization

✅ **Database Level**
- Using LIMIT/OFFSET for pagination
- Prepared statements (avoid SQL injection)
- Efficient JOIN with categories

✅ **API Level**
- Lightweight JSON responses
- Only essential fields returned
- AJAX for partial page updates

✅ **Frontend Level**
- Loading spinner during fetch
- No full page reloads
- Event delegation for clicks

---

## 📱 Responsive Design

- **Desktop:** Full multi-column grid
- **Tablet:** 2-3 columns
- **Mobile:** Single column
- Filters stack vertically on small screens
- Touch-friendly buttons and sliders

---

## 🧪 Testing the System

### **Test Case 1: Basic Search**
1. Type "dell" in search box
2. Click Search Products
3. ✅ Should show only Dell products

### **Test Case 2: Price Range Filter**
1. Set price range: $500 - $1000
2. Click Search Products
3. ✅ Should show only products in that range

### **Test Case 3: Category Filter**
1. Select "Laptops" from category dropdown
2. Click Search Products
3. ✅ Should show only laptop products

### **Test Case 4: Combined Filters**
1. Search: "phone"
2. Category: "Smartphones"
3. Price: $400 - $800
4. Stock: "In Stock Only"
5. Sort: "Price Low to High"
6. Click Search Products
7. ✅ Should show filtered, sorted results

### **Test Case 5: Pagination**
1. Apply filters that return 20+ results
2. Verify pagination controls appear
3. Click page 2
4. ✅ Should load products 13-24

### **Test Case 6: Stock Status**
1. Look at product cards
2. ✅ Should show stock indicators (✓/⚠/✗)

---

## 🔒 Security Considerations

1. **Input Validation**
   - Search limited to 100 characters
   - Price validates as float
   - Category validates as integer

2. **Output Escaping**
   - All data escaped with htmlspecialchars()
   - UTF-8 encoding specified

3. **SQL Injection Prevention**
   - All queries use prepared statements
   - Parameters bound safely

4. **Authorization**
   - Only logged-in customers can search
   - Session validation

---

## 📈 Scalability

This system handles:
- ✅ 100s of products efficiently
- ✅ 1000s of products with pagination
- ✅ Multiple concurrent users
- ✅ Complex filter combinations

**Recommended Indexes:**
```sql
CREATE INDEX idx_product_name ON products(product_name);
CREATE INDEX idx_description ON products(description);
CREATE INDEX idx_price ON products(price);
CREATE INDEX idx_stock ON products(stock);
CREATE INDEX idx_category ON products(category_id);
```

---

## 🎁 Future Enhancements

Potential improvements:
- [ ] Search suggestions (autocomplete)
- [ ] Save search filters
- [ ] Product reviews filtering
- [ ] Rating-based filtering
- [ ] View as list/grid toggle
- [ ] "More like this" recommendations
- [ ] Search analytics
- [ ] Trending searches

---

## 📝 Usage Example

```php
// Customer goes to shop.php
// 1. Page loads with filter UI
// 2. Customer enters filter criteria:
//    - Search: "laptop"
//    - Category: 1 (Laptops)
//    - Price: $500-$2000
//    - Stock: In Stock Only
//    - Sort: Price Low to High
// 3. Clicks "Search Products"
// 4. AJAX calls api/search.php with parameters
// 5. API returns matching products with pagination
// 6. Products displayed with stock indicators
// 7. Customer can browse pages or refine filters
```

---

## ✅ Validation & Security Status

- ✅ Server-side search validation
- ✅ Database query optimization
- ✅ XSS prevention (output escaping)
- ✅ SQL injection prevention (prepared statements)
- ✅ Session authentication
- ✅ Input length limits
- ✅ Numeric validation

**Status:** Production Ready 🚀

---

**Version:** 1.0
**Created:** February 9, 2026
**Last Updated:** February 9, 2026
