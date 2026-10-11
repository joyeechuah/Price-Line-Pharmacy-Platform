<?php
// STEP 1: Start the session. Visitors can browse without logging in.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cart.php';

$user = $_SESSION['user'] ?? null;
$isCustomer = isCustomer();
$error = '';
$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);

// STEP 2: Read the search and category filters.
$search   = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';

// These five categories match the five product sections.
$sections = [
    ['id' => 'vitamins', 'name' => 'Vitamins & Supplements', 'category' => 'Vitamins and Supplements', 'banner' => '../images/customer-vitamins-banner.png'],
    ['id' => 'skincare', 'name' => 'Skincare', 'category' => 'Skincare', 'banner' => '../images/customer-skincare-banner.png'],
    ['id' => 'mom-baby', 'name' => 'Mom & Baby', 'category' => 'Mom and Baby', 'banner' => '../images/customer-mom-baby-banner.png'],
    ['id' => 'medical', 'name' => 'Medical Supplies', 'category' => 'Medical Supplies', 'banner' => '../images/customer-medical-banner.png'],
    ['id' => 'healthy-food', 'name' => 'Healthy Food & Beverages', 'category' => 'Healthy Food and Beverages', 'banner' => '../images/customer-healthy-food-banner.png']
];

// Keep the same search after adding a product.
$pageUrl = 'products.php?q=' . urlencode($search) . '&category=' . urlencode($category);

// STEP 3: Add a product to a guest or customer cart.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity  = (int) ($_POST['quantity'] ?? 0);

    $stmt = $pdo->prepare('SELECT name, stock FROM products WHERE product_id = ? AND is_active = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Count what is already in the cart, so the stock is not exceeded.
    if ($isCustomer) {
        $inCart = getCartQuantity($pdo, $user['user_id'], $productId);
    } else {
        $inCart = $_SESSION['guest_cart'][$productId] ?? 0;
    }

    if ($user && !$isCustomer) {
        $error = 'Only customer accounts can add products to a cart.';
    } elseif (!$product) {
        $error = 'This product is no longer available.';
    } elseif ($quantity < 1) {
        $error = 'Enter a quantity of at least 1.';
    } elseif ($inCart + $quantity > $product['stock']) {
        $error = 'There is not enough stock for this quantity, including items already in your cart.';
    } else {
        if ($isCustomer) {
            addToCart($pdo, $user['user_id'], $productId, $quantity);
        } else {
            // Guest carts store product IDs and quantities in the session.
            $_SESSION['guest_cart'][$productId] = $inCart + $quantity;
        }

        $_SESSION['cart_message'] = $quantity . ' x ' . $product['name'] . ' added to your cart.';
        header('Location: ' . $pageUrl);
        exit;
    }
}

