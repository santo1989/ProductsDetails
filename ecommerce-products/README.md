# Tosrifa Industries Ltd E-Commerce Products Gallery

A complete PHP-based web application for displaying company products with detailed specifications, image galleries, and admin management system.

## 📋 Features

### Frontend
- **Product Gallery**: Responsive grid layout displaying all products
- **Product Details**: Detailed view with image carousel (5 images per product)
- **Interactive Gallery**: Auto-rotating carousel with thumbnail navigation
- **Responsive Design**: Bootstrap 5 powered, mobile-friendly interface
- **Search & Filter**: Easy navigation through products

### Backend/Admin
- **User Authentication**: Secure login/registration with password hashing
- **Role-Based Access**: Admin and User roles with different permissions
- **CRUD Operations**: 
  - CREATE: Any logged-in user can add products
  - READ: View products in frontend and backend
  - UPDATE: Users can edit their own products
  - DELETE: Only admins can delete products
- **Image Upload**: Support for 1 main + 4 additional images (JPG/PNG, max 5MB each)
- **Session Management**: Secure session-based authentication

### Security
- Prepared statements (SQL injection prevention)
- Password hashing (bcrypt)
- Input validation and sanitization
- File upload validation
- XSS protection

## 🗂️ Project Structure

```
ecommerce-products/
├── admin/
│   ├── index.php           # Login page
│   ├── register.php        # User registration
│   ├── dashboard.php       # Products list (admin panel)
│   ├── create_product.php  # Add new product
│   ├── edit_product.php    # Edit existing product
│   ├── delete_product.php  # Delete product (admin only)
│   └── logout.php          # Logout handler
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   ├── js/
│   │   └── script.js       # Interactive features
│   └── images/
│       └── placeholder.jpg # Default product image
├── includes/
│   ├── db.php              # Database connection & helpers
│   ├── header.php          # Common header
│   └── footer.php          # Common footer
├── uploads/                # Product images (auto-created)
├── index.php               # Products gallery (homepage)
├── details.php             # Product details page
├── init.sql                # Database setup script
└── README.md               # This file
```

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher / MariaDB 10.3+
- Apache/Nginx web server
- PHP Extensions: mysqli, gd (for image handling)

### Step 1: Clone/Download Files
1. Download this project to your web server directory
2. Place it in your `htdocs`, `www`, or appropriate web directory

### Step 2: Database Setup
1. Open phpMyAdmin or MySQL command line
2. Import the database:
   ```sql
   mysql -u root -p < init.sql
   ```
   Or manually:
   - Open `init.sql` in phpMyAdmin
   - Click "Import" and select the file
   - Execute the SQL

3. The script will:
   - Create `products_db` database
   - Create `users` and `products` tables
   - Insert 2 demo users and 7 sample products
   - Load demo product images from Unsplash URLs for a presentable initial gallery

If you already imported data earlier, run `update_demo_images.sql` to refresh existing sample products with Unsplash demo images.

