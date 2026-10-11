<?php
// STEP 1: Only logged-in customers can place orders.
require_once __DIR__ . '/../includes/auth.php';

if (!isCustomer()) {
    // Come back to checkout after logging in.
    $_SESSION['after_login'] = 'customer/checkout.php';
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = $_SESSION['user']['user_id'];
$address = '';
$phone = '';
$error = '';

// STEP 2: Read prices and quantities from the database, not the form.
$stmt = $pdo->prepare('SELECT cart_items.product_id, cart_items.quantity,
        products.name, products.price, products.stock
    FROM cart_items
    JOIN products ON cart_items.product_id = products.product_id
    WHERE cart_items.user_id = ? AND products.is_active = 1
    ORDER BY cart_items.cart_item_id');
$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// STEP 3: Add up the total and check the stock.
$cartCount = 0;
$total = 0;
$cartIssue = '';

foreach ($items as $item) {
    $cartCount += $item['quantity'];
    $total += $item['price'] * $item['quantity'];

    if ($item['quantity'] > $item['stock']) {
        $cartIssue = 'There is not enough stock for ' . $item['name'] . '. Please update your cart before checking out.';
    }
}

// STEP 4: Place the order when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim($_POST['delivery_address'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');

    if (empty($address) || empty($phone)) {
        $error = 'Please enter your delivery address and contact number.';
    } elseif (!$items) {
        $error = 'Your cart is empty. No order was placed.';
    } elseif ($cartIssue !== '') {
        $error = $cartIssue;
    } else {
        // STEP 5: Create the order and remember its new ID.
        $stmt = $pdo->prepare('INSERT INTO orders (user_id, delivery_address, phone, total_amount) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $address, $phone, $total]);
        $orderId = $pdo->lastInsertId();

        // STEP 6: Save each purchase price and reduce its stock.
        foreach ($items as $item) {
            $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)');
            $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);

            $stmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE product_id = ?');
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }

        // STEP 7: Empty the customer's cart.
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
        $stmt->execute([$userId]);

        $_SESSION['order_message'] = 'Order #' . $orderId . ' placed successfully. Your order details are below.';
        header('Location: orders.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Price Line Pharmacy</title>
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
            <a href="products.php" >Products</a>
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
            <h1>Checkout</h1>
            <p>Review your items and enter your delivery details.</p>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (!$items): ?>
            <div class="customer-empty cart-empty">
                <h2>Your cart is empty</h2>
                <p>Add products before checking out.</p>
                <a href="products.php" class="customer-shop-link">Browse Products</a>
            </div>
        <?php else: ?>
            <?php if ($cartIssue !== '' && $cartIssue !== $error): ?>
                <p class="customer-error" role="alert"><?php echo htmlspecialchars($cartIssue); ?></p>
            <?php endif; ?>
            <div class="cart-layout">
                <section class="customer-form-card checkout-form-card" aria-labelledby="delivery-heading">
                    <h2 id="delivery-heading">Delivery Details</h2>
                    <form action="checkout.php" method="POST" class="customer-request-form">
                        <label for="delivery-address">Delivery Address</label>
                        <textarea id="delivery-address" name="delivery_address" class="customer-textarea" rows="5" maxlength="500" autocomplete="street-address" placeholder="House number, street, postcode, city and state" required><?php echo htmlspecialchars($address); ?></textarea>

                        <label for="phone">Contact Number</label>
                        <input type="tel" id="phone" name="phone" class="customer-input" maxlength="30" autocomplete="tel" placeholder="e.g. 012-3456789" value="<?php echo htmlspecialchars($phone); ?>" required>

                        <p>This checkout records your order. No online payment is collected.</p>
                        <button type="submit" <?php if ($cartIssue !== '') echo 'class="disabled-button" disabled'; ?>>Place Order</button>
                        <a href="cart.php" class="checkout-back-link">Back to Cart</a>
                    </form>
                </section>

                <aside class="cart-summary" aria-labelledby="order-summary-heading">
                    <h2 id="order-summary-heading">Order Summary</h2>
                    <ul class="checkout-items">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <p><strong><?php echo htmlspecialchars($item['name']); ?></strong></p>
                                <p><?php echo (int) $item['quantity']; ?> x RM <?php echo number_format((float) $item['price'], 2); ?></p>
                                <p>Subtotal: RM <?php echo number_format((float) $item['price'] * $item['quantity'], 2); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($cartIssue === ''): ?>
                        <p class="cart-total">Order Total<br><strong>RM <?php echo number_format($total, 2); ?></strong></p>
                    <?php endif; ?>
                </aside>
            </div>
        <?php endif; ?>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
