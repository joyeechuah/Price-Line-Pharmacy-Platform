<?php
// STEP 1: Check login and allow admins only.
require_once __DIR__ . '/../includes/auth.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// STEP 2: Connect to the database.
require_once __DIR__ . '/../config/database.php';

// STEP 3: Read orders and their customers' names and emails.
// JOIN combines matching records from two tables.
$stmt = $pdo->query(
    'SELECT orders.*, users.name AS customer_name, users.email AS customer_email
     FROM orders
     JOIN users ON orders.user_id = users.user_id
     ORDER BY orders.created_at DESC, orders.order_id DESC'
);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// STEP 4: Read the items in the orders and their product names.
$stmt = $pdo->query(
    'SELECT order_items.*, products.name AS product_name
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
    <nav class="headbar" aria-label="Admin navigation">
        <div class="pharmacy-navbar">
            <a href="../homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
                <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
            </a>
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="products.php">Products</a>
        <a href="orders.php" aria-current="page">Orders</a>
        <a href="consultation.php">Consultations</a>
        <div class="account-links">
        <form action="../logout.php" method="POST">
            <button type="submit" >Log Out</button>
        </form>
        </div>
        </div>
    </nav>
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p>Admin Panel</p>
    </header>



    <main class="admin-content admin-orders">
        <h2>Customer Orders</h2>
        <p>View customer details, delivery information and purchased items.</p>

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

                        <h4>Purchased Items</h4>
                        <ul class="order-items">
                            <?php $hasItems = false; ?>
                            <?php foreach ($items as $item): ?>
                                <?php if ((int) $item['order_id'] === (int) $order['order_id']): ?>
                                    <?php $hasItems = true; ?>
                                    <li>
                                        <p><strong><?php echo htmlspecialchars($item['product_name']); ?></strong></p>
                                        <p>Quantity: <?php echo htmlspecialchars($item['quantity']); ?></p>
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

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
