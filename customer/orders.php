<?php
// STEP 1: Check login and allow customers only.
require_once __DIR__ . '/../includes/auth.php';
if (($_SESSION['user']['role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/../config/database.php';

function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

$userId = $_SESSION['user']['user_id'];
$error = '';
$orders = [];
$items = [];
$cartCount = null;
$message = $_SESSION['order_message'] ?? '';
unset($_SESSION['order_message']);

try {
    // STEP 2: Read only the logged-in customer's orders.
    $stmt = $pdo->prepare('SELECT order_id, delivery_address, phone, total_amount, created_at
        FROM orders WHERE user_id = ? ORDER BY created_at DESC, order_id DESC');
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();

    // STEP 3: Read only items belonging to those orders.
    $stmt = $pdo->prepare('SELECT order_items.order_id, order_items.quantity, order_items.price_at_purchase,
        products.name AS product_name
        FROM order_items
        JOIN orders ON order_items.order_id = orders.order_id
        JOIN products ON order_items.product_id = products.product_id
        WHERE orders.user_id = ? ORDER BY order_items.order_item_id');
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    $cartCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Unable to load your orders. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/customer.css">
</head>
<body class="customer-page" id="page-top">
    <header class="customer-header">
        <nav class="customer-nav" aria-label="Main navigation">
            <a href="../homepage.php">Home</a>
            <a href="products.php">Products</a>
            <a href="../membership-benefits.php">Membership Benefits</a>
            <a href="consultation.php">Consultations</a>
            <a href="orders.php" aria-current="page">My Orders</a>
            <div class="customer-account-links">
                <a href="cart.php" class="customer-cart-link"><img src="../images/cart-icon.svg" class="customer-cart-icon" alt="" width="28" height="28"> <span>Cart<?php if ($cartCount !== null): ?> (<?php echo $cartCount; ?>)<?php endif; ?></span></a>
                <form action="../logout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                    <button type="submit">Log Out</button>
                </form>
            </div>
        </nav>
        <div class="customer-intro">
            <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
            <h1>My Orders</h1>
            <p>View your order history and purchased items.</p>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($message !== ''): ?>
            <p class="customer-success" role="status"><?php echo escape($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo escape($error); ?></p>
        <?php elseif (!$orders): ?>
            <div class="customer-empty">
                <h2>No orders yet</h2>
                <p>Your orders will appear here after you complete checkout. Items in your cart are not orders yet.</p>
                <a href="products.php" class="customer-shop-link">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="customer-history-list">
                <?php foreach ($orders as $order): ?>
                    <article class="customer-record-card">
                        <h2>Order #<?php echo (int) $order['order_id']; ?></h2>
                        <p><strong>Order Date:</strong> <?php echo escape($order['created_at']); ?></p>
                        <p><strong>Phone:</strong> <?php echo escape($order['phone']); ?></p>
                        <p><strong>Delivery Address:</strong><br><?php echo nl2br(escape($order['delivery_address'])); ?></p>

                        <h3>Purchased Items</h3>
                        <ul class="customer-order-items">
                            <?php $hasItems = false; ?>
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['order_id'] == $order['order_id']): ?>
                                    <?php $hasItems = true; ?>
                                    <li>
                                        <p><strong><?php echo escape($item['product_name']); ?></strong></p>
                                        <p>Quantity: <?php echo (int) $item['quantity']; ?></p>
                                        <!-- Use the price saved at checkout, not today's product price. -->
                                        <p>Price Each: RM <?php echo number_format((float) $item['price_at_purchase'], 2); ?></p>
                                        <p>Subtotal: RM <?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?></p>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (!$hasItems): ?>
                                <li>No purchased items were recorded for this order.</li>
                            <?php endif; ?>
                        </ul>
                        <p class="customer-order-total"><strong>Order Total: RM <?php echo number_format((float) $order['total_amount'], 2); ?></strong></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="customer-footer">
        <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
        <p>Better health. Brighter tomorrow.</p>
        <nav aria-label="Footer navigation">
            <a href="../homepage.php">Home</a>
            <a href="products.php">Products</a>
            <a href="consultation.php">Consultations</a>
            <a href="#page-top">Back to Top</a>
        </nav>
        <p>&copy; <?php echo date('Y'); ?> Price Line Pharmacy.</p>
    </footer>
</body>
</html>
