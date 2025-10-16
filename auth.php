<?php
if (!defined('AUTH_ENTRY')) {
    http_response_code(403);
    exit('Access denied');
}

session_start();
require_once __DIR__ . '/includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$allowedRoles = ['owner', 'customer'];
$initialMode = isset($initialMode) && $initialMode === 'signup' ? 'signup' : 'login';
$activeMode = $initialMode;

$loginErrors = [];
$signupErrors = [];
$infoMessage = '';

$formValues = [
    'login' => [
        'username' => '',
    ],
    'signup' => [
        'first_name' => '',
        'middle_name' => '',
        'last_name' => '',
        'email' => '',
        'address' => '',
        'contact_number' => '',
        'username' => '',
        'role' => 'customer',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? 'login';
    $activeMode = $formType === 'signup' ? 'signup' : 'login';

    if ($formType === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $formValues['login']['username'] = $username;

        if ($username === '' || $password === '') {
            $loginErrors[] = 'Username and password are required.';
        }

        if (!$loginErrors) {
            try {
                $conn = get_db_connection();
                $stmt = $conn->prepare(
                    'SELECT ua.user_id, ua.password, ua.role, ui.first_name, ui.last_name
                     FROM tbl_user_account AS ua
                     LEFT JOIN tbl_user_info AS ui ON ui.user_id = ua.user_id
                     WHERE ua.username = ? LIMIT 1'
                );

                if (!$stmt) {
                    throw new RuntimeException('Failed to prepare login query.');
                }

                $stmt->bind_param('s', $username);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows === 0) {
                    $loginErrors[] = 'Invalid username or password.';
                } else {
                    $stmt->bind_result($userId, $hash, $role, $firstName, $lastName);
                    $stmt->fetch();

                    if (!password_verify($password, $hash)) {
                        $loginErrors[] = 'Invalid username or password.';
                    } else {
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;
                        $_SESSION['full_name'] = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));

                        header('Location: dashboard.php');
                        exit;
                    }
                }

                $stmt->close();
            } catch (Throwable $exception) {
                $loginErrors[] = $exception->getMessage();
            }
        }

        $activeMode = 'login';
    } elseif ($formType === 'signup') {
        $signup = &$formValues['signup'];
        $signup['first_name'] = trim($_POST['first_name'] ?? '');
        $signup['middle_name'] = trim($_POST['middle_name'] ?? '');
        $signup['last_name'] = trim($_POST['last_name'] ?? '');
        $signup['email'] = trim($_POST['email'] ?? '');
        $signup['address'] = trim($_POST['address'] ?? '');
        $signup['contact_number'] = trim($_POST['contact_number'] ?? '');
        $signup['username'] = trim($_POST['username'] ?? '');
        $signup['role'] = in_array($_POST['role'] ?? 'customer', $allowedRoles, true) ? $_POST['role'] : 'customer';

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($signup['first_name'] === '' || $signup['last_name'] === '') {
            $signupErrors[] = 'First name and last name are required.';
        }

        if ($signup['email'] === '' || !filter_var($signup['email'], FILTER_VALIDATE_EMAIL)) {
            $signupErrors[] = 'A valid email address is required.';
        }

        if ($signup['username'] === '' || strlen($signup['username']) < 4) {
            $signupErrors[] = 'Username must be at least 4 characters long.';
        }

        if ($password === '' || strlen($password) < 6) {
            $signupErrors[] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirmPassword) {
            $signupErrors[] = 'Password confirmation does not match.';
        }

        if (!$signupErrors) {
            try {
                $conn = get_db_connection();

                $checkStmt = $conn->prepare('SELECT user_id FROM tbl_user_account WHERE username = ? LIMIT 1');
                if (!$checkStmt) {
                    throw new RuntimeException('Failed to prepare statement for username validation.');
                }

                $checkStmt->bind_param('s', $signup['username']);
                $checkStmt->execute();
                $checkStmt->store_result();

                if ($checkStmt->num_rows > 0) {
                    $signupErrors[] = 'Username is already taken. Please choose another one.';
                }

                $checkStmt->close();

                if (!$signupErrors) {
                    $conn->begin_transaction();

                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $accountStmt = $conn->prepare('INSERT INTO tbl_user_account (username, password, role) VALUES (?, ?, ?)');
                    if (!$accountStmt) {
                        throw new RuntimeException('Failed to prepare account insert statement.');
                    }

                    $accountStmt->bind_param('sss', $signup['username'], $hashedPassword, $signup['role']);
                    if (!$accountStmt->execute()) {
                        $conn->rollback();
                        throw new RuntimeException('Failed to save user account. Please try again.');
                    }

                    $userId = $conn->insert_id;
                    $accountStmt->close();

                    $infoStmt = $conn->prepare(
                        'INSERT INTO tbl_user_info (user_id, first_name, middle_name, last_name, email, address, contact_number)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );

                    if (!$infoStmt) {
                        $conn->rollback();
                        throw new RuntimeException('Failed to prepare user info insert statement.');
                    }

                    $infoStmt->bind_param(
                        'issssss',
                        $userId,
                        $signup['first_name'],
                        $signup['middle_name'],
                        $signup['last_name'],
                        $signup['email'],
                        $signup['address'],
                        $signup['contact_number']
                    );

                    if (!$infoStmt->execute()) {
                        $infoStmt->close();
                        $conn->rollback();
                        throw new RuntimeException('Failed to save user information. Please try again.');
                    }

                    $infoStmt->close();

                    $conn->commit();

                    $infoMessage = 'Account created successfully. You can now log in.';
                    $formValues['login']['username'] = $signup['username'];
                    $formValues['signup'] = [
                        'first_name' => '',
                        'middle_name' => '',
                        'last_name' => '',
                        'email' => '',
                        'address' => '',
                        'contact_number' => '',
                        'username' => '',
                        'role' => 'customer',
                    ];
                    $activeMode = 'login';
                }
            } catch (Throwable $exception) {
                $signupErrors[] = $exception->getMessage();
            }
        }
    }
}

