<?php
// STEP 1: Start the session and connect to the database.
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cart.php';

$error = '';
$email = '';

// STEP 2: Check the email and password when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        // STEP 3: Password is correct, so save the user to the session.
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'user_id' => $user['user_id'],
            'name'    => $user['name'],
            'email'   => $user['email'],
            'role'    => $user['role'],
        ];
    } else {
        $error = 'Invalid email or password.';
    }
}

// STEP 4: Send logged-in users to the correct area for their role.
if (isAdmin()) {
    header('Location: admin/dashboard.php');
    exit;
}

if (isPharmacist()) {
    header('Location: pharmacist/dashboard.php');
    exit;
}

if (isStorekeeper()) {
    header('Location: admin/products.php');
    exit;
}

if (isCustomer()) {
    // Keep the items the customer added before logging in.
    saveGuestCart($pdo, $_SESSION['user']['user_id']);

    // Return to the page that asked the customer to log in.
    $nextPage = $_SESSION['after_login'] ?? 'customer/products.php';
    unset($_SESSION['after_login']);

    header('Location: ' . $nextPage);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Log In | Price Line Pharmacy</title>

    <link rel="stylesheet" href="CSS/customer.css">
</head>

<body class="login-page">
    <main class="login-box">
        <a href="homepage.php">
            <img src="images/PriceLine Pharmacy Logo in Blue and Green.png"
                 alt="Price Line Pharmacy home"
                 class="login-logo">
        </a>

        <h1>Welcome Back</h1>
        <p>Log in to your Price Line Pharmacy account.</p>
        <?php if (($_SESSION['after_login'] ?? '') === 'customer/checkout.php'): ?>
            <p>Please log in to continue to checkout. Your guest cart will be kept.</p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="register-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="email">Email</label>

            <input type="email"
                   id="email"
                   name="email"
                   value="<?php echo htmlspecialchars($email); ?>"
                   placeholder="Enter your email"
                   autocomplete="username"
                   required>

            <label for="password">Password</label>

            <input type="password"
                   id="password"
                   name="password"
                   placeholder="Enter your password"
                   autocomplete="current-password"
                   required>

            <button type="submit">Log In</button>
        </form>

        <p class="login-register">
            Don't have an account?
            <a href="register.php">Register</a>
        </p>

        <a href="homepage.php">Back to Home</a>
    </main>
</body>
</html>