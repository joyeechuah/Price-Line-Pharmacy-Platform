<?php
// Shared navbar for the shop pages (home, products, cart, orders and so on).
// Pages set $currentPage and $cartCount before including this file.
// Pages inside a folder also set $navBase = '../'.
$navBase = $navBase ?? '';
$currentPage = $currentPage ?? '';
?>
    <nav class="headbar" aria-label="Main navigation">
        <div class="pharmacy-navbar">
            <a href="<?php echo $navBase; ?>homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
                <img src="<?php echo $navBase; ?>images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
            </a>
            <a href="<?php echo $navBase; ?>homepage.php" <?php if ($currentPage === 'home') echo 'class="current-page" aria-current="page"'; ?>>Home</a>
            <a href="<?php echo $navBase; ?>customer/products.php" <?php if ($currentPage === 'products') echo 'class="current-page" aria-current="page"'; ?>>Products</a>
            <a href="<?php echo $navBase; ?>membership-benefits.php" <?php if ($currentPage === 'membership') echo 'class="current-page" aria-current="page"'; ?>>Membership Benefits</a>
            <?php if (isGuest() || isCustomer()): ?>
                <?php if ($currentPage === 'consultation'): ?>
                    <a href="<?php echo $navBase; ?>customer/consultation.php" class="nav-button current-page" aria-current="page">E-Consult Now</a>
                <?php else: ?>
                    <a href="<?php echo $navBase; ?>customer/consultation.php" class="nav-button">E-Consult Now</a>
                <?php endif; ?>
            <?php endif; ?>
            <div class="account-links">
                <?php if (isGuest() || isCustomer()): ?>
                    <?php if ($currentPage === 'cart'): ?>
                        <a href="<?php echo $navBase; ?>customer/cart.php" class="navbar-cart current-page" aria-current="page">
                    <?php else: ?>
                        <a href="<?php echo $navBase; ?>customer/cart.php" class="navbar-cart">
                    <?php endif; ?>
                        <img src="<?php echo $navBase; ?>images/cart-icon.svg" alt="" width="24" height="24">
                        <span>Cart (<?php echo $cartCount; ?>)</span>
                    </a>
                <?php endif; ?>
                <?php if (!isGuest()): ?>
                    <?php if (isCustomer()): ?>
                        <a href="<?php echo $navBase; ?>customer/orders.php" <?php if ($currentPage === 'orders') echo 'class="current-page" aria-current="page"'; ?>>My Orders</a>
                    <?php elseif (isAdmin()): ?>
                        <a href="<?php echo $navBase; ?>admin/dashboard.php">Staff Panel</a>
                    <?php elseif (isPharmacist()): ?>
                        <a href="<?php echo $navBase; ?>pharmacist/dashboard.php">Staff Panel</a>
                    <?php elseif (isStorekeeper()): ?>
                        <a href="<?php echo $navBase; ?>admin/products.php">Staff Panel</a>
                    <?php endif; ?>
                    <form action="<?php echo $navBase; ?>logout.php" method="POST">
                        <button type="submit">Log Out</button>
                    </form>
                <?php else: ?>
                    <a href="<?php echo $navBase; ?>login.php">Log In</a>
                    <a href="<?php echo $navBase; ?>register.php" class="register-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
