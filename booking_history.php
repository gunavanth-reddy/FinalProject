<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id'])) {
    error_log("Booking History: User not logged in");
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch booking history
$stmt = $pdo->prepare("
    SELECT b.id, m.title, t.name AS theater_name, l.name AS location_name, 
           st.show_date, st.show_time, st.price, GROUP_CONCAT(s.seat_number) AS seat_numbers
    FROM bookings b
    JOIN showtimes st ON b.showtime_id = st.id
    JOIN movies m ON st.movie_id = m.id
    JOIN theaters t ON st.theater_id = t.id
    JOIN locations l ON t.location_id = l.id
    JOIN booking_seats bs ON b.id = bs.booking_id
    JOIN seats s ON bs.seat_id = s.id
    WHERE b.user_id = ?
    GROUP BY b.id
    ORDER BY b.booking_date DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

error_log("Booking History: Loaded " . count($bookings) . " bookings for User ID: $user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Booking History - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>My Booking History</h2>
        <?php if (empty($bookings)): ?>
            <p class="error">No bookings found.</p>
        <?php else: ?>
            <div class="booking-list">
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-card">
                        <h3><?php echo htmlspecialchars($booking['title']); ?></h3>
                        <p><strong>Theater:</strong> <?php echo htmlspecialchars($booking['theater_name']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($booking['location_name']); ?></p>
                        <p><strong>Date:</strong> <?php echo htmlspecialchars($booking['show_date']); ?></p>
                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($booking['show_time'])); ?></p>
                        <p><strong>Seats:</strong> <?php echo htmlspecialchars($booking['seat_numbers']); ?></p>
                        <p><strong>Price per Ticket:</strong> ₹<?php echo number_format($booking['price'], 2); ?></p>
                        <p><strong>Total Amount:</strong> ₹<?php echo number_format(count(explode(',', $booking['seat_numbers'])) * $booking['price'], 2); ?></p>
                        <p><strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['id']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a href="movies.php" class="btn">Book More Tickets</a>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>