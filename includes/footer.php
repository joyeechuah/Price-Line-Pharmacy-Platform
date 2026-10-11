<?php
// Shared footer for every page.
// Pages inside a folder set $footerBase = '../' before including this file.
$footerBase = $footerBase ?? '';
?>
    <footer class="site-footer">
        <p class="site-footer-brand">Price <span>Line</span> Pharmacy</p>
        <p>Better health. Brighter tomorrow.</p>
        <nav aria-label="Footer navigation">
            <a href="<?php echo $footerBase; ?>homepage.php">Home</a>
            <a href="<?php echo $footerBase; ?>membership-benefits.php">Membership Benefits</a>
            <a href="#page-top">Back to Top</a>
        </nav>
        <p>&copy; <?php echo date('Y'); ?> Price Line Pharmacy.</p>
    </footer>
