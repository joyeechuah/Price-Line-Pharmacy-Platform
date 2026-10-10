<?php
// Move the guest's quantities into their customer cart after login.
function saveGuestCart($pdo, $userId) {
    $guestCart = $_SESSION['guest_cart'] ?? [];
    if (!$guestCart) {
        return false;
    }

    $adjusted = false;
    ksort($guestCart);
    try {
        $pdo->beginTransaction();
        foreach ($guestCart as $productId => $quantity) {
            // Recheck stock because it may have changed while browsing.
            $stmt = $pdo->prepare('SELECT stock FROM products WHERE product_id = ? FOR UPDATE');
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            if (!$product) {
                $adjusted = true;
                continue;
            }

            $stmt = $pdo->prepare('SELECT cart_item_id, quantity FROM cart_items WHERE user_id = ? AND product_id = ? ORDER BY cart_item_id FOR UPDATE');
            $stmt->execute([$userId, $productId]);
            $rows = $stmt->fetchAll();
            $existingQuantity = 0;
            foreach ($rows as $row) {
                $existingQuantity += (int) $row['quantity'];
            }

            $available = max(0, (int) $product['stock'] - $existingQuantity);
            $addQuantity = min($quantity, $available);
            if ($addQuantity < $quantity) {
                $adjusted = true;
            }
            if ($addQuantity > 0) {
                if ($rows) {
                    $stmt = $pdo->prepare('UPDATE cart_items SET quantity = quantity + ? WHERE cart_item_id = ? AND user_id = ?');
                    $stmt->execute([$addQuantity, $rows[0]['cart_item_id'], $userId]);
                } else {
                    $stmt = $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)');
                    $stmt->execute([$userId, $productId, $addQuantity]);
                }
            }
        }
        $pdo->commit();
        // Clear it only after every database change succeeds.
        unset($_SESSION['guest_cart']);
        return $adjusted;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
