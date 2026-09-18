<?php

session_start();

include "db.php";

/* TEMPORARY USER ID */
$user_id = 2;


/* ================= CANCEL BOOKING ================= */

if (isset($_POST['cancel_booking'])) {

    $booking_id = intval($_POST['booking_id']);

    $cancel_sql = "UPDATE booking
                   SET booking_status = 'Cancelled'
                   WHERE booking_id = $booking_id
                   AND booking_status = 'Confirmed'";

    mysqli_query($conn, $cancel_sql);

    header("Location: bookings.php");
    exit();
}


/* ================= GET BOOKINGS ================= */

$sql = "SELECT
            b.booking_id,
            b.request_id,
            b.booking_date,
            b.booking_status,

            rr.user_id,
            rr.ride_id,

            r.start_location,
            r.destination,
            r.ride_date,
            r.departure_time,
            r.ride_type,
            r.fare,

            u.full_name,
            u.profile_picture

        FROM booking b

        JOIN ride_request rr
            ON b.request_id = rr.request_id

        JOIN ride r
            ON rr.ride_id = r.ride_id

        JOIN `USER` u
            ON r.user_id = u.user_id

        WHERE rr.user_id = $user_id

        ORDER BY b.booking_date DESC";


$result = mysqli_query($conn, $sql);


