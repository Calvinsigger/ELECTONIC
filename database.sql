-- ===============================================
-- COMPLETE DATABASE FOR ELECTRONIC STORE
-- Database: electronics_ordering
-- Created: 2026
-- ===============================================

-- Drop existing database if exists
DROP DATABASE IF EXISTS `electronics_ordering`;

-- Create database
CREATE DATABASE `electronics_ordering` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `electronics_ordering`;

-- ===============================================
-- TABLE 1: USERS
-- ===============================================
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fullname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'customer') DEFAULT 'customer',
  `status` ENUM('active', 'blocked') DEFAULT 'active',
  `profile_image` VARCHAR(255) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABLE 2: CATEGORIES
-- ===============================================
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABLE 3: PRODUCTS
-- ===============================================
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_name` VARCHAR(150) NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `category_id` INT NOT NULL,
  `image` VARCHAR(255) NULL,
  `description` TEXT NULL,
  `stock` INT DEFAULT 10,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_category_id` (`category_id`),
  INDEX `idx_product_name` (`product_name`),
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABLE 4: CART
-- ===============================================
CREATE TABLE `cart` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_product_id` (`product_id`),
  UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABLE 5: ORDERS
-- ===============================================
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `address` TEXT NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- TABLE 6: ORDER_ITEMS
-- ===============================================
CREATE TABLE `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order_id` (`order_id`),
  INDEX `idx_product_id` (`product_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================
-- SAMPLE DATA: USERS
-- ===============================================
-- Admin User: email=admin@electronics.com, password=admin123
-- Customer Users: password=customer123
INSERT INTO `users` (`fullname`, `email`, `password_hash`, `role`, `status`) VALUES
('Admin User', 'admin@electronics.com', '$2y$10$7S.rj8aVMKQCXM9lFYmQne3Hxc3kNSaY9s7gMqL8dPz5v7MvMIqzy', 'admin', 'active'),
('John Doe', 'john@example.com', '$2y$10$DmMqjvh5RZmQDPR8rCvjQe8Tc6MxRh0NqD3Sv7jVt1v2a5lKqQ5Bq', 'customer', 'active'),
('Jane Smith', 'jane@example.com', '$2y$10$DmMqjvh5RZmQDPR8rCvjQe8Tc6MxRh0NqD3Sv7jVt1v2a5lKqQ5Bq', 'customer', 'active'),
('Mike Johnson', 'mike@example.com', '$2y$10$DmMqjvh5RZmQDPR8rCvjQe8Tc6MxRh0NqD3Sv7jVt1v2a5lKqQ5Bq', 'customer', 'active');

-- ===============================================
-- SAMPLE DATA: CATEGORIES
-- ===============================================
INSERT INTO `categories` (`category_name`) VALUES
('Laptops'),
('Smartphones'),
('Tablets'),
('Headphones'),
('Cameras'),
('Monitors'),
('Keyboards'),
('Mice');

-- ===============================================
-- SAMPLE DATA: PRODUCTS
-- ===============================================
INSERT INTO `products` (`product_name`, `price`, `category_id`, `image`, `description`, `stock`) VALUES
('DELL PC', 999.99, 1, 'DELL PC.jpg', 'Ultra-portable laptop with stunning display', 15),
('Macbook Air', 1499.99, 1, 'Macbook Air.jpg', 'Powerful laptop for professionals', 12),
('Apple iPhone 15 Pro', 999.99, 2, 'Apple iPhone 15 Pro.jpg', 'Latest iPhone with advanced camera', 25),
('Infinix Smart 9', 799.99, 2, 'Infinix Smart 9.jpg', 'Flagship Android smartphone', 20),
('iPhone 11', 599.99, 2, 'iPhone 11.jpg', 'Premium smartphone with great camera', 18),
('ASUS PC', 1299.99, 1, 'ASUS PC.jpg', 'Gaming laptop with high performance', 10),
('LENOVO', 899.99, 1, 'LENOVO.jpg', 'Reliable business laptop', 12),
('HP PC', 749.99, 1, 'HP PC.jpg', 'Versatile laptop for everyday use', 14),
('Tecno Camon 40', 449.99, 2, 'tecno camon40.jpg', 'Smartphone with excellent camera quality', 22),
('Storage 1TB', 89.99, 1, '1tb.jpg', 'Fast NVMe storage drive', 50),
('Storage 4TB', 199.99, 1, '4 tb.jpg', 'Large capacity external storage', 35),
('Storage 500GB', 49.99, 1, '500gb.jpg', 'Compact portable storage', 45);

-- ===============================================
-- SAMPLE DATA: CART (Optional - usually empty on fresh DB)
-- ===============================================
-- Commented out - carts are usually populated by users
-- INSERT INTO `cart` (`user_id`, `product_id`, `quantity`) VALUES
-- (2, 1, 1),
-- (2, 4, 2),
-- (3, 6, 1);

-- ===============================================
-- SAMPLE DATA: ORDERS
-- ===============================================
INSERT INTO `orders` (`user_id`, `fullname`, `address`, `phone`, `total_amount`, `status`) VALUES
(2, 'John Doe', '123 Main Street, New York, NY 10001', '+1-234-567-8900', 1999.98, 'completed'),
(3, 'Jane Smith', '456 Oak Avenue, Los Angeles, CA 90001', '+1-234-567-8901', 499.99, 'completed'),
(4, 'Mike Johnson', '789 Pine Road, Chicago, IL 60601', '+1-234-567-8902', 1149.97, 'pending');

-- ===============================================
-- SAMPLE DATA: ORDER_ITEMS
-- ===============================================
INSERT INTO `order_items` (`order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 3, 1, 999.99),
(1, 6, 1, 999.99),
(2, 6, 1, 499.99),
(3, 1, 1, 999.99),
(3, 2, 1, 149.98);

-- ===============================================
-- CREATE INDEXES FOR BETTER PERFORMANCE
-- ===============================================
CREATE INDEX `idx_orders_user_status` ON `orders` (`user_id`, `status`);
CREATE INDEX `idx_order_items_order` ON `order_items` (`order_id`);
CREATE INDEX `idx_products_category_stock` ON `products` (`category_id`, `stock`);

-- ===============================================
-- DATABASE SETUP COMPLETE
-- ===============================================
-- Total Tables: 6
-- Users: 4 (1 admin, 3 customers)
-- Categories: 8
-- Products: 12
-- Orders: 3 (with order items)
-- ===============================================
