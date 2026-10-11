<?php
// STEP 1: Check login and allow admins and storekeepers.
require_once __DIR__ . '/../includes/auth.php';

if (!isAdmin() && !isStorekeeper()) {
    header('Location: ../login.php');
    exit;
}

// STEP 2: Include the database connection.
require_once __DIR__ . '/../config/database.php';

// STEP 3: Set the starting values for the form.
$error = '';
$id = 0; // Zero means a new product. An existing ID means editing a product.
$name = '';
$description = '';
$category = '';
$price = '';
$stock = 0;
$image_url = '';
$categories = [
    'Vitamins and Supplements',
    'Skincare',
    'Mom and Baby',
    'Medical Supplies',
    'Healthy Food and Beverages'
];
$message = $_SESSION['products_message'] ?? '';
unset($_SESSION['products_message']);

// STEP 4: Handle the submitted form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['product_id'] ?? 0);

    if ($action === 'delete') {
        // STEP 5 - DELETE: Clear the product from carts first.
        $stmt = $pdo->prepare('DELETE FROM cart_items WHERE product_id = ?');
        $stmt->execute([$id]);

        // Purchased products are archived, so the order history is kept.
        $stmt = $pdo->prepare('SELECT order_item_id FROM order_items WHERE product_id = ?');
        $stmt->execute([$id]);

        if ($stmt->fetch()) {
            $stmt = $pdo->prepare('UPDATE products SET is_active = 0 WHERE product_id = ?');
            $stmt->execute([$id]);
            $_SESSION['products_message'] = 'Product archived. Existing orders are kept.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM products WHERE product_id = ?');
            $stmt->execute([$id]);
            $_SESSION['products_message'] = 'Product deleted.';
        }

        header('Location: products.php');
        exit;
    } elseif ($action === 'restore') {
        $stmt = $pdo->prepare('UPDATE products SET is_active = 1 WHERE product_id = ?');
        $stmt->execute([$id]);

        $_SESSION['products_message'] = 'Product restored.';
        header('Location: products.php');
        exit;
    } elseif ($action === 'save') {
        // STEP 6: Read and check the Add / Edit form.
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category    = $_POST['category'] ?? '';
        $price       = (float) ($_POST['price'] ?? 0);
        $stock       = (int) ($_POST['stock'] ?? 0);
        $image_url   = trim($_POST['image_url'] ?? '');

        if (empty($name) || empty($description) || empty($category) || empty($image_url)) {
            $error = 'All fields are required.';
        } elseif ($price < 0 || $stock < 0) {
            $error = 'The price and stock cannot be negative.';
        } elseif (strpos($image_url, 'images/') !== 0 || !is_file(__DIR__ . '/../' . $image_url)) {
            // The path must start with images/ and the file must exist.
            $error = 'Choose an existing image in the images folder, for example images/vitamin-c.png.';
        } elseif ($id > 0) {
            // STEP 7 - UPDATE: Save the changes to an existing product.
            $stmt = $pdo->prepare(
                'UPDATE products SET name = :name, description = :description, category = :category,
                 price = :price, stock = :stock, image_url = :image_url WHERE product_id = :product_id'
            );
            $stmt->execute([
                ':name'        => $name,
                ':description' => $description,
                ':category'    => $category,
                ':price'       => $price,
                ':stock'       => $stock,
                ':image_url'   => $image_url,
                ':product_id'  => $id,
            ]);

            // Redirect so refreshing the page does not submit the form again.
            $_SESSION['products_message'] = 'Product updated.';
            header('Location: products.php');
            exit;
        } else {
            // STEP 7 - CREATE: Add a new product.
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, description, category, price, stock, image_url)
                 VALUES (:name, :description, :category, :price, :stock, :image_url)'
            );
            $stmt->execute([
                ':name'        => $name,
                ':description' => $description,
                ':category'    => $category,
                ':price'       => $price,
                ':stock'       => $stock,
                ':image_url'   => $image_url,
            ]);

            $_SESSION['products_message'] = 'Product added.';
            header('Location: products.php');
            exit;
        }
    }
}

// STEP 8: Fill in the form when an Edit Product link is clicked.
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $id          = $product['product_id'];
        $name        = $product['name'];
        $description = $product['description'];
        $category    = $product['category'];
        $price       = $product['price'];
        $stock       = $product['stock'];
        $image_url   = $product['image_url'];
    } else {
        $error = 'Product not found.';
    }
}

