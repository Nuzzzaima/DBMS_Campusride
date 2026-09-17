<?php

$id = $_GET['id'];
include "db.php";
if (isset($_POST['confirm'])) {
    $sql = "DELETE FROM ride WHERE ride_id=$id";
    if (mysqli_query($conn, $sql)) {

        echo "<script>
                alert('Ride cancelled successfully!');
                window.location='my_rides.php';
              </script>";

    }

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Ride</title>
    <link rel="stylesheet" href="css/cancel_ride.css">

</head>

<body>

<div class="box">
    <h3>Cancel Ride</h3>
    <p>Are you sure you want to cancel this ride?</p>
    <form method="POST">
        <button type="submit" name="confirm">
            Yes, Cancel Ride
        </button>

        <a href="my_rides.php">
            No, Go Back
        </a>
    </form>

</div>

</body>

</html>