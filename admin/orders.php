<?php
// STEP 1: Check login and allow admins only.
require_once __DIR__ . '/../includes/auth.php';

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

// STEP 2: Connect to the database.
require_once __DIR__ . '/../config/database.php';

// Display text safely in HTML.
function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// STEP 3: Set starting values.
$error = '';
$orders = [];
$items = [];

try {
    // STEP 4: Read orders and their customers' names and emails.
    // JOIN combines matching records from two tables.
    $stmt = $pdo->query(
        'SELECT orders.*, users.name AS customer_name, users.email AS customer_email
         FROM orders
         JOIN users ON orders.user_id = users.user_id
         ORDER BY orders.created_at DESC, orders.order_id DESC'
    );
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // STEP 5: Read the items in the orders and their product names.
    $stmt = $pdo->query(
        'SELECT order_items.*, products.name AS product_name
         FROM order_items
         JOIN products ON order_items.product_id = products.product_id
         ORDER BY order_items.order_item_id'
    );
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Unable to load orders. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../CSS/admin.css">
</head>
<body class="admin-page">
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p>Admin Panel</p>
    </header>

    <nav class="admin-nav" aria-label="Admin navigation">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="products.php">Products</a>
        <a href="orders.php" aria-current="page">Orders</a>
        <a href="consultation.php">Consultations</a>
        <form action="../logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </nav>

    <main class="admin-content admin-orders">
        <h2>Customer Orders</h2>
        <p>View customer details, delivery information and purchased items.</p>

        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert"><?php echo escape($error); ?></p>
        <?php elseif (empty($orders)): ?>
            <section class="consultation-card">
                <h3>No orders yet</h3>
                <p>Orders will appear here after customers complete checkout.</p>
            </section>
        <?php else: ?>
            <div class="consultation-list">
                <?php foreach ($orders as $order): ?>
                    <article class="consultation-card">
                        <h3>Order #<?php echo escape($order['order_id']); ?></h3>
                        <p><strong>Customer:</strong> <?php echo escape($order['customer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo escape($order['customer_email']); ?></p>
                        <p><strong>Date:</strong> <?php echo escape($order['created_at']); ?></p>
                        <p><strong>Phone:</strong> <?php echo escape($order['phone']); ?></p>
                        <p><strong>Delivery Address:</strong><br><?php echo nl2br(escape($order['delivery_address'])); ?></p>

                        <h4>Purchased Items</h4>
                        <ul class="order-items">
                            <?php $hasItems = false; ?>
                            <?php foreach ($items as $item): ?>
                                <?php if ((int) $item['order_id'] === (int) $order['order_id']): ?>
                                    <?php $hasItems = true; ?>
                                    <li>
                                        <p><strong><?php echo escape($item['product_name']); ?></strong></p>
                                        <p>Quantity: <?php echo escape($item['quantity']); ?></p>
                                        <!-- Use the saved purchase price, even if the current product price changes. -->
                                        <p>Price Each: RM <?php echo number_format((float) $item['price_at_purchase'], 2); ?></p>
                                        <p>Subtotal: RM <?php echo number_format((float) $item['price_at_purchase'] * (int) $item['quantity'], 2); ?></p>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (!$hasItems): ?>
                                <li>No purchased items were recorded for this order.</li>
                            <?php endif; ?>
                        </ul>

                        <p class="order-total"><strong>Order Total:</strong> RM <?php echo number_format((float) $order['total_amount'], 2); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="admin-footer">
        <p>&copy; 2026 Price Line Pharmacy.</p>
        <a href="../homepage.php">Back to Home</a>
    </footer>
</body>
</html>
