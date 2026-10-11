<?php
// STEP 1: Check login and allow admins only.
require_once __DIR__ . '/../includes/auth.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// STEP 2: Connect to the database.
require_once __DIR__ . '/../config/database.php';

// STEP 3: Update or delete a request when a button is clicked.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['consultation_id'] ?? 0);

    if ($action === 'contacted') {
        $stmt = $pdo->prepare("UPDATE consultations SET status = 'contacted' WHERE consultation_id = ?");
        $stmt->execute([$id]);
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM consultations WHERE consultation_id = ?');
        $stmt->execute([$id]);
    }

    // Reload without repeating the action on refresh.
    header('Location: consultation.php');
    exit;
}

// STEP 4: Load requests to display on the page.
$stmt = $pdo->query(
    'SELECT consultations.*, users.name AS customer_name
     FROM consultations
     JOIN users ON consultations.user_id = users.user_id
     ORDER BY consultations.created_at DESC'
);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Consultation Requests | Price Line Pharmacy</title>

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
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="consultation.php" aria-current="page">Consultations</a>
        <div class="account-links">
        <form action="../logout.php" method="POST">
        <button type="submit" >
        Log Out
    </button>
    </form>
        </div>
        </div>
    </nav>
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p>Admin Panel</p>
    </header>



    <main class="admin-content">
        <h2>Consultation Requests</h2>

        <p>
            Review customer questions and update their contact status.
        </p>

        <?php if (empty($requests)): ?>
            <p>No consultation requests yet.</p>
        <?php endif; ?>

        <div class="consultation-list">

            <?php foreach ($requests as $request): ?>

                <article class="consultation-card">
                    <h3>
                        Request #<?php echo htmlspecialchars($request['consultation_id']); ?>
                    </h3>

                    <p>
                        <strong>Customer:</strong>
                        <?php echo htmlspecialchars($request['customer_name']); ?>
                    </p>

                    <p>
                        <strong>Contact Number:</strong>
                        <?php echo htmlspecialchars($request['contact_number']); ?>
                    </p>

                    <p>
                        <strong>Preferred Date and Time:</strong>
                        <?php echo htmlspecialchars($request['preferred_datetime']); ?>
                    </p>

                    <p>
                        <strong>Question:</strong><br>
                        <?php echo nl2br(htmlspecialchars($request['question'])); ?>
                    </p>

                    <p>
                        <strong>Status:</strong>

                        <span class="consultation-status">
                            <?php echo htmlspecialchars(ucfirst($request['status'])); ?>
                        </span>
                    </p>

                    <form method="POST"
                          action="consultation.php"
                          class="consultation-actions">

                        <input type="hidden"
                               name="consultation_id"
                               value="<?php echo htmlspecialchars($request['consultation_id']); ?>">

                        <button type="submit"
                                name="action"
                                value="contacted"
                                <?php if ($request['status'] === 'contacted') {
                                    echo 'disabled';
                                } ?>>
                            Mark as Contacted
                        </button>

                        <button type="submit"
                                name="action"
                                value="delete"
                                class="delete-button"
                                onclick="return confirm('Delete this request permanently?');">
                            Delete
                        </button>
                    </form>
                </article>

            <?php endforeach; ?>

        </div>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>

</body>
</html>