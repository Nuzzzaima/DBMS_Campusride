<?php

session_start();

include "db.php";

$user_id = 1;

/* ACCEPT OR REJECT REQUEST */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $request_id = intval($_POST["request_id"]);
    $action = $_POST["action"];

    if ($action == "accept") {

        /* Get ride connected with this request */

        $getRide = mysqli_query(
            $conn,
            "SELECT ride_id
             FROM ride_request
             WHERE request_id = $request_id
             AND request_status = 'Pending'"
        );

        if ($row = mysqli_fetch_assoc($getRide)) {

            $ride_id = $row["ride_id"];

            /* Check available seats */

            $seatCheck = mysqli_query(
                $conn,
                "SELECT available_seats
                 FROM ride
                 WHERE ride_id = $ride_id"
            );

            $seatRow = mysqli_fetch_assoc($seatCheck);

            if ($seatRow && $seatRow["available_seats"] > 0) {

                mysqli_query(
                    $conn,
                    "UPDATE ride_request
                     SET request_status = 'Accepted'
                     WHERE request_id = $request_id"
                );

                mysqli_query(
                    $conn,
                    "UPDATE ride
                     SET available_seats = available_seats - 1
                     WHERE ride_id = $ride_id"
                );

                mysqli_query(
                    $conn,
                    "INSERT INTO booking
                    (request_id, booking_date, booking_status)
                    VALUES
                    ($request_id, CURDATE(), 'Confirmed')"
                );
            }
        }

    } elseif ($action == "reject") {

        mysqli_query(
            $conn,
            "UPDATE ride_request
             SET request_status = 'Rejected'
             WHERE request_id = $request_id"
        );
    }

    header("Location: ride_requests.php");
    exit();
}


/* GET RIDE REQUESTS */

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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>CampusRide - Ride Requests</title>

    <link rel="stylesheet" href="ride_requests.css">

</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">🚗</div>

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

            <a href="ride_requests.php" class="active">
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



    <!-- MAIN CONTENT -->

    <main class="main">

        <!-- HEADER -->

        <header class="header">

            <div>

                <h2>Ride Requests</h2>

                <p>Manage incoming requests for your rides</p>

            </div>


            <div class="header-right">

                <div class="notification">
                    ♧
                </div>

                <div class="user">

                    <div class="small-pic">
                        N
                    </div>

                    <span>Nadia</span>

                </div>

            </div>

        </header>



        <!-- REQUESTS -->

        <section class="content">

            <?php if (mysqli_num_rows($result) > 0) { ?>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                    <div class="request-card">

                        <div class="request-top">

                            <div class="request-user">

                                <?php if (!empty($row["profile_picture"])) { ?>

                                    <img
                                        src="<?php echo htmlspecialchars($row["profile_picture"]); ?>"
                                        alt="Profile"
                                    >

                                <?php } else { ?>

                                    <div class="request-picture">

                                        <?php
                                        echo strtoupper(
                                            substr($row["full_name"], 0, 1)
                                        );
                                        ?>

                                    </div>

                                <?php } ?>


                                <div>

                                    <h3>
                                        <?php
                                        echo htmlspecialchars(
                                            $row["full_name"]
                                        );
                                        ?>
                                    </h3>

                                    <p>
                                        ID:
                                        <?php
                                        echo htmlspecialchars(
                                            $row["student_id"]
                                        );
                                        ?>
                                    </p>

                                    <div class="stars">
                                        ★★★★★
                                    </div>

                                </div>

                            </div>


                            <span class="status <?php echo strtolower($row["request_status"]); ?>">

                                <?php
                                echo strtolower(
                                    $row["request_status"]
                                );
                                ?>

                            </span>

                        </div>



                        <div class="ride-info">

                            <span>

                                Ride:

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $row["start_location"]
                                    );
                                    ?>

                                    →

                                    <?php
                                    echo htmlspecialchars(
                                        $row["destination"]
                                    );
                                    ?>

                                </b>

                            </span>


                            <span>

                                <?php
                                echo date(
                                    "d M",
                                    strtotime($row["request_time"])
                                );
                                ?>

                            </span>

                        </div>



                        <?php if ($row["request_status"] == "Pending") { ?>

                            <div class="buttons">

                                <!-- ACCEPT -->

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="request_id"
                                        value="<?php echo $row["request_id"]; ?>"
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
                                        value="<?php echo $row["request_id"]; ?>"
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