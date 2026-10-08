CREATE DATABASE price_line_pharmacy_dev;
USE price_line_pharmacy_dev;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(225) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM(
        'admin',
        'pharmacist',
        'storekeeper',
        'customer'
    ) NOT NULL DEFAULT 'customer',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE consultations (
    consultation_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_number VARCHAR(30) NOT NULL,
    preferred_datetime DATETIME NOT NULL,
    question TEXT NOT NULL,
    status ENUM(
        'pending',
        'contacted'
    ) NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255) NOT NULL
);

CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,

    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    delivery_address VARCHAR(500) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL,

    FOREIGN KEY (order_id) REFERENCES orders(order_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- All three demo staff accounts use the password: Pharmacy123!

USE price_line_pharmacy_dev;

START TRANSACTION;

INSERT INTO users (name, email, password_hash, role)
VALUES
    (
        'Demo Admin',
        'admin.demo@example.com',
        '$2y$10$0gkwnVp3vGxf757vDuujReMaaCW6jfz3a5xifCZkJhJMR9.qwm8o2',
        'admin'
    ),
    (
        'Demo Pharmacist',
        'pharmacist.demo@example.com',
        '$2y$10$0gkwnVp3vGxf757vDuujReMaaCW6jfz3a5xifCZkJhJMR9.qwm8o2',
        'pharmacist'
    ),
    (
        'Demo Storekeeper',
        'storekeeper.demo@example.com',
        '$2y$10$0gkwnVp3vGxf757vDuujReMaaCW6jfz3a5xifCZkJhJMR9.qwm8o2',
        'storekeeper'
    );

INSERT INTO products (name, description, category, price, stock, image_url)
VALUES
    (
        'Demo Vitamin C',
        'Sample vitamin product for testing the catalogue.',
        'Vitamins and Supplements',
        48.00,
        30,
        'images/flavettes vitamin c.jpg'
    ),
    (
        'Demo Fish Oil',
        'Sample supplement product for testing the catalogue.',
        'Vitamins and Supplements',
        89.00,
        20,
        'images/fish oil.jpg'
    ),
    (
        'Demo Moisturising Cream',
        'Sample skincare product for testing the catalogue.',
        'Skincare',
        65.00,
        15,
        'images/cerave moisturizing cream.jpg'
    ),
    (
        'Demo Hand Sanitizer',
        'Sample personal care product for testing the catalogue.',
        'Medical Supplies',
        11.50,
        40,
        'images/Dettol hand sanitizer 2 in 1.jpg'
    ),
    (
        'Demo Plasters',
        'Sample first aid product for testing the catalogue.',
        'Medical Supplies',
        15.00,
        25,
        'images/hansaplast plaster.jpg'
    );

COMMIT;

SELECT user_id, name, email, role
FROM users
WHERE email IN (
    'admin.demo@example.com',
    'pharmacist.demo@example.com',
    'storekeeper.demo@example.com'
);

SELECT product_id, name, category, price, stock, image_url
FROM products
WHERE name IN (
    'Demo Vitamin C',
    'Demo Fish Oil',
    'Demo Moisturising Cream',
    'Demo Hand Sanitizer',
    'Demo Plasters'
);

