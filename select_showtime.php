<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['theater_id']) || !isset($_GET['movie_id']) || !isset($_SESSION['user_id'])) {
    error_log("Select Showtime: Missing parameters or user not logged in. Theater: " . ($_GET['theater_id'] ?? 'N/A') . ", Movie: " . ($_GET['movie_id'] ?? 'N/A') . ", User ID: " . ($_SESSION['user_id'] ?? 'N/A'));
    header("Location: login.php");
    exit();
}

$theater_id = $_GET['theater_id'];
$movie_id = $_GET['movie_id'];

error_log("Select Showtime: Fetching showtimes for Theater ID: $theater_id, Movie ID: $movie_id, User ID: {$_SESSION['user_id']}");

$stmt = $pdo->prepare("
    SELECT st.id, st.show_date, st.show_time, t.name AS theater_name, m.title
    FROM showtimes st
    JOIN theaters t ON st.theater_id = t.id
    JOIN movies m ON st.movie_id = m.id
    WHERE st.theater_id = ? AND st.movie_id = ?
    ORDER BY st.show_date, st.show_time
");
$stmt->execute([$theater_id, $movie_id]);
$showtimes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($showtimes)) {
    error_log("Select Showtime: No showtimes found for Theater ID: $theater_id, Movie ID: $movie_id");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Showtime - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Select Showtime</h2>
        <?php if (empty($showtimes)): ?>
            <p class="error">No showtimes available for this movie and theater.</p>
        <?php else: ?>
            <div class="showtime-grid">
                <?php foreach ($showtimes as $showtime): ?>
                    <?php
                        // Convert to 12-hour format
                        $time = date('h:i A', strtotime($showtime['show_time']));
                    ?>
                    <a href="select_seat.php?showtime_id=<?php echo $showtime['id']; ?>" class="showtime-card">
                        <h3><?php echo htmlspecialchars($showtime['theater_name']); ?></h3>
                        <p><?php echo htmlspecialchars($showtime['title']); ?></p>
                        <p><?php echo htmlspecialchars($showtime['show_date']); ?></p>
                        <p><?php echo htmlspecialchars($time); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>