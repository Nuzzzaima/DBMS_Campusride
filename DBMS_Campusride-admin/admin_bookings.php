<?php
session_start();

include __DIR__ . "/db.php";


/* ADMIN SESSION */

if (!isset($_SESSION["admin_user_id"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_id = (int)$_SESSION["admin_user_id"];


/* ADMIN INFORMATION */

$admin_sql = "
    SELECT full_name, university_email
    FROM `USER`
    WHERE user_id = $admin_id
    LIMIT 1
";

$admin_result = mysqli_query($conn, $admin_sql);

if (!$admin_result || mysqli_num_rows($admin_result) === 0) {
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

$admin = mysqli_fetch_assoc($admin_result);

$admin_name = $admin["full_name"];
$admin_email = $admin["university_email"];


/* LOGOUT */

if (isset($_GET["logout"])) {

    session_unset();
    session_destroy();

    header("Location: admin_login.php");
    exit();
}


/* FILTERS */

$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");

$page = max(1, (int)($_GET["page"] ?? 1));

$view_id = (int)($_GET["view"] ?? 0);

$per_page = 8;

$search_safe = mysqli_real_escape_string($conn, $search);
$status_safe = mysqli_real_escape_string($conn, $status_filter);


/* BOOKING STATUS OPTIONS */

$status_sql = "
    SELECT DISTINCT booking_status
    FROM booking
    WHERE booking_status IS NOT NULL
    AND booking_status <> ''
    ORDER BY CASE booking_status
        WHEN 'Confirmed' THEN 1
        WHEN 'Completed' THEN 2
        WHEN 'Cancelled' THEN 3
        ELSE 4
    END,
    booking_status ASC
";

$status_result = mysqli_query($conn, $status_sql);


/* COUNT BOOKINGS */

$count_sql = "
    SELECT COUNT(*) AS total

    FROM booking

    JOIN ride_request
        ON booking.request_id = ride_request.request_id

    JOIN `USER` AS passenger
        ON ride_request.user_id = passenger.user_id

    JOIN ride
        ON ride_request.ride_id = ride.ride_id

    JOIN `USER` AS driver
        ON ride.user_id = driver.user_id

    WHERE 1=1
";


/* SEARCH CONDITION */

if ($search !== "") {

    $count_sql .= "
        AND (
            CAST(booking.booking_id AS CHAR) LIKE '%$search_safe%'

            OR passenger.full_name LIKE '%$search_safe%'

            OR driver.full_name LIKE '%$search_safe%'

            OR CAST(ride.ride_id AS CHAR) LIKE '%$search_safe%'

            OR ride.start_location LIKE '%$search_safe%'

            OR ride.destination LIKE '%$search_safe%'
        )
    ";
}


/* STATUS CONDITION */

if ($status_filter !== "" && $status_filter !== "All Status") {

    $count_sql .= "
        AND booking.booking_status = '$status_safe'
    ";
}


/* RUN COUNT QUERY */

$count_result = mysqli_query($conn, $count_sql);

if (!$count_result) {
    die("Booking count query failed: " . mysqli_error($conn));
}

$count_row = mysqli_fetch_assoc($count_result);

$total_bookings = (int)$count_row["total"];

$total_pages = max(
    1,
    (int)ceil($total_bookings / $per_page)
);

if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $per_page;


/* BOOKING LIST */

$sql = "
    SELECT
        booking.booking_id,
        booking.booking_date,
        booking.booking_status,

        ride_request.request_id,

        passenger.full_name AS passenger_name,

        driver.full_name AS driver_name,

        ride.ride_id,
        ride.start_location,
        ride.destination,
        ride.ride_date,
        ride.departure_time

    FROM booking

    JOIN ride_request
        ON booking.request_id = ride_request.request_id

    JOIN `USER` AS passenger
        ON ride_request.user_id = passenger.user_id

    JOIN ride
        ON ride_request.ride_id = ride.ride_id

    JOIN `USER` AS driver
        ON ride.user_id = driver.user_id

    WHERE 1=1
";


/* SEARCH */

if ($search !== "") {

    $sql .= "
        AND (
            CAST(booking.booking_id AS CHAR) LIKE '%$search_safe%'

            OR passenger.full_name LIKE '%$search_safe%'

            OR driver.full_name LIKE '%$search_safe%'

            OR CAST(ride.ride_id AS CHAR) LIKE '%$search_safe%'

            OR ride.start_location LIKE '%$search_safe%'

            OR ride.destination LIKE '%$search_safe%'
        )
    ";
}


/* STATUS */

if ($status_filter !== "" && $status_filter !== "All Status") {

    $sql .= "
        AND booking.booking_status = '$status_safe'
    ";
}


/* SORT + PAGINATION */

$sql .= "
    ORDER BY booking.booking_date DESC,
             booking.booking_id DESC

    LIMIT $offset, $per_page
";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Booking query failed: " . mysqli_error($conn));
}


/* VIEW BOOKING DETAILS */

$booking_detail = null;

if ($view_id > 0) {

    $detail_sql = "
        SELECT
            booking.booking_id,
            booking.booking_date,
            booking.booking_status,

            ride_request.request_id,
            ride_request.request_time,

            passenger.full_name AS passenger_name,
            passenger.student_id AS passenger_student_id,
            passenger.university_email AS passenger_email,
            passenger.phone AS passenger_phone,
            passenger.department AS passenger_department,

            driver.full_name AS driver_name,
            driver.student_id AS driver_student_id,
            driver.university_email AS driver_email,
            driver.phone AS driver_phone,
            driver.department AS driver_department,

            ride.ride_id,
            ride.ride_date,
            ride.departure_time,
            ride.start_location,
            ride.destination,
            ride.ride_type,
            ride.available_seats,
            ride.fare,
            ride.women_only,
            ride.ride_status

        FROM booking

        JOIN ride_request
            ON booking.request_id = ride_request.request_id

        JOIN `USER` AS passenger
            ON ride_request.user_id = passenger.user_id

        JOIN ride
            ON ride_request.ride_id = ride.ride_id

        JOIN `USER` AS driver
            ON ride.user_id = driver.user_id

        WHERE booking.booking_id = $view_id

        LIMIT 1
    ";

    $detail_result = mysqli_query($conn, $detail_sql);

    if ($detail_result) {
        $booking_detail = mysqli_fetch_assoc($detail_result);
    }
}


/* PAGINATION LINK */

function booking_page_link($number)
{
    $params = $_GET;

    $params["page"] = $number;

    unset($params["view"]);

    return "admin_bookings.php?" . http_build_query($params);
}


/* BOOKING STATUS CLASS */

function booking_status_class($status)
{
    if ($status === "Confirmed") {
        return "confirmed";
    }

    if ($status === "Cancelled") {
        return "cancelled";
    }

    if ($status === "Completed") {
        return "completed";
    }

    return "other";
}

?>

<!DOCTYPE html>
<html>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Booking Management - CampusRide</title>

    <link
        rel="stylesheet"
        href="admin_bookings.css"
    >

</head>

<body>


<div class="dashboard">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">

            <div>
                <b>CampusRide</b>
                <small>Operations Console</small>
            </div>

        </div>


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

            <a
                href="admin_bookings.php"
                class="active"
            >
                Bookings
            </a>

            <a href="admin_sos.php">
                SOS Alerts
            </a>

            <a href="#">
                Settings
            </a>

        </nav>


        <div class="sidebar-bottom">

            <div class="admin-profile">

                <div class="admin-pic">

                    <?php
                    echo htmlspecialchars(
                        strtoupper(
                            substr($admin_name, 0, 1)
                        )
                    );
                    ?>

                </div>


                <div class="admin-info">

                    <b>
                        <?php
                        echo htmlspecialchars($admin_name);
                        ?>
                    </b>

                    <small>
                        <?php
                        echo htmlspecialchars($admin_email);
                        ?>
                    </small>

                </div>


                <a
                    href="admin_bookings.php?logout=1"
                    class="logout"
                >
                    ↪
                </a>

            </div>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main">


        <!-- TOP HEADER -->

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
                    echo htmlspecialchars($admin_name);
                    ?>
                </span>

            </div>

        </header>


        <section class="content">


            <?php if ($view_id > 0 && $booking_detail): ?>


                <!-- BOOKING DETAILS -->

                <div class="page-heading">

                    <div>

                        <h1>
                            Booking Details
                        </h1>

                        <p>
                            Complete information for Booking #
                            <?php
                            echo (int)$booking_detail["booking_id"];
                            ?>
                        </p>

                    </div>


                    <a
                        href="admin_bookings.php"
                        class="back-btn"
                    >
                        ← Back to Bookings
                    </a>

                </div>


                <div class="detail-grid">


                    <!-- BOOKING INFORMATION -->

                    <div class="detail-card">

                        <div class="card-title">

                            <span>
                                Booking Information
                            </span>

                            <span
                                class="status <?php echo booking_status_class($booking_detail["booking_status"]); ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["booking_status"]
                                );
                                ?>
                            </span>

                        </div>


                        <div class="detail-row">

                            <span>
                                Booking ID
                            </span>

                            <b>
                                #<?php
                                echo (int)$booking_detail["booking_id"];
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Booking Date
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["booking_date"]
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Request ID
                            </span>

                            <b>
                                #<?php
                                echo (int)$booking_detail["request_id"];
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Request Time
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["request_time"]
                                );
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
                                Name
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["passenger_name"]
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Student ID
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["passenger_student_id"]
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
                                    $booking_detail["passenger_email"]
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
                                    $booking_detail["passenger_phone"]
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Department
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["passenger_department"]
                                );
                                ?>
                            </b>

                        </div>

                    </div>


                    <!-- RELATED RIDE -->

                    <div class="detail-card">

                        <div class="card-title">

                            <span>
                                Related Ride
                            </span>

                            <span
                                class="status <?php echo booking_status_class($booking_detail["ride_status"]); ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["ride_status"]
                                );
                                ?>
                            </span>

                        </div>


                        <div class="detail-row">

                            <span>
                                Ride ID
                            </span>

                            <b>
                                #<?php
                                echo (int)$booking_detail["ride_id"];
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Route
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["start_location"]
                                );
                                ?>

                                →

                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["destination"]
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Ride Date
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["ride_date"]
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Departure
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    date(
                                        "h:i A",
                                        strtotime(
                                            $booking_detail["departure_time"]
                                        )
                                    )
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Ride Type
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["ride_type"]
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Fare
                            </span>

                            <b>
                                ৳<?php
                                echo htmlspecialchars(
                                    number_format(
                                        (float)$booking_detail["fare"],
                                        2
                                    )
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Available Seats
                            </span>

                            <b>
                                <?php
                                echo (int)$booking_detail["available_seats"];
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Women Only
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["women_only"]
                                );
                                ?>
                            </b>

                        </div>

                    </div>


                    <!-- DRIVER -->

                    <div class="detail-card">

                        <div class="card-title">

                            <span>
                                Driver
                            </span>

                        </div>


                        <div class="detail-row">

                            <span>
                                Name
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["driver_name"]
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Student ID
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["driver_student_id"]
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
                                    $booking_detail["driver_email"]
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
                                    $booking_detail["driver_phone"]
                                );
                                ?>
                            </b>

                        </div>


                        <div class="detail-row">

                            <span>
                                Department
                            </span>

                            <b>
                                <?php
                                echo htmlspecialchars(
                                    $booking_detail["driver_department"]
                                );
                                ?>
                            </b>

                        </div>

                    </div>


                </div>


            <?php else: ?>


                <!-- BOOKING LIST -->

                <div class="page-heading">

                    <div>

                        <h1>
                            Booking Management
                        </h1>

                        <p>
                            View and monitor all bookings in the system.
                        </p>

                    </div>

                </div>


                <!-- FILTER -->

                <form
                    method="GET"
                    action="admin_bookings.php"
                    class="filter-card"
                >

                    <div class="filter-field search-field">

                        <label>
                            Search Bookings
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="<?php
                            echo htmlspecialchars($search);
                            ?>"
                            placeholder="Booking ID, passenger, driver, ride or location"
                        >

                    </div>


                    <div class="filter-field status-field">

                        <label>
                            Status
                        </label>

                        <select name="status">

                            <option value="">
                                All Status
                            </option>


                            <?php if ($status_result): ?>

                                <?php while ($sr = mysqli_fetch_assoc($status_result)): ?>

                                    <option
                                        value="<?php
                                        echo htmlspecialchars(
                                            $sr["booking_status"]
                                        );
                                        ?>"
                                        <?php
                                        echo (
                                            $status_filter ===
                                            $sr["booking_status"]
                                        )
                                        ? "selected"
                                        : "";
                                        ?>
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $sr["booking_status"]
                                        );
                                        ?>
                                    </option>

                                <?php endwhile; ?>

                            <?php endif; ?>

                        </select>

                    </div>


                    <div class="filter-actions">

                        <button
                            type="submit"
                            class="search-btn"
                        >
                            Search
                        </button>

                        <a
                            href="admin_bookings.php"
                            class="clear-btn"
                        >
                            Clear
                        </a>

                    </div>

                </form>


                <!-- TABLE -->

                <div class="table-card">

                    <div class="table-top">

                        <div>

                            <b>
                                All Bookings
                            </b>

                            <span>
                                <?php
                                echo number_format($total_bookings);
                                ?>

                                booking<?php
                                echo $total_bookings === 1
                                    ? ""
                                    : "s";
                                ?>
                            </span>

                        </div>

                    </div>


                    <div class="table-wrap">

                        <table>

                            <thead>

                                <tr>

                                    <th>
                                        Booking ID
                                    </th>

                                    <th>
                                        Passenger
                                    </th>

                                    <th>
                                        Ride
                                    </th>

                                    <th>
                                        Driver
                                    </th>

                                    <th>
                                        Booking Date
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                            <?php if (mysqli_num_rows($result) > 0): ?>


                                <?php while ($booking = mysqli_fetch_assoc($result)): ?>

                                    <tr>


                                        <td>

                                            <b class="booking-id">
                                                #<?php
                                                echo (int)$booking["booking_id"];
                                                ?>
                                            </b>

                                        </td>


                                        <td>

                                            <div class="person-name">
                                                <?php
                                                echo htmlspecialchars(
                                                    $booking["passenger_name"]
                                                );
                                                ?>
                                            </div>

                                        </td>


                                        <td>

                                            <div class="ride-cell">

                                                <b>
                                                    #<?php
                                                    echo (int)$booking["ride_id"];
                                                    ?>
                                                </b>

                                                <small>

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $booking["start_location"]
                                                    );
                                                    ?>

                                                    →

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $booking["destination"]
                                                    );
                                                    ?>

                                                </small>

                                            </div>

                                        </td>


                                        <td>

                                            <div class="person-name">
                                                <?php
                                                echo htmlspecialchars(
                                                    $booking["driver_name"]
                                                );
                                                ?>
                                            </div>

                                        </td>


                                        <td>
                                            <?php
                                            echo htmlspecialchars(
                                                $booking["booking_date"]
                                            );
                                            ?>
                                        </td>


                                        <td>

                                            <span
                                                class="status <?php echo booking_status_class($booking["booking_status"]); ?>"
                                            >
                                                <?php
                                                echo htmlspecialchars(
                                                    $booking["booking_status"]
                                                );
                                                ?>
                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                href="admin_bookings.php?view=<?php echo (int)$booking["booking_id"]; ?>"
                                                class="view-btn"
                                            >
                                                View
                                            </a>

                                        </td>


                                    </tr>

                                <?php endwhile; ?>


                            <?php else: ?>

                                <tr>

                                    <td
                                        colspan="7"
                                        class="empty-state"
                                    >
                                        No bookings found for the selected filters.
                                    </td>

                                </tr>

                            <?php endif; ?>


                            </tbody>

                        </table>

                    </div>


                    <!-- PAGINATION -->

                    <div class="pagination">

                        <span>

                            Showing

                            <?php
                            echo $total_bookings > 0
                                ? $offset + 1
                                : 0;
                            ?>

                            -

                            <?php
                            echo min(
                                $offset + $per_page,
                                $total_bookings
                            );
                            ?>

                            of

                            <?php
                            echo $total_bookings;
                            ?>

                        </span>


                        <?php if ($total_pages > 1): ?>

                            <div class="page-links">


                                <?php if ($page > 1): ?>

                                    <a
                                        href="<?php
                                        echo htmlspecialchars(
                                            booking_page_link($page - 1)
                                        );
                                        ?>"
                                    >
                                        ‹
                                    </a>

                                <?php else: ?>

                                    <span class="disabled">
                                        ‹
                                    </span>

                                <?php endif; ?>


                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>

                                    <?php if ($i === $page): ?>

                                        <span class="current">
                                            <?php echo $i; ?>
                                        </span>

                                    <?php else: ?>

                                        <a
                                            href="<?php
                                            echo htmlspecialchars(
                                                booking_page_link($i)
                                            );
                                            ?>"
                                        >
                                            <?php echo $i; ?>
                                        </a>

                                    <?php endif; ?>

                                <?php endfor; ?>


                                <?php if ($page < $total_pages): ?>

                                    <a
                                        href="<?php
                                        echo htmlspecialchars(
                                            booking_page_link($page + 1)
                                        );
                                        ?>"
                                    >
                                        ›
                                    </a>

                                <?php else: ?>

                                    <span class="disabled">
                                        ›
                                    </span>

                                <?php endif; ?>


                            </div>

                        <?php endif; ?>

                    </div>

                </div>


            <?php endif; ?>


        </section>

    </main>

</div>

</body>
</html>
