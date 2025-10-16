<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userProfile = [
    'full_name' => $_SESSION['full_name'] ?? '',
    'role' => $_SESSION['role'] ?? '',
    'email' => '',
    'address' => '',
    'contact_number' => '',
];

try {
    $conn = get_db_connection();
    $stmt = $conn->prepare(
        'SELECT first_name, middle_name, last_name, email, address, contact_number
         FROM tbl_user_info WHERE user_id = ? LIMIT 1'
    );

    if ($stmt) {
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($firstName, $middleName, $lastName, $email, $address, $contactNumber);
        if ($stmt->fetch()) {
            $nameParts = array_filter([$firstName, $middleName, $lastName]);
            $userProfile['full_name'] = trim(implode(' ', $nameParts));
            $userProfile['email'] = $email;
            $userProfile['address'] = $address;
            $userProfile['contact_number'] = $contactNumber;
        }
        $stmt->close();
    }
} catch (Throwable $exception) {
    // Keep dashboard functional even if profile fetch fails.
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; }
        header { background: #2563eb; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: #fff; text-decoration: none; font-weight: bold; }
        main { max-width: 720px; margin: 40px auto; background: #fff; padding: 24px 32px; border-radius: 10px; box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08); }
        h1 { margin-top: 0; }
        .profile { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .card { background: #f1f5f9; border-radius: 8px; padding: 16px; }
        .card h2 { margin-top: 0; font-size: 18px; }
        .label { font-weight: bold; color: #475569; }
    </style>
</head>
<body>
    <header>
        <div>Pet Adoption Portal</div>
        <a href="logout.php">Log out</a>
    </header>

    <main>
        <h1>Hello, <?php echo htmlspecialchars($userProfile['full_name'] ?: ($_SESSION['username'] ?? 'User'), ENT_QUOTES, 'UTF-8'); ?>!</h1>
        <p>Your current role is <strong><?php echo htmlspecialchars($_SESSION['role'] ?? 'customer', ENT_QUOTES, 'UTF-8'); ?></strong>.</p>

        <section class="profile">
            <div class="card">
                <h2>Contact Info</h2>
                <p><span class="label">Email:</span> <?php echo htmlspecialchars($userProfile['email'] ?: 'Not provided', ENT_QUOTES, 'UTF-8'); ?></p>
                <p><span class="label">Contact:</span> <?php echo htmlspecialchars($userProfile['contact_number'] ?: 'Not provided', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="card">
                <h2>Address</h2>
                <p><?php echo htmlspecialchars($userProfile['address'] ?: 'Not provided', ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </section>
    </main>
</body>
</html>
