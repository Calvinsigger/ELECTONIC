# Database Setup Instructions

## Database File: `database.sql`

This file contains the complete database schema and sample data for the Electronic Store application.

**✅ UPDATED:** Fixed column names and password hashes to match your application code.

---

## ⚠️ IMPORTANT: Re-Import the Database

The database has been updated to fix compatibility issues. You **MUST** re-import it:

### **Step 1: Delete Old Database**
1. Open `http://localhost/phpmyadmin`
2. Find `electronics_ordering` database (left sidebar)
3. Click it and then click **Drop** button
4. Confirm deletion

### **Step 2: Import New Fixed Database**

**Using phpMyAdmin (Recommended):**
1. Still in phpMyAdmin, click **Import** tab
2. Click **Choose File** button
3. Select `database.sql` from your project folder
4. Click **Go** button
5. ✅ Wait for success message

**Using Command Line (PowerShell):**
```powershell
cd C:\xampp\mysql\bin
mysql -u root < "C:\xampp\htdocs\Electronic\database.sql"
```

---

## 🔑 Login Credentials

After importing the fixed database, use these credentials:

### **Admin Account:**
- **Email:** admin@electronics.com
- **Password:** admin123

### **Customer Test Accounts:**
- **Email:** john@example.com | **Password:** customer123
- **Email:** jane@example.com | **Password:** customer123
- **Email:** mike@example.com | **Password:** customer123

**Note:** You can also register your own account from the Register page.

---

## ✅ What Was Fixed

- ✅ Column name: `total_price` → `total_amount` (matches your code)
- ✅ Password hashes: Now valid bcrypt hashes (not dummy text)
- ✅ Checkout code: Updated to use correct column name
- ✅ All foreign key relationships verified

---

## Database Structure

### **Tables Created:**

1. **users** - Customer and admin accounts
2. **categories** - Product categories (Laptops, Phones, etc.)
3. **products** - Electronic products with prices and stock
4. **cart** - Shopping cart items for customers
5. **orders** - Customer orders (with total_amount column)
6. **order_items** - Individual items within each order

---

## Sample Data Included

- ✅ **4 Users:** 1 admin + 3 customers
- ✅ **8 Categories:** Laptops, Smartphones, Tablets, Headphones, Cameras, Monitors, Keyboards, Mice
- ✅ **12 Products:** Various electronics with stock and prices
- ✅ **3 Sample Orders:** With order items and status tracking

---

## 🧪 Testing the Application

After importing the fixed database:

1. Start XAMPP (Apache + MySQL)
2. Go to `http://localhost/Electronic/`
3. **Test Admin Login:**
   - Email: `admin@electronics.com`
   - Password: `admin123`
   - ✅ Should see admin dashboard

4. **Test Customer Login:**
   - Email: `john@example.com`
   - Password: `customer123`
   - ✅ Should see customer dashboard (no more SQL errors!)
   - Try to view orders, cart, profile

---

## Important Notes

⚠️ **XAMPP MySQL Default Credentials:**
- **Host:** localhost
- **Username:** root
- **Password:** (empty)
- **Database:** electronics_ordering

These are already configured in `api/db.php`

---

## Reset Database

If you need to reset the database at any time:
1. In phpMyAdmin, select `electronics_ordering` database
2. Click **Drop** to delete it
3. Re-import `database.sql` as shown above

---

**Status:** ✅ Ready to use (after re-importing with the updated SQL file)
**Last Updated:** February 9, 2026
