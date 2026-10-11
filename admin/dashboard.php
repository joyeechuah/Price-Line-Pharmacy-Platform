<?php
require_once __DIR__ . '/../includes/auth.php';

// Only admins can open this page.
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
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
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>

<body class="admin-page" id="page-top">

    <nav class="headbar" aria-label="Admin navigation">
        <div class="pharmacy-navbar">
            <a href="../homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
                <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
            </a>
        <a href="dashboard.php" aria-current="page">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
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



    <main class="admin-content">
        <h2>Admin Dashboard</h2>

        <p>
            Welcome,
            <?php echo htmlspecialchars($name); ?>!
        </p>

        <p>Select an area to manage.</p>

        <div class="dashboard-cards">

            <article class="dashboard-card">
                <h3>Users</h3>
                <p>View registered accounts and create staff accounts.</p>
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

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>