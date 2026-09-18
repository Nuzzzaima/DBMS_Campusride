<?php

session_start();

include "db.php";

$user_id = 2;


/* ==============================
   SEND SOS
   ============================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $booking_id = intval($_POST["booking_id"]);

    /* Check whether this booking belongs to the current user */

    $checkBooking = mysqli_query(
        $conn,
        "SELECT b.booking_id
         FROM booking b
         JOIN ride_request rr
             ON b.request_id = rr.request_id
         WHERE b.booking_id = $booking_id
         AND rr.user_id = $user_id"
    );

    if (mysqli_num_rows($checkBooking) > 0) {

        /* Check if an active SOS already exists */

        $checkSOS = mysqli_query(
            $conn,
            "SELECT alert_id
             FROM sos_alert
             WHERE user_id = $user_id
             AND booking_id = $booking_id
             AND alert_status = 'Active'"
        );

        if (mysqli_num_rows($checkSOS) == 0) {

            mysqli_query(
                $conn,
                "INSERT INTO sos_alert
                (user_id, booking_id, alert_time, alert_status)
                VALUES
                ($user_id, $booking_id, NOW(), 'Active')"
            );
        }
    }

    header("Location: sos.php");
    exit();
}


/* ==============================
   GET ACTIVE RIDE
   ============================== */

$sql = "SELECT
            b.booking_id,
            r.ride_id,
            r.start_location,
            r.destination,
            r.ride_date,
            r.departure_time,
            u.full_name,
            u.profile_picture,
            v.vehicle_type,
            v.registration_number
        FROM booking b
        JOIN ride_request rr
            ON b.request_id = rr.request_id
        JOIN ride r
            ON rr.ride_id = r.ride_id
        JOIN `USER` u
            ON r.user_id = u.user_id
        LEFT JOIN vehicle v
            ON r.vehicle_id = v.vehicle_id
        WHERE rr.user_id = $user_id
        AND b.booking_status = 'Confirmed'
        AND r.ride_status = 'Active'
        ORDER BY r.ride_date ASC,
                 r.departure_time ASC
        LIMIT 1";

$result = mysqli_query($conn, $sql);

$ride = mysqli_fetch_assoc($result);


/* ==============================
   CHECK ACTIVE SOS
   ============================== */

$active_sos = false;

