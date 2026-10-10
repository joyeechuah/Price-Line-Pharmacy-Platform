<?php
// STEP 1: Guests and customers can view their own cart.
session_start();
$user = $_SESSION['user'] ?? null;
if ($user && $user['role'] !== 'customer') {
    http_response_code(403);
    exit('Access denied.');
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/guest-cart.php';

function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

$userId = $user['user_id'] ?? null;
$error = '';
$loadError = '';
$message = $_SESSION['cart_message'] ?? '';
unset($_SESSION['cart_message']);

// After login, combine the guest cart with this customer's saved cart.
if ($user && !empty($_SESSION['guest_cart'])) {
    try {
        $adjusted = saveGuestCart($pdo, $userId);
        $_SESSION['cart_message'] = 'Your guest items have been added to your account cart.';
        if ($adjusted) {
            $_SESSION['cart_message'] = 'Some guest quantities were reduced or unavailable items skipped because stock changed. Please review your cart.';
            unset($_SESSION['checkout_after_login']);
        }
        header('Location: cart.php');
        exit;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $loadError = 'Unable to save your guest cart. Your items are still kept. Please reload to try again.';
    }
}
if ($user && $loadError === '' && !empty($_SESSION['checkout_after_login'])) {
    unset($_SESSION['checkout_after_login']);
    header('Location: checkout.php');
    exit;
}

// STEP 2: Check a submitted Update or Remove form.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $loadError === '') {
    $token = (string) filter_input(INPUT_POST, 'csrf_token');
    $action = (string) filter_input(INPUT_POST, 'action');
    $cartItemId = filter_input(INPUT_POST, 'cart_item_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Please reload the page and try again.';
    } elseif (!in_array($action, ['update', 'remove'], true)) {
        $error = 'Please choose Update Quantity or Remove.';
    } elseif (!$cartItemId || $cartItemId < 1) {
        $error = 'Please select a cart item.';
    } elseif ($action === 'update' && (!$quantity || $quantity < 1 || $quantity > 2147483647)) {
        $error = 'Enter a whole-number quantity of at least 1. Use Remove to delete an item.';
    } elseif (!$user) {
        // In a guest cart, the form's item ID is the product ID.
        if (!isset($_SESSION['guest_cart'][$cartItemId])) {
            $error = 'This item is no longer in your cart.';
        } elseif ($action === 'remove') {
            unset($_SESSION['guest_cart'][$cartItemId]);
            $_SESSION['cart_message'] = 'Item removed from your cart.';
            header('Location: cart.php');
            exit;
        } else {
            try {
                $stmt = $pdo->prepare('SELECT stock FROM products WHERE product_id = ?');
                $stmt->execute([$cartItemId]);
                $product = $stmt->fetch();
                if (!$product) {
                    $error = 'This product is no longer available. Please remove it.';
                } elseif ($quantity > $product['stock']) {
                    $error = 'There is not enough stock for that quantity.';
                } else {
                    $_SESSION['guest_cart'][$cartItemId] = $quantity;
                    $_SESSION['cart_message'] = 'Cart quantity updated.';
                    header('Location: cart.php');
                    exit;
                }
            } catch (PDOException $e) {
                error_log($e->getMessage());
                $error = 'Unable to change your cart. Please try again.';
            }
        }
    } else {
        try {
            // STEP 3: Remove only an item owned by this customer.
            if ($action === 'remove') {
                $stmt = $pdo->prepare('DELETE FROM cart_items WHERE cart_item_id = ? AND user_id = ?');
                $stmt->execute([$cartItemId, $userId]);

                if ($stmt->rowCount() === 1) {
                    $_SESSION['cart_message'] = 'Item removed from your cart.';
                    header('Location: cart.php');
                    exit;
                }
                $error = 'This item is no longer in your cart.';
            }

            // STEP 4: Check stock before changing the quantity.
            if ($action === 'update') {
                $stmt = $pdo->prepare('SELECT product_id FROM cart_items WHERE cart_item_id = ? AND user_id = ?');
                $stmt->execute([$cartItemId, $userId]);
                $productId = $stmt->fetchColumn();

                if (!$productId) {
                    $error = 'This item is no longer in your cart.';
                } else {
                    // Keep the stock check and quantity change together.
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare('SELECT stock FROM products WHERE product_id = ? FOR UPDATE');
                    $stmt->execute([$productId]);
                    $product = $stmt->fetch();

                    // Read all entries for this product in the customer's cart.
                    $stmt = $pdo->prepare('SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? ORDER BY cart_item_id FOR UPDATE');
                    $stmt->execute([$userId, $productId]);
                    $rows = $stmt->fetchAll();
                    $itemFound = false;
                    $otherQuantity = 0;

                    foreach ($rows as $row) {
                        if ($row['cart_item_id'] == $cartItemId) {
                            $itemFound = true;
                        } else {
                            $otherQuantity += (int) $row['quantity'];
                        }
                    }

                    if (!$product || !$itemFound) {
                        $error = 'This item is no longer available in your cart.';
                    } elseif ($quantity + $otherQuantity > $product['stock']) {
                        $error = 'There is not enough stock for that quantity. Please choose a smaller amount or remove the item.';
                    } else {
                        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND user_id = ?');
                        $stmt->execute([$quantity, $cartItemId, $userId]);
                        $pdo->commit();
                        $_SESSION['cart_message'] = 'Cart quantity updated.';
                        header('Location: cart.php');
                        exit;
                    }
                    $pdo->rollBack();
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($e->getMessage());
            $error = 'Unable to change your cart. Please try again.';
        }
    }
}

// STEP 5: Read this customer's cart using current product prices.
$items = [];
$cartCount = 0;
$total = 0;
$productQuantities = [];

try {
    if ($user) {
    $stmt = $pdo->prepare('SELECT c.cart_item_id, c.product_id, c.quantity, p.name, p.category, p.price, p.stock
        FROM cart_items c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = ? ORDER BY c.cart_item_id DESC');
    $stmt->execute([$userId]);
    $items = $stmt->fetchAll();
    } else {
        // Always read names, prices and stock from the database.
        $stmt = $pdo->prepare('SELECT product_id, name, category, price, stock FROM products WHERE product_id = ?');
        foreach ($_SESSION['guest_cart'] ?? [] as $productId => $quantity) {
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if (!$product) {
                unset($_SESSION['guest_cart'][$productId]);
                $message = 'An unavailable product was removed from your cart.';
                continue;
            }
            $product['cart_item_id'] = $productId;
            $product['quantity'] = $quantity;
            $items[] = $product;
        }
    }

    foreach ($items as $item) {
        $cartCount += (int) $item['quantity'];
        $total += $item['price'] * $item['quantity'];
        $productId = $item['product_id'];
        $productQuantities[$productId] = ($productQuantities[$productId] ?? 0) + $item['quantity'];
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $loadError = 'Unable to load your cart. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | Price Line Pharmacy</title>
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
                <a href="cart.php" class="customer-cart-link" aria-current="page">
                    <img src="../images/cart-icon.svg" class="customer-cart-icon" alt="" width="28" height="28"> <span>Cart<?php if ($loadError === ''): ?> (<?php echo $cartCount; ?>)<?php endif; ?></span>
                </a>
                <?php if ($user): ?>
                <form action="../logout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                    <button type="submit">Log Out</button>
                </form>
                <?php else: ?>
                    <a href="../login.php">Log In</a>
                    <a href="../register.php">Register</a>
                <?php endif; ?>
            </div>
        </nav>
        <div class="customer-intro">
            <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
            <h1>My Shopping Cart</h1>
            <p>Review your products and update the quantities below.</p>
            <?php if (!$user): ?>
                <p>You can shop as a guest. Log in or create an account when you are ready to check out.</p>
            <?php endif; ?>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($message !== ''): ?>
            <p class="customer-success" role="status"><?php echo escape($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo escape($error); ?></p>
        <?php endif; ?>
        <?php if ($loadError !== ''): ?>
            <p class="customer-error" role="alert"><?php echo escape($loadError); ?></p>
        <?php elseif (!$items): ?>
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
                            <!-- Replace the placeholder when your product pictures are ready. -->
                            <img src="../images/product-placeholder.svg" alt="Placeholder image for <?php echo escape($item['name']); ?>" class="cart-item-image" width="320" height="240">
                            <div class="cart-item-details">
                                <h2><?php echo escape($item['name']); ?></h2>
                                <p><?php echo escape($item['category']); ?></p>
                                <p>Unit price: <strong>RM <?php echo number_format((float) $item['price'], 2); ?></strong></p>
                                <p>Subtotal: <strong>RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong></p>

                                <?php if ($item['stock'] < 1): ?>
                                    <p class="customer-out-of-stock">This product is now out of stock. Please remove it from your cart.</p>
                                <?php elseif ($productQuantities[$item['product_id']] > $item['stock']): ?>
                                    <p class="customer-out-of-stock">Only <?php echo (int) $item['stock']; ?> available. Please reduce your cart quantity.</p>
                                <?php else: ?>
                                    <p class="customer-stock">In stock: <?php echo (int) $item['stock']; ?></p>
                                <?php endif; ?>

                                <div class="cart-item-actions">
                                    <form action="cart.php" method="POST" class="cart-update-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="cart_item_id" value="<?php echo (int) $item['cart_item_id']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <div class="cart-quantity-field">
                                            <label for="quantity-<?php echo (int) $item['cart_item_id']; ?>">Quantity</label>
                                            <input type="number" class="customer-input quantity-input" id="quantity-<?php echo (int) $item['cart_item_id']; ?>" name="quantity" min="1" max="<?php echo max(1, (int) $item['stock']); ?>" value="<?php echo (int) $item['quantity']; ?>" required <?php if ($item['stock'] < 1) echo 'disabled'; ?>>
                                        </div>
                                        <button type="submit" <?php if ($item['stock'] < 1) echo 'disabled'; ?>>Update Quantity</button>
                                    </form>
                                    <form action="cart.php" method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="cart_item_id" value="<?php echo (int) $item['cart_item_id']; ?>">
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
                    <a href="checkout.php" class="customer-shop-link checkout-link"><?php echo $user ? 'Proceed to Checkout' : 'Log In to Checkout'; ?></a>
                    <a href="products.php" class="customer-shop-link">Continue Shopping</a>
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
            <a href="#page-top">Back to Top</a>
        </nav>
        <p>&copy; <?php echo date('Y'); ?> Price Line Pharmacy.</p>
    </footer>
</body>
</html>