if (!$result) {
    die("Booking Query Error: " . mysqli_error($conn));
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Bookings - CampusRide</title>

    <link rel="stylesheet" href="bookings.css">

</head>


<body>


<div class="dashboard">


    <!-- ================= SIDEBAR ================= -->

    <div class="sidebar">


        <!-- LOGO -->

        <div class="logo">

            <div class="logo-icon">
                C
            </div>

            <div>

                <b>CampusRide</b>

                <small>UIU Student Portal</small>

            </div>

        </div>


        <!-- MENU -->

        <div class="menu">


            <a href="#">
                Dashboard
            </a>


            <a href="find_ride.php">
                Find a Ride
            </a>


            <a href="#">
                Create a Ride
            </a>


            <a href="#">
                My Rides
            </a>


            <a href="#">
                Ride Summary
            </a>


            <a href="ride_requests.php">
                Ride Requests
            </a>


            <a href="bookings.php" class="active">
                Bookings
            </a>


            <a href="#">
                Vehicles
            </a>


            <a href="#">
                Profile
            </a>


            <a href="ratings.php">
                Ratings & Reviews
            </a>


            <a href="#">
                My Savings
            </a>


            <a href="#">
                Notifications
            </a>


            <a href="sos.php">
                SOS / Emergency
            </a>


        </div>


        <!-- SIDEBAR USER -->

        <div class="sidebar-bottom">


            <div class="profile">


                <div class="profile-pic">
                    N
                </div>


                <div>

                    <b>Nadia</b>

                    <small>Student</small>

                </div>


            </div>


            <a href="#" class="logout">
                Logout
            </a>


        </div>


    </div>



    <!-- ================= MAIN ================= -->

    <div class="main">


        <!-- ================= HEADER ================= -->

        <div class="header">


            <div>

                <h2>Bookings</h2>

                <p>Your ride booking history</p>

            </div>


            <div class="header-right">


                <div class="notification">
                    ♧
                </div>


                <div class="user">


                    <div class="small-pic">
                        N
                    </div>


                    Nadia


                </div>


            </div>


        </div>



        <!-- ================= CONTENT ================= -->

        <div class="content">


            <div class="page-heading">

                <h2>My Bookings</h2>

                <p>View and manage your ride bookings</p>

            </div>



            <?php if (mysqli_num_rows($result) > 0) { ?>


                <div class="booking-list">


                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>


                        <?php

                        $status = strtolower($row['booking_status']);

                        $status_class = '';

                        if ($status == 'confirmed') {
                            $status_class = 'confirmed';
                        } elseif ($status == 'completed') {
                            $status_class = 'completed';
                        } elseif ($status == 'cancelled') {
                            $status_class = 'cancelled';
                        }

                        ?>


                        <!-- ================= BOOKING CARD ================= -->

                        <div class="booking-card">


                            <!-- TOP ROW -->

                            <div class="booking-top">


                                <div class="booking-left">


                                    <div class="booking-number">

                                        #BK<?php echo $row['booking_id']; ?>

                                        <span class="status <?php echo $status_class; ?>">

                                            <?php echo htmlspecialchars($row['booking_status']); ?>

                                        </span>

                                    </div>


                                    <div class="booking-date">

                                        <?php

                                        echo date(
                                            "j M Y",
                                            strtotime($row['ride_date'])
                                        );

                                        ?>

                                        ·

                                        <?php

                                        echo date(
                                            "h:i A",
                                            strtotime($row['departure_time'])
                                        );

                                        ?>

                                    </div>


                                </div>



                                <div class="fare">

                                    ৳<?php echo number_format($row['fare'], 0); ?>

                                </div>


                            </div>



                            <!-- ROUTE -->

                            <div class="route">


                                <div class="location">


                                    <div class="start-dot"></div>


                                    <div>

                                        <small>From</small>

                                        <b>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['start_location']
                                            );
                                            ?>
                                        </b>

                                    </div>


                                </div>



                                <div class="route-line"></div>



                                <div class="location">


                                    <div class="end-dot"></div>


                                    <div>

                                        <small>To</small>

                                        <b>
                                            <?php
                                            echo htmlspecialchars(
                                                $row['destination']
                                            );
                                            ?>
                                        </b>

                                    </div>


                                </div>


                            </div>



                            <!-- BOTTOM -->

                            <div class="booking-bottom">


                                <!-- DRIVER -->

                                <div class="driver">


                                    <?php if (!empty($row['profile_picture'])) { ?>


                                        <img
                                            src="uploads/<?php echo htmlspecialchars($row['profile_picture']); ?>"
                                            class="driver-pic"
                                            alt="Driver"
                                        >


                                    <?php } else { ?>


                                        <div class="driver-pic driver-initial">

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


                                    <?php } ?>


                                    <div class="driver-name">

                                        <?php
                                        echo htmlspecialchars(
                                            $row['full_name']
                                        );
                                        ?>

                                    </div>


                                </div>



                                <!-- ACTIONS -->

                                <div class="actions">


                                    <a
                                        href="ride_details.php?id=<?php echo $row['ride_id']; ?>"
                                        class="view-btn"
                                    >
                                        View Details
                                    </a>



                                    <?php if ($row['booking_status'] == 'Confirmed') { ?>


                                        <form
                                            method="POST"
                                            action="bookings.php"
                                            class="cancel-form"
                                        >


                                            <input
                                                type="hidden"
                                                name="booking_id"
                                                value="<?php echo $row['booking_id']; ?>"
                                            >


                                            <button
                                                type="submit"
                                                name="cancel_booking"
                                                class="cancel-btn"
                                            >
                                                Cancel
                                            </button>


                                        </form>



                                        <a
                                            href="sos.php"
                                            class="sos-btn"
                                        >
                                            ⚠ SOS
                                        </a>


                                    <?php } ?>


                                    <?php if ($row['booking_status'] == 'Completed') { ?>


                                        <a
                                            href="ratings.php"
                                            class="rate-btn"
                                        >
                                            ☆ Rate
                                        </a>


                                    <?php } ?>


                                </div>


                            </div>


                        </div>


                    <?php } ?>


                </div>


            <?php } else { ?>


                <!-- NO BOOKINGS -->

                <div class="no-bookings">

                    <h3>No bookings found.</h3>

                    <p>
                        You don't have any ride bookings yet.
                    </p>

                    <a href="find_ride.php">
                        Find a Ride
                    </a>

                </div>


            <?php } ?>


        </div>


    </div>


</div>


</body>

</html>