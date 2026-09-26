<?php

session_start();

include __DIR__ . "/db.php";


/* =========================================================
   ADMIN SESSION
   ========================================================= */

if (!isset($_SESSION["admin_user_id"])) {

    header("Location: admin_login.php");
    exit;
}


$admin_id = (int) $_SESSION["admin_user_id"];

$admin_name = $_SESSION["admin_name"] ?? "Admin";

$admin_email = $_SESSION["admin_email"] ?? "";


/* =========================================================
   LOGOUT
   ========================================================= */

if (isset($_GET["logout"])) {

    session_unset();

    session_destroy();

    header("Location: admin_login.php");
    exit;
}


/* =========================================================
   MARK SOS ALERT AS RESOLVED
   ========================================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && isset($_POST["resolve_alert"])
) {

    $alert_id = (int) $_POST["resolve_alert"];


    mysqli_query(
        $conn,
        "UPDATE sos_alert
         SET alert_status = 'Resolved'
         WHERE alert_id = $alert_id"
    );


    $params = $_GET;

    unset($params["view"]);


    $url = "admin_sos.php";


    if (!empty($params)) {

        $url .= "?" . http_build_query($params);
    }


    header("Location: $url");
    exit;
}


/* =========================================================
   FILTERS
   ========================================================= */

$search = trim($_GET["search"] ?? "");

$status = trim($_GET["status"] ?? "Active");

$page = max(
    1,
    (int) ($_GET["page"] ?? 1)
);

$per_page = 8;


/* =========================================================
   ESCAPED FILTER VALUES
   ========================================================= */

$s = mysqli_real_escape_string(
    $conn,
    $search
);

$st = mysqli_real_escape_string(
    $conn,
    $status
);


/* =========================================================
   GET AVAILABLE SOS STATUSES
   ========================================================= */

$status_result = mysqli_query(
    $conn,
    "SELECT DISTINCT alert_status
     FROM sos_alert
     WHERE alert_status <> ''
     ORDER BY
        FIELD(alert_status, 'Active', 'Resolved'),
        alert_status"
);


/* =========================================================
   WHERE CONDITIONS
   ========================================================= */

$where = " WHERE 1 = 1 ";


/* SEARCH */

if ($search !== "") {

    $where .= "
        AND (
            CAST(sos_alert.alert_id AS CHAR) LIKE '%$s%'
            OR passenger.full_name LIKE '%$s%'
            OR passenger.student_id LIKE '%$s%'
            OR CAST(sos_alert.booking_id AS CHAR) LIKE '%$s%'
            OR CAST(ride.ride_id AS CHAR) LIKE '%$s%'
            OR ride.start_location LIKE '%$s%'
            OR ride.destination LIKE '%$s%'
            OR driver.full_name LIKE '%$s%'
        )
    ";
}


/* STATUS */

if (
    $status !== ""
    && $status !== "All Status"
) {

    $where .= "
        AND sos_alert.alert_status = '$st'
    ";
}


/* =========================================================
   COMMON TABLE JOINS
   ========================================================= */

$base = "
    FROM sos_alert

    JOIN `USER` AS passenger
        ON sos_alert.user_id = passenger.user_id

    JOIN booking
        ON sos_alert.booking_id = booking.booking_id

    JOIN ride_request
        ON booking.request_id = ride_request.request_id

    JOIN ride
        ON ride_request.ride_id = ride.ride_id

    JOIN `USER` AS driver
        ON ride.user_id = driver.user_id
";


/* =========================================================
   COUNT TOTAL RESULTS
   ========================================================= */

$count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     $base
     $where"
);


$total = 0;


if ($count_result) {

    $count_row = mysqli_fetch_assoc($count_result);

    $total = (int) $count_row["total"];
}


$total_pages = max(
    1,
    (int) ceil($total / $per_page)
);


if ($page > $total_pages) {

    $page = $total_pages;
}


$offset = ($page - 1) * $per_page;


/* =========================================================
   GET SOS ALERTS
   ========================================================= */

$sql = "
    SELECT
        sos_alert.alert_id,
        sos_alert.booking_id,
        sos_alert.alert_time,
        sos_alert.alert_status,

        passenger.full_name AS passenger_name,
        passenger.student_id,

        ride.ride_id,
        ride.start_location,
        ride.destination,
        ride.ride_date,
        ride.departure_time,

        driver.full_name AS driver_name

    $base

    $where

    ORDER BY
        CASE
            WHEN sos_alert.alert_status = 'Active'
            THEN 0
            ELSE 1
        END,

        sos_alert.alert_time DESC

    LIMIT $offset, $per_page
