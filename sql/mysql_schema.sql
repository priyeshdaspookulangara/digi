-- Database Schema for MLM Marketplace (MySQL Compatible)

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    role ENUM('admin', 'shop_owner', 'member', 'agent') DEFAULT 'member',
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
    added_by_agent_id INT,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (added_by_agent_id) REFERENCES users(id) ON DELETE SET NULL
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
    longitude DECIMAL(11, 8)
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

CREATE TABLE IF NOT EXISTS professional_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS portfolios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    professional_name VARCHAR(255) NOT NULL,
    category_id INT,
    description TEXT,
    experience_years INT,
    skills TEXT,
    services TEXT,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    locality VARCHAR(255),
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES professional_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO professional_categories (name) VALUES
('Software Engineer'), ('Graphic Designer'), ('Photographer'), ('Plumber'), ('Electrician'), ('Tutor'), ('Accountant'), ('Makeup Artist'), ('Consultant');

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
