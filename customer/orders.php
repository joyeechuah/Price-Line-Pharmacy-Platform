<?php
// STEP 1: Check login and allow customers only.
require_once __DIR__ . '/../includes/auth.php';

if (!isCustomer()) {
    // Come back to this page after logging in.
    $_SESSION['after_login'] = 'customer/orders.php';
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cart.php';

$userId = $_SESSION['user']['user_id'];
$message = $_SESSION['order_message'] ?? '';
unset($_SESSION['order_message']);

// STEP 2: Read only the logged-in customer's orders.
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC, order_id DESC');
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// STEP 3: Read only items belonging to those orders.
$stmt = $pdo->prepare('SELECT order_items.order_id, order_items.quantity, order_items.price_at_purchase,
        products.name AS product_name
    FROM order_items
    JOIN orders ON order_items.order_id = orders.order_id
    JOIN products ON order_items.product_id = products.product_id
    WHERE orders.user_id = ?
    ORDER BY order_items.order_item_id');
$stmt->execute([$userId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cartCount = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/customer.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<body class="customer-page" id="page-top">
<?php $navBase = '../'; $currentPage = 'orders'; require __DIR__ . '/../includes/navbar.php'; ?>
    <header class="customer-header">
        <div class="customer-intro">
            <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy" class="page-logo">
            <h1>My Orders</h1>
            <p>View your order history and purchased items.</p>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($message !== ''): ?>
            <p class="customer-success" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if (empty($orders)): ?>
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
                        <p><strong>Order Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <p><strong>Delivery Address:</strong><br><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>

                        <h3>Purchased Items</h3>
                        <ul class="customer-order-items">
                            <?php $hasItems = false; ?>
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['order_id'] == $order['order_id']): ?>
                                    <?php $hasItems = true; ?>
                                    <li>
                                        <p><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></p>
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

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
