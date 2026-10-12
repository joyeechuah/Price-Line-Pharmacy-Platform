CREATE DATABASE price_line_pharmacy_dev;
USE price_line_pharmacy_dev;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
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
    image_url VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
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

-- All three staff accounts use the password: Pharmacy123!

INSERT INTO users (name, email, password_hash, role)
VALUES
    (
        'Admin',
        'admin.demo@example.com',
        '$2y$10$0gkwnVp3vGxf757vDuujReMaaCW6jfz3a5xifCZkJhJMR9.qwm8o2',
        'admin'
    ),
    (
        'Pharmacist',
        'pharmacist.demo@example.com',
        '$2y$10$0gkwnVp3vGxf757vDuujReMaaCW6jfz3a5xifCZkJhJMR9.qwm8o2',
        'pharmacist'
    ),
    (
        'Storekeeper',
        'storekeeper.demo@example.com',
        '$2y$10$0gkwnVp3vGxf757vDuujReMaaCW6jfz3a5xifCZkJhJMR9.qwm8o2',
        'storekeeper'
    );

INSERT INTO products (name, description, category, price, stock, image_url)
VALUES
    (
        'Flavettes Vitamin C 500mg (60s)',
        'Flavettes Vitamin C 500mg (60s) is a sugar-free, orange-flavored chewable dietary supplement formulated to support immune health and provide antioxidant protection.',
        'Vitamins and Supplements',
        31.35,
        30,
        'images/flavettes vitamin c.jpg'
    ),
    (
        'BiO-LiFE Fish Oil (2 x 100s)',
        'BiO-LiFE fish oil and omega-3 supplement is designed for heart, brain, and general well-being.',
        'Vitamins and Supplements',
        131.10,
        20,
        'images/fish oil.jpg'
    ),
    (
        'CeraVe Moisturizing Cream 454g',
        'CeraVe Moisturizing Cream is a rich, non-greasy, fast-absorbing daily moisturizer designed for normal to dry skin on both the face and body.',
        'Skincare',
        58.90,
        15,
        'images/cerave moisturizing cream.jpg'
    ),
    (
        'Dettol 2 in 1 Hand Sanitizer 50ml',
        'Dettol 2-in-1 Sanitizer Spray is a portable, aerosol-format sanitizer designed to kill 99.9% of germs on both hands and hard or soft surfaces without needing water.',
        'Medical Supplies',
        11.50,
        40,
        'images/Dettol hand sanitizer 2 in 1.jpg'
    ),
    (
        'Hansaplast Plaster 100s',
        'Hansaplast Plaster 100s comes in common everyday variants like Universal Water-Resistant and Elastic Extra Flexible strips, packaged with 100 pieces per box.',
        'Medical Supplies',
        15.00,
        25,
        'images/hansaplast plaster.jpg'
    ),
    (
        'Blackmores Multivitamins and Minerals 120s',
        'Blackmores Multivitamins and Minerals 120s is a daily nutritional supplement designed to fill dietary gaps, support energy production, and maintain overall health.',
        'Vitamins and Supplements',
        105.90,
        20,
        'images/blackmoresmy2020multivitaminmineral120tabs200mlwithcode1-Photoroom.png'
    ),
    (
        'La Roche-Posay Toleriane Caring Wash 200ml',
        'La Roche-Posay Toleriane Caring Wash 200ml is a gentle, hydrating facial cleanser and makeup remover formulated for normal to dry, sensitive skin',
        'Skincare',
        68.50,
        15,
        'images/La roche posay gentle cleanser.png'
    ),
    (
        'Neutrogena Hydro Boost Water Gel 50g',
        'Neutrogena Hydro Boost Water Gel 50g is an ultra-lightweight, oil-free face moisturizer designed to deliver intense, long-lasting hydration and strengthen the skin barrier',
        'Skincare',
        58.00,
        15,
        'images/neutrogena hydro boost water gel.jpg'
    ),
    (
        'Omron Blood Pressure Monitor HEM-7121',
        'Omron Blood Pressure Monitor HEM-7121 is a fully automatic upper-arm blood pressure monitor designed for simple and reliable home health tracking.',
        'Medical Supplies',
        338.00,
        10,
        'images/omron blood pressure monitor.jpg'
    ),
    (
        'Cetaphil Baby Daily Lotion 400ml',
        'Cetaphil Baby Daily Lotion 400ml is a gentle, dermatologically tested moisturizer formulated with shea butter to provide 24-hour hydration and protect delicate or sensitive baby skin from dryness.',
        'Mom and Baby',
        58.00,
        10,
        'images/cetaphil baby lotion.jpg'
    ),
    (
        'Friso Gold Step 3 900g',
        'Friso Gold Step 3 is a growing-up milk formula designed for toddlers aged 1 to 3 years old',
        'Mom and Baby',
        98.60,
        10,
        'images/Friso Gold Step 3 900G.jpg'
    ),
    (
        'Huggies Dry Pants Pampers XL 40s',
        'Huggies Dry Pants Size XL (40s) are pull-on diaper pants designed for toddlers weighing 12-17 kg, featuring an X-shaped absorbent core and up to 10 hours of wetness protection.',
        'Mom and Baby',
        46.00,
        8,
        'images/huggies dry pants diapers XL 40s.jpg'
    ),
    (
        'Nutrabliss Organic Instant Oat 500g',
        'Nutrabliss Organic Instant Oat''s Beta Glucan from oat helps lower or reduce cholesterol. Amount recommended for cholesterol lowering effect is 3G/Day. It is 100% Natural.',
        'Healthy Food and Beverages',
        9.90,
        15,
        'images/Nutrabliss organic instant oat.jpg'
    ),
    (
        'Oatside Oat Milk 1L',
        'OATSIDE Plain Oat Milk is dairy free which you can add to coffee, tea or even have on its own.',
        'Healthy Food and Beverages',
        9.90,
        10,
        'images/Oatside Oat Milk.jpg'
    ),
    (
        'Nutri Grains Plus With 6 Black 30gx15s',
        'NH Nutri Grains Plus with 6-Black is a multi grain drink that combines 18 types of grains with 6 nutritious black ingredients and enhanced with Medium- Chain Triglycerides (MCTs), Coconut Oil Powder and Vitamin B12.',
        'Healthy Food and Beverages',
        37.80,
        10,
        'images/nutri grains plus cereal drink.jpg'
    );

SELECT user_id, name, email, role
FROM users
WHERE email IN (
    'admin.demo@example.com',
    'pharmacist.demo@example.com',
    'storekeeper.demo@example.com'
);

SELECT product_id, name, category, price, stock, image_url
FROM products
ORDER BY product_id;
