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
$error = '';
$requests = [];

// Read the status selected in the filter. Show all requests by default.
$status = (string) (filter_input(INPUT_GET, 'status') ?? 'all');
$validStatus = in_array($status, ['all', 'pending', 'contacted'], true);

if (!$validStatus) {
    $error = 'Please select All, Pending or Contacted.';
}

// STEP 4: Check the submitted form before updating a request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'consultation_id', FILTER_VALIDATE_INT);

    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Please reload the page and try again.';
    } elseif (!$id || $id < 1) {
        $error = 'Invalid request ID.';
    } elseif ($action !== 'contacted') {
        $error = 'Invalid action.';
    }

    if ($error === '') {
        try {
            // Only pending requests can be changed to contacted.
            $stmt = $pdo->prepare(
                "UPDATE consultations SET status = 'contacted'
                 WHERE consultation_id = ? AND status = 'pending'"
            );
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 1) {
                $_SESSION['pharmacist_message'] = 'Request marked as contacted.';

                // Redirect so refreshing does not submit the form again.
                header('Location: consultation.php');
                exit;
            } else {
                $error = 'This request no longer exists or is already marked as contacted.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Unable to update the request. Please try again.';
        }
    }
}

// STEP 5: Read requests with their customers' names and emails.
if ($validStatus) {
    try {
        $sql = 'SELECT consultations.*, users.name AS customer_name,
                       users.email AS customer_email
                FROM consultations
                JOIN users ON consultations.user_id = users.user_id';

        if ($status !== 'all') {
            $sql .= ' WHERE consultations.status = ?';
        }

        // Show the earliest preferred date and time first.
        $sql .= ' ORDER BY consultations.preferred_datetime, consultations.consultation_id';

        $stmt = $pdo->prepare($sql);

        if ($status === 'all') {
            $stmt->execute();
        } else {
            $stmt->execute([$status]);
        }

        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $error = 'Unable to load consultation requests. Please try again.';
    }
}

$message = $_SESSION['pharmacist_message'] ?? '';
unset($_SESSION['pharmacist_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/pharmacist.css">
</head>
<!-- Reuse the existing panel styles to keep the pages consistent. -->
<body class="admin-page">
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p>Pharmacist Panel</p>
    </header>

    <nav class="admin-nav" aria-label="Pharmacist navigation">
        <a href="dashboard.php">Dashboard</a>
        <a href="consultation.php" aria-current="page">Consultations</a>
        <form action="../logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </nav>

    <main class="admin-content">
        <h2>Consultation Requests</h2>
        <p>Review customer questions. After contacting a customer, mark their request as contacted.</p>

        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert"><?php echo escape($error); ?></p>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <p class="admin-success" role="status"><?php echo escape($message); ?></p>
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

        <?php if (empty($requests) && $error === ''): ?>
            <p>No consultation requests found for this selection.</p>
        <?php endif; ?>

        <div class="consultation-list">
            <?php foreach ($requests as $request): ?>
                <article class="consultation-card">
                    <h3>Request #<?php echo escape($request['consultation_id']); ?></h3>
                    <p><strong>Customer:</strong> <?php echo escape($request['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo escape($request['customer_email']); ?></p>
                    <p><strong>Contact Number:</strong> <?php echo escape($request['contact_number']); ?></p>
                    <p><strong>Preferred Date and Time:</strong> <?php echo escape($request['preferred_datetime']); ?></p>
                    <p><strong>Submitted:</strong> <?php echo escape($request['created_at']); ?></p>
                    <p><strong>Question:</strong><br><?php echo nl2br(escape($request['question'])); ?></p>
                    <p>
                        <strong>Status:</strong>
                        <span class="consultation-status"><?php echo escape(ucfirst($request['status'])); ?></span>
                    </p>

                    <form action="consultation.php" method="POST" class="consultation-actions">
                        <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="consultation_id" value="<?php echo escape($request['consultation_id']); ?>">
                        <button type="submit" name="action" value="contacted"
                            <?php if ($request['status'] === 'contacted') echo 'disabled'; ?>>
                            Mark as Contacted
                        </button>
                    </form>
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
