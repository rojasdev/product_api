-- ============================================================
-- schema.sql
-- Creates a `products` table and seeds it with sample random data.
-- Target: MySQL 5.7+ / MariaDB 10.2+
-- ============================================================

CREATE DATABASE IF NOT EXISTS demo_shop
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE demo_shop;

DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sku             VARCHAR(32)     NOT NULL UNIQUE,
    name            VARCHAR(150)    NOT NULL,
    description     TEXT            NULL,
    category        VARCHAR(60)     NOT NULL,
    price           DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    stock_quantity  INT UNSIGNED    NOT NULL DEFAULT 0,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed data: a handful of "random" products across categories
-- ------------------------------------------------------------
INSERT INTO products (sku, name, description, category, price, stock_quantity, is_active) VALUES
('SKU-0001', 'Wireless Mouse X200',        'Ergonomic 2.4GHz wireless mouse with adjustable DPI.',   'Electronics', 19.99,  152, 1),
('SKU-0002', 'Mechanical Keyboard TKL',    'Tenkeyless mechanical keyboard, hot-swappable switches.', 'Electronics', 74.50,   64, 1),
('SKU-0003', 'Stainless Steel Water Bottle','1L double-wall insulated bottle, keeps drinks cold 24h.','Home & Kitchen', 15.75,  310, 1),
('SKU-0004', 'Organic Green Tea (100 bags)','Loose-leaf organic green tea, 100 individually wrapped bags.', 'Groceries', 8.99, 500, 1),
('SKU-0005', 'Yoga Mat Premium',           'Non-slip 6mm thick yoga mat with carrying strap.',        'Sports & Outdoors', 29.99, 87, 1),
('SKU-0006', 'Bluetooth Over-Ear Headphones','Noise-cancelling headphones, 30h battery life.',         'Electronics', 89.00,  42, 1),
('SKU-0007', 'Ceramic Coffee Mug Set (4pc)','Set of 4 handcrafted ceramic mugs, 350ml each.',          'Home & Kitchen', 22.40, 120, 1),
('SKU-0008', 'Running Shoes Trail Pro',    'Breathable trail running shoes with reinforced grip.',    'Sports & Outdoors', 64.99, 58, 1),
('SKU-0009', 'Notebook A5 Dotted (3-pack)','3-pack dotted-grid notebooks, 160 pages each.',            'Office Supplies', 13.20, 200, 1),
('SKU-0010', 'USB-C Fast Charger 65W',     'GaN 65W USB-C wall charger, compact form factor.',        'Electronics', 34.99, 95, 0);
