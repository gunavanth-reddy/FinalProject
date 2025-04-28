<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['booking_id']) || !isset($_SESSION['user_id'])) {
    error_log("Receipt.php: Missing booking_id or user_id. Booking ID: " . ($_GET['booking_id'] ?? 'N/A') . ", User ID: " . ($_SESSION['user_id'] ?? 'N/A'));
    header("Location: login.php");
    exit();
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

error_log("Receipt.php - Booking ID: $booking_id, User ID: $user_id");

$stmt = $pdo->prepare("
    SELECT b.id, m.title, t.name AS theater_name, GROUP_CONCAT(s.seat_number) AS seat_numbers, 
           st.show_date, st.show_time, l.name AS location_name, st.price
    FROM bookings b
    JOIN showtimes st ON b.showtime_id = st.id
    JOIN movies m ON st.movie_id = m.id
    JOIN theaters t ON st.theater_id = t.id
    JOIN locations l ON t.location_id = l.id
    JOIN booking_seats bs ON b.id = bs.booking_id
    JOIN seats s ON bs.seat_id = s.id
    WHERE b.id = ? AND b.user_id = ?
    GROUP BY b.id
");
$stmt->execute([$booking_id, $user_id]);
$booking = $stmt->fetch();

if (!$booking) {
    error_log("Receipt.php: Booking not found for ID: $booking_id, User ID: $user_id");
    $error = "Booking not found.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Booking Receipt</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <a href="movies.php" class="btn">Back to Movies</a>
        <?php else: ?>
            <p><strong>Movie:</strong> <?php echo htmlspecialchars($booking['title']); ?></p>
            <p><strong>Theater:</strong> <?php echo htmlspecialchars($booking['theater_name']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['location_name']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['show_date']); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['show_time'])); ?></p>
            <p><strong>Seats:</strong> <?php echo htmlspecialchars($booking['seat_numbers']); ?></p>
            <p><strong>Price per Ticket:</strong> ₹<?php echo number_format($booking['price'], 2); ?></p>
            <p><strong>Total Amount:</strong> ₹<?php echo number_format(count(explode(',', $booking['seat_numbers'])) * $booking['price'], 2); ?></p>
            <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['id']); ?></p>
            <a href="movies.php" class="btn">Book Another Ticket</a>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>