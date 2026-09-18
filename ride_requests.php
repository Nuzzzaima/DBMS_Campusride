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
// ACCEPT OR REJECT REQUEST
// ==============================

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $request_id = intval($_POST["request_id"]);
    $action = $_POST["action"];


    // ==========================
    // ACCEPT REQUEST
    // ==========================

    if ($action == "accept") {


        // Get ride connected with this request

        $getRide = mysqli_query(
            $conn,
            "SELECT ride_id
             FROM ride_request
             WHERE request_id = $request_id
             AND request_status = 'Pending'"
        );


        if (!$getRide) {
            die("Database Error: " . mysqli_error($conn));
        }


        if ($row = mysqli_fetch_assoc($getRide)) {

            $ride_id = $row["ride_id"];


            // Make sure this ride belongs to the logged-in driver

            $ownerCheck = mysqli_query(
                $conn,
                "SELECT ride_id
                 FROM ride
                 WHERE ride_id = $ride_id
                 AND user_id = $user_id"
            );


            if (!$ownerCheck) {
                die("Database Error: " . mysqli_error($conn));
            }


            if (mysqli_num_rows($ownerCheck) > 0) {


                // Check available seats

                $seatCheck = mysqli_query(
                    $conn,
                    "SELECT available_seats
                     FROM ride
                     WHERE ride_id = $ride_id"
                );


                if (!$seatCheck) {
                    die("Database Error: " . mysqli_error($conn));
                }


                $seatRow = mysqli_fetch_assoc($seatCheck);


                if ($seatRow && $seatRow["available_seats"] > 0) {


                    // Accept request

                    $updateRequest = mysqli_query(
                        $conn,
                        "UPDATE ride_request
                         SET request_status = 'Accepted'
                         WHERE request_id = $request_id"
                    );


                    if (!$updateRequest) {
                        die("Database Error: " . mysqli_error($conn));
                    }


                    // Reduce available seats

                    $updateRide = mysqli_query(
                        $conn,
                        "UPDATE ride
                         SET available_seats = available_seats - 1
                         WHERE ride_id = $ride_id"
                    );


                    if (!$updateRide) {
                        die("Database Error: " . mysqli_error($conn));
                    }


                    // Create booking

                    $insertBooking = mysqli_query(
                        $conn,
                        "INSERT INTO booking
                        (request_id, booking_date, booking_status)
                        VALUES
                        ($request_id, CURDATE(), 'Confirmed')"
                    );


                    if (!$insertBooking) {
                        die("Database Error: " . mysqli_error($conn));
                    }
                }
            }
        }


    // ==========================
    // REJECT REQUEST
    // ==========================

    } elseif ($action == "reject") {


        // Make sure the request belongs to one of the
        // logged-in user's rides

        $ownerCheck = mysqli_query(
            $conn,
            "SELECT rr.request_id
             FROM ride_request rr
             JOIN ride r
                 ON rr.ride_id = r.ride_id
             WHERE rr.request_id = $request_id
             AND r.user_id = $user_id"
        );


        if (!$ownerCheck) {
            die("Database Error: " . mysqli_error($conn));
        }


        if (mysqli_num_rows($ownerCheck) > 0) {

            $updateRequest = mysqli_query(
                $conn,
                "UPDATE ride_request
                 SET request_status = 'Rejected'
                 WHERE request_id = $request_id"
            );


            if (!$updateRequest) {
                die("Database Error: " . mysqli_error($conn));
            }
        }
    }


    header("Location: ride_requests.php");
    exit();
}


// ==============================
// GET RIDE REQUESTS
// ==============================

$sql = "SELECT
            rr.request_id,
            rr.request_time,
            rr.request_status,
            u.full_name,
            u.student_id,
            u.profile_picture,
            r.ride_id,
            r.start_location,
            r.destination
        FROM ride_request rr
        JOIN ride r
            ON rr.ride_id = r.ride_id
        JOIN `USER` u
            ON rr.user_id = u.user_id
        WHERE r.user_id = $user_id
        ORDER BY rr.request_time DESC";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database Error: " . mysqli_error($conn));
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusRide - Ride Requests</title>

    <link rel="stylesheet" href="ride_requests.css">

</head>


<body>


<div class="dashboard">


    <!-- SHARED SIDEBAR -->

    <?php include __DIR__ . '/includes/sidebar.php'; ?>


    <!-- MAIN CONTENT -->

    <main class="main">


        <!-- HEADER -->

        <header class="header">


            <div>

                <h2>
                    Ride Requests
                </h2>

                <p>
                    Manage incoming requests for your rides
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



        <!-- REQUESTS -->

        <section class="content">


            <?php if (mysqli_num_rows($result) > 0) { ?>


                <?php while ($row = mysqli_fetch_assoc($result)) { ?>


                    <div class="request-card">


                        <div class="request-top">


                            <!-- REQUESTER -->

                            <div class="request-user">


                                <?php if (!empty($row["profile_picture"])) { ?>


                                    <img
                                        src="<?= htmlspecialchars($row["profile_picture"]); ?>"
                                        alt="Profile"
                                    >


                                <?php } else { ?>


                                    <div class="request-picture">


                                        <?= htmlspecialchars(
                                            strtoupper(
                                                substr($row["full_name"], 0, 1)
                                            )
                                        ); ?>


                                    </div>


                                <?php } ?>


                                <div>


                                    <h3>

                                        <?= htmlspecialchars(
                                            $row["full_name"]
                                        ); ?>

                                    </h3>


                                    <p>

                                        ID:

                                        <?= htmlspecialchars(
                                            $row["student_id"]
                                        ); ?>

                                    </p>


                                    <div class="stars">

                                        ★★★★★

                                    </div>


                                </div>


                            </div>



                            <!-- STATUS -->

                            <span class="status <?= strtolower(
                                $row["request_status"]
                            ); ?>">

                                <?= htmlspecialchars(
                                    strtolower(
                                        $row["request_status"]
                                    )
                                ); ?>

                            </span>


                        </div>



                        <!-- RIDE INFORMATION -->

                        <div class="ride-info">


                            <span>

                                Ride:

                                <b>

                                    <?= htmlspecialchars(
                                        $row["start_location"]
                                    ); ?>

                                    →

                                    <?= htmlspecialchars(
                                        $row["destination"]
                                    ); ?>

                                </b>

                            </span>


                            <span>

                                <?= date(
                                    "d M",
                                    strtotime($row["request_time"])
                                ); ?>

                            </span>


                        </div>



                        <!-- ACTION BUTTONS -->

                        <?php if ($row["request_status"] == "Pending") { ?>


                            <div class="buttons">


                                <!-- ACCEPT -->

                                <form method="POST">


                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= $row["request_id"]; ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="accept"
                                    >


                                    <button
                                        type="submit"
                                        class="accept"
                                    >

                                        ✓ &nbsp; Accept

                                    </button>


                                </form>



                                <!-- REJECT -->

                                <form method="POST">


                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?= $row["request_id"]; ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="action"
                                        value="reject"
                                    >


                                    <button
                                        type="submit"
                                        class="reject"
                                    >

                                        × &nbsp; Reject

                                    </button>


                                </form>


                            </div>


                        <?php } ?>


                    </div>


                <?php } ?>


            <?php } else { ?>


                <div class="empty">

                    No ride requests found.

                </div>


            <?php } ?>


        </section>


    </main>


</div>


</body>

</html>