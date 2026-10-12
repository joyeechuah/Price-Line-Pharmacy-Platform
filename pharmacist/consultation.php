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
$message = $_SESSION['pharmacist_message'] ?? '';
unset($_SESSION['pharmacist_message']);

// Read the status selected in the filter. Show all requests by default.
$status = $_GET['status'] ?? 'all';
if ($status !== 'pending' && $status !== 'contacted') {
    $status = 'all';
}

// STEP 4: Mark a request as contacted when its button is clicked.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['consultation_id'] ?? 0);

    $stmt = $pdo->prepare("UPDATE consultations SET status = 'contacted' WHERE consultation_id = ?");
    $stmt->execute([$id]);

    // Redirect so refreshing does not submit the form again.
    $_SESSION['pharmacist_message'] = 'Request marked as contacted.';
    header('Location: consultation.php?status=' . $status);
    exit;
}

// STEP 5: Read requests with their customers' names and emails.
// Show the earliest preferred date and time first.
if ($status === 'all') {
    $stmt = $pdo->prepare(
        'SELECT consultations.*, users.name AS customer_name, users.email AS customer_email
         FROM consultations
         JOIN users ON consultations.user_id = users.user_id
         ORDER BY consultations.preferred_datetime, consultations.consultation_id'
    );
    $stmt->execute();
} else {
    $stmt = $pdo->prepare(
        'SELECT consultations.*, users.name AS customer_name, users.email AS customer_email
         FROM consultations
         JOIN users ON consultations.user_id = users.user_id
         WHERE consultations.status = ?
         ORDER BY consultations.preferred_datetime, consultations.consultation_id'
    );
    $stmt->execute([$status]);
}
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/pharmacist.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<!-- Reuse the existing panel styles to keep the pages consistent. -->
<body class="admin-page" id="page-top">
<?php $currentPage = 'consultation'; require __DIR__ . '/../includes/staff-navbar.php'; ?>

    <main class="admin-content">
        <h2>Consultation Requests</h2>
        <p>Review customer questions. After contacting a customer, mark their request as contacted.</p>

        <?php if ($message !== ''): ?>
            <p class="admin-success" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <section class="consultation-card admin-form-card">
            <h3>Filter Requests</h3>
            <form action="consultation.php" method="GET" class="admin-form">
                <label for="status">Contact Status</label>
                <select id="status" name="status">
                    <option value="all" <?php if ($status === 'all') echo 'selected'; ?>>All</option>
                    <option value="pending" <?php if ($status === 'pending') echo 'selected'; ?>>Pending</option>
                    <option value="contacted" <?php if ($status === 'contacted') echo 'selected'; ?>>Contacted</option>
                </select>
                <button type="submit">Show Requests</button>
            </form>
        </section>

        <?php if (empty($requests)): ?>
            <p>No consultation requests found for this selection.</p>
        <?php endif; ?>

        <div class="consultation-list">
            <?php foreach ($requests as $request): ?>
                <article class="consultation-card">
                    <h3>Request #<?php echo htmlspecialchars($request['consultation_id']); ?></h3>
                    <p><strong>Customer:</strong> <?php echo htmlspecialchars($request['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($request['customer_email']); ?></p>
                    <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($request['contact_number']); ?></p>
                    <p><strong>Preferred Date and Time:</strong> <?php echo htmlspecialchars($request['preferred_datetime']); ?></p>
                    <p><strong>Submitted:</strong> <?php echo htmlspecialchars($request['created_at']); ?></p>
                    <p><strong>Question:</strong><br><?php echo nl2br(htmlspecialchars($request['question'])); ?></p>
                    <p>
                        <strong>Status:</strong>
                        <span class="consultation-status"><?php echo htmlspecialchars(ucfirst($request['status'])); ?></span>
                    </p>

                    <form action="consultation.php?status=<?php echo htmlspecialchars($status); ?>" method="POST" class="consultation-actions">
                        <input type="hidden" name="consultation_id" value="<?php echo htmlspecialchars($request['consultation_id']); ?>">
                        <button type="submit" name="action" value="contacted"
                            <?php if ($request['status'] === 'contacted') echo 'class="disabled-button" disabled'; ?>>
                            Mark as Contacted
                        </button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
