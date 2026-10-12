<?php
// STEP 1: Start the session.
require_once __DIR__ . '/includes/auth.php';

// Registration is for guests. Logged-in users already have an account.
if (!isGuest()) {
    header('Location: homepage.php');
    exit;
}

// STEP 2: Include the database connection.
require_once __DIR__ . '/config/database.php';

// STEP 3: Set up a variable to hold any error message.
$error = '';

// STEP 4: Run this code only when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // STEP 5: Get the submitted values.
    $name            = trim($_POST['name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // STEP 6: Check whether the email is already registered.
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute([$email]);

    // STEP 7: Check the submitted information.
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please complete all fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Your password must have at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'The passwords do not match.';
    } elseif ($stmt->fetch()) {
        $error = 'That email is already registered.';
    } else {
        // STEP 8: Hash the password and create the account.
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'customer')");
        $stmt->execute([$name, $email, $hashedPassword]);

        // STEP 9: Log the new customer in.
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'user_id' => $pdo->lastInsertId(),
            'name'    => $name,
            'email'   => $email,
            'role'    => 'customer',
        ];

        // STEP 10: login.php sends logged-in users to the correct page.
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register | Price Line Pharmacy</title>

    <link rel="stylesheet" href="CSS/customer.css">
</head>

<body class="login-page">
    <main class="login-box">

        <a href="homepage.php">
            <img src="images/PriceLine Pharmacy Logo in Blue and Green.png"
                 alt="Price Line Pharmacy home"
                 class="login-logo">
        </a>

        <h1>Create Account</h1>
        <p>Join Price Line Pharmacy and start shopping.</p>

        <?php if ($error !== ''): ?>
            <p class="register-error" role="alert">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form action="register.php" method="POST">

            <label for="name">Full Name</label>
            <input type="text"
                   id="name"
                   name="name"
                   placeholder="Enter your full name"
                   maxlength="100"
                   autocomplete="name"
                   required>

            <label for="email">Email</label>
            <input type="email"
                   id="email"
                   name="email"
                   placeholder="Enter your email"
                   maxlength="225"
                   autocomplete="email"
                   required>

            <label for="password">Password</label>
            <input type="password"
                   id="password"
                   name="password"
                   placeholder="At least 8 characters"
                   minlength="8"
                   maxlength="72"
                   autocomplete="new-password"
                   required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password"
                   id="confirm_password"
                   name="confirm_password"
                   placeholder="Enter your password again"
                   minlength="8"
                   maxlength="72"
                   autocomplete="new-password"
                   required>

            <button type="submit">Create Account</button>
        </form>

        <p class="login-register">
            Already have an account?
            <a href="login.php">Log In</a>
        </p>

        <a href="homepage.php">Back to Home</a>

    </main>
</body>
</html>
