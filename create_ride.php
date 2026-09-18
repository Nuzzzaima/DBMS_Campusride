<?php
session_start();
include "db.php";
if (isset($_POST['create'])) {
   $user_id = $_SESSION['user_id'] ?? 1;
$start =$_POST['start'];
$destination =$_POST['destination'];
$date =$_POST['date'];
$time =$_POST['time'];
$ride_type =$_POST['ride_type'];
$seats =$_POST['seats'];
$fare =$_POST['fare'];
$vehicle_id = !empty($_POST['vehicle_id']) ? $_POST['vehicle_id'] : "NULL";
$women_only = isset($_POST['women_only']) ? 'Yes' : 'No';
$description =$_POST['description'];

    $sql = "INSERT INTO ride (user_id,vehicle_id,ride_date,departure_time,start_location,
    destination,ride_type,available_seats,fare,women_only,description)
    VALUES
('$user_id', $vehicle_id, '$date', '$time', '$start',
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
    <style>
body {
    display: grid !important;
    grid-template-columns: 197px 1fr !important;
    grid-template-areas: "sdbr main" !important;
    margin: 0 !important;
}

.sidebar {
    grid-area: sdbr !important;
    min-height: 100vh !important;
    background-color: white !important;
    border-right: 1px solid #ded9f3 !important;
}

.sidebar .logo {
    padding: 20px 15px !important;
}

.sidebar .logo b {
    display: block !important;
    font-size: 16px !important;
    color: #08194f !important;
}

.sidebar .logo small {
    display: block !important;
    margin-top: 4px !important;
    font-size: 10px !important;
    color: #8790a5 !important;
}

.sidebar ul {
    list-style: none !important;
    margin: 0 !important;
    padding: 12px 10px !important;
}

.sidebar li {
    display: block !important;
    margin-bottom: 4px !important;
}

.sidebar a {
    display: block !important;
    padding: 11px 12px !important;
    text-decoration: none !important;
    color: #5f6375 !important;
    font-size: 13px !important;
}

.main-content {
    grid-area: main !important;
    margin-left: 0 !important;
}
</style>
</head>
<body>
    <div class="sidebar">
        <div class="logo"> <b>CampusRide</b> <small>UIU Student Portal</small> </div> <div class="menu"> <ul> <li><a href="dashboard.php">Dashboard</a></li> <li><a href="find_ride.php">Find a Ride</a></li> <li><a href="create_ride.php">Create a Ride</a></li> <li><a href="my_rides.php">My Rides</a></li> <li><a href="ride_summary.php">Ride Summary</a></li> <li><a href="#">Ride Requests</a></li> <li><a href="#">Bookings</a></li> <li><a href="#">Vehicles</a></li> <li><a href="profile.php">Profile</a></li> <li><a href="#">Ratings & Reviews</a></li> <li><a href="#">My Savings</a></li> <li><a href="#">Notifications</a></li> <li><a href="#">SOS / Emergency</a></li> </ul> </div>
    </div>
    <div class="main-content">
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
    </div>
</body>
</html>