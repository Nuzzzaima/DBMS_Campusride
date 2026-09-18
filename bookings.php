<?php

session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


/* ================= CANCEL BOOKING ================= */

if (isset($_POST['cancel_booking'])) {

    $booking_id = intval($_POST['booking_id']);

    // Check that this booking belongs to the logged-in user
    $check_sql = "SELECT b.booking_id
                  FROM booking b
                  JOIN ride_request rr
                      ON b.request_id = rr.request_id
                  WHERE b.booking_id = $booking_id
                  AND rr.user_id = $user_id
                  AND b.booking_status = 'Confirmed'";

    $check_result = mysqli_query($conn, $check_sql);

    if (!$check_result) {
        die("Booking Check Error: " . mysqli_error($conn));
    }

    if (mysqli_num_rows($check_result) > 0) {

        $cancel_sql = "UPDATE booking
                       SET booking_status = 'Cancelled'
                       WHERE booking_id = $booking_id
                       AND booking_status = 'Confirmed'";

        if (!mysqli_query($conn, $cancel_sql)) {
            die("Cancel Booking Error: " . mysqli_error($conn));
        }
    }

    header("Location: bookings.php");
    exit();
}


/* ================= GET CURRENT USER ================= */

$user_sql = "SELECT full_name, university_email, profile_picture
             FROM `USER`
             WHERE user_id = $user_id";

$user_result = mysqli_query($conn, $user_sql);

if (!$user_result) {
    die("User Query Error: " . mysqli_error($conn));
}

$user = mysqli_fetch_assoc($user_result);

$user_name = $user['full_name'];
$user_email = $user['university_email'];
$user_picture = $user['profile_picture'];


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

    <?php include __DIR__ . '/includes/sidebar.php'; ?>


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

                    <?php if (!empty($user_picture)) { ?>

                        <img
                            src="uploads/<?php echo htmlspecialchars($user_picture); ?>"
                            class="small-pic"
                            alt="Profile"
                        >

                    <?php } else { ?>

                        <div class="small-pic">

                            <?php
                            echo strtoupper(
                                substr(
                                    $user_name,
                                    0,
                                    1
                                )
                            );
                            ?>

                        </div>

                    <?php } ?>


                    <?php echo htmlspecialchars($user_name); ?>

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

                                            <?php
                                            echo htmlspecialchars(
                                                $row['booking_status']
                                            );
                                            ?>

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


                                    ৳<?php
                                    echo number_format(
                                        $row['fare'],
                                        0
                                    );
                                    ?>


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
                                        href="ride_details.php?ride_id=<?php echo $row['ride_id']; ?>"
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


                <!-- ================= NO BOOKINGS ================= -->

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
