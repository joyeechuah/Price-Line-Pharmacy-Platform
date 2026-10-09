<?php
require_once __DIR__ . '/../includes/auth.php';

// Only admins can open this page.
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

// 2. Connect to the database.
require_once __DIR__ . '/../config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$error = '';
$requests = [];

// Safely display text in HTML.
function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// Create a security token for the forms.
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

try {
    // 3. Check the form when a button is clicked.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? '';
        $action = $_POST['action'] ?? '';
        $id = filter_input(INPUT_POST, 'consultation_id', FILTER_VALIDATE_INT);

        if (!is_string($token) ||
            !hash_equals($_SESSION['csrf_token'], $token)) {
            $error = 'Please reload the page and try again.';
        } elseif (!$id || $id < 1) {
            $error = 'Invalid request ID.';
        } elseif ($action !== 'contacted' && $action !== 'delete') {
            $error = 'Invalid action.';
        }

        // Change the database only if the form is valid.
        if ($error === '') {
            if ($action === 'contacted') {
                $sql = "UPDATE consultations
                        SET status = 'contacted'
                        WHERE consultation_id = ?";
            } else {
                $sql = "DELETE FROM consultations
                        WHERE consultation_id = ?";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);

            // Reload without repeating the action on refresh.
            header('Location: consultation.php');
            exit;
        }
    }

    // 4. Load requests to display on the page.
    $sql = "SELECT consultations.*, users.name AS customer_name
            FROM consultations
            JOIN users ON consultations.user_id = users.user_id
            ORDER BY consultations.created_at DESC";

    $stmt = $pdo->query($sql);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'A database error occurred. Please try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Consultation Requests | Price Line Pharmacy</title>

    <link rel="stylesheet" href="../CSS/admin.css">
</head>

<body class="admin-page">

    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p>Admin Panel</p>
    </header>

    <nav class="admin-nav" aria-label="Admin navigation">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Users</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="consultation.php" aria-current="page">Consultations</a>
        <form action="../logout.php" method="POST">
        <input type="hidden"
           name="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <button type="submit" class="logout-button">
        Log Out
    </button>
    </form>
    </nav>

    <main class="admin-content">
        <h2>Consultation Requests</h2>

        <p>
            Review customer questions and update their contact status.
        </p>

        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert">
                <?php echo escape($error); ?>
            </p>
        <?php endif; ?>

        <?php if (empty($requests) && $error === ''): ?>
            <p>No consultation requests yet.</p>
        <?php endif; ?>

        <div class="consultation-list">

            <?php foreach ($requests as $request): ?>

                <article class="consultation-card">
                    <h3>
                        Request #<?php echo escape($request['consultation_id']); ?>
                    </h3>

                    <p>
                        <strong>Customer:</strong>
                        <?php echo escape($request['customer_name']); ?>
                    </p>

                    <p>
                        <strong>Contact Number:</strong>
                        <?php echo escape($request['contact_number']); ?>
                    </p>

                    <p>
                        <strong>Preferred Date and Time:</strong>
                        <?php echo escape($request['preferred_datetime']); ?>
                    </p>

                    <p>
                        <strong>Question:</strong><br>
                        <?php echo nl2br(escape($request['question'])); ?>
                    </p>

                    <p>
                        <strong>Status:</strong>

                        <span class="consultation-status">
                            <?php echo escape(ucfirst($request['status'])); ?>
                        </span>
                    </p>

                    <form method="POST"
                          action="consultation.php"
                          class="consultation-actions">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?php echo escape($_SESSION['csrf_token']); ?>">

                        <input type="hidden"
                               name="consultation_id"
                               value="<?php echo escape($request['consultation_id']); ?>">

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

    <footer class="admin-footer">
        <p>&copy; 2026 Price Line Pharmacy.</p>
        <a href="../homepage.php">Back to Home</a>
    </footer>

</body>
</html>