";


$result = mysqli_query(
    $conn,
    $sql
);


/* =========================================================
   ACTIVE SOS COUNT
   ========================================================= */

$active_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM sos_alert
     WHERE alert_status = 'Active'"
);


$active_count = 0;


if ($active_result) {

    $active_row = mysqli_fetch_assoc($active_result);

    $active_count = (int) $active_row["total"];
}


/* =========================================================
   VIEW DETAILS
   ========================================================= */

$view_id = (int) ($_GET["view"] ?? 0);

$detail = null;


if ($view_id > 0) {

    $detail_sql = "
        SELECT
            sos_alert.*,

            passenger.full_name AS passenger_name,
            passenger.student_id AS passenger_student_id,
            passenger.university_email AS passenger_email,
            passenger.phone AS passenger_phone,

            driver.full_name AS driver_name,

            ride.ride_id,
            ride.ride_date,
            ride.departure_time,
            ride.start_location,
            ride.destination,
            ride.ride_status,

            booking.booking_date,
            booking.booking_status,

            ride_request.request_id

        $base

        WHERE sos_alert.alert_id = $view_id

        LIMIT 1
    ";


    $detail_result = mysqli_query(
        $conn,
        $detail_sql
    );


    if ($detail_result) {

        $detail = mysqli_fetch_assoc(
            $detail_result
        );
    }
}


/* =========================================================
   HELPER FUNCTIONS
   ========================================================= */

function page_link($number)
{
    $params = $_GET;

    $params["page"] = $number;

    unset($params["view"]);

    return "admin_sos.php?" . http_build_query($params);
}


function status_class($status)
{
    if ($status === "Active") {

        return "active";
    }


    if ($status === "Resolved") {

        return "resolved";
    }


    return "other";
}


function alert_time($value)
{
    if (empty($value)) {

        return "—";
    }


    $timestamp = strtotime($value);


    if ($timestamp === false) {

        return htmlspecialchars($value);
    }


    return date(
        "M j, Y · g:i A",
        $timestamp
    );
}


function display_date($value)
{
    if (empty($value)) {

        return "—";
    }


    $timestamp = strtotime($value);


    if ($timestamp === false) {

        return htmlspecialchars($value);
    }


    return date(
        "M j, Y",
        $timestamp
    );
}


