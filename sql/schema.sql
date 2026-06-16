-- Database Schema for MLM Marketplace (SQLite Compatible)

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    role TEXT CHECK(role IN ('admin', 'shop_owner', 'member')) DEFAULT 'member',
    referrer_id INTEGER,
    level INTEGER DEFAULT 1,
    rebirth_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS shop_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS shops (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    logo TEXT,
    wallpaper TEXT,
    meta_keywords TEXT,
    og_title TEXT,
    og_description TEXT,
    locality TEXT,
    category TEXT,
    type TEXT CHECK(type IN ('privilege', 'classic', 'free_listing')) DEFAULT 'free_listing',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shop_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT,
    price REAL NOT NULL,
    discount_entry TEXT,
    image TEXT,
    video TEXT,
    is_featured INTEGER DEFAULT 0,
    meta_keywords TEXT,
    og_title TEXT,
    og_description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id)
);

CREATE TABLE IF NOT EXISTS mlm_hierarchy (
    user_id INTEGER PRIMARY KEY,
    parent_id INTEGER,
    level_in_tree INTEGER,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    amount REAL NOT NULL,
    type TEXT, -- 'entry_fee', 'commission', 'rebirth', 'royalty'
    bucket TEXT, -- 'Burfee', 'Level', 'Royalty Chamber', 'Platform', 'Reserve'
    status TEXT DEFAULT 'completed',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    key_name TEXT UNIQUE NOT NULL,
    key_value TEXT
);

CREATE TABLE IF NOT EXISTS enquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shop_id INTEGER NOT NULL,
    product_id INTEGER,
    customer_name TEXT,
    customer_email TEXT,
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS offers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    shop_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    discount_percent REAL,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id)
);

CREATE TABLE IF NOT EXISTS cart (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE IF NOT EXISTS wishlist (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Seed Initial Data
INSERT OR IGNORE INTO users (username, password, email, role) VALUES ('admin', '$2y$10$V9h4PcIocyO/Q5xl1FX//u/ka7bxSHo1SpKlF3RjOG4oVmp4z6gXC', 'admin@example.com', 'admin');

INSERT OR IGNORE INTO shop_categories (name) VALUES ('Electronics'), ('Fashion'), ('Home'), ('Beauty'), ('Sports'), ('Grocery'), ('Automobile');

CREATE TABLE IF NOT EXISTS localities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    latitude REAL,
    longitude REAL
);

INSERT OR IGNORE INTO localities (name, latitude, longitude) VALUES
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

INSERT OR IGNORE INTO site_settings (key_name, key_value) VALUES ('entry_fee', '3000.00');
INSERT OR IGNORE INTO site_settings (key_name, key_value) VALUES ('level_commission', '100.00');
INSERT OR IGNORE INTO site_settings (key_name, key_value) VALUES ('rebirth_milestone', '10'); -- Rebirth every 10 referrals for example

CREATE TABLE IF NOT EXISTS banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    image_url TEXT NOT NULL,
    target_url TEXT,
    title TEXT,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    display_order INTEGER DEFAULT 0,
    views INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS ads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    image_url TEXT NOT NULL,
    target_url TEXT,
    position TEXT, -- e.g., 'below_hero_left', 'below_hero_right'
    is_active INTEGER DEFAULT 1,
    views INTEGER DEFAULT 0,
    clicks INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
