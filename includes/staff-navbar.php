<?php
// Shared navbar and header for the admin, storekeeper and pharmacist panels.
// Pages set $currentPage before including this file.
$currentPage = $currentPage ?? '';

$panelTitle = 'Pharmacist Panel';
if (isAdmin()) {
    $panelTitle = 'Admin Panel';
} elseif (isStorekeeper()) {
    $panelTitle = 'Storekeeper Panel';
}
?>
    <nav class="headbar" aria-label="Staff navigation">
        <div class="pharmacy-navbar">
            <a href="../homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
                <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
            </a>
            <!-- Each role only sees links to the pages it is allowed to open. -->
            <?php if (isAdmin()): ?>
                <a href="dashboard.php" <?php if ($currentPage === 'dashboard') echo 'class="current-page" aria-current="page"'; ?>>Dashboard</a>
                <a href="users.php" <?php if ($currentPage === 'users') echo 'class="current-page" aria-current="page"'; ?>>Users</a>
                <a href="products.php" <?php if ($currentPage === 'products') echo 'class="current-page" aria-current="page"'; ?>>Products</a>
                <a href="orders.php" <?php if ($currentPage === 'orders') echo 'class="current-page" aria-current="page"'; ?>>Orders</a>
                <a href="consultation.php" <?php if ($currentPage === 'consultation') echo 'class="current-page" aria-current="page"'; ?>>Consultations</a>
            <?php elseif (isStorekeeper()): ?>
                <a href="products.php" <?php if ($currentPage === 'products') echo 'class="current-page" aria-current="page"'; ?>>Products</a>
            <?php elseif (isPharmacist()): ?>
                <a href="dashboard.php" <?php if ($currentPage === 'dashboard') echo 'class="current-page" aria-current="page"'; ?>>Dashboard</a>
                <a href="consultation.php" <?php if ($currentPage === 'consultation') echo 'class="current-page" aria-current="page"'; ?>>Consultations</a>
            <?php endif; ?>
            <div class="account-links">
                <form action="../logout.php" method="POST">
                    <button type="submit">Log Out</button>
                </form>
            </div>
        </div>
    </nav>
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p><?php echo htmlspecialchars($panelTitle); ?></p>
    </header>
