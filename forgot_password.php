<?php
session_start();
include 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Validation
    if (empty($email)) {
        $error = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store token
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, token_expires = ? WHERE email = ?");
                $stmt->execute([$token, $expires, $email]);

                // Create reset link
                $reset_link = "http://localhost/movie_booking/reset_password.php?token=$token&email=" . urlencode($email);

                // Log email (local testing)
                $message = "To reset your BookMyMovie password, click this link: $reset_link\nLink expires in 1 hour.";
                if (file_put_contents('emails.log', "Email to $email: $message\n", FILE_APPEND) === false) {
                    error_log("Forgot Password: Failed to write to emails.log");
                    $error = "Failed to send reset link. Please try again.";
                } else {
                    error_log("Forgot Password: Reset link for $email: $reset_link");
                    $success = "A password reset link has been sent to your email.";
                }
            } else {
                $error = "No account found with that email.";
                error_log("Forgot Password: No account for $email");
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
            error_log("Forgot Password Error: " . $e->getMessage());
        } catch (Exception $e) {
            $error = "Error generating token: " . $e->getMessage();
            error_log("Forgot Password Error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Forgot Password</h2>
        <?php if ($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php elseif ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="email" name="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            <button type="submit">Send Reset Link</button>
        </form>
        <p><a href="login.php" class="btn">Back to Login</a></p>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>