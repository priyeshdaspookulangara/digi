-- Database Schema for MLM Marketplace (MySQL Compatible)

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'shop_owner', 'member') DEFAULT 'member',
    referrer_id INT,
    level INT DEFAULT 1,
    rebirth_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shop_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    logo VARCHAR(255),
    wallpaper VARCHAR(255),
    meta_keywords TEXT,
    og_title VARCHAR(255),
    og_description TEXT,
    locality VARCHAR(255),
    category VARCHAR(255),
    type ENUM('privilege', 'classic', 'free_listing') DEFAULT 'free_listing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    discount_entry VARCHAR(255),
    image VARCHAR(255),
    video VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    meta_keywords TEXT,
    og_title VARCHAR(255),
    og_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS mlm_hierarchy (
    user_id INT PRIMARY KEY,
    parent_id INT,
    level_in_tree INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type VARCHAR(50), -- 'entry_fee', 'commission', 'rebirth', 'royalty'
    bucket VARCHAR(50), -- 'Burfee', 'Level', 'Royalty Chamber', 'Platform', 'Reserve'
    status VARCHAR(20) DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    key_value TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    product_id INT,
    customer_name VARCHAR(255),
    customer_email VARCHAR(255),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    discount_percent DECIMAL(5, 2),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed Initial Data
INSERT IGNORE INTO users (username, password, email, role) VALUES ('admin', '$2y$10$V9h4PcIocyO/Q5xl1FX//u/ka7bxSHo1SpKlF3RjOG4oVmp4z6gXC', 'admin@example.com', 'admin');

INSERT IGNORE INTO shop_categories (name) VALUES ('Electronics'), ('Fashion'), ('Home'), ('Beauty'), ('Sports'), ('Grocery'), ('Automobile');

CREATE TABLE IF NOT EXISTS localities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    district VARCHAR(255) DEFAULT 'Thrissur'
) ENGINE=InnoDB;

INSERT IGNORE INTO localities (name, latitude, longitude) VALUES
('Thrissur City', 10.52, 76.21), ('Chalakudy', 10.31, 76.33), ('Chavakkad', 10.58, 76.02),
('Guruvayur', 10.59, 76.04), ('Irinjalakuda', 10.35, 76.21), ('Kodungallur', 10.23, 76.20),
('Kunnamkulam', 10.65, 76.07), ('Wadakkanchery', 10.65, 76.24), ('Kaipamangalam', 10.33, 76.14),
('Nattika', 10.43, 76.09), ('Triprayar', 10.41, 76.13), ('Eriyad', 10.22, 76.16),
('Azhikode', 11.92, 75.34), ('Perinjanam', 10.31, 76.15), ('Mathilakam', 10.29, 76.16),
('Thalikulam', 10.46, 76.08), ('Mullassery', 10.54, 76.09), ('Ayyanthole', 10.52, 76.19),
('Punkunnam', 10.54, 76.20), ('Ollur', 10.48, 76.24), ('Mannuthy', 10.54, 76.27),
('Patturaikkal', 10.53, 76.21), ('Amalanagar', 10.56, 76.17), ('Kolazhy', 10.58, 76.22),
('Nadathara', 10.51, 76.27), ('Puzhakkal', 10.55, 76.18), ('Kuriachira', 10.50, 76.23),
('Thiruvilwamala', 10.68, 76.33), ('Cheruthuruthy', 10.74, 76.28), ('Chelakkara', 10.69, 76.35),
('Puthukkad', 10.42, 76.28), ('Mala', 10.24, 76.26), ('Puthenchira', 10.28, 76.28),
('Peechi', 10.53, 76.36), ('Koratty', 10.26, 76.35), ('Pattikad', 10.55, 76.34),
('Alagappa Nagar', 10.43, 76.27), ('Adat', 10.55, 76.15), ('Vadakkumkara', 10.50, 76.20);

INSERT IGNORE INTO localities (name, latitude, longitude, district) VALUES
('Aluva', 10.1075, 76.3457, 'Ernakulam'),
('Angamaly', 10.1915, 76.3820, 'Ernakulam'),
('Cherai', 10.1416, 76.1783, 'Ernakulam'),
('Edappally', 10.0261, 76.3088, 'Ernakulam'),
('Eloor', 10.0632, 76.2974, 'Ernakulam'),
('Ernakulam', 9.9816, 76.2999, 'Ernakulam'),
('Fort Kochi', 9.9648, 76.2421, 'Ernakulam'),
('Kadavanthra', 9.9675, 76.2991, 'Ernakulam'),
('Kakkanad', 10.0159, 76.3419, 'Ernakulam'),
('Kalamassery', 10.0542, 76.3120, 'Ernakulam'),
('Kaloor', 10.0031, 76.2997, 'Ernakulam'),
('Koothattukulam', 9.8732, 76.5599, 'Ernakulam'),
('Kothamangalam', 10.0617, 76.6214, 'Ernakulam'),
('Maradu', 9.9472, 76.3149, 'Ernakulam'),
('Mattancherry', 9.9591, 76.2573, 'Ernakulam'),
('Mulanthuruthy', 9.9022, 76.3888, 'Ernakulam'),
('Muvattupuzha', 9.9874, 76.5816, 'Ernakulam'),
('Nedumbassery', 10.1518, 76.3908, 'Ernakulam'),
('North Paravur', 10.1436, 76.2250, 'Ernakulam'),
('Palarivattom', 10.0076, 76.3115, 'Ernakulam'),
('Perumbavoor', 10.1143, 76.4829, 'Ernakulam'),
('Piravom', 9.8752, 76.4913, 'Ernakulam'),
('Thrikkakara', 10.0292, 76.3283, 'Ernakulam'),
('Thrippunithura', 9.9482, 76.3458, 'Ernakulam'),
('Vypin', 9.9788, 76.2236, 'Ernakulam'),
('Vyttila', 9.9671, 76.3218, 'Ernakulam'),
('Peruvaram', 10.1464, 76.2163, 'Ernakulam'),
('Moothakunnam', 10.1916, 76.1950, 'Ernakulam'),
('Munambam', 10.1873, 76.1821, 'Ernakulam'),
('Munambam Junction', 10.1812, 76.1855, 'Ernakulam'),
('Pattanam', 10.1554, 76.2154, 'Ernakulam'),
('Chittattukara', 10.1433, 76.2082, 'Ernakulam'),
('Pooyappilly', 10.1264, 76.2110, 'Ernakulam');

INSERT IGNORE INTO site_settings (key_name, key_value) VALUES ('entry_fee', '3000.00');
INSERT IGNORE INTO site_settings (key_name, key_value) VALUES ('level_commission', '100.00');
INSERT IGNORE INTO site_settings (key_name, key_value) VALUES ('rebirth_milestone', '10');

CREATE TABLE IF NOT EXISTS banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    target_url VARCHAR(255),
    title VARCHAR(255),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    views INT DEFAULT 0,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(255) NOT NULL,
    target_url VARCHAR(255),
    position VARCHAR(50),
    is_active TINYINT(1) DEFAULT 1,
    views INT DEFAULT 0,
    clicks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
