<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['movie_id']) || !isset($_GET['location_id']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$movie_id = $_GET['movie_id'];
$location_id = $_GET['location_id'];
$stmt = $pdo->prepare("SELECT * FROM theaters WHERE location_id = ?");
$stmt->execute([$location_id]);
$theaters = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Theater - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Select Theater</h2>
        <div class="theater-grid">
            <?php foreach ($theaters as $theater): ?>
                <a href="select_showtime.php?movie_id=<?php echo $movie_id; ?>&theater_id=<?php echo $theater['id']; ?>" class="theater-card">
                    <h3><?php echo $theater['name']; ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>