<?php
// 1. Check login and allow admins and storekeepers.
require_once __DIR__ . '/../includes/auth.php';
$role = $_SESSION['user']['role'] ?? '';

if ($role !== 'admin' && $role !== 'storekeeper') {
    http_response_code(403);
    exit('Access denied.');
}

// 2. Connect to the database.
require_once __DIR__ . '/../config/database.php';

// Safely display database text in HTML.
function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

$error = '';
$products = [];
$id = 0;
$name = '';
$description = '';
$category = '';
$price = '';
$stock = '0';
$image_url = '';
$action = '';

try {
    // 3. Check every submitted form before changing the database.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = (string) filter_input(INPUT_POST, 'action');
        $submittedId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $token = (string) filter_input(INPUT_POST, 'csrf_token');

        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            $error = 'Please reload the page and try again.';
        } elseif ($action !== 'save' && $action !== 'delete') {
            $error = 'Invalid action.';
        } elseif ($submittedId === false || $submittedId === null || $submittedId < 0) {
            $error = 'Invalid product ID.';
        } elseif ($action === 'delete' && $submittedId === 0) {
            $error = 'Select a product to delete.';
        }

        // DELETE: only remove products that are not used in carts or orders.
        if ($error === '' && $action === 'delete') {
            $stmt = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ?');
            $stmt->execute([$submittedId]);

            if (!$stmt->fetch()) {
                $error = 'Product not found. It may already have been deleted.';
            } else {
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM cart_items WHERE product_id = ?');
                $stmt->execute([$submittedId]);
                $cartCount = $stmt->fetchColumn();

                $stmt = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
                $stmt->execute([$submittedId]);
                $orderCount = $stmt->fetchColumn();

                if ($cartCount > 0 || $orderCount > 0) {
                    $error = 'Cannot delete this product because it is used in a cart or an order.';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
                    $stmt->execute([$submittedId]);

                    if ($stmt->rowCount() === 1) {
                        $_SESSION['products_message'] = 'Product deleted.';
                        header('Location: products.php');
                        exit;
                    }
                    $error = 'Product not found. It may already have been deleted.';
                }
            }
        }

        // CREATE / UPDATE: read and check the product details.
        if ($error === '' && $action === 'save') {
            $id = $submittedId;
            $name = trim((string) filter_input(INPUT_POST, 'name'));
            $description = trim((string) filter_input(INPUT_POST, 'description'));
            $category = trim((string) filter_input(INPUT_POST, 'category'));
            $price = trim((string) filter_input(INPUT_POST, 'price'));
            $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
            $image_url = trim((string) filter_input(INPUT_POST, 'image_url'));

            if ($name === '' || mb_strlen($name) > 150) {
                $error = 'Enter a product name of 1 to 150 characters.';
            } elseif ($description === '' || strlen($description) > 65535) {
                $error = 'Enter a description shorter than 65,536 bytes.';
            } elseif ($category === '' || mb_strlen($category) > 100) {
                $error = 'Enter a category of 1 to 100 characters.';
            } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $price)) {
                $error = 'Enter a price from 0 to 99999999.99, with up to two decimal places.';
            } elseif ($stock === false || $stock === null || $stock < 0 || $stock > 2147483647) {
                $error = 'Enter a whole-number stock quantity from 0 to 2147483647.';
            } elseif ($image_url === '' || mb_strlen($image_url) > 255) {
                $error = 'Enter an image path of 1 to 255 characters, such as images/vitamin-c.png.';
            }

            if ($error === '' && $id > 0) {
                $stmt = $pdo->prepare('SELECT product_id FROM products WHERE product_id = ?');
                $stmt->execute([$id]);
                if (!$stmt->fetch()) {
                    $error = 'This product no longer exists.';
                }
            }

            if ($error === '') {
                if ($id > 0) {
                    $stmt = $pdo->prepare('UPDATE products SET name = ?, description = ?,
                        category = ?, price = ?, stock = ?, image_url = ? WHERE product_id = ?');
                    $stmt->execute([$name, $description, $category, $price, $stock, $image_url, $id]);
                    $_SESSION['products_message'] = 'Product updated.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO products
                        (name, description, category, price, stock, image_url) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $description, $category, $price, $stock, $image_url]);
                    $_SESSION['products_message'] = 'Product added.';
                }
                header('Location: products.php');
                exit;
            }
        }
    }

    // 4. Fill the form when an Edit link is clicked.
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
        $id = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            $error = 'Invalid product ID.';
            $id = 0;
        } else {
            $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($product) {
                $name = $product['name'];
                $description = $product['description'];
                $category = $product['category'];
                $price = $product['price'];
                $stock = $product['stock'];
                $image_url = $product['image_url'];
            } else {
                $error = 'Product not found.';
                $id = 0;
            }
        }
    }

} catch (PDOException $e) {
    error_log($e->getMessage());
    // A foreign key also blocks deletion if a cart or order was added just now.
    if ($action === 'delete' && ($e->errorInfo[1] ?? 0) === 1451) {
        $error = 'Cannot delete this product because it is used in a cart or an order.';
    } else {
        $error = 'Unable to complete the product request. Please try again.';
    }
}

