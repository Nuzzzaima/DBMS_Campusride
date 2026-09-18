<?php

session_start();
include "db.php";

$ride_id = $_GET['id'] ?? 0;


/* GET RIDE DETAILS */

$sql = "SELECT
            ride.*,
            `USER`.full_name,
            `USER`.student_id,
            `USER`.university_email,
            `USER`.phone,
            `USER`.department,
            `USER`.profile_picture,
            vehicle.vehicle_type,
            vehicle.registration_number,
            vehicle.seat_capacity
        FROM ride
        JOIN `USER`
            ON ride.user_id = `USER`.user_id
        LEFT JOIN vehicle
            ON ride.vehicle_id = vehicle.vehicle_id
        WHERE ride.ride_id = '$ride_id'";

$result = mysqli_query($conn, $sql);

$ride = mysqli_fetch_assoc($result);


/* IF RIDE DOES NOT EXIST */

if (!$ride) {
    die("Ride not found.");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Ride Details - CampusRide</title>

    <link rel="stylesheet" href="ride_details.css">

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


        <!-- SIDEBAR BOTTOM -->

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


            <a href="logout.php" class="logout">
                ↪ Logout
            </a>

        </div>

    </div>



    <!-- MAIN -->

    <div class="main">


        <!-- HEADER -->

        <div class="header">

            <div>

                <h2>Ride Details</h2>

                <p>View complete information about this ride</p>

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


            <a href="find_ride.php" class="back">
                ← Back to Find a Ride
            </a>


            <!-- MAIN RIDE CARD -->

            <div class="details-card">


                <!-- DRIVER -->

                <div class="driver-section">

                    <div class="driver-pic">

                        <?php

                        echo strtoupper(
                            substr(
                                $ride['full_name'],
                                0,
                                1
                            )
                        );

                        ?>

                    </div>


                    <div class="driver-info">

                        <h2>

                            <?php

                            echo htmlspecialchars(
                                $ride['full_name']
                            );

                            ?>

                        </h2>


                        <p>

                            <?php

                            echo htmlspecialchars(
                                $ride['department']
                            );

                            ?>

                            Student

                        </p>


                        <div class="stars">
                            ★★★★★
                        </div>

                    </div>


                    <div class="status">

                        <?php

                        echo htmlspecialchars(
                            $ride['ride_status']
                        );

                        ?>

                    </div>

                </div>



                <!-- ROUTE -->

                <div class="section">

                    <h3>Ride Route</h3>


                    <div class="route">


                        <div class="location">

                            <span class="start-dot"></span>

                            <div>

                                <small>Starting Location</small>

                                <b>

                                    <?php

                                    echo htmlspecialchars(
                                        $ride['start_location']
                                    );

                                    ?>

                                </b>

                            </div>

                        </div>


                        <div class="line"></div>


                        <div class="location">

                            <span class="end-dot"></span>

                            <div>

                                <small>Destination</small>

                                <b>

                                    <?php

                                    echo htmlspecialchars(
                                        $ride['destination']
                                    );

                                    ?>

                                </b>

                            </div>

                        </div>


                    </div>

                </div>



                <!-- RIDE INFORMATION -->

                <div class="section">

                    <h3>Ride Information</h3>


                    <div class="info-grid">


                        <div class="info-box">

                            <small>Date</small>

                            <b>

                                <?php

                                echo date(
                                    "M j, Y",
                                    strtotime(
                                        $ride['ride_date']
                                    )
                                );

                                ?>

                            </b>

                        </div>


                        <div class="info-box">

                            <small>Departure Time</small>

                            <b>

                                <?php

                                echo date(
                                    "g:i A",
                                    strtotime(
                                        $ride['departure_time']
                                    )
                                );

                                ?>

                            </b>

                        </div>


                        <div class="info-box">

                            <small>Ride Type</small>

                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $ride['ride_type']
                                );

                                ?>

                            </b>

                        </div>


                        <div class="info-box">

                            <small>Fare</small>

                            <b class="fare">

                                ৳<?php

                                echo number_format(
                                    $ride['fare'],
                                    2
                                );

                                ?>

                            </b>

                        </div>


                        <div class="info-box">

                            <small>Available Seats</small>

                            <b>

                                <?php

                                echo $ride['available_seats'];

                                ?>

                            </b>

                        </div>


                        <div class="info-box">

                            <small>Women Only</small>

                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $ride['women_only']
                                );

                                ?>

                            </b>

                        </div>


                    </div>

                </div>



                <!-- VEHICLE -->

                <div class="section">

                    <h3>Vehicle Information</h3>


                    <div class="vehicle-box">


                        <div>

                            <small>Vehicle Type</small>

                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $ride['vehicle_type']
                                );

                                ?>

                            </b>

                        </div>


                        <div>

                            <small>Registration Number</small>

                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $ride['registration_number']
                                );

                                ?>

                            </b>

                        </div>


                        <div>

                            <small>Seat Capacity</small>

                            <b>

                                <?php

                                echo $ride['seat_capacity'];

                                ?>

                            </b>

                        </div>


                    </div>

                </div>



                <!-- DESCRIPTION -->

                <div class="section">

                    <h3>Ride Description</h3>

                    <p class="description">

                        <?php

                        echo htmlspecialchars(
                            $ride['description']
                        );

                        ?>

                    </p>

                </div>



                <!-- BUTTON -->

                <div class="bottom-button">

                    <?php

                    if (
                        $ride['available_seats'] > 0 &&
                        $ride['ride_status'] == "Active"
                    ) {

                    ?>

                        <a
                            href="request_ride.php?id=<?php echo $ride['ride_id']; ?>"
                            class="request-btn"
                        >
                            Request This Ride
                        </a>

                    <?php

                    } else {

                    ?>

                        <button class="disabled-btn">
                            No Seats Available
                        </button>

                    <?php

                    }

                    ?>

                </div>


            </div>

        </div>

    </div>

</div>


</body>
</html>