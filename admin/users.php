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
$id = 0; // Zero means a new staff account. An existing ID means editing a user.
$name = '';
$email = '';
$role = 'pharmacist';
$myId = (int) $_SESSION['user']['user_id']; // The admin who is logged in.
$message = $_SESSION['users_message'] ?? '';
unset($_SESSION['users_message']);

// STEP 4: Handle the submitted form.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['user_id'] ?? 0);

    if ($action === 'delete') {
        // STEP 5 - DELETE: Accounts with orders are kept, so the order history is not lost.
        $stmt = $pdo->prepare('SELECT order_id FROM orders WHERE user_id = ?');
        $stmt->execute([$id]);
        $hasOrders = $stmt->fetch();

        // Count the admins, so the website is never left without one.
        $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $adminCount = (int) $row['total'];

        if ($id === $myId && $adminCount === 1) {
            $error = 'You are the only admin, so your account cannot be removed.';
        } elseif ($hasOrders) {
            $error = 'This user has orders, so the account cannot be removed.';
        } else {
            // Clear the user's cart and consultation requests first.
            $stmt = $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?');
            $stmt->execute([$id]);

            $stmt = $pdo->prepare('DELETE FROM consultations WHERE user_id = ?');
            $stmt->execute([$id]);

            $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->execute([$id]);

            // Admins who remove their own account are logged out.
            if ($id === $myId) {
                session_destroy();
                header('Location: ../homepage.php');
                exit;
            }

            $_SESSION['users_message'] = 'User removed.';
            header('Location: users.php');
            exit;
        }

        // The user was not removed, so show the empty form again.
        $id = 0;
    } elseif ($action === 'save') {
        // STEP 6: Read and check the Create / Edit form.
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';

        // Check whether another account already uses this email.
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
        $stmt->execute([$email, $id]);

        if (empty($name) || empty($email)) {
            $error = 'Name and email are required.';
        } elseif ($stmt->fetch()) {
            $error = 'That email is already registered.';
        } elseif ($id === 0 && strlen($password) < 8) {
            $error = 'Use at least 8 characters for the password.';
        } elseif ($id === 0 && $role === 'customer') {
            $error = 'Select a valid staff role.';
        } elseif ($role !== 'admin' && $role !== 'pharmacist' && $role !== 'storekeeper' && $role !== 'customer') {
            $error = 'Select a valid role.';
        } elseif ($id === $myId && $role !== 'admin') {
            $error = 'You cannot change your own role.';
        } elseif ($id > 0) {
            // STEP 7 - UPDATE: Save the changes to an existing user.
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE user_id = ?');
            $stmt->execute([$name, $email, $role, $id]);

            // Keep the welcome message correct when admins edit their own account.
            if ($id === $myId) {
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
            }

            // Redirect so refreshing the page does not submit the form again.
            $_SESSION['users_message'] = 'User updated.';
            header('Location: users.php');
            exit;
        } else {
            // STEP 7 - CREATE: Hash the password and save the new staff account.
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hashedPassword, $role]);

            $_SESSION['users_message'] = 'Staff account created successfully.';
            header('Location: users.php');
            exit;
        }
    }
}

// STEP 8: Fill in the form when an Edit User link is clicked.
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT user_id, name, email, role FROM users WHERE user_id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $id    = (int) $user['user_id'];
        $name  = $user['name'];
        $email = $user['email'];
        $role  = $user['role'];
    } else {
        $error = 'User not found.';
    }
}

// STEP 9 - READ: Load the users. Password hashes are not needed for this list.
$stmt = $pdo->query('SELECT user_id, name, email, role, created_at FROM users ORDER BY user_id DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set the labels here so the HTML below stays easy to read.
$formTitle = 'Create Staff Account';
$buttonText = 'Create Staff Account';
if ($id > 0) {
    $formTitle = 'Edit User';
    $buttonText = 'Save Changes';
}
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
        <p>Create staff accounts, and edit or remove registered users. Customers register through the registration page.</p>

        <?php if ($error !== ''): ?>
            <p class="admin-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <?php if ($message !== ''): ?>
            <p class="admin-success" role="status"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <section class="consultation-card admin-form-card">
            <h3><?php echo htmlspecialchars($formTitle); ?></h3>

            <form action="users.php" method="POST" class="admin-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($id); ?>">

                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" maxlength="100" autocomplete="off" required value="<?php echo htmlspecialchars($name); ?>">

                <label for="email">Email</label>
                <input type="email" id="email" name="email" maxlength="255" autocomplete="off" required value="<?php echo htmlspecialchars($email); ?>">

                <!-- A password is only set when a new account is created. -->
                <?php if ($id === 0): ?>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                <?php endif; ?>

                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="pharmacist" <?php if ($role === 'pharmacist') echo 'selected'; ?>>Pharmacist</option>
                    <option value="storekeeper" <?php if ($role === 'storekeeper') echo 'selected'; ?>>Storekeeper</option>
                    <option value="admin" <?php if ($role === 'admin') echo 'selected'; ?>>Admin</option>
                    <!-- New accounts made here are staff only. Customers register themselves. -->
                    <?php if ($id > 0): ?>
                        <option value="customer" <?php if ($role === 'customer') echo 'selected'; ?>>Customer</option>
                    <?php endif; ?>
                </select>

                <button type="submit"><?php echo htmlspecialchars($buttonText); ?></button>
                <?php if ($id > 0): ?><a href="users.php">Cancel editing</a><?php endif; ?>
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
                    <div class="product-actions">
                        <a class="admin-edit-link" href="users.php?edit=<?php echo htmlspecialchars($user['user_id']); ?>">Edit User</a>
                        <form action="users.php" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                            <button type="submit" name="action" value="delete" class="product-delete-button"
                                    onclick="return confirm('Remove this user account?');">
                                Remove User
                            </button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

<?php $footerBase = '../'; require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
