<?php
session_start();
include 'includes/db.php';

if (!isset($_POST['showtime_id']) || !isset($_POST['seat_ids']) || !isset($_SESSION['user_id'])) {
    error_log("Payment.php: Missing required data. Showtime: " . ($_POST['showtime_id'] ?? 'N/A') . ", Seat IDs: " . ($_POST['seat_ids'] ?? 'N/A') . ", User ID: " . ($_SESSION['user_id'] ?? 'N/A'));
    header("Location: login.php");
    exit();
}

$showtime_id = $_POST['showtime_id'];
$seat_ids = explode(',', $_POST['seat_ids']);
$user_id = $_SESSION['user_id'];

error_log("Payment.php - User ID: $user_id, Showtime ID: $showtime_id, Seat IDs: " . implode(',', $seat_ids));

// Verify user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    error_log("Payment.php: Invalid user_id: $user_id");
    unset($_SESSION['user_id']);
    header("Location: login.php");
    exit();
}

$pdo->beginTransaction();
try {
    // Verify showtime and get price
    $stmt = $pdo->prepare("SELECT id, price FROM showtimes WHERE id = ?");
    $stmt->execute([$showtime_id]);
    $showtime = $stmt->fetch();
    if (!$showtime) {
        throw new Exception("Invalid showtime ID: $showtime_id");
    }
    $price_per_ticket = $showtime['price'];

    // Verify seats
    foreach ($seat_ids as $seat_id) {
        $stmt = $pdo->prepare("
            SELECT s.id 
            FROM seats s 
            WHERE s.id = ? AND NOT EXISTS (
                SELECT 1 
                FROM booking_seats bs 
                JOIN bookings b ON bs.booking_id = b.id 
                WHERE b.showtime_id = ? AND bs.seat_id = s.id
            )
        ");
        $stmt->execute([$seat_id, $showtime_id]);
        if (!$stmt->fetch()) {
            throw new Exception("Seat ID $seat_id is invalid or already booked for showtime $showtime_id");
        }
    }

    // Create booking
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, showtime_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $showtime_id]);
    $booking_id = $pdo->lastInsertId();

    // Link seats
    $stmt = $pdo->prepare("INSERT INTO booking_seats (booking_id, seat_id) VALUES (?, ?)");
    foreach ($seat_ids as $seat_id) {
        $stmt->execute([$booking_id, $seat_id]);
        error_log("Payment.php: Linked seat ID $seat_id to booking ID $booking_id");
    }

    error_log("Payment.php: Booking created with ID: $booking_id");
    $pdo->commit();
    header("Location: receipt.php?booking_id=" . $booking_id);
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Payment.php Error: " . $e->getMessage());
    $error = "Booking failed: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Payment</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <a href="movies.php" class="btn">Back to Movies</a>
        <?php else: ?>
            <p>Confirm your booking for <?php echo count($seat_ids); ?> seat(s) at ₹<?php echo number_format($price_per_ticket, 2); ?> each.</p>
            <p>Total: ₹<?php echo number_format(count($seat_ids) * $price_per_ticket, 2); ?></p>
            <form method="POST" action="">
                <input type="hidden" name="showtime_id" value="<?php echo htmlspecialchars($showtime_id); ?>">
                <input type="hidden" name="seat_ids" value="<?php echo htmlspecialchars($_POST['seat_ids']); ?>">
                <button type="submit">Confirm Payment</button>
            </form>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>