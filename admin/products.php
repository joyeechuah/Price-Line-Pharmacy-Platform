<?php
// STEP 1: Check login and allow admins and storekeepers.
require_once __DIR__ . '/../includes/auth.php';
$role = $_SESSION['user']['role'] ?? '';

if ($role !== 'admin' && $role !== 'storekeeper') {
    http_response_code(403);
    exit('Access denied.');
}

// STEP 2: Include the database connection.
require_once __DIR__ . '/../config/database.php';

// Safely display text in HTML.
function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Check a price such as 12, 12.5 or 12.50 without a regular expression.
function isValidPrice($price) {
    // explode splits the price at the decimal point: 12.50 becomes 12 and 50.
    $parts = explode('.', $price);

    // ctype_digit checks that a string contains only digits from 0 to 9.
    if (!ctype_digit($parts[0]) || strlen($parts[0]) > 8) {
        return false;
    }

    if (count($parts) > 2) {
        return false;
    }

    if (count($parts) === 2) {
        if (!ctype_digit($parts[1]) || strlen($parts[1]) > 2) {
            return false;
        }
    }

    return true;
}

// STEP 3: Set the starting values for the form and product list.
$error = '';
$products = [];
$id = 0; // Zero means a new product. An existing ID means editing a product.
$name = '';
$description = '';
$category = '';
$price = '';
$stock = '0';
$image_url = '';
$action = '';
$categories = array(
    'Vitamins and Supplements',
    'Skincare',
    'Mom and Baby',
    'Medical Supplies',
    'Healthy Food and Beverages'
);

try {
    // STEP 4: Check the submitted form before changing products.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Each field in this form must contain one text value, not an array.
        foreach ($_POST as $value) {
            if (!is_string($value)) {
                $error = 'Invalid form information.';
                break;
            }
        }

        if ($error === '') {
            $action = $_POST['action'] ?? '';
            $token = $_POST['csrf_token'] ?? '';

            // FILTER_VALIDATE_INT checks for a whole number; invalid input returns false.
            $submittedId = filter_var($_POST['product_id'] ?? '', FILTER_VALIDATE_INT);

            // The token is created in auth.php and included in the hidden form field.
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                $error = 'Please reload the page and try again.';
            } elseif ($action !== 'save' && $action !== 'delete') {
                $error = 'Invalid action.';
            } elseif ($submittedId === false || $submittedId < 0) {
                $error = 'Invalid product ID.';
            } elseif ($action === 'delete' && $submittedId === 0) {
                $error = 'Select a product to delete.';
            }
        }

        // STEP 5 - DELETE: Check whether the product can be removed.
        if ($error === '' && $action === 'delete') {
            $statement = $pdo->prepare('SELECT product_id FROM products WHERE product_id = :product_id');
            $statement->execute(array(':product_id' => $submittedId));
            $product = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $error = 'Product not found. It may already have been deleted.';
            } else {
                $statement = $pdo->prepare('SELECT COUNT(*) AS total FROM cart_items WHERE product_id = :product_id');
                $statement->execute(array(':product_id' => $submittedId));
                $cartResult = $statement->fetch(PDO::FETCH_ASSOC);

                $statement = $pdo->prepare('SELECT COUNT(*) AS total FROM order_items WHERE product_id = :product_id');
                $statement->execute(array(':product_id' => $submittedId));
                $orderResult = $statement->fetch(PDO::FETCH_ASSOC);

                if ($cartResult['total'] > 0 || $orderResult['total'] > 0) {
                    $error = 'Cannot delete this product because it is used in a cart or an order.';
                } else {
                    $statement = $pdo->prepare('DELETE FROM products WHERE product_id = :product_id');
                    $statement->execute(array(':product_id' => $submittedId));

                    // rowCount tells us how many products were deleted.
                    if ($statement->rowCount() === 1) {
                        $_SESSION['products_message'] = 'Product deleted.';
                        header('Location: products.php');
                        exit;
                    }
                    $error = 'Product not found. It may already have been deleted.';
                }
            }
        }

        // STEP 6: Read and validate the Add / Edit form.
        if ($error === '' && $action === 'save') {
            $id = $submittedId;
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category = trim($_POST['category'] ?? '');
            // Accept the same category names with either 'and' or '&'.
            foreach ($categories as $choice) {
                if (strtolower(str_replace('&', 'and', $category)) === strtolower($choice)) {
                    $category = $choice;
                    break;
                }
            }
            $price = trim($_POST['price'] ?? '');
            $stock = filter_var($_POST['stock'] ?? '', FILTER_VALIDATE_INT);
            $image_url = trim($_POST['image_url'] ?? '');

            // Check required values and the limits of the products table.
            // mb_strlen counts characters, including non-English characters.
            if ($name === '' || mb_strlen($name) > 150) {
                $error = 'Enter a product name of 1 to 150 characters.';
            } elseif ($description === '' || strlen($description) > 65535) {
                $error = 'Enter a description shorter than 65,536 bytes.';
            } elseif (!in_array($category, $categories, true)) {
                $error = 'Choose one of the five product categories.';
            } elseif (!isValidPrice($price)) {
                $error = 'Enter a price from 0 to 99999999.99, with up to two decimal places.';
            } elseif ($stock === false || $stock < 0 || $stock > 2147483647) {
                $error = 'Enter a whole-number stock quantity from 0 to 2147483647.';
            } elseif ($image_url === '' || mb_strlen($image_url) > 255) {
                $error = 'Enter an image path of 1 to 255 characters, such as images/vitamin-c.png.';
            }

            // Before editing, check that this product still exists.
            if ($error === '' && $id > 0) {
                $statement = $pdo->prepare('SELECT product_id FROM products WHERE product_id = :product_id');
                $statement->execute(array(':product_id' => $id));
                if (!$statement->fetch(PDO::FETCH_ASSOC)) {
                    $error = 'This product no longer exists.';
                }
            }

            // STEP 7 - CREATE / UPDATE: Save valid product details.
            if ($error === '') {
                // The names here match the placeholders in the SQL below.
                $values = array(
                    ':name' => $name,
                    ':description' => $description,
                    ':category' => $category,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':image_url' => $image_url
                );

                if ($id > 0) {
                    $statement = $pdo->prepare(
                        'UPDATE products SET name = :name, description = :description,
                         category = :category, price = :price, stock = :stock,
                         image_url = :image_url WHERE product_id = :product_id'
                    );
                    $values[':product_id'] = $id;
                    $statement->execute($values);
                    $_SESSION['products_message'] = 'Product updated.';
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO products (name, description, category, price, stock, image_url)
                         VALUES (:name, :description, :category, :price, :stock, :image_url)'
                    );
                    $statement->execute($values);
                    $_SESSION['products_message'] = 'Product added.';
                }

                // Redirect so refreshing the page does not submit the form again.
                header('Location: products.php');
                exit;
            }
        }
    }

    // STEP 8: Fill in the form when an Edit Product link is clicked.
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['edit'])) {
        $id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            $error = 'Invalid product ID.';
            $id = 0;
        } else {
            $statement = $pdo->prepare('SELECT * FROM products WHERE product_id = :product_id');
            $statement->execute(array(':product_id' => $id));
            $product = $statement->fetch(PDO::FETCH_ASSOC);

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
    // MySQL error 1451 means another table still refers to this product.
    // This also handles a cart or order being added just before deletion.
    if ($action === 'delete' && ($e->errorInfo[1] ?? 0) == 1451) {
        $error = 'Cannot delete this product because it is used in a cart or an order.';
    } else {
        $error = 'Unable to complete the product request. Please try again.';
    }
}

