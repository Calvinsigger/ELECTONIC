# Product Images Mapping

## ✅ Image to Product Mapping

Here's exactly how your renamed images are now mapped to products in the database:

### **Laptops & Computers:**
| Product Name | Image File | Price |
|---|---|---|
| DELL PC | DELL PC.jpg | $999.99 |
| Macbook Air | Macbook Air.jpg | $1499.99 |
| ASUS PC | ASUS PC.jpg | $1299.99 |
| LENOVO | LENOVO.jpg | $899.99 |
| HP PC | HP PC.jpg | $749.99 |

### **Smartphones:**
| Product Name | Image File | Price |
|---|---|---|
| Apple iPhone 15 Pro | Apple iPhone 15 Pro.jpg | $999.99 |
| Infinix Smart 9 | Infinix Smart 9.jpg | $799.99 |
| iPhone 11 | iPhone 11.jpg | $599.99 |
| Tecno Camon 40 | tecno camon40.jpg | $449.99 |

### **Storage:**
| Product Name | Image File | Price |
|---|---|---|
| Storage 1TB | 1tb.jpg | $89.99 |
| Storage 4TB | 4 tb.jpg | $199.99 |
| Storage 500GB | 500gb.jpg | $49.99 |

---

## 🔄 Re-Import Updated Database

After updating the images mapping, you need to **re-import the database**:

### **Using phpMyAdmin:**
1. Go to `http://localhost/phpmyadmin`
2. Select `electronics_ordering` database
3. Click **Drop** to delete old database
4. Click **Import** tab
5. Choose `database.sql` file
6. Click **Go** ✅

### **Using Command Line:**
```powershell
cd C:\xampp\mysql\bin
mysql -u root < "C:\xampp\htdocs\Electronic\database.sql"
```

---

## ✅ What Happens After Import

When you go to the **Shop page**, you should see:
- ✅ 12 products with your renamed images
- ✅ Products organized by category
- ✅ All images displaying correctly from the uploads folder

---

## 🖼️ Available Images You Haven't Used Yet

If you want to use more images, these are available in your uploads folder:

**Generic/Other Images:**
- one.jpg through eleven.jpg (numeric images)
- home.png, new.png
- shop.jpg
- kelv.jpg, mmk.jpg, zz.jpg, abcd.jpg, az.jpg

You can upload more products and assign these images, or use them for other purposes (hero banner, etc.)

---

## 📝 Notes

- All your product image references in the database now match the `.jpg` files you have renamed
- The shop.php automatically displays images from the database
- If you add new products through the admin panel, their images will also be stored in the uploads folder
- Images are stored with the upload timestamp for new products (e.g., `1770411667_DELL PC.jpg`)

---

**Status:** Ready to use after re-importing the database!