if ($ride) {

    $booking_id = intval($ride["booking_id"]);

    $sosCheck = mysqli_query(
        $conn,
        "SELECT alert_id
         FROM sos_alert
         WHERE user_id = $user_id
         AND booking_id = $booking_id
         AND alert_status = 'Active'"
    );

    if (mysqli_num_rows($sosCheck) > 0) {
        $active_sos = true;
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>CampusRide - SOS / Emergency</title>

    <link rel="stylesheet" href="sos.css">

</head>

<body>

<div class="dashboard">


    <!-- SIDEBAR -->

    <aside class="sidebar">

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

            <a href="find_ride.php">
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

            <a href="sos.php" class="active">
                <span>SOS / Emergency</span>
            </a>

        </div>


        <!-- SIDEBAR BOTTOM -->

        <div class="sidebar-bottom">

            <div class="profile">

                <div class="profile-pic">
                    N
                </div>

                <div>
                    <b>Nadia Rahman</b>
                    <small>nadia@uiu.ac.bd</small>
                </div>

            </div>


            <a href="logout.php" class="logout">
                ⇥ &nbsp; Logout
            </a>

        </div>

    </aside>



    <!-- MAIN -->

    <main class="main">


        <!-- HEADER -->

        <header class="header">

            <div>

                <h2>
                    SOS / Emergency
                </h2>

                <p>
                    Your safety is our top priority
                </p>

            </div>


            <div class="header-right">

                <div class="notification">
                    ♧
                </div>

                <div class="user">

                    <div class="small-pic">
                        N
                    </div>

                    <span>
                        Nadia
                    </span>

                </div>

            </div>

        </header>



        <!-- CONTENT -->

        <section class="content">


            <!-- ACTIVE RIDE CARD -->

            <?php if ($ride) { ?>

                <div class="ride-card">

                    <div class="ride-title">
                        CURRENT ACTIVE RIDE
                    </div>


                    <div class="driver">

                        <?php if (!empty($ride["profile_picture"])) { ?>

                            <img
                                src="<?php echo htmlspecialchars($ride["profile_picture"]); ?>"
                                class="driver-picture"
                                alt="Driver"
                            >

                        <?php } else { ?>

                            <div class="driver-picture">

                                <?php
                                echo strtoupper(
                                    substr($ride["full_name"], 0, 1)
                                );
                                ?>

                            </div>

                        <?php } ?>


                        <div class="driver-info">

                            <h3>

                                <?php
                                echo htmlspecialchars(
                                    $ride["full_name"]
                                );
                                ?>

                            </h3>


                            <p>

                                <?php
                                echo htmlspecialchars(
                                    $ride["vehicle_type"]
                                );
                                ?>

                                ·

                                <?php
                                echo htmlspecialchars(
                                    $ride["registration_number"]
                                );
                                ?>

                            </p>

                        </div>


                        <span class="active">
                            Active
                        </span>

                    </div>



                    <!-- ROUTE -->

                    <div class="route">

                        <div class="route-item">

                            <span class="dot"></span>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $ride["start_location"]
                                );
                                ?>

                            </span>

                        </div>


                        <div class="route-line"></div>


                        <div class="route-item">

                            <span class="circle"></span>

                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $ride["destination"]
                                );
                                ?>

                            </span>

                        </div>

                    </div>

                </div>


            <?php } else { ?>


                <div class="ride-card no-ride">

                    No active ride found.

                </div>


            <?php } ?>



            <!-- SOS -->

            <div class="sos-card">

                <p class="sos-description">

                    Press the SOS button in an emergency.
                    This will alert emergency contacts and
                    campus security with your real-time location.

                </p>


                <?php if ($ride) { ?>

                    <form method="POST" action="sos.php">

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?php echo $ride["booking_id"]; ?>"
                        >


                        <button
                            type="submit"
                            class="sos-button"
                        >

                            <span class="warning">
                                ⚠
                            </span>

                            <span class="sos-text">
                                SOS
                            </span>

                        </button>

                    </form>


                    <?php if ($active_sos) { ?>

                        <div class="hold active-message">
                            SOS alert is currently active
                        </div>

                    <?php } else { ?>

                        <div class="hold">
                            Press to send an emergency alert
                        </div>

                    <?php } ?>


                <?php } else { ?>


                    <button
                        type="button"
                        class="sos-button disabled"
                        disabled
                    >

                        <span class="warning">
                            ⚠
                        </span>

                        <span class="sos-text">
                            SOS
                        </span>

                    </button>


                    <div class="hold">
                        No active ride available
                    </div>


                <?php } ?>

            </div>



            <!-- EMERGENCY CONTACTS -->

            <div class="contacts-card">

                <h3>
                    Emergency Contacts
                </h3>


                <div class="contact">

                    <div class="contact-left">

                        <div class="contact-icon purple">
                            ♢
                        </div>

                        <div>

                            <b>
                                UIU Campus Security
                            </b>

                            <span>
                                +880 1900-000100
                            </span>

                        </div>

                    </div>


                    <a
                        href="tel:+8801900000100"
                        class="call"
                    >
                        ☎ &nbsp; Call
                    </a>

                </div>



                <div class="contact">

                    <div class="contact-left">

                        <div class="contact-icon red">
                            ⚠
                        </div>

                        <div>

                            <b>
                                National Emergency
                            </b>

                            <span>
                                999
                            </span>

                        </div>

                    </div>


                    <a
                        href="tel:999"
                        class="call"
                    >
                        ☎ &nbsp; Call
                    </a>

                </div>



                <div class="contact">

                    <div class="contact-left">

                        <div class="contact-icon blue">
                            ☎
                        </div>

                        <div>

                            <b>
                                Mom (Rashida Begum)
                            </b>

                            <span>
                                +880 1711-000222
                            </span>

                        </div>

                    </div>


                    <a
                        href="tel:+8801711000222"
                        class="call"
                    >
                        ☎ &nbsp; Call
                    </a>

                </div>

            </div>

        </section>

    </main>

</div>

</body>

</html>