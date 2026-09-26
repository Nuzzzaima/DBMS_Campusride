<?php
session_start();

include "db.php";

if (!isset($_SESSION["admin_user_id"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin_name"] ?? "Admin";
$admin_email = $_SESSION["admin_email"] ?? "";


/* RESOLVE SOS ALERT */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["resolve_alert"])) {

    $alert_id = (int)$_POST["resolve_alert"];

    mysqli_query(
        $conn,
        "UPDATE sos_alert
         SET alert_status = 'Resolved'
         WHERE alert_id = $alert_id"
    );

    header("Location: admin_dashboard.php");
    exit();
}


/* SORT */
$sort = $_GET["sort"] ?? "departure";

if ($sort == "fare") {
    $order_by = "ride.fare ASC";
    $sort_text = "Fare (Lowest)";
} elseif ($sort == "seats") {
    $order_by = "ride.available_seats DESC";
    $sort_text = "Seats Available";
} else {
    $order_by = "ride.ride_date ASC, ride.departure_time ASC";
    $sort_text = "Departure Time (Earliest)";
}


/* TOTAL USERS */
$user_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM `USER`"
);

$total_users = mysqli_fetch_assoc($user_result)["total"];


/* TOTAL RIDES */
$ride_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total FROM ride"
);

$total_rides = mysqli_fetch_assoc($ride_result)["total"];


/* ACTIVE RIDES */
$active_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ride
     WHERE ride_status = 'Active'
     AND available_seats > 0"
);

$active_rides = mysqli_fetch_assoc($active_result)["total"];


/* PENDING REQUESTS */
$pending_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ride_request
     WHERE request_status = 'Pending'"
);

$pending_requests = mysqli_fetch_assoc($pending_result)["total"];


/* ACTIVE ROUTES */
$route_sql = "SELECT
                ride.ride_id,
                ride.user_id,
                ride.ride_date,
                ride.departure_time,
                ride.start_location,
                ride.destination,
                ride.ride_type,
                ride.available_seats,
                ride.fare,
                ride.women_only,
                `USER`.full_name,
                `USER`.profile_picture,
                vehicle.vehicle_type
              FROM ride
              JOIN `USER`
                ON ride.user_id = `USER`.user_id
              LEFT JOIN vehicle
                ON ride.vehicle_id = vehicle.vehicle_id
              WHERE ride.ride_status = 'Active'
              AND ride.available_seats > 0
              ORDER BY $order_by";

$route_result = mysqli_query($conn, $route_sql);


/* RATINGS */
$rating_sql = "SELECT
                reviewee_id,
                AVG(stars) AS average_stars,
                COUNT(*) AS rating_count
               FROM rating
               GROUP BY reviewee_id";

$rating_result = mysqli_query($conn, $rating_sql);

$ratings = [];

while ($rating = mysqli_fetch_assoc($rating_result)) {
    $ratings[$rating["reviewee_id"]] = [
        "average" => $rating["average_stars"],
        "count" => $rating["rating_count"]
    ];
}


/* PENDING ISSUES FROM SOS */
$issue_sql = "SELECT
                sos_alert.alert_id,
                sos_alert.alert_time,
                sos_alert.alert_status,
                `USER`.full_name,
                booking.booking_id
              FROM sos_alert
              JOIN `USER`
                ON sos_alert.user_id = `USER`.user_id
              JOIN booking
                ON sos_alert.booking_id = booking.booking_id
              WHERE sos_alert.alert_status = 'Active'
              ORDER BY sos_alert.alert_time DESC";

$issue_result = mysqli_query($conn, $issue_sql);


/* RECENT REGISTRATIONS */
$recent_sql = "SELECT
                user_id,
                student_id,
                full_name,
                university_email,
                department,
                created_at
               FROM `USER`
               ORDER BY created_at DESC
               LIMIT 5";

$recent_result = mysqli_query($conn, $recent_sql);