### Step 3: Configure Database Connection
1. Open `includes/db.php`
2. Update database credentials (if needed):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // Your MySQL password
   define('DB_NAME', 'products_db');
   ```

### Step 4: Set Permissions
Ensure the `uploads/` directory is writable:
```bash
chmod 755 uploads/
```

### Step 5: Access the Application
1. **Frontend**: http://localhost/ecommerce-products/
2. **Admin Login**: http://localhost/ecommerce-products/admin/

### Default Credentials
- **Admin Account**
  - Username: `admin`
  - Password: `admin123`
  
- **User Account**
  - Username: `testuser`
  - Password: `admin123`

**⚠️ IMPORTANT**: Change these passwords immediately after first login!

## 📖 Usage Guide

### For End Users (Frontend)
1. Navigate to the homepage to browse products
2. Click "View Details" on any product card
3. View product specifications, images, and details
4. Use image carousel to view all product photos
5. Click thumbnails to switch main image

### For Admin Users
1. **Login**: Go to `/admin/` and use your credentials
2. **View Dashboard**: See all your products (or all products if admin)
3. **Create Product**:
   - Click "Add New Product"
   - Fill in all product details
   - Upload images (Main + up to 4 additional)
   - Submit form
4. **Edit Product**:
   - Click edit icon on any product in dashboard
   - Modify details or upload new images
   - Save changes
5. **Delete Product** (Admin only):
   - Click delete icon
   - Confirm deletion

### User vs Admin Permissions

| Action | User | Admin |
|--------|------|-------|
| Create Products | ✅ | ✅ |
| View Products | ✅ | ✅ |
| Edit Own Products | ✅ | ✅ |
| Edit Others' Products | ❌ | ✅ |
| Delete Products | ❌ | ✅ |

## 🗄️ Database Schema

### Users Table
```sql
ID (INT, PK, AUTO_INCREMENT)
Username (VARCHAR(50), UNIQUE)
Password (VARCHAR(255), hashed)
Role (ENUM: 'user', 'admin')
Created_At (TIMESTAMP)
```

### Products Table
```sql
ID (INT, PK, AUTO_INCREMENT)
Product_Name (VARCHAR(255))
Category (VARCHAR(100))
Size (VARCHAR(50))
Description (TEXT)
Fabrication (VARCHAR(100))
Construction (VARCHAR(100))
GSM (VARCHAR(50))
Finishes (VARCHAR(100))
Color (VARCHAR(50))
Buyer (VARCHAR(100))
Style (VARCHAR(100))
Tags (VARCHAR(255))
Tag (VARCHAR(255))
Main_Image (VARCHAR(255))
Image1-4 (VARCHAR(255))
Product_URL (VARCHAR(255), UNIQUE)
Price (DECIMAL(10,2))
Created_By (INT, FK to Users.ID)
Created_At (TIMESTAMP)
Updated_At (TIMESTAMP)
```

## 🎨 Customization

### Changing Colors
Edit `assets/css/style.css` - update CSS variables:
```css
:root {
    --primary-color: #0d6efd;
    --secondary-color: #6c757d;
    /* ... */
}
```

### Adding More Fields
1. Add column to database:
   ```sql
   ALTER TABLE products ADD COLUMN New_Field VARCHAR(255);
   ```
2. Update forms in `create_product.php` and `edit_product.php`
3. Add display logic in `details.php`

### Modifying Image Limits
- Update database columns (add Image5, Image6, etc.)
- Update `$image_fields` arrays in create/edit files
- Adjust upload form HTML

## 🔒 Security Best Practices

1. **Change Default Passwords**: Immediately after installation
2. **Update DB Credentials**: Use strong passwords in production
3. **File Permissions**: 
   - Files: 644
   - Directories: 755
   - uploads/: 755
4. **HTTPS**: Use SSL certificate in production
5. **Regular Updates**: Keep PHP and MySQL updated
6. **Backup**: Regularly backup database and uploads folder

## 🐛 Troubleshooting

### Images not uploading
- Check `uploads/` directory exists and is writable
- Verify PHP `upload_max_filesize` and `post_max_size` in php.ini
- Check file extensions (only JPG, PNG allowed)

### Database connection errors
- Verify MySQL service is running
- Check credentials in `includes/db.php`
- Ensure database `products_db` exists

### Login issues
- Clear browser cookies/cache
- Check session configuration in PHP
- Verify users exist in database

### Images not displaying
- Check file paths are correct
- Verify images exist in `uploads/` folder
- Check file permissions

## 📝 File Upload Specifications
- **Allowed Formats**: JPG, JPEG, PNG
- **Max File Size**: 5MB per image
- **Image Fields**: 1 Main Image + 4 Additional Images
- **Validation**: Type and size checked server-side

## 🔄 Future Enhancements
- Product search and filtering
- Categories management
- Product ratings/reviews
- Shopping cart functionality
- Order management
- Export products to CSV/Excel
- Multi-language support
- Advanced image editor
- Product comparison feature

## 📧 Support & Contact
For issues, questions, or suggestions:
- Tosrifa Industries Ltd, Corporate & Finance Office
- Holding No 4/2 A, Plot 49 & 57 135
- Gopalpur Munnu Nagar, Tongi,
- Gazipur Bangladesh
- Phone: 8802224410051, 02224410052, 02224410053, 02224410054
- FAX: 880-2-9817743

## 📜 License
This project is open-source and available for educational and commercial use.

---

**Version**: 1.0.0  
**Last Updated**: March 2026  
**Developer**: Tosrifa Industries Ltd Team
