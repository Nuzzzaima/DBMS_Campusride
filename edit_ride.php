<?php
$id = $_GET['id'];
include "db.php";
$sql = "SELECT * FROM ride WHERE ride_id=$id";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update'])) {

    $departure_time = $_POST['departure_time'];
    $start_location = $_POST['start_location'];
    $destination = $_POST['destination'];
    $ride_type = $_POST['ride_type'];
    $available_seats = $_POST['available_seats'];
    $fare = $_POST['fare'];

    $sql = "UPDATE ride SET departure_time='$departure_time', start_location='$start_location', destination='$destination',
    ride_type='$ride_type',available_seats='$available_seats',fare='$fare' WHERE ride_id=$id";

    if (mysqli_query($conn, $sql)) {
        echo "Updated Successfully";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Ride</title>
    <link rel="stylesheet" href="css/edit_ride.css">

</head>

<body>

<div class="header">
    <h2>Edit Ride</h2>
    <p>Update your ride information</p>
</div>


<div class="container">
<form method="POST">
    <div class="row">

    <div>

        <label>Departure Time</label>

            <input type="time" name="departure_time" value="<?php echo $row['departure_time']; ?>">
        </div>


        <div>

            <label>Start Location</label>

            <input type="text" name="start_location" value="<?php echo $row['start_location']; ?>">

        </div>


        <div>

            <label>Destination</label>

            <input type="text" name="destination" value="<?php echo $row['destination']; ?>">

        </div>


        <div>

            <label>Ride Type</label>
            <input type="text" name="ride_type" value="<?php echo $row['ride_type']; ?>">

        </div>


        <div>

            <label>Available Seats</label>

            <input type="number" name="available_seats" value="<?php echo $row['available_seats']; ?>">

        </div>


        <div>

            <label>Fare per passenger</label>

            <input type="number" name="fare" value="<?php echo $row['fare']; ?>">

        </div>

    </div>


    <div class="buttons">

        <button type="submit" name="update">
            Update Ride
        </button>

        <a href="my_rides.php">
            Cancel
        </a>

    </div>

</form>

</div>

</body>

</html>