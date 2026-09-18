<?php

session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM ride WHERE user_id = '$user_id' ORDER BY ride_date DESC, departure_time DESC";
$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>
<html>

<head>
    <title>My Rides - CampusRide</title>
    <link rel="stylesheet" href="css/my_rides.css">
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

    <div class="logo">
        <b>CampusRide</b>
        <small>UIU Student Portal</small>
    </div>

    <div class="menu">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="find_ride.php">Find a Ride</a></li>
            <li><a href="create_ride.php">Create a Ride</a></li>
            <li><a href="my_rides.php"  class="active">My Rides</a></li>
            <li><a href="ride_summary.php">Ride Summary</a></li>
            <li><a href="ride_requests.php">Ride Requests</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="vehicles.php">Vehicles</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="ratings.php">Ratings & Reviews</a></li>
            <li><a href="#">My Savings</a></li>
            <li><a href="#">Notifications</a></li>
            <li><a href="sos.php">SOS / Emergency</a></li>
        </ul>
    </div>

</div>
<div class="main-content">
<div class="header">
    <div>
        <h2>My Rides</h2>
        <p>Manage your current and previous rides</p>
    </div>
    <a href="create_ride.php" class="create-btn">+ Create a Ride</a>
</div>


<div class="container">
<div class="page-title">
        <h3>Current Ride</h3>
        <p>Your active ride</p>
    </div>


    <?php
    $current_found =false;
    if (mysqli_num_rows($result) > 0) {
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_assoc($result)) {
            $ride_datetime = $row['ride_date'] . ' ' . $row['departure_time'];
            $current_datetime = date('Y-m-d H:i:s');
            if ($row['ride_status'] == 'Active' && $ride_datetime >= $current_datetime) {
                $current_found = true;

    ?>

    <div class="ride-card current-card">
        <div class="ride-top">
            <div>
                <h3>
                    <?php echo $row['start_location']; ?>
                    -
                    <?php echo $row['destination']; ?>
                </h3>

                <span class="ride-type">
                    <?php echo $row['ride_type']; ?>
                </span>

            </div>
            <span class="status">
                Active
            </span>
        </div>


        <div class="ride-info">

        <div>
                <small>Date</small>
                <p><?php echo $row['ride_date']; ?></p>
            </div>

        <div>
                <small>Departure</small>
                <p><?php echo $row['departure_time']; ?></p>
            </div>

        <div>
                <small>Available Seats</small>
                <p><?php echo $row['available_seats']; ?></p>
            </div>

            <div>
                <small>Fare / Passenger</small>
                <p><?php echo $row['fare']; ?> Taka</p>
            </div>

        </div>


        <div class="ride-bottom">

            <div>

                <?php

                if ($row['women_only'] == 1){
                    echo "<span class='women'>Women Only</span>";

                }else{
                    echo "<span class='normal'>Open to all students</span>";
                }

                ?>

            </div>


            <div class="actions">
                <a href="edit_ride.php?id=<?php echo $row['ride_id']; ?>">
                    Edit
                </a>
                <a href="cancel_ride.php?id=<?php echo $row['ride_id']; ?>" class="cancel">
                    Cancel
                </a>
                <a href="ride_details.php?ride_id=<?php echo $row['ride_id']; ?>" class="details-btn">
    View Details
</a>
            </div>

        </div>

    </div>

    <?php

            }
        }
    }


    if (!$current_found) {

        echo "<div class='no-rides'>";
        echo "<h3>No current ride</h3>";
        echo "<p>You do not have an active ride right now.</p>";
        echo "</div>";

    }

    ?>


    <div class="page-title previous-title">
        <h3>Previous Rides</h3>
        <p>Rides that have already been completed</p>
    </div>


    <?php
    $previous_found = false;
    if (mysqli_num_rows($result) > 0) {
        mysqli_data_seek($result, 0);
        while ($row = mysqli_fetch_assoc($result)) {
            $ride_datetime =$row['ride_date'] . ' ' . $row['departure_time'];
            $current_datetime =date('Y-m-d H:i:s');
            if ($ride_datetime < $current_datetime || $row['ride_status'] != 'Active') {
                $previous_found = true;

    ?>

    <div class="ride-card previous-card">
        <div class="ride-top">
            <div>
                <h3>
                    <?php echo $row['start_location']; ?>
                    -
                    <?php echo $row['destination']; ?>
                </h3>
                <span class="ride-type">
                    <?php echo $row['ride_type']; ?>
                </span>

            </div>
            <span class="completed">
                Completed
            </span>
        </div>


        <div class="ride-info">

            <div>
                <small>Date</small>
                <p><?php echo $row['ride_date']; ?></p>
            </div>

            <div>
                <small>Departure</small>
                <p><?php echo $row['departure_time']; ?></p>
            </div>

            <div>
                <small>Available Seats</small>
                <p><?php echo $row['available_seats']; ?></p>
            </div>

            <div>
                <small>Fare / Passenger</small>
                <p><?php echo $row['fare']; ?> Taka</p>
            </div>

        </div>


        <div class="ride-bottom">
            <div>
                <?php
                if ($row['women_only'] == 1) {

                    echo "<span class='women'>Women Only</span>";
                } else {
                    echo "<span class='normal'>Open to all students</span>";
                }

                ?>

            </div>

            <div class="actions">
                 <a href="ride_details.php?ride_id=<?php echo $row['ride_id']; ?>" class="details-btn">
    View Details
</a>
            </div>

        </div>
    </div>

    <?php

            }
        }
    }


    if (!$previous_found){

        echo "<div class='no-rides'>";
        echo "<h3>No previous rides</h3>";
        echo "<p>Your completed rides will appear here.</p>";
        echo "</div>";

    }
    ?>
</div>
</div>
</body>
</html>