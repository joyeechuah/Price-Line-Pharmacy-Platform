<?php
// STEP 1: Check login and allow customers only.
require_once __DIR__ . '/../includes/auth.php';

if (!isCustomer()) {
    // Come back to this page after logging in.
    $_SESSION['after_login'] = 'customer/consultation.php';
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/cart.php';

// Preferred contact times are entered and displayed in Malaysia time.
date_default_timezone_set('Asia/Kuala_Lumpur');

// STEP 2: Prepare the form and messages.
$userId = $_SESSION['user']['user_id'];
$contactNumber = '';
$preferredDatetime = '';
$question = '';
$error = '';
$message = $_SESSION['customer_consultation_message'] ?? '';
unset($_SESSION['customer_consultation_message']);

// STEP 3: Check the form before saving a request.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contactNumber     = trim($_POST['contact_number'] ?? '');
    $preferredDatetime = $_POST['preferred_datetime'] ?? '';
    $question          = trim($_POST['question'] ?? '');

    // strtotime changes the chosen date and time into a number of seconds.
    $preferredTime = strtotime($preferredDatetime);

    if (empty($contactNumber) || empty($preferredDatetime) || empty($question)) {
        $error = 'All fields are required.';
    } elseif ($preferredTime <= time()) {
        $error = 'Please choose a future date and time.';
    } else {
        // STEP 4: Save it under the logged-in customer's account.
        $stmt = $pdo->prepare("INSERT INTO consultations
            (user_id, contact_number, preferred_datetime, question, status)
            VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $contactNumber, date('Y-m-d H:i:s', $preferredTime), $question]);

        $_SESSION['customer_consultation_message'] = 'Your request has been sent. You can check its status below.';
        // Redirect so refreshing does not submit the form again.
        header('Location: consultation.php');
        exit;
    }
}

// STEP 5: Read only this customer's consultation history.
$stmt = $pdo->prepare('SELECT * FROM consultations WHERE user_id = ? ORDER BY created_at DESC, consultation_id DESC');
$stmt->execute([$userId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cartCount = getCartCount($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Consultations | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/customer.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<body class="customer-page" id="page-top">
    <nav class="headbar" aria-label="Main navigation">
        <div class="pharmacy-navbar">
            <a href="../homepage.php" class="navbar-logo" aria-label="Price Line Pharmacy home">
                <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy logo">
            </a>
            <a href="../homepage.php">Home</a>
            <a href="products.php" >Products</a>
            <a href="../membership-benefits.php">Membership Benefits</a>
            <?php if (isGuest() || isCustomer()): ?>
                <a href="consultation.php" class="nav-button current-page" aria-current="page">E-Consult Now</a>
            <?php endif; ?>
            <div class="account-links">
                <?php if (isGuest() || isCustomer()): ?>
                    <a href="cart.php" class="navbar-cart" >
                        <img src="../images/cart-icon.svg" alt="" width="24" height="24">
                        <span>Cart (<?php echo $cartCount; ?>)</span>
                    </a>
                <?php endif; ?>
                <?php if (!isGuest()): ?>
                    <?php if (isCustomer()): ?>
                        <a href="orders.php" >My Orders</a>
                    <?php elseif (isAdmin()): ?>
                        <a href="../admin/dashboard.php">Staff Panel</a>
                    <?php elseif (isPharmacist()): ?>
                        <a href="../pharmacist/dashboard.php">Staff Panel</a>
                    <?php elseif (isStorekeeper()): ?>
                        <a href="../admin/products.php">Staff Panel</a>
                    <?php endif; ?>
                    <form action="../logout.php" method="POST">
                        <button type="submit">Log Out</button>
                    </form>
                <?php else: ?>
                    <a href="../login.php">Log In</a>
                    <a href="../register.php" class="register-link">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <header class="customer-header">
        <div class="customer-intro">
            <img src="../images/PriceLine Pharmacy Logo in Blue and Green.png" alt="Price Line Pharmacy" class="page-logo">
            <h1>Consultations</h1>
            <p>Send your question and let our pharmacist know when you would prefer to be contacted.</p>
        </div>
    </header>

    <main class="customer-content">
        <?php if ($message !== ''): ?>
            <p class="customer-success" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <p class="customer-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <section class="customer-form-card" aria-labelledby="request-heading">
            <h2 id="request-heading">Request a Consultation</h2>
            <p>The preferred time is a request, not a confirmed appointment. All times below are Malaysia time.</p>
            <form action="consultation.php" method="POST" class="customer-request-form">
                <label for="contact-number">Contact Number</label>
                <input type="tel" class="customer-input" id="contact-number" name="contact_number" maxlength="30" autocomplete="tel" placeholder="e.g. 012-3456789" value="<?php echo htmlspecialchars($contactNumber); ?>" required>

                <label for="preferred-datetime">Preferred Date and Time</label>
                <input type="datetime-local" class="customer-input" id="preferred-datetime" name="preferred_datetime" min="<?php echo date('Y-m-d\TH:i', time() + 60); ?>" value="<?php echo htmlspecialchars($preferredDatetime); ?>" required>

                <label for="question">Your Question</label>
                <textarea class="customer-textarea" id="question" name="question" rows="5" maxlength="2000" placeholder="Type your question here..." required><?php echo htmlspecialchars($question); ?></textarea>

                <button type="submit">Send Request</button>
            </form>
        </section>

        <section class="customer-history" aria-labelledby="history-heading">
            <h2 id="history-heading">My Requests</h2>
            <p>Pending means your request is awaiting contact. Contacted means staff have marked it as contacted.</p>
            <?php if (!$requests): ?>
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
                            <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($request['contact_number']); ?></p>
                            <p><strong>Preferred Date and Time:</strong> <?php echo htmlspecialchars($request['preferred_datetime']); ?> (Malaysia time)</p>
                            <p><strong>Question:</strong><br><?php echo nl2br(htmlspecialchars($request['question'])); ?></p>
                            <p><strong>Submitted:</strong> <?php echo htmlspecialchars($request['created_at']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
