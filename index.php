<?php
session_start();
include 'includes/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - BookMyMovie</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
        <h2>Welcome to Movie Booking</h2>
        <p>Browse and book your favorite movies!</p>
        <a href="movies.php" class="btn">View Movies</a>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>