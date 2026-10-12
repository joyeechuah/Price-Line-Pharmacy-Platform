<?php
// STEP 1: Guests and customers can view their own cart.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cart.php';

// Staff accounts do not have a cart.
if (!isGuest() && !isCustomer()) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['user'] ?? null;
$error = '';
$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);

// STEP 2: Check a submitted Update or Remove form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action    = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);
    $quantity  = (int) ($_POST['quantity'] ?? 0);

    if ($action === 'remove') {
        // STEP 3: Remove the item from the saved cart or the guest cart.
        if ($user) {
            $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
            $stmt->execute([$user['user_id'], $productId]);
        } else {
            unset($_SESSION['guest_cart'][$productId]);
        }

        $_SESSION['cart_message'] = 'Item removed from your cart.';
        header('Location: cart.php');
        exit;
    } elseif ($action === 'update') {
        // STEP 4: Check stock before changing the quantity.
        $stmt = $pdo->prepare('SELECT stock FROM products WHERE product_id = ? AND is_active = 1');
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($quantity < 1) {
            $error = 'Enter a quantity of at least 1. Use Remove to delete an item.';
        } elseif (!$product || $quantity > $product['stock']) {
            $error = 'There is not enough stock for that quantity.';
        } elseif (!$user && !isset($_SESSION['guest_cart'][$productId])) {
            $error = 'This item is no longer in your cart.';
        } else {
            if ($user) {
                $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?');
                $stmt->execute([$quantity, $user['user_id'], $productId]);
            } else {
                $_SESSION['guest_cart'][$productId] = $quantity;
            }

            $_SESSION['cart_message'] = 'Cart quantity updated.';
            header('Location: cart.php');
            exit;
        }
    }
}

// STEP 5: Read the cart using current product names, prices and stock.
$items = [];

if ($user) {
    $stmt = $pdo->prepare('SELECT cart_items.product_id, cart_items.quantity,
            products.name, products.category, products.price, products.stock, products.image_url
        FROM cart_items
        JOIN products ON cart_items.product_id = products.product_id
        WHERE cart_items.user_id = ? AND products.is_active = 1
        ORDER BY cart_items.cart_item_id DESC');
    $stmt->execute([$user['user_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare('SELECT product_id, name, category, price, stock, image_url
        FROM products WHERE product_id = ? AND is_active = 1');

    foreach ($_SESSION['guest_cart'] ?? [] as $productId => $quantity) {
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $product['quantity'] = $quantity;
            $items[] = $product;
        } else {
            // The product was removed from the shop.
            unset($_SESSION['guest_cart'][$productId]);
        }
    }
}

// STEP 6: Add up the quantity and the total price.
$cartCount = 0;
$total = 0;

foreach ($items as $item) {
    $cartCount += $item['quantity'];
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/customer.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<body class="customer-page" id="page-top">
<?php $navBase = '../'; $currentPage = 'cart'; require __DIR__ . '/../includes/navbar.php'; ?>
    <header class="customer-header">
        <div class="customer-intro">
            <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy" class="page-logo">
            <h1>My Shopping Cart</h1>
            <p>Review your products and update the quantities below.</p>
            <?php if (!$user): ?>
                <p>You can shop as a guest. Log in or create an account when you are ready to check out.</p>
            <?php endif; ?>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($message !== ''): ?>
            <p class="customer-success" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <?php if (empty($items)): ?>
            <div class="customer-empty cart-empty">
                <h2>Your cart is empty</h2>
                <p>Browse our products and add something to your cart.</p>
                <a href="products.php" class="customer-shop-link">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                        <article class="cart-item">
                            <img src="../<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-image" width="320" height="240">
                            <div class="cart-item-details">
                                <h2><?php echo htmlspecialchars($item['name']); ?></h2>
                                <p><?php echo htmlspecialchars($item['category']); ?></p>
                                <p>Unit price: <strong>RM <?php echo number_format((float) $item['price'], 2); ?></strong></p>
                                <p>Subtotal: <strong>RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></p>

                                <?php if ($item['stock'] < 1): ?>
                                    <p class="customer-out-of-stock">This product is now out of stock. Please remove it from your cart.</p>
                                <?php elseif ($item['quantity'] > $item['stock']): ?>
                                    <p class="customer-out-of-stock">Only <?php echo (int) $item['stock']; ?> available. Please reduce your cart quantity.</p>
                                <?php endif; ?>

                                <div class="cart-item-actions">
                                    <form action="cart.php" method="POST" class="cart-update-form">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <div class="cart-quantity-field">
                                            <label for="quantity-<?php echo (int) $item['product_id']; ?>">Quantity</label>
                                            <input type="number" class="customer-input quantity-input" id="quantity-<?php echo (int) $item['product_id']; ?>" name="quantity" min="1" max="<?php echo (int) $item['stock']; ?>" value="<?php echo (int) $item['quantity']; ?>" required <?php if ($item['stock'] < 1) echo 'disabled'; ?>>
                                        </div>
                                        <button type="submit" <?php if ($item['stock'] < 1) echo 'class="disabled-button" disabled'; ?>>Update Quantity</button>
                                    </form>
                                    <form action="cart.php" method="POST">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $item['product_id']; ?>">
                                        <button type="submit" name="action" value="remove" class="cart-remove-button">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <aside class="cart-summary" aria-labelledby="cart-summary-heading">
                    <h2 id="cart-summary-heading">Cart Summary</h2>
                    <p>Total quantity: <strong><?php echo $cartCount; ?></strong></p>
                    <p class="cart-total">Product total<br><strong>RM <?php echo number_format($total, 2); ?></strong></p>
                    <p>Items in your cart are not reserved. Prices and stock may change.</p>
                    <?php if ($user): ?>
                        <a href="checkout.php" class="customer-shop-link checkout-link">Proceed to Checkout</a>
                    <?php else: ?>
                        <a href="checkout.php" class="customer-shop-link checkout-link">Log In to Checkout</a>
                    <?php endif; ?>
                    <a href="products.php" class="customer-shop-link">Continue Shopping</a>
                </aside>
            </div>
        <?php endif; ?>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