// 5. READ: load the list, even if a submitted form had an error.
try {
    $stmt = $pdo->query('SELECT * FROM products ORDER BY product_id DESC');
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Unable to load products. Please try again.';
}
$message = $_SESSION['products_message'] ?? '';
unset($_SESSION['products_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/admin.css">
</head>
<body class="admin-page">
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p><?php echo $role === 'admin' ? 'Admin Panel' : 'Storekeeper Panel'; ?></p>
    </header>
    <nav class="admin-nav" aria-label="Admin navigation">
        <?php if ($role === 'admin'): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Users</a>
        <?php endif; ?>
        <a href="products.php" aria-current="page">Products</a>
        <?php if ($role === 'admin'): ?>
            <a href="orders.php">Orders</a>
            <a href="consultation.php">Consultations</a>
        <?php endif; ?>
        <form action="../logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </nav>
    <main class="admin-content admin-products">
        <h2>Manage Products</h2>
        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert"><?php echo escape($error); ?></p>
        <?php endif; ?>
        <p>Add, view, edit and delete products.</p>
        <?php if ($message !== ''): ?>
            <p class="admin-success" role="status"><?php echo escape($message); ?></p>
        <?php endif; ?>
        <section class="consultation-card admin-form-card">
            <h3><?php echo $id > 0 ? 'Edit product' : 'Add product'; ?></h3>
            <form action="products.php" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="product_id" value="<?php echo escape($id); ?>">
                <label for="name">Product name</label>
                <input id="name" name="name" maxlength="150" required value="<?php echo escape($name); ?>">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required><?php echo escape($description); ?></textarea>
                <label for="category">Category</label>
                <input id="category" name="category" maxlength="100" required value="<?php echo escape($category); ?>">
                <label for="price">Price (RM)</label>
                <input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="<?php echo escape($price); ?>">
                <label for="stock">Stock quantity</label>
                <input id="stock" name="stock" type="number" min="0" max="2147483647" step="1" required value="<?php echo escape($stock); ?>">
                <label for="image_url">Image path</label>
                <input id="image_url" name="image_url" maxlength="255" placeholder="images/vitamin-c.png" required value="<?php echo escape($image_url); ?>">
                <p>Place the image in your project's images folder, then enter its path here.</p>
                <button type="submit"><?php echo $id > 0 ? 'Save Changes' : 'Add Product'; ?></button>
                <?php if ($id > 0): ?><a href="products.php">Cancel editing</a><?php endif; ?>
            </form>
        </section>
        <h3>Product list</h3>
        <p>Products used in carts or orders cannot be deleted.</p>
        <?php if ($error === '' && empty($products)): ?><p>No products yet. Add your first product above.</p><?php endif; ?>
        <div class="consultation-list">
            <?php foreach ($products as $product): ?>
            <article class="consultation-card">
                <h3><?php echo escape($product['name']); ?></h3>
                <p><strong>Product ID:</strong> <?php echo escape($product['product_id']); ?></p>
                <p><strong>Category:</strong> <?php echo escape($product['category']); ?></p>
                <p><?php echo nl2br(escape($product['description'])); ?></p>
                <p><strong>Price:</strong> RM <?php echo number_format((float) $product['price'], 2); ?></p>
                <p><strong>Stock:</strong> <?php echo escape($product['stock']); ?></p>
                <p><strong>Image path:</strong> <?php echo escape($product['image_url']); ?></p>
                <div class="product-actions">
                    <a class="admin-edit-link" href="products.php?edit=<?php echo escape($product['product_id']); ?>">Edit Product</a>
                    <form action="products.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="product_id" value="<?php echo escape($product['product_id']); ?>">
                        <button type="submit" name="action" value="delete" class="product-delete-button"
                                onclick="return confirm('Delete this product permanently?');">
                            Delete Product
                        </button>
                    </form>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </main>
    <footer class="admin-footer">
        <p>&copy; 2026 Price Line Pharmacy.</p>
        <a href="../homepage.php">Back to Home</a>
    </footer>
</body>
</html>