<?php
// Cart functions shared by the shop pages.
// Guests:    the cart is kept in $_SESSION['guest_cart'] as product_id => quantity.
// Customers: the cart is kept in the cart_items table.

// How many of one product are already in a customer's cart?
function getCartQuantity($pdo, $userId, $productId)
{
    $stmt = $pdo->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$userId, $productId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        return $item['quantity'];
    }
    return 0;
}

// Add a product to a customer's cart, or increase its quantity.
function addToCart($pdo, $userId, $productId, $quantity)
{
    if (getCartQuantity($pdo, $userId, $productId) > 0) {
        $stmt = $pdo->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$quantity, $userId, $productId]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $productId, $quantity]);
    }
}

// After login, move the guest's items into the customer's saved cart.
function saveGuestCart($pdo, $userId)
{
    foreach ($_SESSION['guest_cart'] ?? [] as $productId => $quantity) {
        // Skip products that were removed from the shop.
        $stmt = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ? AND is_active = 1');
        $stmt->execute([$productId]);

        if ($stmt->fetch()) {
            addToCart($pdo, $userId, $productId, $quantity);
        }
    }
    unset($_SESSION['guest_cart']);
}

// Count the items in the cart. The navbar shows this number.
function getCartCount($pdo)
{
    if (isCustomer()) {
        $stmt = $pdo->prepare('SELECT SUM(quantity) AS total FROM cart_items WHERE user_id = ?');
        $stmt->execute([$_SESSION['user']['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $row['total'];
    }
    return array_sum($_SESSION['guest_cart'] ?? []);
}
