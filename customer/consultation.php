<?php
// STEP 1: Check login and allow customers only.
require_once __DIR__ . '/../includes/auth.php';
if (($_SESSION['user']['role'] ?? '') !== 'customer') {
    http_response_code(403);
    exit('Access denied.');
}

require_once __DIR__ . '/../config/database.php';
// Preferred contact times are entered and displayed in Malaysia time.
date_default_timezone_set('Asia/Kuala_Lumpur');

function escape($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// STEP 2: Prepare the form and messages.
$userId = $_SESSION['user']['user_id'];
$contactNumber = '';
$preferredDatetime = '';
$question = '';
$error = '';
$loadError = '';
$requests = [];
$cartCount = null;
$message = $_SESSION['customer_consultation_message'] ?? '';
unset($_SESSION['customer_consultation_message']);

// STEP 3: Check the form before saving a request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) filter_input(INPUT_POST, 'csrf_token');
    $contactNumber = trim((string) filter_input(INPUT_POST, 'contact_number'));
    $preferredDatetime = (string) filter_input(INPUT_POST, 'preferred_datetime');
    $question = trim((string) filter_input(INPUT_POST, 'question'));
    $preferredTime = strtotime($preferredDatetime);

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Please reload the page and try again.';
    } elseif ($contactNumber === '' || mb_strlen($contactNumber) > 30) {
        $error = 'Enter a contact number of up to 30 characters.';
    } elseif (strlen($preferredDatetime) !== 16 || $preferredTime === false || date('Y-m-d\TH:i', $preferredTime) !== $preferredDatetime) {
        $error = 'Please choose a valid date and time.';
    } elseif ($preferredTime <= time()) {
        $error = 'Please choose a future date and time.';
    } elseif ($question === '' || mb_strlen($question) > 2000) {
        $error = 'Enter a question of 1 to 2,000 characters.';
    }

    // STEP 4: Save it under the logged-in customer's account.
    if ($error === '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO consultations
                (user_id, contact_number, preferred_datetime, question, status)
                VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$userId, $contactNumber, date('Y-m-d H:i:s', $preferredTime), $question]);

            $_SESSION['customer_consultation_message'] = 'Your request has been sent. You can check its status below.';
            // Redirect so refreshing does not submit the form again.
            header('Location: consultation.php');
            exit;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Unable to send your request. Please try again.';
        }
    }
}

// STEP 5: Read only this customer's consultation history.
try {
    $stmt = $pdo->prepare('SELECT consultation_id, contact_number, preferred_datetime, question, status, created_at
        FROM consultations WHERE user_id = ? ORDER BY created_at DESC, consultation_id DESC');
    $stmt->execute([$userId]);
    $requests = $stmt->fetchAll();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    $cartCount = (int) $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $loadError = 'Unable to load your consultation history. Please try again later.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Consultations | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/customer.css">
</head>
<body class="customer-page" id="page-top">
    <header class="customer-header">
        <nav class="customer-nav" aria-label="Main navigation">
            <a href="../homepage.php">Home</a>
            <a href="products.php">Products</a>
            <a href="../membership-benefits.php">Membership Benefits</a>
            <a href="consultation.php" aria-current="page">Consultations</a>
            <a href="orders.php">My Orders</a>
            <div class="customer-account-links">
                <a href="cart.php" class="customer-cart-link"><img src="../images/cart-icon.svg" class="customer-cart-icon" alt="" width="28" height="28"> <span>Cart<?php if ($cartCount !== null): ?> (<?php echo $cartCount; ?>)<?php endif; ?></span></a>
                <form action="../logout.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
                    <button type="submit">Log Out</button>
                </form>
            </div>
        </nav>
        <div class="customer-intro">
            <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
            <h1>Consultations</h1>
            <p>Send your question and let our pharmacist know when you would prefer to be contacted.</p>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($message !== ''): ?>
            <p class="customer-success" role="status"><?php echo escape($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo escape($error); ?></p>
        <?php endif; ?>

        <section class="customer-form-card" aria-labelledby="request-heading">
            <h2 id="request-heading">Request a Consultation</h2>
            <p>The preferred time is a request, not a confirmed appointment. All times below are Malaysia time.</p>
            <form action="consultation.php" method="POST" class="customer-request-form">
                <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">

                <label for="contact-number">Contact Number</label>
                <input type="tel" class="customer-input" id="contact-number" name="contact_number" maxlength="30" autocomplete="tel" placeholder="e.g. 012-3456789" value="<?php echo escape($contactNumber); ?>" required>

                <label for="preferred-datetime">Preferred Date and Time</label>
                <input type="datetime-local" class="customer-input" id="preferred-datetime" name="preferred_datetime" min="<?php echo date('Y-m-d\TH:i', time() + 60); ?>" value="<?php echo escape($preferredDatetime); ?>" required>

                <label for="question">Your Question</label>
                <textarea class="customer-textarea" id="question" name="question" rows="5" maxlength="2000" placeholder="Type your question here..." required><?php echo escape($question); ?></textarea>

                <button type="submit">Send Request</button>
            </form>
        </section>

        <section class="customer-history" aria-labelledby="history-heading">
            <h2 id="history-heading">My Requests</h2>
            <p>Pending means your request is awaiting contact. Contacted means staff have marked it as contacted.</p>
            <?php if ($loadError !== ''): ?>
                <p class="customer-error" role="alert"><?php echo escape($loadError); ?></p>
            <?php elseif (!$requests): ?>
                <p class="customer-empty">You have not sent any consultation requests yet.</p>
            <?php else: ?>
                <div class="customer-history-list">
                    <?php foreach ($requests as $request): ?>
                        <article class="customer-record-card">
                            <h3>Request #<?php echo (int) $request['consultation_id']; ?></h3>
                            <p><strong>Status:</strong>
                                <?php if ($request['status'] === 'contacted'): ?>
                                    <span class="customer-status customer-status-contacted">Contacted</span>
                                <?php else: ?>
                                    <span class="customer-status">Pending</span>
                                <?php endif; ?>
                            </p>
                            <p><strong>Contact Number:</strong> <?php echo escape($request['contact_number']); ?></p>
                            <p><strong>Preferred Date and Time:</strong> <?php echo escape($request['preferred_datetime']); ?> (Malaysia time)</p>
                            <p><strong>Question:</strong><br><?php echo nl2br(escape($request['question'])); ?></p>
                            <p><strong>Submitted:</strong> <?php echo escape($request['created_at']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer class="customer-footer">
        <p class="customer-brand">Price <span>Line</span> Pharmacy</p>
        <p>Better health. Brighter tomorrow.</p>
        <nav aria-label="Footer navigation">
            <a href="../homepage.php">Home</a>
            <a href="products.php">Products</a>
            <a href="orders.php">My Orders</a>
            <a href="#page-top">Back to Top</a>
        </nav>
        <p>&copy; <?php echo date('Y'); ?> Price Line Pharmacy.</p>
    </footer>
</body>
</html>
