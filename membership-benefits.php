<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart.php';

$user = $_SESSION['user'] ?? null;
$isCustomer = isCustomer();
$cartCount = getCartCount($pdo);

// Staff members get a link back to their own panel.
$staffPage = '';
if (isAdmin()) {
    $staffPage = 'admin/dashboard.php';
} elseif (isPharmacist()) {
    $staffPage = 'pharmacist/dashboard.php';
} elseif (isStorekeeper()) {
    $staffPage = 'admin/products.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Membership Benefits | Price Line Pharmacy</title>

    <link rel="stylesheet" href="CSS/membership-benefits.css">
    <link rel="stylesheet" href="CSS/navbar.css">
    <link rel="stylesheet" href="CSS/footer.css">
</head>

<body class="membership-page" id="page-top">

    <nav class="headbar" aria-label="Main navigation">
    <div class="pharmacy-navbar">
        <a href="homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
            <img src="images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
        </a>
        <a href="homepage.php">Home</a>
        <a href="customer/products.php">Products</a>
        <a href="membership-benefits.php" class="current-page" aria-current="page">Membership Benefits</a>
        <?php if (!$user || $isCustomer): ?>
            <a href="customer/consultation.php" class="nav-button">E-Consult Now</a>
        <?php endif; ?>
        <div class="account-links">
            <?php if (!$user || $isCustomer): ?>
                <a href="customer/cart.php" class="navbar-cart"><img src="images/cart-icon.svg" alt="" width="24" height="24"> Cart (<?php echo $cartCount; ?>)</a>
            <?php endif; ?>
            <?php if ($user): ?>
                <?php if ($isCustomer): ?>
                    <a href="customer/orders.php">My Orders</a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($staffPage); ?>">Staff Panel</a>
                <?php endif; ?>
                <form action="logout.php" method="POST">
                    <button type="submit">Log Out</button>
                </form>
            <?php else: ?>
                <a href="login.php">Log In</a>
                <a href="register.php" class="register-link">Register</a>
            <?php endif; ?>
        </div>
    </div>
    </nav>

    <main>
        <section class="membership-intro">
            <img src="images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy" class="page-logo">

            <h1>Membership Benefits</h1>

            <?php if (!$user): ?>
                <p>Create a customer account to shop for health essentials, view your orders, and request a pharmacist consultation.</p>
                <a href="register.php" class="membership-button">Create an Account</a>
                <p class="membership-login">Already have an account? <a href="login.php">Log In</a></p>
            <?php elseif ($isCustomer): ?>
                <p>Welcome, <?php echo htmlspecialchars($user['name']); ?>! Use your account to shop, view your orders, and request a pharmacist consultation.</p>
                <a href="customer/products.php" class="membership-button">Browse Products</a>
            <?php else: ?>
                <p>Explore the benefits available to customer accounts.</p>
                <a href="<?php echo htmlspecialchars($staffPage); ?>" class="membership-button">Back to Staff Panel</a>
            <?php endif; ?>
        </section>

        <section class="membership-benefits"
                 aria-labelledby="benefits-heading">

            <h2 id="benefits-heading">Your Member Benefits</h2>

            <div class="benefits-grid">
                <article class="benefit-card">
                    <span class="benefit-number" aria-hidden="true">01</span>

                    <h3>Shop Online</h3>

                    <p>
                        Add products to your cart, adjust quantities,
                        and place an order with your delivery details.
                    </p>
                </article>

                <article class="benefit-card">
                    <span class="benefit-number" aria-hidden="true">02</span>

                    <h3>View Your Orders</h3>

                    <p>
                        Find your previous orders and review the
                        products you purchased in one place.
                    </p>
                </article>

                <article class="benefit-card">
                    <span class="benefit-number" aria-hidden="true">03</span>

                    <h3>Request a Consultation</h3>

                    <p>
                        Send your question and preferred contact time
                        for a pharmacist to follow up.
                    </p>
                </article>
            </div>
        </section>

        <section class="membership-steps"
                 aria-labelledby="join-heading">

            <?php if (!$user): ?>
            <h2 id="join-heading">How to Get Started</h2>

            <ol>
                <li>Create your customer account.</li>
                <li>Log in using your email and password.</li>
                <li>Browse products or submit a consultation request.</li>
            </ol>

            <a href="register.php" class="membership-button">
                Register Now
            </a>
            <?php else: ?>
                <h2 id="join-heading">Explore Your Next Step</h2>
                <p>Browse the catalogue and discover our customer services.</p>
                <a href="customer/products.php" class="membership-button">Explore Products</a>
            <?php endif; ?>
        </section>
    </main>

<?php require __DIR__ . '/includes/footer.php'; ?>

</body>
</html>