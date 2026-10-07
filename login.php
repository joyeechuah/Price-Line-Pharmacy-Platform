<?php
session_start();

require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare(
            'SELECT user_id, name, email, password_hash, role
             FROM users
             WHERE email = ?'
        );

        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'user_id' => $user['user_id'],
                'name'    => $user['name'],
                'email'   => $user['email'],
                'role'    => $user['role']
            ];

            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;

                case 'pharmacist':
                    header('Location: pharmacist/dashboard.php');
                    break;

                case 'storekeeper':
                    header('Location: storekeeper/dashboard.php');
                    break;

                default:
                header('Location: customer/products.php');
                break;
            }

            exit;
        } else {
            $error = 'Invalid email or password.';
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

    <link rel="stylesheet" href="style.css">
</head>

<body class="login-page">
    <main class="login-box">
        <a href="homepage.php">
            <img src="images/Price Line Pharmacy logo.png"
                 alt="Price Line Pharmacy home"
                 class="login-logo">
        </a>

        <h1>Welcome Back</h1>
        <p>Log in to your Price Line Pharmacy account.</p>

        <form action="login.php" method="POST">
            <label for="email">Email</label>

            <input type="email"
                   id="email"
                   name="email"
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