$action = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
$currentClass = $activeMode === 'signup' ? 'auth-shell is-signup' : 'auth-shell';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $activeMode === 'signup' ? 'Sign Up' : 'Log In'; ?> - Pet Adoption Portal</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="<?php echo $currentClass; ?>">
        <div class="auth-card">
            <div class="auth-layers">
                <div class="auth-visual">
                    <div class="auth-visual-content">
                        <div class="visual-state visual-state-login <?php echo $activeMode === 'signup' ? '' : 'is-active'; ?>">
                            <h2>Welcome Back!</h2>
                            <p>Manage adoptions, track orders, and explore everything for your furry friends.</p>
                            <button class="js-switch-to-signup">Create an account</button>
                        </div>
                        <div class="visual-state visual-state-signup <?php echo $activeMode === 'signup' ? 'is-active' : ''; ?>">
                            <h2>Join Our Community</h2>
                            <p>Discover loving homes for pets and stay updated with the latest pet care tips.</p>
                            <button class="js-switch-to-login">I already have an account</button>
                        </div>
                    </div>
                </div>

                <div class="auth-forms">
                    <div class="forms-wrapper">
                        <form class="form form-login <?php echo $activeMode === 'login' ? 'is-active' : ''; ?>" method="post" action="<?php echo $action; ?>">
                            <input type="hidden" name="form_type" value="login">
                            <h1>Log In</h1>
                            <p>Enter your credentials to access the dashboard.</p>

                            <?php if ($infoMessage): ?>
                                <div class="alert alert-info"><?php echo htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                            <?php endif; ?>

                            <?php if ($loginErrors): ?>
                                <div class="alert alert-error">
                                    <ul class="validation-list">
                                        <?php foreach ($loginErrors as $error): ?>
                                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <label for="login_username">Username</label>
                            <input type="text" id="login_username" name="username" value="<?php echo htmlspecialchars($formValues['login']['username'], ENT_QUOTES, 'UTF-8'); ?>" required>

                            <label for="login_password">Password</label>
                            <input type="password" id="login_password" name="password" required>

                            <button type="submit">Log In</button>
                            <div class="helper">Need an account? <a href="#" class="js-switch-to-signup">Sign up</a></div>
                        </form>

                        <form class="form form-signup <?php echo $activeMode === 'signup' ? 'is-active' : ''; ?>" method="post" action="<?php echo $action; ?>">
                            <input type="hidden" name="form_type" value="signup">
                            <h1>Create Account</h1>
                            <p>Fill out your details so we can get to know you better.</p>

                            <?php if ($signupErrors): ?>
                                <div class="alert alert-error">
                                    <ul class="validation-list">
                                        <?php foreach ($signupErrors as $error): ?>
                                            <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="compact-row">
                                <div>
                                    <label for="signup_first_name">First Name*</label>
                                    <input type="text" id="signup_first_name" name="first_name" value="<?php echo htmlspecialchars($formValues['signup']['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div>
                                    <label for="signup_middle_name">Middle Name</label>
                                    <input type="text" id="signup_middle_name" name="middle_name" value="<?php echo htmlspecialchars($formValues['signup']['middle_name'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="signup_last_name">Last Name*</label>
                                    <input type="text" id="signup_last_name" name="last_name" value="<?php echo htmlspecialchars($formValues['signup']['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>

                            <label for="signup_email">Email*</label>
                            <input type="email" id="signup_email" name="email" value="<?php echo htmlspecialchars($formValues['signup']['email'], ENT_QUOTES, 'UTF-8'); ?>" required>

                            <label for="signup_address">Address</label>
                            <textarea id="signup_address" name="address" rows="3"><?php echo htmlspecialchars($formValues['signup']['address'], ENT_QUOTES, 'UTF-8'); ?></textarea>

                            <div class="compact-row">
                                <div>
                                    <label for="signup_contact">Contact Number</label>
                                    <input type="text" id="signup_contact" name="contact_number" value="<?php echo htmlspecialchars($formValues['signup']['contact_number'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div>
                                    <label for="signup_role">Role</label>
                                    <select id="signup_role" name="role">
                                        <option value="customer" <?php echo $formValues['signup']['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                        <option value="owner" <?php echo $formValues['signup']['role'] === 'owner' ? 'selected' : ''; ?>>Pet Owner</option>
                                    </select>
                                </div>
                            </div>

                            <div class="compact-row">
                                <div>
                                    <label for="signup_username">Username*</label>
                                    <input type="text" id="signup_username" name="username" value="<?php echo htmlspecialchars($formValues['signup']['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div>
                                    <label for="signup_password">Password*</label>
                                    <input type="password" id="signup_password" name="password" required>
                                </div>
                                <div>
                                    <label for="signup_confirm_password">Confirm*</label>
                                    <input type="password" id="signup_confirm_password" name="confirm_password" required>
                                </div>
                            </div>

                            <button type="submit">Sign Up</button>
                            <div class="helper">Already registered? <a href="#" class="js-switch-to-login">Log in</a></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/auth.js"></script>
</body>
</html>