// STEP 4: Read the matching products and the cart quantity.
$stmt = $pdo->prepare('SELECT * FROM products WHERE is_active = 1
    AND (name LIKE ? OR description LIKE ? OR category LIKE ?) ORDER BY name');
$keyword = '%' . $search . '%';
$stmt->execute([$keyword, $keyword, $keyword]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cartCount = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/customer.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<body class="customer-page" id="page-top">
    <nav class="headbar" aria-label="Main navigation">
        <div class="pharmacy-navbar">
            <a href="../homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
                <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
            </a>
            <a href="../homepage.php">Home</a>
            <a href="products.php" aria-current="page">Products</a>
            <a href="../membership-benefits.php">Membership Benefits</a>
            <?php if (isGuest() || isCustomer()): ?>
                <a href="consultation.php" class="nav-button" >E-Consult Now</a>
            <?php endif; ?>
            <div class="account-links">
                <?php if (isGuest() || isCustomer()): ?>
                    <a href="cart.php" class="navbar-cart" >
                        <img src="../images/cart-icon.svg" alt="" width="24" height="24">
                        <span>Cart (<?php echo $cartCount; ?>)</span>
                    </a>
                <?php endif; ?>
                <?php if (!isGuest()): ?>
                    <?php if (isCustomer()): ?>
                        <a href="orders.php" >My Orders</a>
                    <?php elseif (isAdmin()): ?>
                        <a href="../admin/dashboard.php">Staff Panel</a>
                    <?php elseif (isPharmacist()): ?>
                        <a href="../pharmacist/dashboard.php">Staff Panel</a>
                    <?php elseif (isStorekeeper()): ?>
                        <a href="../admin/products.php">Staff Panel</a>
                    <?php endif; ?>
                    <form action="../logout.php" method="POST">
                        <button type="submit">Log Out</button>
                    </form>
                <?php else: ?>
                    <a href="../login.php">Log In</a>
                    <a href="../register.php" class="register-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <header class="customer-header">

        <div class="customer-intro">
            <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy" class="page-logo">
            <h1>Shop everyday essentials</h1>
            <p>Browse our five categories and find what you need.</p>
            <?php if ($isCustomer): ?>
                <p>Welcome, <?php echo htmlspecialchars($user['name']); ?>! <strong>Items in cart: <?php echo $cartCount; ?></strong></p>
            <?php elseif ($user && !$isCustomer): ?>
                <p>Please use a customer account to add products to a cart.</p>
            <?php endif; ?>

            <form action="products.php" method="GET" class="customer-search" role="search">
                <div class="customer-search-field">
                    <label for="search">Search products</label>
                    <input type="search" class="customer-input" id="search" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search vitamins, skincare and more...">
                </div>
                <div>
                    <label for="category">Category</label>
                    <select id="category" name="category">
                        <option value="">All categories</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo htmlspecialchars($section['id']); ?>" <?php if ($category === $section['id']) echo 'selected'; ?>><?php echo htmlspecialchars($section['name']); ?></option>
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
            <p class="customer-success" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php foreach ($sections as $section): ?>
            <?php
            if ($category !== '' && $category !== $section['id']) {
                continue;
            }
            $sectionProducts = [];
            foreach ($products as $product) {
                if ($product['category'] === $section['category']) {
                    $sectionProducts[] = $product;
                }
            }
            ?>
            <section class="customer-category" id="<?php echo htmlspecialchars($section['id']); ?>" aria-labelledby="heading-<?php echo htmlspecialchars($section['id']); ?>">
                <a class="customer-banner" href="#items-<?php echo htmlspecialchars($section['id']); ?>" aria-label="Browse <?php echo htmlspecialchars($section['name']); ?>">
                    <img src="<?php echo htmlspecialchars($section['banner']); ?>" alt="<?php echo htmlspecialchars($section['name']); ?>" width="2172" height="724" loading="lazy">
                </a>
                <div class="customer-category-heading" id="items-<?php echo htmlspecialchars($section['id']); ?>">
                    <h2 id="heading-<?php echo htmlspecialchars($section['id']); ?>"><?php echo htmlspecialchars($section['name']); ?></h2>
                    <p><?php echo count($sectionProducts); ?> <?php echo count($sectionProducts) === 1 ? 'product' : 'products'; ?></p>
                </div>

                <?php if (!$sectionProducts): ?>
                    <p class="customer-empty"><?php echo $search !== '' ? 'No products match your search in this category.' : 'No products available in this category yet.'; ?></p>
                <?php else: ?>
                    <div class="customer-product-grid">
                        <?php foreach ($sectionProducts as $product): ?>
                            <article class="customer-product-card">
                                <!-- Placeholder for now. Later, use the product's saved image_url here. -->
                                <img class="customer-product-image" src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="customer-description"><?php echo htmlspecialchars($product['description']); ?></p>
                                <p class="customer-price">RM <?php echo number_format((float) $product['price'], 2); ?></p>
                                <?php if ($product['stock'] < 1): ?>
                                    <p class="customer-out-of-stock">Out of stock</p>
                                    <button type="button" disabled>Out of Stock</button>
                                <?php else: ?>
                                    <?php if (!$user || $isCustomer): ?>
                                        <form action="<?php echo htmlspecialchars($pageUrl); ?>" method="POST" class="customer-cart-form">
                                            <input type="hidden" name="product_id" value="<?php echo (int) $product['product_id']; ?>">
                                            <label for="quantity-<?php echo (int) $product['product_id']; ?>">Quantity</label>
                                            <input type="number" class="customer-input quantity-input" id="quantity-<?php echo (int) $product['product_id']; ?>" name="quantity" min="1" max="<?php echo (int) $product['stock']; ?>" value="1" required>
                                            <button type="submit">Add to Cart</button>
                                        </form>
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
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
