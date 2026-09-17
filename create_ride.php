<?php
session_start();
include "db.php";
if (isset($_POST['create'])) {
    $user_id =$_SESSION['user_id'];
$start =$_POST['start'];
$destination =$_POST['destination'];
$date =$_POST['date'];
$time =$_POST['time'];
$ride_type =$_POST['ride_type'];
$seats =$_POST['seats'];
$fare =$_POST['fare'];
$vehicle_id =$_POST['vehicle_id'];
$women_only =isset($_POST['women_only']) ? 1 : 0;
$description =$_POST['description'];

    $sql = "INSERT INTO ride (user_id,vehicle_id,ride_date,departure_time,start_location,
    destination,ride_type,available_seats,fare,women_only,description)
    VALUES
    ('$user_id', '$vehicle_id', '$date', '$time', '$start',
    '$destination', '$ride_type', '$seats', '$fare', '$women_only', '$description')";

    if(mysqli_query($conn,$sql)){
    echo "Ride created successfully!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Rides</title>
    <link rel="stylesheet" href="css/create_ride.css">
</head>
<body>
<div class="header">
    <h2>Create a Ride</h2>
    <p>Offer seats to fellow students on your route</p>
</div>
<div class="container">
<form method="POST">
    <div class="row">
        <div>
            <label>Starting Location</label>
            <input type="text" name="start" placeholder="e.g. Notun Bazar" required>
        </div>
        <div>
            <label>Destination</label>
            <input type="text" name="destination" placeholder="e.g. UIU Permanent Campus,Madani Avenue" required>
        </div>
        <div>
            <label>Ride Date</label>
            <input type="date" name="date" required>
        </div>
        <div>
            <label>Departure Time</label>
            <input type="time" name="time" required>
        </div>
        <div>
            <label>Ride Type</label>
            <select name="ride_type" required>
                <option value="Rickshaw Share">Rickshaw Share</option>
                <option value="CNG Share">CNG Share</option>
                <option value="Car">Car</option>
            </select>
        </div>
        <div>
            <label>Available Seats</label>
            <input type="number" name="seats" min="1" placeholder="e.g. 3" required>
        </div>
        <div>
            <label>Fare (Cost Per Passenger)</label>
            <input type="number" name="fare" min="0" placeholder="e.g. 25" required>
        </div>
        <div>
    <label>Vehicle (optional)</label>
    <select name="vehicle_id">
        <option value="">No personal vehicle</option>
        <?php
        $user_id =$_SESSION['user_id'];
        $sql ="SELECT * FROM vehicle WHERE user_id = '$user_id'";
        $result =mysqli_query($conn, $sql);
        while ($vehicle = mysqli_fetch_assoc($result)) {
        ?>
            <option value="<?php echo $vehicle['vehicle_id'];?>">
                <?php echo $vehicle['vehicle_type'];?>
                <?php echo $vehicle['registration_number'];?>
            </option>
        <?php
        }
        ?>
    </select>
</div>
    </div>

    <div class="women">
        <div>
            <b>Women-Only Ride</b>
            <small>Only female students can request this ride</small>
        </div>
        <input type="checkbox" name="women_only">
    </div>
    <label>Description (optional)</label>
    <textarea name="description"
    placeholder="e.g. I leave from Khilgaon at 8:30 AM sharp, WhatsApp me to coordinate pickup point."></textarea>
    <div class="buttons">
        <button type="submit" name="create">Create Ride</button>
        <button type="reset">Cancel</button>
    </div>
</form>
</div>
</body>
</html>