// STEP 9 - READ: Load the product list.
$stmt = $pdo->query('SELECT * FROM products ORDER BY product_id DESC');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set the labels here so the HTML below stays easy to read.
$panelTitle = 'Storekeeper Panel';
if (isAdmin()) {
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
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<body class="admin-page" id="page-top">
    <nav class="headbar" aria-label="Admin navigation">
        <div class="pharmacy-navbar">
            <a href="../homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
                <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
            </a>
        <?php if (isAdmin()): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">Users</a>
        <?php endif; ?>
        <a href="products.php" aria-current="page">Products</a>
        <?php if (isAdmin()): ?>
            <a href="orders.php">Orders</a>
            <a href="consultation.php">Consultations</a>
        <?php endif; ?>
        <div class="account-links">
        <form action="../logout.php" method="POST">
            <button type="submit" >Log Out</button>
        </form>
        </div>
        </div>
    </nav>
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p><?php echo htmlspecialchars($panelTitle); ?></p>
    </header>

    <main class="admin-content admin-products">
        <h2>Manage Products</h2>
        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <p>Add, view, edit and delete products.</p>
        <?php if ($message !== ''): ?>
            <p class="admin-success" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <section class="consultation-card admin-form-card">
            <h3><?php echo htmlspecialchars($formTitle); ?></h3>
            <form action="products.php" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($id); ?>">
                <label for="name">Product name</label>
                <input id="name" name="name" maxlength="150" required value="<?php echo htmlspecialchars($name); ?>">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($description); ?></textarea>
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="">Choose a category</option>
                    <?php foreach ($categories as $choice): ?>
                        <option value="<?php echo htmlspecialchars($choice); ?>" <?php if ($category === $choice) echo 'selected'; ?>><?php echo htmlspecialchars($choice); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="form-help">Use one of these categories so customers can find the product.</p>
                <label for="price">Price (RM)</label>
                <input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="<?php echo htmlspecialchars($price); ?>">
                <label for="stock">Stock quantity</label>
                <input id="stock" name="stock" type="number" min="0" max="2147483647" step="1" required value="<?php echo htmlspecialchars($stock); ?>">
                <label for="image_url">Image path</label>
                <input id="image_url" name="image_url" maxlength="255" placeholder="images/vitamin-c.png" required value="<?php echo htmlspecialchars($image_url); ?>">
                <p>Place the image in your project's images folder, then enter its path here.</p>
                <button type="submit"><?php echo htmlspecialchars($buttonText); ?></button>
                <?php if ($id > 0): ?><a href="products.php">Cancel editing</a><?php endif; ?>
            </form>
        </section>
        <h3>Product list</h3>
        <p>Removing a product clears it from carts. Purchased products are archived to keep order history.</p>
        <?php if ($error === '' && empty($products)): ?><p>No products yet. Add your first product above.</p><?php endif; ?>
        <div class="consultation-list">
            <?php foreach ($products as $product): ?>
            <article class="consultation-card">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p><strong>Product ID:</strong> <?php echo htmlspecialchars($product['product_id']); ?></p>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <p><strong>Price:</strong> RM <?php echo number_format((float) $product['price'], 2); ?></p>
                <p><strong>Status:</strong> <?php echo $product['is_active'] ? 'Active' : 'Archived'; ?></p>
                <p><strong>Stock:</strong> <?php echo htmlspecialchars($product['stock']); ?></p>
                <p><strong>Image path:</strong> <?php echo htmlspecialchars($product['image_url']); ?></p>
                <div class="product-actions">
                    <a class="admin-edit-link" href="products.php?edit=<?php echo htmlspecialchars($product['product_id']); ?>">Edit Product</a>
                    <form action="products.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['product_id']); ?>">
                        <?php if (!$product['is_active']): ?>
                        <button type="submit" name="action" value="restore">Restore Product</button>
                        <?php else: ?>
                        <button type="submit" name="action" value="delete" class="product-delete-button"
                                onclick="return confirm('Remove this product from the shop and all carts? Purchased products will be archived.');">
                            Remove Product
                        </button>
                        <?php endif; ?>
                    </form>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </main>
<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>