// STEP 9 - READ: Load the product list, even if a submitted form had an error.
try {
    $statement = $pdo->prepare('SELECT * FROM products ORDER BY product_id DESC');
    $statement->execute();
    $products = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Unable to load products. Please try again.';
}

// Show the success message once after a redirect.
$message = $_SESSION['products_message'] ?? '';
unset($_SESSION['products_message']);

// Set the labels here so the HTML below stays easy to read.
$panelTitle = 'Storekeeper Panel';
if ($role === 'admin') {
    $panelTitle = 'Admin Panel';
}

$formTitle = 'Add product';
$buttonText = 'Add Product';
if ($id > 0) {
    $formTitle = 'Edit product';
    $buttonText = 'Save Changes';
}
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
        <p><?php echo escape($panelTitle); ?></p>
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
            <h3><?php echo escape($formTitle); ?></h3>
            <form action="products.php" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="product_id" value="<?php echo escape($id); ?>">
                <label for="name">Product name</label>
                <input id="name" name="name" maxlength="150" required value="<?php echo escape($name); ?>">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required><?php echo escape($description); ?></textarea>
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $choice): ?>
                        <option value="<?php echo escape($choice); ?>" <?php if (strtolower(str_replace('&', 'and', trim($category))) === strtolower($choice)) echo 'selected'; ?>><?php echo escape($choice); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-help">Use one of these categories so customers can find the product.</p>
                <label for="price">Price (RM)</label>
                <input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="<?php echo escape($price); ?>">
                <label for="stock">Stock quantity</label>
                <input id="stock" name="stock" type="number" min="0" max="2147483647" step="1" required value="<?php echo escape($stock); ?>">
                <label for="image_url">Image path</label>
                <input id="image_url" name="image_url" maxlength="255" placeholder="images/vitamin-c.png" required value="<?php echo escape($image_url); ?>">
                <p>Place the image in your project's images folder, then enter its path here.</p>
                <button type="submit"><?php echo escape($buttonText); ?></button>
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