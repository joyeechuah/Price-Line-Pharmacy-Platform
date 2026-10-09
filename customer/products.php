<?php
// STEP 1: Start the session. Visitors can browse without logging in.
session_start();
require_once __DIR__ . '/../config/database.php';

function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user = $_SESSION['user'] ?? null;
$isCustomer = ($user['role'] ?? '') === 'customer';
$error = '';
$loadError = '';
$cartCount = 0;
$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);

// STEP 2: Read the search and category filters.
$search = trim((string) filter_input(INPUT_GET, 'q'));
$category = (string) filter_input(INPUT_GET, 'category');

// These five categories match the five product sections.
$sections = [
    ['id' => 'vitamins', 'name' => 'Vitamins & Supplements', 'category' => 'vitamins and supplements', 'banner' => '../images/customer-vitamins-banner.png'],
    ['id' => 'skincare', 'name' => 'Skincare', 'category' => 'skincare', 'banner' => '../images/customer-skincare-banner.png'],
    ['id' => 'mom-baby', 'name' => 'Mom & Baby', 'category' => 'mom and baby', 'banner' => '../images/customer-mom-baby-banner.png'],
    ['id' => 'medical', 'name' => 'Medical Supplies', 'category' => 'medical supplies', 'banner' => '../images/customer-medical-banner.png'],
    ['id' => 'healthy-food', 'name' => 'Healthy Food & Beverages', 'category' => 'healthy food and beverages', 'banner' => '../images/customer-healthy-food-banner.png']
];

if (!in_array($category, ['', 'vitamins', 'skincare', 'mom-baby', 'medical', 'healthy-food'], true)) {
    $category = '';
}

// Keep the same search after adding a product.
$pageUrl = 'products.php?' . http_build_query(['q' => $search, 'category' => $category]);

// STEP 3: Add a product to the logged-in customer's cart.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$user) {
        header('Location: ../login.php');
        exit;
    }

    $token = (string) filter_input(INPUT_POST, 'csrf_token');
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!$isCustomer) {
        http_response_code(403);
        $error = 'Only customer accounts can add products to a cart.';
    } elseif (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Please reload the page and try again.';
    } elseif (!$productId || $productId < 1 || !$quantity || $quantity < 1 || $quantity > 2147483647) {
        $error = 'Choose a product and enter a whole-number quantity of at least 1.';
    } else {
        try {
            // Keep the stock check and cart change together.
            $pdo->beginTransaction();
            // FOR UPDATE makes another add request wait until this one finishes.
            $stmt = $pdo->prepare('SELECT name, stock FROM products WHERE product_id = ? FOR UPDATE');
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) {
                $error = 'This product is no longer available.';
            } else {
                $stmt = $pdo->prepare('SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? ORDER BY cart_item_id');
                $stmt->execute([$user['user_id'], $productId]);
                $cartRows = $stmt->fetchAll();
                $alreadyInCart = 0;
                foreach ($cartRows as $row) {
                    $alreadyInCart += (int) $row['quantity'];
                }

                if ($alreadyInCart + $quantity > $product['stock']) {
                    $error = 'There is not enough stock for this quantity, including items already in your cart.';
                } elseif ($cartRows) {
                    // If the product is already in this cart, increase its quantity.
                    $stmt = $pdo->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE cart_item_id = ? AND user_id = ?');
                    $stmt->execute([$quantity, $cartRows[0]['cart_item_id'], $user['user_id']]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)');
                    $stmt->execute([$user['user_id'], $productId, $quantity]);
                }
            }

            if ($error === '') {
                $pdo->commit();
                $_SESSION['cart_message'] = $quantity . ' x ' . $product['name'] . ' added to your cart.';
                header('Location: ' . $pageUrl);
                exit;
            }
            $pdo->rollBack();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($e->getMessage());
            $error = 'Unable to add this product. Please try again.';
        }
    }
}