/* LOGOUT */
if (isset($_GET["logout"])) {

    session_unset();
    session_destroy();

    header("Location: admin_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CampusRide</title>
    <link rel="stylesheet" href="admin_dashboard.css">
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->

    <div class="sidebar">

        <div class="logo">

            <div>
                <b>CampusRide</b>
                <small>Operations Console</small>
            </div>

        </div>


        <div class="menu">

            <a href="admin_dashboard.php" class="active">
                Dashboard
            </a>

            <a href="admin_users.php">
                Users Management
            </a>

            <a href="admin_vehicle_fleet.php">
                Vehicles Fleet
            </a>

            <a href="admin_rides.php">
                All Rides
            </a>

            <a href="admin_requests.php">
                Requests
            </a>

            <a href="admin_bookings.php">
                Bookings
            </a>
            <a href="admin_sos.php">SOS Alerts</a>

            <a href="#">
                Settings
            </a>

        </div>


        <div class="sidebar-bottom">

            <div class="admin-profile">

                <div class="admin-pic">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>

                <div class="admin-info">
                    <b><?php echo htmlspecialchars($admin_name); ?></b>
                    <small><?php echo htmlspecialchars($admin_email); ?></small>
                </div>

                <a href="admin_dashboard.php?logout=1" class="logout">
                    ↪
                </a>

            </div>

        </div>

    </div>


    <!-- MAIN -->

    <div class="main">

        <!-- TOP HEADER -->

        <div class="top-header">

            <div class="desk-name">
                ● &nbsp; CAMPUS TRANSIT OPERATIONS DESK
            </div>

            <div class="top-right">

                <span class="db-status">
                    <i></i> Connected to DB
                </span>

                <span class="bell">
                    ♧
                </span>

                <span class="top-user">
                    ● &nbsp; UIU Ops Team
                </span>

            </div>

        </div>


        <div class="content">

            <!-- PAGE HEADER -->

            <div class="page-header">

                <div>
                    <h1>
                        Admin Dashboard
                        <span class="live">Live Telemetry</span>
                    </h1>

                    <p>
                        Real-time telemetry, commuter logistics, and fleet operations across Dhaka university nodes
                    </p>
                </div>

                <div class="page-actions">

                    <span class="db-version">
                        ▣ &nbsp; Live DB Connected
                    </span>

                    <span class="today">
                        □ &nbsp; Today: <?php echo date("h:i A"); ?>
                    </span>

                    <a href="admin_dashboard.php" class="sync-btn">
                        ↻ &nbsp; Sync Node
                    </a>

                </div>

            </div>


            <!-- STAT CARDS -->

            <div class="stats">

                <div class="stat-card">

                    <div class="stat-top">
                        <span>Total Users</span>
                        <b>♙</b>
                    </div>

                    <strong><?php echo number_format($total_users); ?></strong>

                    <small>Active verified UIU students</small>

                </div>


                <div class="stat-card">

                    <div class="stat-top">
                        <span>Total Rides</span>
                        <b>▣</b>
                    </div>

                    <strong><?php echo number_format($total_rides); ?></strong>

                    <small>Fulfilled campus transit orders</small>

                </div>


                <div class="stat-card">

                    <div class="stat-top">
                        <span>Active Rides</span>
                        <b>△</b>
                    </div>

                    <strong>
                        <?php echo number_format($active_rides); ?>
                        <em>●</em>
                    </strong>

                    <small>Active rides with available seats</small>

                </div>


                <div class="stat-card">

                    <div class="stat-top">
                        <span>Pending Requests</span>
                        <b class="pink">▣</b>
                    </div>

                    <strong><?php echo number_format($pending_requests); ?></strong>

                    <small>Ride requests waiting for action</small>

                </div>

            </div>


            <!-- LOWER AREA -->

            <div class="lower-grid">

                <!-- ACTIVE ROUTES -->

                <div class="routes-section">

                    <div class="section-title">

                        <h2>
                            Active Route Streams
                            <span class="question">?</span>
                        </h2>

                        <form method="GET" action="admin_dashboard.php">

                            <label>Sort by:</label>

                            <select name="sort" onchange="this.form.submit()">

                                <option value="departure"
                                    <?php if ($sort == "departure") echo "selected"; ?>>
                                    Departure Time (Earliest)
                                </option>

                                <option value="fare"
                                    <?php if ($sort == "fare") echo "selected"; ?>>
                                    Fare (Lowest)
                                </option>

                                <option value="seats"
                                    <?php if ($sort == "seats") echo "selected"; ?>>
                                    Seats Available
                                </option>

                            </select>

                        </form>

                    </div>


                    <div class="route-list">

                        <?php

                        if (mysqli_num_rows($route_result) > 0) {

                            while ($row = mysqli_fetch_assoc($route_result)) {

                                $user_id = $row["user_id"];

                                $average = "No rating";
                                $rating_count = 0;

                                if (isset($ratings[$user_id])) {
                                    $average = number_format(
                                        $ratings[$user_id]["average"],
                                        1
                                    );

                                    $rating_count = $ratings[$user_id]["count"];
                                }

                        ?>

                            <div class="route-card">

                                <div class="route-top">

                                    <div class="driver">

                                        <div class="driver-pic">
                                            <?php
                                            echo strtoupper(
                                                substr($row["full_name"], 0, 1)
                                            );
                                            ?>
                                        </div>

                                        <div class="driver-info">

                                            <b>
                                                <?php
                                                echo htmlspecialchars(
                                                    $row["full_name"]
                                                );
                                                ?>
                                            </b>

                                            <div class="rating">
                                                ★
                                                <?php echo $average; ?>

                                                <?php
                                                if ($rating_count > 0) {
                                                    echo " (" . $rating_count . ")";
                                                }
                                                ?>
                                            </div>

                                        </div>

                                    </div>


                                    <div class="ride-labels">

                                        <span class="ride-type">
                                            <?php
                                            echo htmlspecialchars(
                                                $row["vehicle_type"] ??
                                                $row["ride_type"]
                                            );
                                            ?>
                                        </span>

                                        <?php
                                        if ($row["women_only"] == "Yes") {
                                        ?>

                                            <span class="women-label">
                                                Women Only
                                            </span>

                                        <?php
                                        }
                                        ?>

                                    </div>

                                </div>


                                <div class="route-path">

                                    <div class="location">
                                        <span class="outline-dot"></span>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["start_location"]
                                        );
                                        ?>
                                    </div>

                                    <div class="location">
                                        <span class="filled-dot"></span>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["destination"]
                                        );
                                        ?>
                                    </div>

                                </div>


                                <div class="route-bottom">

                                    <span>
                                        ◷ <?php
                                        echo date(
                                            "M j · g:i A",
                                            strtotime(
                                                $row["ride_date"] . " " .
                                                $row["departure_time"]
                                            )
                                        );
                                        ?>
                                    </span>

                                    <span>
                                        ♙
                                        <?php
                                        echo number_format(
                                            $row["available_seats"]
                                        );
                                        ?>
                                        seats left
                                    </span>

                                    <strong>
                                        ৳<?php
                                        echo number_format(
                                            $row["fare"],
                                            2
                                        );
                                        ?>
                                        <small>/person</small>
                                    </strong>

                                </div>


                                <div class="route-buttons">

                                    <a
                                        href="ride_details.php?id=<?php echo $row["ride_id"]; ?>"
                                        class="audit-btn"
                                    >
                                        Audit Telemetry
                                    </a>

                                    <a
                                        href="ride_details.php?id=<?php echo $row["ride_id"]; ?>"
                                        class="dispatch-btn"
                                    >
                                        Manage Dispatch
                                    </a>

                                </div>

                            </div>

                        <?php

                            }

                        } else {

                        ?>

                            <div class="empty">
                                No active rides found.
                            </div>

                        <?php
                        }
                        ?>

                    </div>

                </div>


                <!-- RIGHT SIDE -->

                <div class="right-column">


                    <!-- PENDING ISSUES -->

                    <div class="side-card">

                        <div class="side-title">

                            <h2>
                                ⚠ &nbsp; Pending Issues
                            </h2>

                            <span class="urgent">
                                <?php
                                echo mysqli_num_rows($issue_result);
                                ?>
                                Active
                            </span>

                        </div>


                        <?php

                        if (mysqli_num_rows($issue_result) > 0) {

                            while ($issue = mysqli_fetch_assoc($issue_result)) {

                        ?>

                            <div class="issue">

                                <div class="issue-name">
                                    SOS Alert
                                </div>

                                <p>
                                    Alert #
                                    <?php
                                    echo number_format(
                                        $issue["alert_id"]
                                    );
                                    ?>
                                    reported by
                                    <?php
                                    echo htmlspecialchars(
                                        $issue["full_name"]
                                    );
                                    ?>
                                </p>

                                <small>
                                    Booking #
                                    <?php
                                    echo number_format(
                                        $issue["booking_id"]
                                    );
                                    ?>
                                    ·
                                    <?php
                                    echo date(
                                        "M j, g:i A",
                                        strtotime(
                                            $issue["alert_time"]
                                        )
                                    );
                                    ?>
                                </small>

                                <div class="issue-actions">

                                    <a
                                        href="sos.php"
                                        class="review"
                                    >
                                        Review
                                    </a>

                                    <form method="POST" action="admin_dashboard.php">
                                        <input
                                            type="hidden"
                                            name="resolve_alert"
                                            value="<?php echo $issue["alert_id"]; ?>"
                                        >
                                        <button type="submit" class="resolve">
                                            Resolve
                                        </button>
                                    </form>

                                </div>

                            </div>

                        <?php

                            }

                        } else {

                        ?>

                            <div class="no-issue">
                                No active SOS alerts.
                            </div>

                        <?php
                        }
                        ?>


                        <a href="sos.php" class="view-all">
                            View All Alerts
                        </a>

                    </div>


                    <!-- RECENT REGISTRATIONS -->

                    <div class="side-card recent">

                        <div class="side-title">

                            <h2>
                                ♙ &nbsp; Recent Registrations
                            </h2>

                            <span class="view-link">
                                View All
                            </span>

                        </div>


                        <?php

                        while ($recent = mysqli_fetch_assoc($recent_result)) {

                            $initials = strtoupper(
                                substr($recent["full_name"], 0, 2)
                            );

                        ?>

                            <div class="registration">

                                <div class="reg-pic">
                                    <?php echo htmlspecialchars($initials); ?>
                                </div>

                                <div class="reg-info">

                                    <b>
                                        <?php
                                        echo htmlspecialchars(
                                            $recent["full_name"]
                                        );
                                        ?>
                                    </b>

                                    <small>
                                        <?php
                                        echo htmlspecialchars(
                                            $recent["student_id"]
                                        );
                                        ?>
                                        ·
                                        <?php
                                        echo htmlspecialchars(
                                            $recent["department"]
                                        );
                                        ?>
                                    </small>

                                </div>

                                <span class="approved">
                                    Registered
                                </span>

                            </div>

                        <?php
                        }
                        ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
