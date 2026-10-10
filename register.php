<?php
// STEP 1: Start the session.
session_start();

// Registration is for guests. Logged-in users already have an account.
if (isset($_SESSION['user'])) {
    header('Location: homepage.php');
    exit;
}

// STEP 2: Include the database connection.
require_once __DIR__ . '/config/database.php';

// STEP 3: Prepare an error message and form security token.
$error = '';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// STEP 4: Run this code only when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // STEP 5: Get the submitted values.
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    // STEP 6: Check the submitted information.
    if (!is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Please reload the page and try again.';
    } elseif (!is_string($name) || !is_string($email) ||
              !is_string($password) || !is_string($confirmPassword)) {
        $error = 'Invalid form information.';
    } else {
        $name = trim($name);
        $email = trim($email);

        if ($name === '' || $email === '' || $password === '') {
            $error = 'Please complete all fields.';
        } elseif (mb_strlen($name) > 100) {
            $error = 'Your name must be no longer than 100 characters.';
        } elseif (strlen($email) > 225 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Your password must have at least 8 characters.';
        } elseif (strlen($password) > 72) {
            $error = 'Your password is too long. Please use a shorter password.';
        } elseif (strpos($password, "\0") !== false) {
            $error = 'Your password contains an invalid character.';
        } elseif ($password !== $confirmPassword) {
            $error = 'The passwords do not match.';
        }
    }

    // Continue only if the information is valid.
    if ($error === '') {
        try {
            // STEP 7: Check whether the email is already registered.
            $stmt = $pdo->prepare(
                'SELECT user_id FROM users WHERE email = ?'
            );
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'That email is already registered.';
            } else {
                // STEP 8: Hash the password and create the account.
                $hashedPassword = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare(
                    "INSERT INTO users (name, email, password_hash, role)
                     VALUES (?, ?, ?, 'customer')"
                );

                $stmt->execute([$name, $email, $hashedPassword]);

                // STEP 9: Log the new customer in.
                $guestCart = $_SESSION['guest_cart'] ?? [];
                $checkoutAfterLogin = !empty($_SESSION['checkout_after_login']);
                session_regenerate_id(true);
                $_SESSION = array();

                $_SESSION['user'] = [
                    'user_id' => $pdo->lastInsertId(),
                    'name'    => $name,
                    'email'   => $email,
                    'role'    => 'customer'
                ];

                // STEP 10: Keep guest items when a new customer registers.
                if ($guestCart) {
                    $_SESSION['guest_cart'] = $guestCart;
                }
                if ($checkoutAfterLogin) {
                    $_SESSION['checkout_after_login'] = true;
                }
                if ($guestCart || $checkoutAfterLogin) {
                    header('Location: customer/cart.php');
                } else {
                    header('Location: customer/products.php');
                }
                exit;
            }
        } catch (PDOException $e) {
            // Handle duplicate emails even if two forms arrive together.
            if (($e->errorInfo[1] ?? 0) == 1062) {
                $error = 'That email is already registered.';
            } else {
                error_log($e->getMessage());
                $error = 'Unable to create your account. Please try again.';
            }
        }
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
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form action="register.php" method="POST">

            <input type="hidden"
                   name="csrf_token"
                   value="<?php echo htmlspecialchars(
                       $_SESSION['csrf_token'],
                       ENT_QUOTES,
                       'UTF-8'
                   ); ?>">

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