// STEP 4: Read the matching products and the customer's cart quantity.
$products = [];
try {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE ? OR description LIKE ? OR category LIKE ? ORDER BY name');
    $keyword = '%' . $search . '%';
    $stmt->execute([$keyword, $keyword, $keyword]);
    $products = $stmt->fetchAll();

    if ($isCustomer) {
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
        $stmt->execute([$user['user_id']]);
        $cartCount = (int) $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $loadError = 'Unable to load the product catalogue. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/customer.css">
</head>
<body class="customer-page" id="page-top">
    <header class="customer-header">
        <nav class="customer-nav" aria-label="Main navigation">
            <a href="../homepage.php">Home</a>
            <a href="products.php" aria-current="page">Products</a>
            <a href="../membership-benefits.php">Membership Benefits</a>
            <div class="customer-account-links">
                <?php if ($user): ?>
                    <form action="../logout.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                        <button type="submit">Log Out</button>
                    </form>
                <?php else: ?>
                    <a href="../login.php">Log In</a>
                    <a href="../register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>

        <div class="customer-intro">
            <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
            <h1>Shop everyday essentials</h1>
            <p>Browse our five categories and find what you need.</p>
            <?php if ($isCustomer && $loadError === ''): ?>
                <p>Welcome, <?php echo escape($user['name']); ?>! <strong>Items in cart: <?php echo $cartCount; ?></strong></p>
            <?php elseif ($user && !$isCustomer): ?>
                <p>Please use a customer account to add products to a cart.</p>
            <?php endif; ?>

            <form action="products.php" method="GET" class="customer-search" role="search">
                <div class="customer-search-field">
                    <label for="search">Search products</label>
                    <input type="search" class="customer-input" id="search" name="q" value="<?php echo escape($search); ?>" placeholder="Search vitamins, skincare and more...">
                </div>
                <div>
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo escape($section['id']); ?>" <?php if ($category === $section['id']) echo 'selected'; ?>><?php echo escape($section['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Search</button>
                <a href="products.php">Clear filters</a>
            </form>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($message !== ''): ?>
            <p class="customer-success" role="status"><?php echo escape($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo escape($error); ?></p>
        <?php endif; ?>
        <?php if ($loadError !== ''): ?>
            <p class="customer-error" role="alert"><?php echo escape($loadError); ?></p>
        <?php else: ?>
            <?php foreach ($sections as $section): ?>
                <?php
                if ($category !== '' && $category !== $section['id']) {
                    continue;
                }
                $sectionProducts = [];
                foreach ($products as $product) {
                    // Accept both "and" and "&" in the saved category name.
                    $productCategory = strtolower(trim(str_replace('&', 'and', $product['category'])));
                    if ($productCategory === $section['category']) {
                        $sectionProducts[] = $product;
                    }
                }
                ?>
                <section class="customer-category" id="<?php echo escape($section['id']); ?>" aria-labelledby="heading-<?php echo escape($section['id']); ?>">
                    <a class="customer-banner" href="#items-<?php echo escape($section['id']); ?>" aria-label="Browse <?php echo escape($section['name']); ?>">
                        <img src="<?php echo escape($section['banner']); ?>" alt="<?php echo escape($section['name']); ?>" width="2172" height="724" loading="lazy">
                    </a>
                    <div class="customer-category-heading" id="items-<?php echo escape($section['id']); ?>">
                        <h2 id="heading-<?php echo escape($section['id']); ?>"><?php echo escape($section['name']); ?></h2>
                        <p><?php echo count($sectionProducts); ?> products</p>
                    </div>

                    <?php if (!$sectionProducts): ?>
                        <p class="customer-empty"><?php echo $search !== '' ? 'No products match your search in this category.' : 'No products available in this category yet.'; ?></p>
                    <?php else: ?>
                        <div class="customer-product-grid">
                            <?php foreach ($sectionProducts as $product): ?>
                                <article class="customer-product-card">
                                    <!-- Placeholder for now. Later, use the product's saved image_url here. -->
                                    <img class="customer-product-image" src="../images/product-placeholder.svg" alt="Placeholder image for <?php echo escape($product['name']); ?>" width="320" height="240" loading="lazy">
                                    <h3><?php echo escape($product['name']); ?></h3>
                                    <p class="customer-description"><?php echo escape($product['description']); ?></p>
                                    <p class="customer-price">RM <?php echo number_format((float) $product['price'], 2); ?></p>
                                    <?php if ($product['stock'] < 1): ?>
                                        <p class="customer-out-of-stock">Out of stock</p>
                                        <button type="button" disabled>Out of Stock</button>
                                    <?php else: ?>
                                        <p class="customer-stock">In stock: <?php echo (int) $product['stock']; ?></p>
                                        <?php if ($isCustomer): ?>
                                            <form action="<?php echo escape($pageUrl); ?>" method="POST" class="customer-cart-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">
                                                <label for="quantity-<?php echo (int) $product['product_id']; ?>">Quantity</label>
                                                <input type="number" class="customer-input quantity-input" id="quantity-<?php echo (int) $product['product_id']; ?>" name="quantity" min="1" max="<?php echo (int) $product['stock']; ?>" value="1" required>
                                                <button type="submit">Add to Cart</button>
                                            </form>
                                        <?php elseif (!$user): ?>
                                            <a class="customer-shop-link" href="../login.php">Log In to Shop</a>
                                        <?php else: ?>
                                            <button type="button" disabled>Customer Account Required</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer class="customer-footer">
        <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
        <p>Better health. Brighter tomorrow.</p>
        <nav aria-label="Footer navigation">
            <a href="../homepage.php">Home</a>
            <a href="../membership-benefits.php">Membership Benefits</a>
            <a href="#page-top">Back to Top</a>
        </nav>
        <p>&copy; <?php echo date('Y'); ?> Price Line Pharmacy.</p>
    </footer>
</body>
</html>
