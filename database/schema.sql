-- ============================================================
-- Inventory Management System - schema.sql
-- Jalankan di phpMyAdmin (tab Import) atau: mysql -u root < schema.sql
-- Akun demo: admin / admin123  dan  staff / staff123
-- ============================================================
CREATE DATABASE IF NOT EXISTS inventory_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS stock_movements, product_suppliers, products, suppliers, categories, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
    id          INT PRIMARY KEY AUTO_INCREMENT,
    name        VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id      INT PRIMARY KEY AUTO_INCREMENT,
    name    VARCHAR(100) NOT NULL,
    phone   VARCHAR(30)  NULL,
    email   VARCHAR(100) NULL,
    address VARCHAR(255) NULL
) ENGINE=InnoDB;

-- One-to-many: categories 1 --- N products
CREATE TABLE products (
    id            INT PRIMARY KEY AUTO_INCREMENT,
    category_id   INT NOT NULL,
    sku           VARCHAR(50)  NOT NULL UNIQUE,
    name          VARCHAR(150) NOT NULL,
    price         DECIMAL(12,2) NOT NULL,
    stock         INT NOT NULL DEFAULT 0,
    minimum_stock INT NOT NULL DEFAULT 5,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_price_nonneg CHECK (price >= 0),
    CONSTRAINT chk_stock_nonneg CHECK (stock >= 0),
    CONSTRAINT chk_minstock_nonneg CHECK (minimum_stock >= 0),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_products_name (name)
) ENGINE=InnoDB;

-- Many-to-many: products N --- N suppliers (junction table)
CREATE TABLE product_suppliers (
    product_id     INT NOT NULL,
    supplier_id    INT NOT NULL,
    supplier_price DECIMAL(12,2) NULL,
    PRIMARY KEY (product_id, supplier_id),
    CONSTRAINT fk_ps_product  FOREIGN KEY (product_id)  REFERENCES products(id)  ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ps_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Histori barang masuk/keluar (RESTRICT: produk/user berhistori tidak bisa dihapus)
CREATE TABLE stock_movements (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id    INT NOT NULL,
    type       ENUM('IN','OUT') NOT NULL,
    quantity   INT NOT NULL,
    note       VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_qty_positive CHECK (quantity > 0),
    CONSTRAINT fk_sm_product FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_sm_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON UPDATE CASCADE ON DELETE RESTRICT,
    INDEX idx_sm_product (product_id),
    INDEX idx_sm_created (created_at)
) ENGINE=InnoDB;

-- ===================== DATA CONTOH =====================
INSERT INTO users (username, full_name, password_hash, role) VALUES
('admin', 'Administrator', '$2b$10$wHbUrEUrcZMKC0Si3M46vuBZjuXumAzM1Tnf4mGfVWQFZjhGgmF7C', 'admin'),
('staff', 'Staff Gudang',  '$2b$10$vi94ztw4DF0lptJ25Q955uv5PQ30vX1Okztg6AeiRbnpT4dt4sVRO', 'staff');

INSERT INTO categories (name, description) VALUES
('Elektronik', 'Perangkat elektronik'),
('Aksesoris Komputer', 'Keyboard, mouse, dan sejenisnya'),
('Alat Tulis', 'Kebutuhan kantor');

INSERT INTO suppliers (name, phone, email) VALUES
('PT Maju Jaya', '061-555-0101', 'sales@majujaya.test'),
('CV Sumber Teknik', '061-555-0202', 'info@sumberteknik.test');

INSERT INTO products (category_id, sku, name, price, minimum_stock) VALUES
(2, 'KBD-001', 'Keyboard Mechanical', 350000, 5),
(2, 'MSE-001', 'Mouse Wireless',      120000, 5),
(1, 'MON-001', 'Monitor 24 inci',    1800000, 3),
(3, 'PEN-001', 'Pulpen Hitam (box)',   45000, 10);

INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES
(1,1,300000),(1,2,310000),(2,1,100000),(3,2,1600000),(4,1,38000);

-- Stok awal dicatat sebagai histori IN, lalu stok dihitung dari histori
INSERT INTO stock_movements (product_id, user_id, type, quantity, note) VALUES
(1,1,'IN',20,'Stok awal'),(2,1,'IN',15,'Stok awal'),
(3,1,'IN',6,'Stok awal'),(4,1,'IN',8,'Stok awal'),
(1,2,'OUT',4,'Penjualan'),(3,2,'OUT',4,'Penjualan'),(4,2,'OUT',1,'Pemakaian kantor');

UPDATE products p SET stock = (
    SELECT COALESCE(SUM(CASE WHEN sm.type='IN' THEN sm.quantity ELSE -sm.quantity END), 0)
    FROM stock_movements sm WHERE sm.product_id = p.id
);
