<?php
// STEP 1: Check login and allow admins only.
require_once __DIR__ . '/../includes/auth.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// STEP 2: Connect to the database.
require_once __DIR__ . '/../config/database.php';

// STEP 3: Set starting values.
$error = '';
$name = '';
$email = '';
$role = 'pharmacist';
$message = $_SESSION['users_message'] ?? '';
unset($_SESSION['users_message']);

// STEP 4: Create a staff account when the form is submitted.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    // STEP 5: Check whether the email is already registered.
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
    $stmt->execute([$email]);

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 8) {
        $error = 'Use at least 8 characters for the password.';
    } elseif ($role !== 'admin' && $role !== 'pharmacist' && $role !== 'storekeeper') {
        $error = 'Select a valid staff role.';
    } elseif ($stmt->fetch()) {
        $error = 'That email is already registered.';
    } else {
        // STEP 6: Hash the password and save the new staff account.
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hashedPassword, $role]);

        // Redirect so refreshing the page does not create another account.
        $_SESSION['users_message'] = 'Staff account created successfully.';
        header('Location: users.php');
        exit;
    }
}

// STEP 7: Read the users. Password hashes are not needed for this list.
$stmt = $pdo->query('SELECT user_id, name, email, role, created_at FROM users ORDER BY user_id DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | Price Line Pharmacy</title>
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
</head>
<body class="admin-page" id="page-top">
<?php $currentPage = 'users'; require __DIR__ . '/../includes/staff-navbar.php'; ?>

    <main class="admin-content admin-users">
        <h2>Manage Users</h2>
        <p>View registered users and create staff accounts. Customers register through the registration page.</p>

        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <p class="admin-success" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <section class="consultation-card admin-form-card">
            <h3>Create Staff Account</h3>

            <form action="users.php" method="POST" class="admin-form">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" maxlength="100" autocomplete="off" required value="<?php echo htmlspecialchars($name); ?>">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="255" autocomplete="off" required value="<?php echo htmlspecialchars($email); ?>">

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
                    <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                    <p><strong>User ID:</strong> <?php echo htmlspecialchars($user['user_id']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Role:</strong> <?php echo htmlspecialchars(ucfirst($user['role'])); ?></p>
                    <p><strong>Joined:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
