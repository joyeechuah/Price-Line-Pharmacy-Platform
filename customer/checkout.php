<?php
// STEP 1: Only logged-in customers can place orders.
session_start();
if (!isset($_SESSION['user'])) {
    $_SESSION['checkout_after_login'] = true;
    header('Location: ../login.php');
    exit;
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if (($_SESSION['user']['role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Access denied.');
}
// Save pending guest items before displaying or submitting checkout.
if (!empty($_SESSION['guest_cart'])) {
    $_SESSION['checkout_after_login'] = true;
    header('Location: cart.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

$userId = $_SESSION['user']['user_id'];
$address = '';
$phone = '';
$error = '';
$loadError = '';
$cartIssue = '';
$items = [];
$cartCount = 0;
$totalCents = 0;
$validSubmission = false;

// STEP 2: Check the form and its security tokens.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address = trim((string) filter_input(INPUT_POST, 'delivery_address'));
    $phone = trim((string) filter_input(INPUT_POST, 'phone'));
    $token = (string) filter_input(INPUT_POST, 'csrf_token');
    $checkoutToken = (string) filter_input(INPUT_POST, 'checkout_token');

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Please reload the page and try again.';
    } elseif ($checkoutToken === '' || !isset($_SESSION['checkout_token']) || !hash_equals($_SESSION['checkout_token'], $checkoutToken)) {
        $error = 'This checkout form has expired or was already used. Please review your cart again.';
    } elseif ($address === '' || mb_strlen($address) > 500) {
        $error = 'Enter a delivery address of 1 to 500 characters.';
    } elseif ($phone === '' || mb_strlen($phone) > 30) {
        $error = 'Enter a contact number of 1 to 30 characters.';
    } else {
        $validSubmission = true;
    }
}

try {
    // STEP 3: Read prices and quantities from the database, not the form.
    if ($validSubmission) {
        // A transaction saves all order changes together, or cancels them all.
        $pdo->beginTransaction();
    }

    $sql = 'SELECT c.cart_item_id, c.product_id, c.quantity, p.name, p.price, p.stock
        FROM cart_items c JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = ? ORDER BY c.product_id, c.cart_item_id';
    if ($validSubmission) {
        // Lock the rows until this order finishes, so stock cannot be sold twice.
        $sql .= ' FOR UPDATE';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    $productQuantities = [];
    $reviewRows = [];
    foreach ($items as $item) {
        $quantity = (int) $item['quantity'];
        // Calculate money in whole cents to avoid rounding differences.
        $priceCents = (int) round((float) $item['price'] * 100);
        $lineCents = $priceCents * $quantity;
        if ($quantity < 1 || $priceCents < 0) {
            $cartIssue = 'Your cart contains an invalid quantity or price. Please return to your cart.';
        } elseif ($lineCents > 9999999999 - $totalCents) {
            // The orders table stores totals up to RM 99,999,999.99.
            $cartIssue = 'This order exceeds the supported total. Please reduce your cart quantity.';
        } else {
            $totalCents += $lineCents;
        }
        $cartCount += $quantity;
        $id = $item['product_id'];
        $productQuantities[$id] = ($productQuantities[$id] ?? 0) + $quantity;
        $reviewRows[] = [(int) $item['cart_item_id'], (int) $id, $quantity, $priceCents];
    }
    foreach ($items as $item) {
        if ($productQuantities[$item['product_id']] > $item['stock']) {
            $cartIssue = 'There is not enough stock for ' . $item['name'] . '. Please update your cart before checking out.';
            break;
        }
    }

    // Remember what the customer reviewed, including quantities and prices.
    $signature = hash('sha256', json_encode($reviewRows));

    // STEP 4: Recheck the reviewed cart before placing the order.
    if ($validSubmission) {
        if (!$items) {
            $error = 'Your cart is empty. No order was placed.';
        } elseif ($cartIssue !== '') {
            $error = $cartIssue;
        } elseif ($signature !== ($_SESSION['checkout_signature'] ?? '')) {
            $error = 'Your cart or its prices changed. Review the updated details below before placing your order.';
        } else {
            // STEP 5: Create the order and remember its new ID.
            $totalAmount = number_format($totalCents / 100, 2, '.', '');
            $stmt = $pdo->prepare('INSERT INTO orders (user_id, delivery_address, phone, total_amount) VALUES (?, ?, ?, ?)');
            $stmt->execute([$userId, $address, $phone, $totalAmount]);
            $orderId = $pdo->lastInsertId();

            // STEP 6: Save each purchase price and reduce its stock.
            foreach ($items as $item) {
                $stmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?');
                $stmt->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
                if ($stmt->rowCount() !== 1) {
                    $error = 'Stock changed while placing your order. No order was saved. Please review your cart.';
                    break;
                }

                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?)');
                $stmt->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);

                // Clear only the cart rows that are included in this order.
                $stmt = $pdo->prepare('DELETE FROM cart_items WHERE cart_item_id = ? AND user_id = ?');
                $stmt->execute([$item['cart_item_id'], $userId]);
            }

            if ($error === '') {
                $pdo->commit();
                unset($_SESSION['checkout_token'], $_SESSION['checkout_signature']);
                $_SESSION['order_message'] = 'Order #' . $orderId . ' placed successfully. Your order details are below.';
                header('Location: orders.php');
                exit;
            }
        }
        $pdo->rollBack();
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage());
    $loadError = 'Unable to complete checkout. Please return to your cart and try again.';
}

// STEP 7: Make a fresh form token. A successful order cannot reuse this form.
$canCheckout = $items && $loadError === '' && $cartIssue === '';
if ($canCheckout) {
    $_SESSION['checkout_token'] = bin2hex(random_bytes(32));
    $_SESSION['checkout_signature'] = $signature;
} else {
    unset($_SESSION['checkout_token'], $_SESSION['checkout_signature']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/customer.css">
</head>
<body class="customer-page" id="page-top">
    <header class="customer-header">
        <nav class="customer-nav" aria-label="Main navigation">
            <a href="../homepage.php">Home</a>
            <a href="products.php">Products</a>
            <a href="../membership-benefits.php">Membership Benefits</a>
            <a href="consultation.php">Consultations</a>
            <a href="orders.php">My Orders</a>
            <div class="customer-account-links">
                <a href="cart.php" class="customer-cart-link"><img src="../images/cart-icon.svg" class="customer-cart-icon" alt="" width="28" height="28"> <span>Cart<?php if ($loadError === ''): ?> (<?php echo $cartCount; ?>)<?php endif; ?></span></a>
                <form action="../logout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                    <button type="submit">Log Out</button>
                </form>
            </div>
        </nav>
        <div class="customer-intro">
            <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
            <h1>Checkout</h1>
            <p>Review your items and enter your delivery details.</p>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo escape($error); ?></p>
        <?php endif; ?>
        <?php if ($loadError !== ''): ?>
            <p class="customer-error" role="alert"><?php echo escape($loadError); ?></p>
            <a href="cart.php" class="customer-shop-link">Back to Cart</a>
        <?php elseif (!$items): ?>
            <div class="customer-empty cart-empty">
                <h2>Your cart is empty</h2>
                <p>Add products before checking out.</p>
                <a href="products.php" class="customer-shop-link">Browse Products</a>
            </div>
        <?php else: ?>
            <?php if ($cartIssue !== '' && $cartIssue !== $error): ?>
                <p class="customer-error" role="alert"><?php echo escape($cartIssue); ?></p>
            <?php endif; ?>
            <div class="cart-layout">
                <section class="customer-form-card checkout-form-card" aria-labelledby="delivery-heading">
                    <h2 id="delivery-heading">Delivery Details</h2>
                    <form action="checkout.php" method="POST" class="customer-request-form">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="checkout_token" value="<?php echo escape($_SESSION['checkout_token'] ?? ''); ?>">

                        <label for="delivery-address">Delivery Address</label>
                        <textarea id="delivery-address" name="delivery_address" class="customer-textarea" rows="5" maxlength="500" autocomplete="street-address" placeholder="House number, street, postcode, city and state" required><?php echo escape($address); ?></textarea>

                        <label for="phone">Contact Number</label>
                        <input type="tel" id="phone" name="phone" class="customer-input" maxlength="30" autocomplete="tel" placeholder="e.g. 012-3456789" value="<?php echo escape($phone); ?>" required>

                        <p>This checkout records your order. No online payment is collected.</p>
                        <button type="submit" <?php if (!$canCheckout) echo 'disabled'; ?>>Place Order</button>
                        <a href="cart.php" class="checkout-back-link">Back to Cart</a>
                    </form>
                </section>

                <aside class="cart-summary" aria-labelledby="order-summary-heading">
                    <h2 id="order-summary-heading">Order Summary</h2>
                    <ul class="checkout-items">
                        <?php foreach ($items as $item): ?>
                            <li>
                                <p><strong><?php echo escape($item['name']); ?></strong></p>
                                <p><?php echo (int) $item['quantity']; ?> x RM <?php echo number_format((float) $item['price'], 2); ?></p>
                                <p>Subtotal: RM <?php echo number_format((float) $item['price'] * $item['quantity'], 2); ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($cartIssue === ''): ?>
                        <p class="cart-total">Order Total<br><strong>RM <?php echo number_format($totalCents / 100, 2); ?></strong></p>
                    <?php endif; ?>
                </aside>
            </div>
        <?php endif; ?>
    </main>

    <footer class="customer-footer">
        <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
        <p>Better health. Brighter tomorrow.</p>
        <nav aria-label="Footer navigation">
            <a href="../homepage.php">Home</a>
            <a href="products.php">Products</a>
            <a href="orders.php">My Orders</a>
            <a href="#page-top">Back to Top</a>
        </nav>
        <p>&copy; <?php echo date('Y'); ?> Price Line Pharmacy.</p>
    </footer>
</body>
</html>
