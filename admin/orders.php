<?php
// STEP 1: Check login and allow admins only.
require_once __DIR__ . '/../includes/auth.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// STEP 2: Connect to the database.
require_once __DIR__ . '/../config/database.php';

// STEP 3: Update the order status when a button is clicked.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['order_id'] ?? 0);

    // An order moves one step at a time: pending, then shipped, then delivered.
    if ($action === 'shipped') {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'shipped' WHERE order_id = ? AND status = 'pending'");
        $stmt->execute([$id]);
    }

    if ($action === 'delivered') {
        $stmt = $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE order_id = ? AND status = 'shipped'");
        $stmt->execute([$id]);
    }

    // Reload without repeating the action on refresh.
    header('Location: orders.php');
    exit;
}

// STEP 4: Read orders and their customers' names and emails.
// JOIN combines matching records from two tables.
$stmt = $pdo->query(
    'SELECT orders.*, users.name AS customer_name, users.email AS customer_email
     FROM orders
     JOIN users ON orders.user_id = users.user_id
     ORDER BY orders.created_at DESC, orders.order_id DESC'
);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// STEP 5: Read the items in the orders with their product names and images.
$stmt = $pdo->query(
    'SELECT order_items.*, products.name AS product_name, products.image_url
     FROM order_items
     JOIN products ON order_items.product_id = products.product_id
     ORDER BY order_items.order_item_id'
);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<body class="admin-page" id="page-top">
<?php $currentPage = 'orders'; require __DIR__ . '/../includes/staff-navbar.php'; ?>

    <main class="admin-content admin-orders">
        <h2>Customer Orders</h2>
        <p>View customer details, delivery information and purchased items, and update the order status.</p>

        <?php if (empty($orders)): ?>
            <section class="consultation-card">
                <h3>No orders yet</h3>
                <p>Orders will appear here after customers complete checkout.</p>
            </section>
        <?php else: ?>
            <div class="consultation-list">
                <?php foreach ($orders as $order): ?>
                    <article class="consultation-card">
                        <h3>Order #<?php echo htmlspecialchars($order['order_id']); ?></h3>
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <p><strong>Delivery Address:</strong><br><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                        <p>
                            <strong>Status:</strong>
                            <?php if ($order['status'] === 'delivered'): ?>
                                <span class="consultation-status order-status-delivered">Delivered</span>
                            <?php elseif ($order['status'] === 'shipped'): ?>
                                <span class="consultation-status order-status-shipped">Shipped</span>
                            <?php else: ?>
                                <span class="consultation-status">Pending</span>
                            <?php endif; ?>
                        </p>

                        <h4>Purchased Items</h4>
                        <ul class="order-items">
                            <?php $hasItems = false; ?>
                            <?php foreach ($items as $item): ?>
                                <?php if ((int) $item['order_id'] === (int) $order['order_id']): ?>
                                    <?php $hasItems = true; ?>
                                    <li class="order-item">
                                        <img class="order-item-image" src="../<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                        <div>
                                            <p><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></p>
                                            <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
                                            <!-- Use the saved purchase price, even if the current product price changes. -->
                                            <p>Price Each: RM <?php echo number_format((float) $item['price_at_purchase'], 2); ?></p>
                                            <p>Subtotal: RM <?php echo number_format((float) $item['price_at_purchase'] * (int) $item['quantity'], 2); ?></p>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (!$hasItems): ?>
                                <li>No purchased items were recorded for this order.</li>
                            <?php endif; ?>
                        </ul>

                        <p class="order-total"><strong>Order Total:</strong> RM <?php echo number_format((float) $order['total_amount'], 2); ?></p>

                        <!-- Show the button for the next step. Delivered orders are finished. -->
                        <?php if ($order['status'] !== 'delivered'): ?>
                            <form method="POST" action="orders.php" class="consultation-actions">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button type="submit" name="action" value="shipped">Mark as Shipped</button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="delivered">Mark as Delivered</button>
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
