<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart.php';

$cartCount = getCartCount($pdo);

// Homepage cards always use the same names and prices as the shop.
$stmt = $pdo->query('SELECT name, category, price, image_url FROM products WHERE is_active = 1 ORDER BY product_id');
$featured = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price Line Pharmacy: Your Health, Delivered Fast and Safe</title>
    <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB"
    crossorigin="anonymous"
>

    <link rel="stylesheet" href="CSS/homepage.css">
    <link rel="stylesheet" href="CSS/navbar.css">
    <link rel="stylesheet" href="CSS/footer.css">
</head>
<body id="page-top">
<?php $currentPage = 'home'; require __DIR__ . '/includes/navbar.php'; ?>
    <header class="header">
    <div class="header-hero-section">
    <h1 class="brand-heading"><img src="images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy" class="page-logo">Home</h1>
    <p class="brand-tagline">Your Health, Delivered Fast and Safe.</p>
    </div>
    <div id="pharmacyCarousel"
     class="carousel slide right-header"
     role="region"
     aria-roledescription="carousel"
     aria-label="Pharmacy highlights">

    <div class="carousel-inner">

        <!-- First banner -->
        <div class="carousel-item active">
            <img src="images/Price Line Pharmacy Hero Banner.png" alt="Price Line Pharmacy — quality healthcare within your reach">
        </div>

        <!-- Second banner -->
        <div class="carousel-item">
            <img src="images/HOT ITEMS Pharmacy banner 2.png" alt="Explore our vitamins and supplements">
        </div>

    </div>

    <button class="carousel-control-prev"
            type="button"
            data-bs-target="#pharmacyCarousel"
            data-bs-slide="prev">

        <span class="carousel-control-prev-icon"
              aria-hidden="true"></span>

        <span class="visually-hidden">Previous banner</span>
    </button>

    <button class="carousel-control-next"
            type="button"
            data-bs-target="#pharmacyCarousel"
            data-bs-slide="next">

        <span class="carousel-control-next-icon"
              aria-hidden="true"></span>

        <span class="visually-hidden">Next banner</span>
    </button>
    </div>
    <div class="hero-content">
    <h2>
    Browse health essentials, place an order online,
    and request a pharmacist consultation.
    </h2>
    <form action="customer/products.php" method="GET" role="search" class="search-form">
    <label for="site-search">Search Products</label>
    <div class="search-bar">
        <input
            type="search"
            id="site-search"
            name="q"
            placeholder="Search vitamins, skincare and more..."
        >
        <button type="submit">Search</button>
        </div>
    </form>
    </div>
    </header>
    <section class="section-2">
        <h2>Vitamins &amp; Supplements</h2>
        <p>Tailored nutrition for every body. Browse our pharmacist-approved supplements to support your family's health at every stage of life.</p>
        <img src="images/vitamins&amp;supplements.png" alt="Vitamins and Supplements">
        <h3 class="hot-items-heading">HOT ITEMS</h3>

    <div class="hot-items">
        <!-- Show the first three products in this category. -->
        <?php $shown = 0; ?>
        <?php foreach ($featured as $product): ?>
            <?php if ($product['category'] === 'Vitamins and Supplements' && $shown < 3): ?>
                <article class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p class="product-price">RM <?php echo number_format((float) $product['price'], 2); ?></p>
                    <a href="customer/products.php?category=vitamins&amp;q=<?php echo urlencode($product['name']); ?>" class="product-link">Browse Products</a>
                </article>
                <?php $shown++; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($shown === 0): ?>
            <p>No products available in this category yet.</p>
        <?php endif; ?>
    </div>
    </section>
    <section class="section-3">
        <h2>Skincare Essentials</h2>
        <p>Discover everyday skincare essentials designed to cleanse, hydrate, protect, and keep your skin feeling healthy and refreshed.</p>
        <img src="images/customer-skincare-banner.png" alt="Skincare essentials">
        <h3 class="hot-items-heading">HOT ITEMS</h3>

    <div class="hot-items">
        <!-- Show the first three products in this category. -->
        <?php $shown = 0; ?>
        <?php foreach ($featured as $product): ?>
            <?php if ($product['category'] === 'Skincare' && $shown < 3): ?>
                <article class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p class="product-price">RM <?php echo number_format((float) $product['price'], 2); ?></p>
                    <a href="customer/products.php?category=skincare&amp;q=<?php echo urlencode($product['name']); ?>" class="product-link">Browse Products</a>
                </article>
                <?php $shown++; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($shown === 0): ?>
            <p>No products available in this category yet.</p>
        <?php endif; ?>
    </div>
    </section>
    <section class="section-4">
        <h2>Medical Supplies</h2>
        <p>Find reliable medical supplies for everyday health needs, from first-aid essentials to home healthcare products, conveniently available in one place.</p>
        <img src="images/medical supplies.png" alt="Medical Supplies">
        <h3 class="hot-items-heading">HOT ITEMS</h3>

    <div class="hot-items">
        <!-- Show the first three products in this category. -->
        <?php $shown = 0; ?>
        <?php foreach ($featured as $product): ?>
            <?php if ($product['category'] === 'Medical Supplies' && $shown < 3): ?>
                <article class="product-card">
                    <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                    <p class="product-price">RM <?php echo number_format((float) $product['price'], 2); ?></p>
                    <a href="customer/products.php?category=medical&amp;q=<?php echo urlencode($product['name']); ?>" class="product-link">Browse Products</a>
                </article>
                <?php $shown++; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php if ($shown === 0): ?>
            <p>No products available in this category yet.</p>
        <?php endif; ?>
    </div>
    </section>

<?php require __DIR__ . '/includes/footer.php'; ?>

    <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous">
</script>

<script>
    const pharmacyCarousel = new bootstrap.Carousel(
        document.getElementById('pharmacyCarousel'),
        {
            interval: false,
            ride: false
        }
    );
</script>
</body>
</html>