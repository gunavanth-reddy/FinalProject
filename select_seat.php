<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['showtime_id']) || !isset($_SESSION['user_id'])) {
    error_log("Select Seat: Missing showtime_id or user_id. Showtime: " . ($_GET['showtime_id'] ?? 'N/A') . ", User ID: " . ($_SESSION['user_id'] ?? 'N/A'));
    header("Location: login.php");
    exit();
}

$showtime_id = $_GET['showtime_id'];

// Verify showtime and get price
$stmt = $pdo->prepare("SELECT id, theater_id, price FROM showtimes WHERE id = ?");
$stmt->execute([$showtime_id]);
$showtime = $stmt->fetch();

if (!$showtime) {
    error_log("Select Seat: Invalid showtime_id: $showtime_id");
    header("Location: index.php");
    exit();
}

$price_per_ticket = $showtime['price'] ?? 100.00; // Fallback price
error_log("Select Seat: Price per ticket: $price_per_ticket");

// Fetch seats
$stmt = $pdo->prepare("
    SELECT s.id, s.seat_number,
           EXISTS (
               SELECT 1 
               FROM booking_seats bs 
               JOIN bookings b ON bs.booking_id = b.id 
               WHERE b.showtime_id = ? AND bs.seat_id = s.id
           ) AS is_booked
    FROM seats s 
    WHERE s.theater_id = ? 
    ORDER BY FIELD(LEFT(s.seat_number, 1), 'A','B','C','D','E','F','G'), 
             CAST(SUBSTRING(s.seat_number, 2) AS UNSIGNED)
");
$stmt->execute([$showtime_id, $showtime['theater_id']]);
$seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($seats)) {
    error_log("Select Seat: No seats found for theater_id: {$showtime['theater_id']}, showtime_id: $showtime_id");
}

error_log("Select Seat: Loaded " . count($seats) . " seats for showtime_id: $showtime_id");

// Group seats by row
$rows = [];
foreach ($seats as $seat) {
    $row_letter = substr($seat['seat_number'], 0, 1);
    $rows[$row_letter][] = $seat;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Seats - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Select Your Seats</h2>
        <?php if (empty($seats)): ?>
            <p class="error">No seats available for this showtime.</p>
        <?php else: ?>
            <div class="screen">Screen</div>
            <div class="seat-map">
                <?php foreach ($rows as $row_letter => $row_seats): ?>
                    <div class="seat-row">
                        <?php foreach ($row_seats as $seat): ?>
                            <div class="seat <?php echo $seat['is_booked'] ? 'booked' : 'available'; ?>" 
                                 data-seat-id="<?php echo $seat['id']; ?>">
                                <?php echo htmlspecialchars($seat['seat_number']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <form id="seat-form" method="POST" action="payment.php">
                <input type="hidden" name="showtime_id" value="<?php echo $showtime_id; ?>">
                <input type="hidden" name="seat_ids" id="selected-seats">
                <div class="pricing">
                    <p>Price per ticket: ₹<?php echo number_format($price_per_ticket, 2); ?></p>
                    <p>Selected Seats: <span id="seat-display">None</span></p>
                    <p>Total: ₹<span id="total-amount">0.00</span></p>
                </div>
                <form action="payment.php" method="POST">
    <button type="submit" id="book-btn">Book Seats</button>
</form>

            </form>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="js/script.js"></script>
    <script>
        console.log('Script loaded. Price per ticket: <?php echo $price_per_ticket; ?>');
    </script>
</body>
</html>