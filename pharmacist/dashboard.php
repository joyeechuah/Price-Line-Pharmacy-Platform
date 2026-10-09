<?php
// STEP 1: Check login and allow pharmacists only.
require_once __DIR__ . '/../includes/auth.php';

if (($_SESSION['user']['role'] ?? '') !== 'pharmacist') {
    http_response_code(403);
    exit('Access denied.');
}

// STEP 2: Connect to the database.
require_once __DIR__ . '/../config/database.php';

// Display text safely in HTML.
function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// STEP 3: Set starting values.
$name = $_SESSION['user']['name'] ?? 'Pharmacist';
$error = '';
$pending = 0;
$contacted = 0;

// STEP 4: Count the requests for each status.
try {
    $stmt = $pdo->query(
        'SELECT status, COUNT(*) AS request_count
         FROM consultations GROUP BY status'
    );
    $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($counts as $count) {
        if ($count['status'] === 'pending') {
            $pending = (int) $count['request_count'];
        } elseif ($count['status'] === 'contacted') {
            $contacted = (int) $count['request_count'];
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Unable to load consultation counts. Please try again.';
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
</head>
<!-- Reuse the existing panel styles to keep the pages consistent. -->
<body class="admin-page">
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p>Pharmacist Panel</p>
    </header>

    <nav class="admin-nav" aria-label="Pharmacist navigation">
        <a href="dashboard.php" aria-current="page">Dashboard</a>
        <a href="consultation.php">Consultations</a>
        <form action="../logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </nav>

    <main class="admin-content">
        <h2>Pharmacist Dashboard</h2>
        <p>Welcome, <?php echo escape($name); ?>!</p>
        <p>Review consultation requests and contact customers at their preferred times.</p>

        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert"><?php echo escape($error); ?></p>
        <?php else: ?>
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
        <?php endif; ?>

        <p><a href="consultation.php">View All Consultation Requests</a></p>
    </main>

    <footer class="admin-footer">
        <p>&copy; 2026 Price Line Pharmacy.</p>
        <a href="../homepage.php">Back to Home</a>
    </footer>
</body>
</html>
