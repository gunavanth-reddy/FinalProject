<?php
session_start();
include 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if (!isset($_GET['token']) || !isset($_GET['email'])) {
    $error = "Invalid reset link.";
    error_log("Reset Password: Missing token or email");
} else {
    $token = $_GET['token'];
    $email = $_GET['email'];

    // Verify token
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND token_expires > NOW()");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Invalid or expired reset link.";
            error_log("Reset Password: Invalid or expired token for $email");
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
        error_log("Reset Password Error: " . $e->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($password)) {
        $error = "Password is required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        try {
            // Update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, token_expires = NULL WHERE email = ?");
            $stmt->execute([$hashed_password, $email]);

            error_log("Reset Password: Password updated for $email");
            $success = "Password reset successfully. <a href='login.php'>Login now</a>.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
            error_log("Reset Password Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php else: ?>
            <form method="POST">
                <input type="password" name="password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>
        <p><a href="login.php" class="btn">Back to Login</a></p>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>