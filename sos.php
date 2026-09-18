<?php

session_start();
include "db.php";


// ==============================
// CHECK LOGIN
// ==============================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// ==============================
// GET LOGGED-IN USER
// ==============================

$sql_user = "SELECT full_name, university_email
             FROM `USER`
             WHERE user_id = '$user_id'
             LIMIT 1";

$result_user = mysqli_query($conn, $sql_user);

if (!$result_user) {
    die("Database Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result_user) != 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result_user);

$fullName = $user['full_name'];
$firstName = explode(' ', $fullName)[0];


// ==============================
// SEND SOS
// ==============================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $booking_id = intval($_POST["booking_id"]);


    // Check whether this booking belongs to the current user

    $checkBooking = mysqli_query(
        $conn,
        "SELECT b.booking_id
         FROM booking b
         JOIN ride_request rr
             ON b.request_id = rr.request_id
         WHERE b.booking_id = $booking_id
         AND rr.user_id = $user_id"
    );


    if (!$checkBooking) {
        die("Database Error: " . mysqli_error($conn));
    }


    if (mysqli_num_rows($checkBooking) > 0) {


        // Check if an active SOS already exists

        $checkSOS = mysqli_query(
            $conn,
            "SELECT alert_id
             FROM sos_alert
             WHERE user_id = $user_id
             AND booking_id = $booking_id
             AND alert_status = 'Active'"
        );


        if (!$checkSOS) {
            die("Database Error: " . mysqli_error($conn));
        }


        if (mysqli_num_rows($checkSOS) == 0) {

            $insertSOS = mysqli_query(
                $conn,
                "INSERT INTO sos_alert
                (user_id, booking_id, alert_time, alert_status)
                VALUES
                ($user_id, $booking_id, NOW(), 'Active')"
            );


            if (!$insertSOS) {
                die("Database Error: " . mysqli_error($conn));
            }
        }
    }


    header("Location: sos.php");
    exit();
}


// ==============================
// GET ACTIVE RIDE
// ==============================

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

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

$ride = mysqli_fetch_assoc($result);


// ==============================
// CHECK ACTIVE SOS
// ==============================

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


    if (!$sosCheck) {
        die("Database Error: " . mysqli_error($conn));
    }


    if (mysqli_num_rows($sosCheck) > 0) {
        $active_sos = true;
    }
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusRide - SOS / Emergency</title>

    <link rel="stylesheet" href="sos.css">

</head>


<body>


<div class="dashboard">


    <!-- SHARED SIDEBAR -->

    <?php include __DIR__ . '/includes/sidebar.php'; ?>


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

                        <?= htmlspecialchars(
                            strtoupper(substr($firstName, 0, 1))
                        ) ?>

                    </div>


                    <span>
                        <?= htmlspecialchars($firstName) ?>
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
                                src="<?= htmlspecialchars($ride["profile_picture"]); ?>"
                                class="driver-picture"
                                alt="Driver"
                            >

                        <?php } else { ?>


                            <div class="driver-picture">

                                <?= htmlspecialchars(
                                    strtoupper(
                                        substr($ride["full_name"], 0, 1)
                                    )
                                ); ?>

                            </div>


                        <?php } ?>


                        <div class="driver-info">


                            <h3>

                                <?= htmlspecialchars(
                                    $ride["full_name"]
                                ); ?>

                            </h3>


                            <p>

                                <?= htmlspecialchars(
                                    $ride["vehicle_type"] ?? "No vehicle"
                                ); ?>

                                <?php if (!empty($ride["registration_number"])): ?>

                                    ·

                                    <?= htmlspecialchars(
                                        $ride["registration_number"]
                                    ); ?>

                                <?php endif; ?>

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

                                <?= htmlspecialchars(
                                    $ride["start_location"]
                                ); ?>

                            </span>

                        </div>


                        <div class="route-line"></div>


                        <div class="route-item">

                            <span class="circle"></span>

                            <span>

                                <?= htmlspecialchars(
                                    $ride["destination"]
                                ); ?>

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
                            value="<?= $ride["booking_id"]; ?>"
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


                <!-- CAMPUS SECURITY -->

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



                <!-- NATIONAL EMERGENCY -->

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



                <!-- PERSONAL CONTACT -->

                <div class="contact">


                    <div class="contact-left">


                        <div class="contact-icon blue">
                            ☎
                        </div>


                        <div>


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