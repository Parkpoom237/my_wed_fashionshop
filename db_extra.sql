-- Users
DROP TABLE IF EXISTS users;
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
email VARCHAR(191) UNIQUE NOT NULL,
password_hash VARCHAR(255) NOT NULL,
name VARCHAR(191) NOT NULL,
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Addresses
DROP TABLE IF EXISTS addresses;
CREATE TABLE addresses (
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT NOT NULL,
full_name VARCHAR(191) NOT NULL,
phone VARCHAR(50) NOT NULL,
line1 VARCHAR(255) NOT NULL,
line2 VARCHAR(255) NULL,
district VARCHAR(191) NOT NULL,
province VARCHAR(191) NOT NULL,
postcode VARCHAR(10) NOT NULL,
is_default TINYINT(1) NOT NULL DEFAULT 0,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Coupons (ย้ายจาก hardcode → DB)
DROP TABLE IF EXISTS coupons;
CREATE TABLE coupons (
code VARCHAR(64) PRIMARY KEY,
type ENUM('percent','fixed') NOT NULL,
value INT NOT NULL,
min_subtotal INT NOT NULL DEFAULT 0,
active TINYINT(1) NOT NULL DEFAULT 1,
expires_at DATETIME NULL
) ENGINE=InnoDB;

INSERT INTO coupons(code,type,value,min_subtotal,active) VALUES
('WELCOME100','fixed',100,0,1),
('MIX10','percent',10,500,1);

-- Inventory (จำนวนคงเหลือแยกไซซ์)
DROP TABLE IF EXISTS inventory;
CREATE TABLE inventory (
product_id VARCHAR(16) NOT NULL,
size VARCHAR(16) NOT NULL,
stock INT NOT NULL DEFAULT 0,
PRIMARY KEY (product_id,size),
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed สต็อกแบบเร็ว: ให้ทุกไซซ์มี 50
INSERT INTO inventory(product_id,size,stock)
SELECT p.id, JSON_EXTRACT(js.value,'$') AS size, 50
FROM products p
JOIN JSON_TABLE(p.sizes, '$[*]' COLUMNS(value JSON PATH '$')) js;

-- Product Images (หลายรูป/แรกจะเป็น cover)
DROP TABLE IF EXISTS product_images;
CREATE TABLE product_images (
id INT AUTO_INCREMENT PRIMARY KEY,
product_id VARCHAR(16) NOT NULL,
url VARCHAR(255) NOT NULL,
sort_order INT NOT NULL DEFAULT 0,
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Orders
DROP TABLE IF EXISTS orders;
CREATE TABLE orders (
id INT AUTO_INCREMENT PRIMARY KEY,
order_no VARCHAR(32) UNIQUE NOT NULL,
user_id INT NULL,
address_json JSON NOT NULL,
coupon_code VARCHAR(64) NULL,
subtotal INT NOT NULL,
discount INT NOT NULL,
shipping INT NOT NULL,
vat INT NOT NULL,
grand INT NOT NULL,
payment_method ENUM('COD','CARD') NOT NULL,
payment_status ENUM('PENDING','PAID','FAILED') NOT NULL DEFAULT 'PENDING',
status ENUM('NEW','PROCESSING','SHIPPED','DONE','CANCELLED') NOT NULL DEFAULT 'NEW',
created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
FOREIGN KEY (coupon_code) REFERENCES coupons(code) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Order Items
DROP TABLE IF EXISTS order_items;
CREATE TABLE order_items (
id INT AUTO_INCREMENT PRIMARY KEY,
order_id INT NOT NULL,
product_id VARCHAR(16) NOT NULL,
name VARCHAR(255) NOT NULL,
size VARCHAR(16) NOT NULL,
price INT NOT NULL,
qty INT NOT NULL,
line_total INT NOT NULL,
FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;
