
<?php

session_start();

include "db.php";

/* TEMPORARY USER ID
   Later you can get this from login session */
$user_id = 1;

$from = "";
$to = "";
$date = "";
$time = "";
$ride_type = "";
$women_only = "";


/* SEARCH VALUES */
if (isset($_GET['search'])) {

    $from = $_GET['from'] ?? "";
    $to = $_GET['to'] ?? "";
    $date = $_GET['date'] ?? "";
    $time = $_GET['time'] ?? "";
    $ride_type = $_GET['ride_type'] ?? "";

    if (isset($_GET['women_only'])) {
        $women_only = "Yes";
    }
}


/* GET ACTIVE RIDES */
$sql = "SELECT 
            ride.*,
            `USER`.full_name,
            `USER`.profile_picture,
            vehicle.vehicle_type,
            vehicle.registration_number,
            vehicle.seat_capacity
        FROM ride
        JOIN `USER`
            ON ride.user_id = `USER`.user_id
        LEFT JOIN vehicle
            ON ride.vehicle_id = vehicle.vehicle_id
        WHERE ride.ride_status = 'Active'
        AND ride.available_seats > 0";


/* FROM */
if ($from != "") {
    $sql .= " AND ride.start_location LIKE '%" . mysqli_real_escape_string($conn, $from) . "%'";
}


/* TO */
if ($to != "") {
    $sql .= " AND ride.destination LIKE '%" . mysqli_real_escape_string($conn, $to) . "%'";
}


/* DATE */
if ($date != "") {

    $date_safe = mysqli_real_escape_string($conn, $date);

    $sql .= " AND ride.ride_date = '$date_safe'";
}


/* TIME */
if ($time != "") {

    $time_safe = mysqli_real_escape_string($conn, $time);

    $sql .= " AND ride.departure_time >= '$time_safe'";
}


/* RIDE TYPE */
if ($ride_type != "" && $ride_type != "All Types") {

    $type_safe = mysqli_real_escape_string($conn, $ride_type);

    $sql .= " AND ride.ride_type = '$type_safe'";
}


/* WOMEN ONLY */
if ($women_only == "Yes") {

    $sql .= " AND ride.women_only = 'Yes'";
}


/* SORT */
$sql .= " ORDER BY ride.ride_date ASC, ride.departure_time ASC";


$result = mysqli_query($conn, $sql);

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Find a Ride - CampusRide</title>

    <link rel="stylesheet" href="find_ride.css">

</head>

<body>


