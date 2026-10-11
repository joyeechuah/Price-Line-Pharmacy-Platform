<?php
// STEP 1: Check login and allow pharmacists only.
require_once __DIR__ . '/../includes/auth.php';

if (!isPharmacist()) {
    header('Location: ../login.php');
    exit;
}

// STEP 2: Connect to the database.
require_once __DIR__ . '/../config/database.php';

// STEP 3: Set starting values.
$name = $_SESSION['user']['name'];
$pending = 0;
$contacted = 0;

// STEP 4: Count the requests for each status.
$stmt = $pdo->query('SELECT status, COUNT(*) AS request_count FROM consultations GROUP BY status');
$counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($counts as $count) {
    if ($count['status'] === 'pending') {
        $pending = $count['request_count'];
    } elseif ($count['status'] === 'contacted') {
        $contacted = $count['request_count'];
    }
}

$total = $pending + $contacted;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Dashboard | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/pharmacist.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<!-- Reuse the existing panel styles to keep the pages consistent. -->
<body class="admin-page" id="page-top">
    <nav class="headbar" aria-label="Pharmacist navigation">
        <div class="pharmacy-navbar">
            <a href="../homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
                <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
            </a>
        <a href="dashboard.php" class="current-page" aria-current="page">Dashboard</a>
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
        <p>Pharmacist Panel</p>
    </header>



    <main class="admin-content">
        <h2>Pharmacist Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($name); ?>!</p>
        <p>Review consultation requests and contact customers at their preferred times.</p>

        <p><strong>Total Requests:</strong> <?php echo $total; ?></p>

        <div class="dashboard-cards">
            <article class="dashboard-card">
                <h3>Pending Requests</h3>
                <p><strong><?php echo $pending; ?></strong> awaiting contact.</p>
                <a href="consultation.php?status=pending">View Pending Requests</a>
            </article>

            <article class="dashboard-card">
                <h3>Contacted Customers</h3>
                <p><strong><?php echo $contacted; ?></strong> requests marked as contacted.</p>
                <a href="consultation.php?status=contacted">View Contacted Requests</a>
            </article>
        </div>

        <p><a href="consultation.php">View All Consultation Requests</a></p>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
