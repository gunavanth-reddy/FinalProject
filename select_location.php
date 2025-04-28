<?php
session_start();
include 'includes/db.php';

if (!isset($_GET['movie_id']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
}

$movie_id = $_GET['movie_id'];
$stmt = $pdo->query("SELECT * FROM locations");
$locations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Location - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Select Location</h2>
        <div class="location-grid">
            <?php foreach ($locations as $location): ?>
                <a href="select_theater.php?movie_id=<?php echo $movie_id; ?>&location_id=<?php echo $location['id']; ?>" class="location-card">
                    <h3><?php echo $location['name']; ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>