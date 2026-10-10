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
</head>
<body id="page-top">
    <nav class="headbar" aria-label="Main navigation">
    <div class="pharmacy-navbar">
        <a href="homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
            <img src="images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
        </a>
        <a href="homepage.php" aria-current="page">Home</a>
        <a href="customer/products.php">Products</a>
        <a href="membership-benefits.php">Membership Benefits</a>
        <a href="customer/consultation.php" class="nav-button">E-Consult Now</a>
        <div class="account-links">
       <a href="login.php">Log In</a>
       <a href="register.php" class="register-link">Register</a>
       </div>
    </div>
    </nav>
    <header class="header">
    <div class="header-hero-section">
    <h1 class="brand-heading">
    Price <span class="brand-line">Line</span>
    <span class="brand-pharmacy">Pharmacy</span>

    <span class="brand-tagline">
        Your Health, Delivered Fast and Safe.
    </span>
    </h1>
    </div>
    <div id="pharmacyCarousel"
     class="carousel slide right-header"
     role="region"
     aria-roledescription="carousel"
     aria-label="Pharmacy highlights">

    <div class="carousel-inner">

        <!-- First banner -->
        <div class="carousel-item active">
            <img
                src="images/Price Line Pharmacy Hero Banner.png"
                alt="Price Line Pharmacy — quality healthcare within your reach"
            >
        </div>

        <!-- Second banner -->
        <div class="carousel-item">
            <img
                src="images/HOT ITEMS pharmacy banner.png"
                alt="Explore our vitamins and supplements"
            >
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
        <h2>Vitamins & Supplements</h2>
        <p>Tailored nutrition for every body. Browse our pharmacist-approved supplements to support your family's health at every stage of life.</p>
        <img src="images/Vitamins&Supplements.png" alt="vitamins&supplements">
        <h3 class="hot-items-heading">HOT ITEMS</h3>

    <div class="hot-items">
    <article class="product-card">
        <img src="images/flavettes vitamin c.jpg" alt="Flavettes Vitamin C">

        <h4>Flavettes Vitamin C 500mg (Orange Flavoured)</h4>
        <p class="product-price">RM 31.35</p>

        <a href="customer/products.php?category=vitamins" class="product-link">
            Browse Products
        </a>
    </article>

    <article class="product-card">
        <img src="images/fish oil.jpg" alt="Bio-Life Omega-3 Fish Oil">

        <h4>Bio-Life Omega-3 Fish Oil (2x100s)</h4>
        <p class="product-price">RM 131.10</p>

        <a href="customer/products.php?category=vitamins" class="product-link">
            Browse Products
        </a>
    </article>

    <article class="product-card">
        <img src="images/blackmoresmy2020multivitaminmineral120tabs200mlwithcode1-Photoroom.png" alt="Blackmores Multivitamins">

        <h4>Blackmores Multivitamins and Minerals (120s)</h4>
        <p class="product-price">RM 105.90</p>

        <a href="customer/products.php?category=vitamins" class="product-link">
            Browse Products
        </a>
    </article>
    </div>
    </section>
    <section class="section-3">
        <h2>Skincare Essentials</h2>
        <p>Discover everyday skincare essentials designed to cleanse, hydrate, protect, and keep your skin feeling healthy and refreshed.</p>
        <img src="images/customer-skincare-banner.png" alt="Skincare essentials">
        <h3 class="hot-items-heading">HOT ITEMS</h3>

    <div class="hot-items">
    <article class="product-card">
        <img src="images/cerave moisturizing cream.jpg" alt="Cerave Moisturizing Cream">

        <h4>Cerave Moisturizing Cream 340g</h4>
        <p class="product-price">RM 58.90</p>

        <a href="customer/products.php?category=skincare" class="product-link">
            Browse Products
        </a>
    </article>

    <article class="product-card">
        <img src="images/La roche posay gentle cleanser.png" alt="La Roche Posay Gentle Cleanser">

        <h4>La Roche Posay Toleriane Caring Wash 200ml</h4>
        <p class="product-price">RM 68.50</p>

        <a href="customer/products.php?category=skincare" class="product-link">
            Browse Products
        </a>
    </article>

    <article class="product-card">
        <img src="images/neutrogena hydro boost water gel.jpg" alt="Neutrogena Hydro Boost Water Gel">

        <h4>Neutrogena Hydro Boost Hyaluronic Acid Water Gel 50g</h4>
        <p class="product-price">RM 58.00</p>

        <a href="customer/products.php?category=skincare" class="product-link">
            Browse Products
        </a>
    </article>
    </div>
    </section>
    <section class="section-4">
        <h2>Medical Supplies</h2>
        <p>Find reliable medical supplies for everyday health needs, from first-aid essentials to home healthcare products—conveniently available in one place.</p>
        <img src="images/Medical Supplies.png" alt="Medical Supplies">
        <h3 class="hot-items-heading">HOT ITEMS</h3>

    <div class="hot-items">
    <article class="product-card">
        <img src="images/omron blood pressure monitor.jpg" alt="Omron Blood Pressure Monitor">

        <h4>Omron Automatic Blood Pressure Monitor HEM-7121</h4>
        <p class="product-price">RM 338.00</p>

        <a href="customer/products.php?category=medical" class="product-link">
            Browse Products
        </a>
    </article>

    <article class="product-card">
        <img src="images/Dettol hand sanitizer 2 in 1.jpg" alt="Dettol Hand Sanitizer 2 in 1 50ml">

        <h4>Dettol Hand Sanitizer 2 in 1 50ml</h4>
        <p class="product-price">RM 11.50</p>

        <a href="customer/products.php?category=medical" class="product-link">
            Browse Products
        </a>
    </article>

    <article class="product-card">
        <img src="images/hansaplast plaster.jpg" alt="Hansaplast Universal Plaster 100s">

        <h4>Hansaplast Universal Plaster 100s</h4>
        <p class="product-price">RM 15.00</p>

        <a href="customer/products.php?category=medical" class="product-link">
            Browse Products
        </a>
    </article>
    </div>
    </section>

    <footer class="home-footer">
    <div class="footer-content">

        <div class="footer-brand">
            <h2>Price <span>Line</span> Pharmacy</h2>
            <p>Your Health, Delivered Fast and Safe.</p>
            <p>
                Explore everyday health essentials,
                discover membership benefits and
                request a pharmacist consultation
            </p>
        </div>

        <div class="footer-links">
            <h3>Explore</h3>
            <a href="homepage.php">Home</a>
            <a href="customer/products.php">Products</a>
            <a href="membership-benefits.php">
                Membership Benefits
            </a>
            <a href="customer/consultation.php">
                E-Consult Now
            </a>
        </div>

        <div class="footer-links">
            <h3>Your Account</h3>
            <a href="login.php">Log In</a>
            <a href="register.php">Create an Account</a>
        </div>

    </div>

    <div class="footer-bottom">
        <p>&copy; 2026 Price Line Pharmacy.</p>
        <a href="#page-top">Back to top ↑</a>
    </div>
    </footer>

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