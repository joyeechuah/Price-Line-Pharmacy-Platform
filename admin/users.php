<?php
// STEP 1: Check login and allow admins only.
require_once __DIR__ . '/../includes/auth.php';

if (($_SESSION['user']['role'] ?? '') !== 'admin') {
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
$name = '';
$email = '';
$role = 'pharmacist';
$users = [];

// STEP 4: Create a staff account when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // filter_input reads a form field. Casting to string handles missing fields.
    $name = trim((string) filter_input(INPUT_POST, 'name'));
    $email = trim((string) filter_input(INPUT_POST, 'email'));
    $password = (string) filter_input(INPUT_POST, 'password');
    $role = (string) filter_input(INPUT_POST, 'role');
    $token = (string) filter_input(INPUT_POST, 'csrf_token');

    // Check the security token and the information entered.
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = 'Please reload the page and try again.';
    } elseif ($name === '' || mb_strlen($name) > 100) {
        $error = 'Enter a name of 1 to 100 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 225) {
        $error = 'Enter a valid email address (maximum 225 characters).';
    } elseif (mb_strlen($password) < 8 || strlen($password) > 72) {
        $error = 'Use at least 8 characters. If your password is very long, please shorten it.';
    } elseif (strpos($password, "\0") !== false) {
        $error = 'The password contains an invalid character. Please choose another password.';
    } elseif (!in_array($role, ['admin', 'pharmacist', 'storekeeper'], true)) {
        $error = 'Select a valid staff role.';
    }

    if ($error === '') {
        try {
            // STEP 5: Check whether the email is already registered.
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'That email is already registered.';
            } else {
                // STEP 6: Hash the password and save the new staff account.
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash, role)
                     VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$name, $email, $hashedPassword, $role]);

                // Keep the admin logged in. Do not replace their session user.
                $_SESSION['users_message'] = 'Staff account created successfully.';

                // Redirect so refreshing the page does not create another account.
                header('Location: users.php');
                exit;
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());

            // Also handle two submissions trying to use the same email.
            if (($e->errorInfo[1] ?? 0) == 1062) {
                $error = 'That email is already registered.';
            } else {
                $error = 'Unable to create the staff account. Please try again.';
            }
        }
    }
}

// STEP 7: Read the users. Password hashes are not needed for this list.
try {
    $stmt = $pdo->query(
        'SELECT user_id, name, email, role, created_at
         FROM users ORDER BY user_id DESC'
    );
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $error = 'Unable to load users. Please try again.';
}

$message = $_SESSION['users_message'] ?? '';
unset($_SESSION['users_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../CSS/admin.css">
</head>
<body class="admin-page">
    <header class="admin-header">
        <h1>Price Line Pharmacy</h1>
        <p>Admin Panel</p>
    </header>

    <nav class="admin-nav" aria-label="Admin navigation">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php" aria-current="page">Users</a>
        <a href="products.php">Products</a>
        <a href="orders.php">Orders</a>
        <a href="consultation.php">Consultations</a>
        <form action="../logout.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">
            <button type="submit" class="logout-button">Log Out</button>
        </form>
    </nav>

    <main class="admin-content admin-users">
        <h2>Manage Users</h2>
        <p>View registered users and create staff accounts. Customers register through the registration page.</p>

        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert"><?php echo escape($error); ?></p>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <p class="admin-success" role="status"><?php echo escape($message); ?></p>
        <?php endif; ?>

        <section class="consultation-card admin-form-card">
            <h3>Create Staff Account</h3>

            <form action="users.php" method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo escape($_SESSION['csrf_token']); ?>">

                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" maxlength="100" autocomplete="off" required value="<?php echo escape($name); ?>">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="225" autocomplete="off" required value="<?php echo escape($email); ?>">

                <label for="password">Password</label>
                <input type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" aria-describedby="password-help" required>
                <p id="password-help" class="form-help">Use at least 8 characters, including letters, numbers or symbols.</p>

                <label for="role">Staff Role</label>
                <select id="role" name="role" required>
                    <option value="pharmacist" <?php if ($role === 'pharmacist') echo 'selected'; ?>>Pharmacist</option>
                    <option value="storekeeper" <?php if ($role === 'storekeeper') echo 'selected'; ?>>Storekeeper</option>
                    <option value="admin" <?php if ($role === 'admin') echo 'selected'; ?>>Admin</option>
                </select>

                <button type="submit">Create Staff Account</button>
            </form>
        </section>

        <h3>Registered Users</h3>

        <?php if ($error === '' && empty($users)): ?>
            <p>No users found.</p>
        <?php endif; ?>

        <div class="consultation-list">
            <?php foreach ($users as $user): ?>
                <article class="consultation-card">
                    <h3><?php echo escape($user['name']); ?></h3>
                    <p><strong>User ID:</strong> <?php echo escape($user['user_id']); ?></p>
                    <p><strong>Email:</strong> <?php echo escape($user['email']); ?></p>
                    <p><strong>Role:</strong> <?php echo escape(ucfirst($user['role'])); ?></p>
                    <p><strong>Joined:</strong> <?php echo escape($user['created_at']); ?></p>
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