<div class="dashboard">


    <!-- SIDEBAR -->

    <div class="sidebar">

        <div class="logo">

            <div class="logo-icon">
                🚗
            </div>

            <div>

                <b>CampusRide</b>

                <small>UIU Student Portal</small>

            </div>

        </div>



        <div class="menu">

            <a href="#">
                <span>Dashboard</span>
            </a>

            <a href="find_ride.php" class="active">
                <span>Find a Ride</span>
            </a>

            <a href="#">
                <span>Create a Ride</span>
            </a>

            <a href="#">
                <span>My Rides</span>
            </a>

            <!-- ADDED RIDE SUMMARY -->

            <a href="#">
                <span>Ride Summary</span>
            </a>

            <a href="ride_requests.php">
                <span>Ride Requests</span>
            </a>

            <a href="bookings.php">
                <span>Bookings</span>
            </a>

            <a href="#">
                <span>Vehicles</span>
            </a>

            <a href="#">
                <span>Profile</span>
            </a>

            <a href="ratings.php">
                <span>Ratings & Reviews</span>
            </a>

            <a href="#">
                <span>My Savings</span>
            </a>

            <a href="#">
                <span>Notifications</span>
            </a>

            <a href="sos.php">
                <span>SOS / Emergency</span>
            </a>

        </div>



        <!-- SIDEBAR PROFILE -->

        <div class="sidebar-bottom">

            <div class="profile">

                <div class="profile-pic">
                    A
                </div>

                <div>

                    <b>Arafat Rahman</b>

                    <small>arafat@uiu.ac.bd</small>

                </div>

            </div>



            <a href="#" class="logout">
                ↪ Logout
            </a>

        </div>

    </div>



    <!-- MAIN -->

    <div class="main">


        <!-- HEADER -->

        <div class="header">

            <div>

                <h2>Finding a Ride</h2>

                <p>Search available rides on your route</p>

            </div>



            <div class="header-right">

                <span class="notification">
                    ♧
                </span>



                <div class="user">

                    <div class="small-pic">
                        A
                    </div>

                    Arafat

                </div>

            </div>

        </div>



        <!-- CONTENT -->

        <div class="content">


            <!-- SEARCH BOX -->

            <form
                method="GET"
                action="find_ride.php"
                class="search-box"
            >


                <div class="field">

                    <label>From</label>

                    <input
                        type="text"
                        name="from"
                        placeholder="Starting location"
                        value="<?php echo htmlspecialchars($from); ?>"
                    >

                </div>



                <div class="field">

                    <label>To</label>

                    <input
                        type="text"
                        name="to"
                        placeholder="Destination"
                        value="<?php echo htmlspecialchars($to); ?>"
                    >

                </div>



                <div class="field">

                    <label>Date</label>

                    <input
                        type="date"
                        name="date"
                        value="<?php echo htmlspecialchars($date); ?>"
                    >

                </div>



                <div class="field">

                    <label>Time</label>

                    <input
                        type="time"
                        name="time"
                        value="<?php echo htmlspecialchars($time); ?>"
                    >

                </div>



                <div class="field">

                    <label>Ride Type</label>

                    <select name="ride_type">

                        <option
                            value="All Types"
                            <?php
                            if ($ride_type == "" || $ride_type == "All Types")
                                echo "selected";
                            ?>
                        >
                            All Types
                        </option>



                        <option
                            value="Car Share"
                            <?php
                            if ($ride_type == "Car Share")
                                echo "selected";
                            ?>
                        >
                            Car Share
                        </option>



                        <option
                            value="Bike Share"
                            <?php
                            if ($ride_type == "Bike Share")
                                echo "selected";
                            ?>
                        >
                            Bike Share
                        </option>



                        <option
                            value="Rickshaw Share"
                            <?php
                            if ($ride_type == "Rickshaw Share")
                                echo "selected";
                            ?>
                        >
                            Rickshaw Share
                        </option>

                    </select>

                </div>



                <div class="field">

                    <label>Women Only</label>

                    <label class="check-box">

                        <input
                            type="checkbox"
                            name="women_only"
                            <?php
                            if ($women_only == "Yes")
                                echo "checked";
                            ?>
                        >

                        Women Only Rides

                    </label>

                </div>



                <div class="search-buttons">

                    <button
                        type="submit"
                        name="search"
                        class="search-btn"
                    >
                        Search Rides
                    </button>



                    <a
                        href="find_ride.php"
                        class="clear-btn"
                    >
                        Clear Filters
                    </a>

                </div>

            </form>



            <!-- RESULT HEADER -->

            <div class="result-header">

                <p>

                    <?php

                    echo mysqli_num_rows($result);

                    ?>

                    rides found

                </p>



                <div class="sort">

                    Sort: Departure Time

                </div>

            </div>



            <!-- RIDES -->

            <div class="ride-list">


                <?php

                if (mysqli_num_rows($result) > 0) {

                    while ($row = mysqli_fetch_assoc($result)) {

                ?>



                <!-- RIDE CARD -->

                <div class="ride-card">


                    <!-- DRIVER -->

                    <div class="driver">


                        <div class="driver-pic">

                            <?php

                            echo strtoupper(

                                substr(

                                    $row['full_name'],

                                    0,

                                    1

                                )

                            );

                            ?>

                        </div>



                        <div class="driver-info">

                            <b>

                                <?php

                                echo htmlspecialchars(

                                    $row['full_name']

                                );

                                ?>

                            </b>



                            <div class="stars">

                                ★★★★★

                            </div>

                        </div>



                        <div class="ride-tags">


                            <span class="vehicle">

                                <?php

                                echo htmlspecialchars(

                                    $row['vehicle_type']

                                );

                                ?>

                            </span>



                            <?php

                            if ($row['women_only'] == "Yes") {

                            ?>

                                <span class="women-tag">

                                    Women Only

                                </span>

                            <?php

                            }

                            ?>

                        </div>

                    </div>



                    <!-- ROUTE -->

                    <div class="route">


                        <div class="route-line">

                            <span class="dot"></span>

                            <div>

                                <?php

                                echo htmlspecialchars(

                                    $row['start_location']

                                );

                                ?>

                            </div>

                        </div>



                        <div class="route-line">

                            <span class="circle"></span>

                            <div>

                                <?php

                                echo htmlspecialchars(

                                    $row['destination']

                                );

                                ?>

                            </div>

                        </div>



                        <div class="fare">

                            <b>

                                ৳<?php

                                echo number_format(

                                    $row['fare'],

                                    2

                                );

                                ?>

                            </b>



                            <small>

                                / person

                            </small>

                        </div>

                    </div>



                    <!-- RIDE INFORMATION -->

                    <div class="ride-details">


                        <span>

                            Date:

                            <?php

                            echo date(

                                "M j, Y",

                                strtotime(

                                    $row['ride_date']

                                )

                            );

                            ?>

                        </span>



                        <span>

                            Time:

                            <?php

                            echo date(

                                "g:i A",

                                strtotime(

                                    $row['departure_time']

                                )

                            );

                            ?>

                        </span>



                        <span>

                            <?php

                            echo $row['available_seats'];

                            ?>

                            seats left

                        </span>

                    </div>



                    <!-- RIDE TYPE -->

                    <div class="ride-type">

                        <?php

                        echo htmlspecialchars(

                            $row['ride_type']

                        );

                        ?>

                    </div>



                    <!-- BUTTONS -->

                    <div class="card-buttons">


                        <a
                            href="ride_details.php?id=<?php echo $row['ride_id']; ?>"
                            class="view-btn"
                        >
                            View Details
                        </a>



                        <a
                            href="request_ride.php?id=<?php echo $row['ride_id']; ?>"
                            class="request-btn"
                        >
                            Request Ride
                        </a>

                    </div>

                </div>



                <?php

                    }

                } else {

                ?>



                <div class="no-rides">

                    <h3>
                        No rides found
                    </h3>

                    <p>
                        No active rides match your search.
                    </p>

                </div>



                <?php

                }

                ?>

            </div>

        </div>

    </div>

</div>


</body>

</html>