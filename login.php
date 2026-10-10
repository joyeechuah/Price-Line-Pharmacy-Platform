<?php
session_start();
require_once __DIR__ . '/config/database.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!is_string($email) || !is_string($password)) {
        $error = 'Invalid form information.';
        $email = '';
    } else {
        $email = trim($email);
        if ($email === '' || $password === '') {
            $error = 'Please enter your email and password.';
        }
    }

    if ($error === '') {
        try {
            $statement = $pdo->prepare(
                'SELECT user_id, name, email, password_hash, role
                 FROM users WHERE email = :email'
            );
            $statement->execute(array(':email' => $email));
            $user = $statement->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Carry over a guest cart only when signing in as a customer.
                $guestCart = [];
                $checkoutAfterLogin = false;
                if (!isset($_SESSION['user']) && $user['role'] === 'customer') {
                    $guestCart = $_SESSION['guest_cart'] ?? [];
                    $checkoutAfterLogin = !empty($_SESSION['checkout_after_login']);
                }
                session_regenerate_id(true);
                // A new login should not reuse another account's checkout or messages.
                $_SESSION = array();
                $_SESSION['user'] = array(
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'role' => $user['role']
                );

                if ($guestCart) {
                    $_SESSION['guest_cart'] = $guestCart;
                }
                if ($checkoutAfterLogin) {
                    $_SESSION['checkout_after_login'] = true;
                }

                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin/dashboard.php');
                        break;
                    case 'pharmacist':
                        header('Location: pharmacist/dashboard.php');
                        break;
                    case 'storekeeper':
                        header('Location: admin/products.php');
                        break;
                    default:
                        if ($guestCart || $checkoutAfterLogin) {
                            header('Location: customer/cart.php');
                        } else {
                            header('Location: customer/products.php');
                        }
                        break;
                }
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = 'Unable to log in. Please try again.';
        }
    }
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
        <?php if (!empty($_SESSION['checkout_after_login'])): ?>
            <p>Please log in to continue to checkout. Your guest cart will be kept.</p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="register-error" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="email">Email</label>

            <input type="email"
                   id="email"
                   name="email"
                   value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
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