function display_time($value)
{
    if (empty($value)) {

        return "—";
    }


    $timestamp = strtotime($value);


    if ($timestamp === false) {

        return htmlspecialchars($value);
    }


    return date(
        "g:i A",
        $timestamp
    );
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SOS Alerts - CampusRide</title>

    <link
        rel="stylesheet"
        href="admin_sos.css"
    >

</head>


<body>

<div class="dashboard">


    <!-- =====================================================
         SIDEBAR
         ===================================================== -->

    <aside class="sidebar">


        <!-- LOGO -->

        <div class="logo">


            <div>

                <b>CampusRide</b>

                <small>
                    Operations Console
                </small>

            </div>

        </div>


        <!-- MENU -->

        <nav class="menu">

            <a href="admin_dashboard.php">
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

            <a
                href="admin_sos.php"
                class="active"
            >
                SOS Alerts
            </a>

            <a href="#">
                Settings
            </a>

        </nav>


        <!-- ADMIN PROFILE -->

        <div class="sidebar-bottom">

            <div class="admin-profile">


                <div class="admin-pic">

                    <?php

                    echo htmlspecialchars(
                        strtoupper(
                            substr(
                                $admin_name,
                                0,
                                1
                            )
                        )
                    );

                    ?>

                </div>


                <div class="admin-info">

                    <b>

                        <?php

                        echo htmlspecialchars(
                            $admin_name
                        );

                        ?>

                    </b>


                    <small>

                        <?php

                        echo htmlspecialchars(
                            $admin_email
                        );

                        ?>

                    </small>

                </div>


                <a
                    href="admin_sos.php?logout=1"
                    class="logout"
                >
                    ↪
                </a>

            </div>

        </div>

    </aside>


    <!-- =====================================================
         MAIN CONTENT
         ===================================================== -->

    <main class="main">


        <!-- HEADER -->

        <header class="top-header">


            <div class="desk-name">

                ● &nbsp; CAMPUS TRANSIT OPERATIONS DESK

            </div>


            <div class="top-right">


                <span class="db-status">

                    <i></i>

                    Connected to DB

                </span>


                <span class="bell">
                    ♧
                </span>


                <span class="top-user">

                    ● &nbsp;

                    <?php

                    echo htmlspecialchars(
                        $admin_name
                    );

                    ?>

                </span>

            </div>

        </header>


        <!-- =================================================
             PAGE CONTENT
             ================================================= -->

        <section class="content">


            <?php if ($view_id > 0 && $detail): ?>


                <!-- =================================================
                     SOS DETAIL PAGE
                     ================================================= -->

                <div class="page-heading">


                    <div>

                        <h1>
                            SOS Alert Details
                        </h1>


                        <p>

                            Complete emergency
                            information for Alert #

                            <?php

                            echo (int)
                                $detail["alert_id"];

                            ?>

                        </p>

                    </div>


                    <a
                        href="admin_sos.php"
                        class="back-btn"
                    >
                        ← Back to SOS Alerts
                    </a>

                </div>


                <!-- DETAIL GRID -->

                <div class="detail-grid">


                    <!-- ALERT INFORMATION -->

                    <div class="detail-card">


                        <div class="card-title">

                            <span>
                                Alert Information
                            </span>


                            <span
                                class="status
                                <?php
                                echo status_class(
                                    $detail["alert_status"]
                                );
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $detail["alert_status"]
                                );

                                ?>

                            </span>

                        </div>


                        <div class="detail-row">

                            <span>
                                Alert ID
                            </span>


                            <b>

                                #

                                <?php

                                echo (int)
                                    $detail["alert_id"];

                                ?>

                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Triggered By
                            </span>


                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $detail["passenger_name"]
                                );

                                ?>

                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Alert Time
                            </span>


                            <b>

                                <?php

                                echo alert_time(
                                    $detail["alert_time"]
                                );

                                ?>

                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Booking
                            </span>


                            <b>

                                #

                                <?php

                                echo (int)
                                    $detail["booking_id"];

                                ?>

                            </b>

                        </div>

                    </div>


                    <!-- PASSENGER -->

                    <div class="detail-card">


                        <div class="card-title">

                            <span>
                                Passenger
                            </span>

                        </div>


                        <div class="detail-row">

                            <span>
                                Student ID
                            </span>


                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $detail[
                                        "passenger_student_id"
                                    ]
                                );

                                ?>

                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Email
                            </span>


                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $detail[
                                        "passenger_email"
                                    ]
                                );

                                ?>

                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Phone
                            </span>


                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $detail[
                                        "passenger_phone"
                                    ]
                                );

                                ?>

                            </b>

                        </div>

                    </div>


                    <!-- ASSOCIATED RIDE -->

                    <div class="detail-card wide">


                        <div class="card-title">

                            <span>
                                Associated Ride
                            </span>


                            <b>

                                Ride #

                                <?php

                                echo (int)
                                    $detail["ride_id"];

                                ?>

                            </b>

                        </div>


                        <div class="route">


                            <div>

                                <small>
                                    From
                                </small>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $detail[
                                            "start_location"
                                        ]
                                    );

                                    ?>

                                </strong>

                            </div>


                            <span>
                                →
                            </span>


                            <div>

                                <small>
                                    To
                                </small>


                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $detail[
                                            "destination"
                                        ]
                                    );

                                    ?>

                                </strong>

                            </div>

                        </div>


                        <div class="meta">


                            <span>

                                Date:

                                <?php

                                echo display_date(
                                    $detail["ride_date"]
                                );

                                ?>

                            </span>


                            <span>

                                Time:

                                <?php

                                echo display_time(
                                    $detail["departure_time"]
                                );

                                ?>

                            </span>


                            <span>

                                Driver:

                                <?php

                                echo htmlspecialchars(
                                    $detail["driver_name"]
                                );

                                ?>

                            </span>


                            <span>

                                Ride Status:

                                <?php

                                echo htmlspecialchars(
                                    $detail["ride_status"]
                                );

                                ?>

                            </span>

                        </div>

                    </div>


                    <!-- BOOKING -->

                    <div class="detail-card">


                        <div class="card-title">

                            <span>
                                Booking
                            </span>

                        </div>


                        <div class="detail-row">

                            <span>
                                Booking ID
                            </span>


                            <b>

                                #

                                <?php

                                echo (int)
                                    $detail[
                                        "booking_id"
                                    ];

                                ?>

                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Booking Date
                            </span>


                            <b>

                                <?php

                                echo display_date(
                                    $detail[
                                        "booking_date"
                                    ]
                                );

                                ?>

                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Status
                            </span>


                            <b>

                                <?php

                                echo htmlspecialchars(
                                    $detail[
                                        "booking_status"
                                    ]
                                );

                                ?>

                            </b>

                        </div>

                    </div>


                    <!-- ADMIN ACTION -->

                    <div class="detail-card">


                        <div class="card-title">

                            <span>
                                Admin Action
                            </span>

                        </div>


                        <?php if (
                            $detail["alert_status"]
                            === "Active"
                        ): ?>


                            <p class="action-text">

                                This SOS alert is
                                currently active
                                and requires
                                attention.

                            </p>


                            <form method="POST">

                                <button
                                    type="submit"
                                    class="resolve-large"
                                    name="resolve_alert"
                                    value="<?php
                                    echo (int)
                                        $detail["alert_id"];
                                    ?>"
                                >

                                    ✓ Mark Resolved

                                </button>

                            </form>


                        <?php else: ?>


                            <p class="resolved-message">

                                ✓ This SOS alert
                                has been resolved.

                            </p>


                        <?php endif; ?>


                    </div>


                </div>


            <?php else: ?>


                <!-- =================================================
                     SOS LIST PAGE
                     ================================================= -->

                <div class="page-heading">


                    <div>

                        <h1>
                            SOS Alerts
                        </h1>


                        <p>
                            Monitor and manage
                            CampusRide emergency
                            alerts.
                        </p>

                    </div>


                    <div class="active-summary">

                        ● &nbsp;

                        <?php

                        echo number_format(
                            $active_count
                        );

                        ?>

                        Active

                    </div>

                </div>


                <!-- STATISTICS -->

                <div class="stats-row">


                    <div class="mini-card">

                        <span>
                            Active Alerts
                        </span>


                        <strong>

                            <?php

                            echo number_format(
                                $active_count
                            );

                            ?>

                        </strong>


                        <small>
                            Require attention
                        </small>

                    </div>


                    <div class="mini-card">

                        <span>
                            Showing
                        </span>


                        <strong>

                            <?php

                            echo number_format(
                                $total
                            );

                            ?>

                        </strong>


                        <small>
                            Alerts matching filters
                        </small>

                    </div>

                </div>


                <!-- FILTER -->

                <div class="filter-card">


                    <form
                        method="GET"
                        class="filter-form"
                    >


                        <div class="field search">

                            <label>
                                Search
                            </label>


                            <input
                                type="text"
                                name="search"
                                value="<?php
                                echo htmlspecialchars(
                                    $search
                                );
                                ?>"
                                placeholder="Alert ID, student, ride, booking, location..."
                            >

                        </div>


                        <div class="field">

                            <label>
                                Status
                            </label>


                            <select name="status">


                                <option
                                    value="All Status"
                                    <?php

                                    echo (
                                        $status
                                        === "All Status"
                                    )
                                        ? "selected"
                                        : "";

                                    ?>
                                >
                                    All Status
                                </option>


                                <?php if ($status_result): ?>


                                    <?php while (
                                        $x = mysqli_fetch_assoc(
                                            $status_result
                                        )
                                    ): ?>


                                        <option
                                            value="<?php
                                            echo htmlspecialchars(
                                                $x[
                                                    "alert_status"
                                                ]
                                            );
                                            ?>"
                                            <?php

                                            echo (
                                                $status
                                                === $x[
                                                    "alert_status"
                                                ]
                                            )
                                                ? "selected"
                                                : "";

                                            ?>
                                        >

                                            <?php

                                            echo htmlspecialchars(
                                                $x[
                                                    "alert_status"
                                                ]
                                            );

                                            ?>

                                        </option>


                                    <?php endwhile; ?>


                                <?php endif; ?>


                            </select>

                        </div>


                        <button
                            type="submit"
                            class="filter-btn"
                        >
                            Apply Filter
                        </button>


                        <a
                            href="admin_sos.php"
                            class="clear-btn"
                        >
                            Clear
                        </a>


                    </form>

                </div>


                <!-- ALERT LIST -->

                <div class="alert-list">


                    <?php if (
                        $result
                        && mysqli_num_rows($result) > 0
                    ): ?>


                        <?php while (
                            $a = mysqli_fetch_assoc(
                                $result
                            )
                        ): ?>


                            <div
                                class="alert-card
                                <?php

                                echo (
                                    $a["alert_status"]
                                    === "Active"
                                )
                                    ? "is-active"
                                    : "";

                                ?>"
                            >


                                <!-- ALERT MAIN -->

                                <div class="alert-main">


                                    <div class="alert-icon">
                                        ⚠
                                    </div>


                                    <div class="alert-content">


                                        <div class="alert-top">


                                            <div>

                                                <h2>

                                                    Alert #

                                                    <?php

                                                    echo (int)
                                                        $a[
                                                            "alert_id"
                                                        ];

                                                    ?>

                                                </h2>


                                                <span
                                                    class="alert-status
                                                    <?php

                                                    echo status_class(
                                                        $a[
                                                            "alert_status"
                                                        ]
                                                    );

                                                    ?>"
                                                >

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $a[
                                                            "alert_status"
                                                        ]
                                                    );

                                                    ?>

                                                </span>

                                            </div>


                                            <span class="alert-time">

                                                <?php

                                                echo alert_time(
                                                    $a[
                                                        "alert_time"
                                                    ]
                                                );

                                                ?>

                                            </span>

                                        </div>


                                        <!-- ALERT INFORMATION -->

                                        <div class="info-grid">


                                            <div>

                                                <small>
                                                    Triggered By
                                                </small>


                                                <b>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $a[
                                                            "passenger_name"
                                                        ]
                                                    );

                                                    ?>

                                                </b>


                                                <span>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $a[
                                                            "student_id"
                                                        ]
                                                    );

                                                    ?>

                                                </span>

                                            </div>


                                            <div>

                                                <small>
                                                    Associated Ride
                                                </small>


                                                <b>

                                                    Ride #

                                                    <?php

                                                    echo (int)
                                                        $a[
                                                            "ride_id"
                                                        ];

                                                    ?>

                                                </b>


                                                <span>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $a[
                                                            "start_location"
                                                        ]
                                                    );

                                                    ?>

                                                    →

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $a[
                                                            "destination"
                                                        ]
                                                    );

                                                    ?>

                                                </span>

                                            </div>


                                            <div>

                                                <small>
                                                    Driver
                                                </small>


                                                <b>

                                                    <?php

                                                    echo htmlspecialchars(
                                                        $a[
                                                            "driver_name"
                                                        ]
                                                    );

                                                    ?>

                                                </b>


                                                <span>

                                                    Booking #

                                                    <?php

                                                    echo (int)
                                                        $a[
                                                            "booking_id"
                                                        ];

                                                    ?>

                                                </span>

                                            </div>


                                        </div>

                                    </div>

                                </div>


                                <!-- ACTIONS -->

                                <div class="actions">


                                    <a
                                        href="admin_sos.php?view=<?php
                                        echo (int)
                                            $a["alert_id"];
                                        ?>"
                                        class="view-btn"
                                    >
                                        View Details
                                    </a>


                                    <?php if (
                                        $a["alert_status"]
                                        === "Active"
                                    ): ?>


                                        <form method="POST">

                                            <button
                                                type="submit"
                                                class="resolve-btn"
                                                name="resolve_alert"
                                                value="<?php
                                                echo (int)
                                                    $a[
                                                        "alert_id"
                                                    ];
                                                ?>"
                                            >
                                                ✓ Mark Resolved
                                            </button>

                                        </form>


                                    <?php else: ?>


                                        <span class="resolved-label">
                                            ✓ Resolved
                                        </span>


                                    <?php endif; ?>


                                </div>


                            </div>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- EMPTY STATE -->

                        <div class="empty-state">


                            <div class="empty-icon">
                                ✓
                            </div>


                            <h2>
                                No SOS alerts found
                            </h2>


                            <p>
                                No emergency alerts
                                match the current
                                filters.
                            </p>


                        </div>


                    <?php endif; ?>


                </div>


                <!-- PAGINATION -->

                <?php if ($total_pages > 1): ?>


                    <div class="pagination">


                        <?php if ($page > 1): ?>


                            <a
                                href="<?php
                                echo htmlspecialchars(
                                    page_link(
                                        $page - 1
                                    )
                                );
                                ?>"
                            >
                                ← Previous
                            </a>


                        <?php endif; ?>


                        <span>

                            Page

                            <?php
                            echo $page;
                            ?>

                            of

                            <?php
                            echo $total_pages;
                            ?>

                        </span>


                        <?php if (
                            $page < $total_pages
                        ): ?>


                            <a
                                href="<?php
                                echo htmlspecialchars(
                                    page_link(
                                        $page + 1
                                    )
                                );
                                ?>"
                            >
                                Next →
                            </a>


                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>

</html>