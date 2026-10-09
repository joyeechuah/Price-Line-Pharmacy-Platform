<?php
require_once __DIR__ . '/../includes/auth.php';

// Only admins can open this page.
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

$name = $_SESSION['user']['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard | Price Line Pharmacy</title>

    <link rel="stylesheet" href="../CSS/admin.css">
</head>

<body class="admin-page">

    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p>Admin Panel</p>
    </header>

    <nav class="admin-nav" aria-label="Admin navigation">
        <a href="dashboard.php" aria-current="page">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="consultation.php">Consultations</a>

        <form action="../logout.php" method="POST">
            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </nav>

    <main class="admin-content">
        <h2>Admin Dashboard</h2>

        <p>
            Welcome,
            <?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>!
        </p>

        <p>Select an area to manage.</p>

        <div class="dashboard-cards">

            <article class="dashboard-card">
                <h3>Users</h3>
                <p>Manage customer accounts and create staff accounts.</p>
                <a href="users.php">Manage Users</a>
            </article>

            <article class="dashboard-card">
                <h3>Products</h3>
                <p>Add products, update prices, and manage stock.</p>
                <a href="products.php">Manage Products</a>
            </article>

            <article class="dashboard-card">
                <h3>Orders</h3>
                <p>View customer orders and purchased items.</p>
                <a href="orders.php">View Orders</a>
            </article>

            <article class="dashboard-card">
                <h3>Consultations</h3>
                <p>Review requests and update their contact status.</p>
                <a href="consultation.php">Manage Consultations</a>
            </article>

        </div>
    </main>

    <footer class="admin-footer">
        <p>&copy; 2026 Price Line Pharmacy.</p>
        <a href="../homepage.php">Back to Home</a>
    </footer>

</body>
</html>