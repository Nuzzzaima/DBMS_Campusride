<?php

session_start();

include "db.php";


if (!isset($_SESSION["admin_user_id"])) {

    header("Location: admin_login.php");
    exit();

}


$admin_user_id = (int) $_SESSION["admin_user_id"];


if (isset($_GET["logout"])) {

    session_unset();
    session_destroy();

    header("Location: admin_login.php");
    exit();

}


$admin_result = mysqli_query(
    $conn,
    "SELECT full_name, university_email
     FROM `USER`
     WHERE user_id = $admin_user_id
     LIMIT 1"
);

$admin = $admin_result
    ? mysqli_fetch_assoc($admin_result)
    : [];

$admin_name = $admin["full_name"] ?? "";
$admin_email = $admin["university_email"] ?? "";


$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");
$date_filter = trim($_GET["date"] ?? "");

$page = max(
    1,
    (int) ($_GET["page"] ?? 1)
);

$view_id = (int) ($_GET["view"] ?? 0);

$per_page = 8;


$status_result = mysqli_query(
    $conn,
    "SELECT DISTINCT ride_status
     FROM ride
     ORDER BY CASE ride_status
         WHEN 'Active' THEN 1
         WHEN 'Completed' THEN 2
         WHEN 'Cancelled' THEN 3
         ELSE 4
     END"
);


$where = " WHERE 1=1 ";


if ($search !== "") {

    $s = mysqli_real_escape_string($conn, $search);

    $where .= "
        AND (
            CAST(ride.ride_id AS CHAR) LIKE '%$s%'
            OR `USER`.full_name LIKE '%$s%'
            OR ride.start_location LIKE '%$s%'
            OR ride.destination LIKE '%$s%'
        )
    ";
}


if (
    $status_filter !== ""
    && $status_filter !== "All Status"
) {

    $s = mysqli_real_escape_string(
        $conn,
        $status_filter
    );

    $where .= "
        AND ride.ride_status = '$s'
    ";
}


if ($date_filter !== "") {

    $s = mysqli_real_escape_string(
        $conn,
        $date_filter
    );

    $where .= "
        AND ride.ride_date = '$s'
    ";
}


$count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ride
     JOIN `USER`
         ON ride.user_id = `USER`.user_id
     $where"
);


if (!$count_result) {

    die(
        "Ride count query failed: "
        . mysqli_error($conn)
    );

}


$total_rides = (int) mysqli_fetch_assoc(
    $count_result
)["total"];


$total_pages = max(
    1,
    (int) ceil($total_rides / $per_page)
);


if ($page > $total_pages) {

    $page = $total_pages;

}


$offset = ($page - 1) * $per_page;


$ride_result = mysqli_query(
    $conn,
    "SELECT
        ride.ride_id,
        ride.ride_date,
        ride.departure_time,
        ride.start_location,
        ride.destination,
        ride.available_seats,
        ride.women_only,
        ride.ride_status,
        `USER`.full_name AS driver_name

     FROM ride

     JOIN `USER`
         ON ride.user_id = `USER`.user_id

     $where

     ORDER BY
        ride.ride_date DESC,
        ride.departure_time ASC,
        ride.ride_id DESC

     LIMIT $offset, $per_page"
);


if (!$ride_result) {

    die(
        "Ride query failed: "
        . mysqli_error($conn)
    );

}


$ride_detail = null;


if ($view_id > 0) {

    $detail_result = mysqli_query(
        $conn,
        "SELECT
            ride.*,
            `USER`.full_name AS driver_name,
            `USER`.student_id,
            `USER`.university_email,
            `USER`.phone,
            `USER`.department,
            vehicle.vehicle_type,
            vehicle.registration_number,
            vehicle.seat_capacity

         FROM ride

         JOIN `USER`
             ON ride.user_id = `USER`.user_id

         LEFT JOIN vehicle
             ON ride.vehicle_id = vehicle.vehicle_id

         WHERE ride.ride_id = $view_id

         LIMIT 1"
    );

    if ($detail_result) {

        $ride_detail = mysqli_fetch_assoc(
            $detail_result
        );

    }

}


function ride_page_link($n)
{
    $p = $_GET;

    $p["page"] = $n;

    unset($p["view"]);

    return "admin_rides.php?" . http_build_query($p);
}


function status_class($s)
{
    if ($s === "Completed") {
        return "completed";
    }

    if ($s === "Cancelled") {
        return "cancelled";
    }

    return "active";
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

    <title>
        Ride Management - CampusRide
    </title>

    <link rel="stylesheet" href="admin_rides.css">

</head>


<body>

<div class="dashboard">


    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">
                🚗
            </div>

            <div>

                <b>
                    CampusRide
                </b>

                <small>
                    Operations Console
                </small>

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

            <a href="admin_rides.php" class="active">
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
                    href="admin_rides.php?logout=1"
                    class="logout"
                >
                    ↪
                </a>

            </div>

        </div>

    </aside>


    <main class="main">


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


            <?php if ($view_id > 0 && $ride_detail): ?>


                <div class="page-heading">

                    <div>

                        <h1>
                            Ride Details
                        </h1>

                        <p>

                            Complete information for Ride #

                            <?php
                            echo (int) $ride_detail["ride_id"];
                            ?>

                        </p>

                    </div>


                    <a
                        href="admin_rides.php"
                        class="back-btn"
                    >
                        ← Back to Rides
                    </a>

                </div>


                <div class="detail-grid">


                    <div class="detail-card">

                        <div class="card-title">

                            <span>
                                Ride Information
                            </span>


                            <span
                                class="status <?php echo status_class($ride_detail["ride_status"]); ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    $ride_detail["ride_status"]
                                );
                                ?>

                            </span>

                        </div>


                        <div class="route-box">

                            <div>

                                <small>
                                    FROM
                                </small>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["start_location"]
                                    );
                                    ?>

                                </strong>

                            </div>


                            <div class="route-arrow">
                                →
                            </div>


                            <div>

                                <small>
                                    TO
                                </small>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["destination"]
                                    );
                                    ?>

                                </strong>

                            </div>

                        </div>


                        <div class="info-grid">


                            <div>

                                <small>
                                    Ride ID
                                </small>

                                <b>
                                    #
                                    <?php
                                    echo (int) $ride_detail["ride_id"];
                                    ?>
                                </b>

                            </div>


                            <div>

                                <small>
                                    Date
                                </small>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["ride_date"]
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <small>
                                    Departure Time
                                </small>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        date(
                                            "h:i A",
                                            strtotime(
                                                $ride_detail["departure_time"]
                                            )
                                        )
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <small>
                                    Ride Type
                                </small>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["ride_type"]
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <small>
                                    Available Seats
                                </small>

                                <b>

                                    <?php
                                    echo (int) $ride_detail["available_seats"];
                                    ?>

                                </b>

                            </div>


                            <div>

                                <small>
                                    Fare
                                </small>

                                <b>

                                    ৳

                                    <?php
                                    echo number_format(
                                        (float) $ride_detail["fare"],
                                        2
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <small>
                                    Women Only
                                </small>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["women_only"]
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <small>
                                    Created
                                </small>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["created_at"]
                                    );
                                    ?>

                                </b>

                            </div>


                        </div>

                    </div>


                    <div class="detail-card">


                        <div class="card-title">

                            <span>
                                Creator / Driver
                            </span>

                        </div>


                        <div class="driver-profile">

                            <div class="driver-pic">

                                <?php
                                echo htmlspecialchars(
                                    strtoupper(
                                        substr(
                                            $ride_detail["driver_name"],
                                            0,
                                            1
                                        )
                                    )
                                );
                                ?>

                            </div>


                            <div>

                                <h2>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["driver_name"]
                                    );
                                    ?>

                                </h2>


                                <p>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["department"]
                                    );
                                    ?>

                                </p>

                            </div>

                        </div>


                        <div class="info-list">


                            <div>

                                <span>
                                    Student ID
                                </span>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["student_id"]
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <span>
                                    Email
                                </span>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["university_email"]
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <span>
                                    Phone
                                </span>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["phone"]
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <span>
                                    Department
                                </span>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["department"]
                                    );
                                    ?>

                                </b>

                            </div>


                        </div>

                    </div>


                    <div class="detail-card full-detail">


                        <div class="card-title">

                            <span>
                                Vehicle & Description
                            </span>

                        </div>


                        <div class="info-grid">


                            <div>

                                <small>
                                    Vehicle Type
                                </small>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["vehicle_type"]
                                        ?? "Not assigned"
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <small>
                                    Registration
                                </small>

                                <b>

                                    <?php
                                    echo htmlspecialchars(
                                        $ride_detail["registration_number"]
                                        ?? "Not available"
                                    );
                                    ?>

                                </b>

                            </div>


                            <div>

                                <small>
                                    Seat Capacity
                                </small>

                                <b>

                                    <?php
                                    echo isset(
                                        $ride_detail["seat_capacity"]
                                    )
                                        ? (int) $ride_detail["seat_capacity"]
                                        : "N/A";
                                    ?>

                                </b>

                            </div>


                        </div>


                        <div class="description-box">

                            <small>
                                Description
                            </small>

                            <p>

                                <?php

                                echo ($ride_detail["description"] ?? "") !== ""

                                    ? nl2br(
                                        htmlspecialchars(
                                            $ride_detail["description"]
                                        )
                                    )

                                    : "No description provided.";

                                ?>

                            </p>

                        </div>


                    </div>


                </div>


            <?php else: ?>


                <div class="page-heading">

                    <div>

                        <h1>
                            Ride Management
                        </h1>

                        <p>
                            View and monitor all rides created in the
                            CampusRide system.
                        </p>

                    </div>


                    <div class="ride-count">

                        <?php
                        echo number_format($total_rides);
                        ?>

                        rides

                    </div>

                </div>


                <form
                    method="GET"
                    class="filter-card"
                >


                    <div class="search-field">

                        <label>
                            Search Ride
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Ride ID, driver, from or destination"
                        >

                    </div>


                    <div class="filter-field">

                        <label>
                            Status
                        </label>


                        <select name="status">

                            <option value="">
                                All Status
                            </option>


                            <?php
                            if ($status_result):

                                while (
                                    $sr = mysqli_fetch_assoc(
                                        $status_result
                                    )
                                ):
                            ?>

                                <option
                                    value="<?php echo htmlspecialchars($sr["ride_status"]); ?>"
                                    <?php
                                    echo $status_filter === $sr["ride_status"]
                                        ? "selected"
                                        : "";
                                    ?>
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $sr["ride_status"]
                                    );
                                    ?>

                                </option>


                            <?php
                                endwhile;

                            endif;
                            ?>

                        </select>

                    </div>


                    <div class="filter-field">

                        <label>
                            Date
                        </label>

                        <input
                            type="date"
                            name="date"
                            value="<?php echo htmlspecialchars($date_filter); ?>"
                        >

                    </div>


                    <div class="filter-actions">

                        <button
                            type="submit"
                            class="search-btn"
                        >
                            Search
                        </button>


                        <a
                            href="admin_rides.php"
                            class="clear-btn"
                        >
                            Clear
                        </a>

                    </div>


                </form>


                <div class="table-card">


                    <div class="table-top">

                        <div>

                            <b>
                                All Rides
                            </b>


                            <span>

                                <?php
                                echo number_format($total_rides);
                                ?>

                                result

                                <?php
                                echo $total_rides == 1
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
                                        Ride ID
                                    </th>

                                    <th>
                                        Driver
                                    </th>

                                    <th>
                                        From
                                    </th>

                                    <th>
                                        To
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                    <th>
                                        Time
                                    </th>

                                    <th>
                                        Seats
                                    </th>

                                    <th>
                                        Women Only
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


                                <?php
                                if (mysqli_num_rows($ride_result) > 0):

                                    while (
                                        $ride = mysqli_fetch_assoc(
                                            $ride_result
                                        )
                                    ):
                                ?>

                                    <tr>


                                        <td class="ride-id">

                                            #

                                            <?php
                                            echo (int) $ride["ride_id"];
                                            ?>

                                        </td>


                                        <td class="driver-name">

                                            <?php
                                            echo htmlspecialchars(
                                                $ride["driver_name"]
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $ride["start_location"]
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $ride["destination"]
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                $ride["ride_date"]
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <?php
                                            echo htmlspecialchars(
                                                date(
                                                    "h:i A",
                                                    strtotime(
                                                        $ride["departure_time"]
                                                    )
                                                )
                                            );
                                            ?>

                                        </td>


                                        <td>

                                            <span class="seats">

                                                <?php
                                                echo (int) $ride["available_seats"];
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="women <?php echo $ride["women_only"] === "Yes" ? "yes" : "no"; ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $ride["women_only"]
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <span
                                                class="status <?php echo status_class($ride["ride_status"]); ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $ride["ride_status"]
                                                );
                                                ?>

                                            </span>

                                        </td>


                                        <td>

                                            <a
                                                href="admin_rides.php?view=<?php echo (int) $ride["ride_id"]; ?>"
                                                class="view-btn"
                                            >
                                                View
                                            </a>

                                        </td>


                                    </tr>


                                <?php
                                    endwhile;

                                else:
                                ?>

                                    <tr>

                                        <td
                                            colspan="10"
                                            class="empty-state"
                                        >
                                            No rides found for the selected
                                            filters.
                                        </td>

                                    </tr>

                                <?php endif; ?>


                            </tbody>


                        </table>


                    </div>


                    <?php if ($total_pages > 1): ?>


                        <div class="pagination">


                            <span>

                                Showing

                                <?php
                                echo $total_rides > 0
                                    ? $offset + 1
                                    : 0;
                                ?>

                                -

                                <?php
                                echo min(
                                    $offset + $per_page,
                                    $total_rides
                                );
                                ?>

                                of

                                <?php
                                echo $total_rides;
                                ?>

                            </span>


                            <div class="page-links">


                                <?php if ($page > 1): ?>

                                    <a
                                        href="<?php echo htmlspecialchars(ride_page_link($page - 1)); ?>"
                                    >
                                        ‹
                                    </a>

                                <?php else: ?>

                                    <span class="disabled">
                                        ‹
                                    </span>

                                <?php endif; ?>


                                <?php
                                for (
                                    $i = 1;
                                    $i <= $total_pages;
                                    $i++
                                ):
                                ?>

                                    <?php if ($i == $page): ?>

                                        <span class="current">

                                            <?php
                                            echo $i;
                                            ?>

                                        </span>

                                    <?php else: ?>

                                        <a
                                            href="<?php echo htmlspecialchars(ride_page_link($i)); ?>"
                                        >

                                            <?php
                                            echo $i;
                                            ?>

                                        </a>

                                    <?php endif; ?>


                                <?php endfor; ?>


                                <?php if ($page < $total_pages): ?>

                                    <a
                                        href="<?php echo htmlspecialchars(ride_page_link($page + 1)); ?>"
                                    >
                                        ›
                                    </a>

                                <?php else: ?>

                                    <span class="disabled">
                                        ›
                                    </span>

                                <?php endif; ?>


                            </div>

                        </div>


                    <?php endif; ?>


                </div>


            <?php endif; ?>


        </section>


    </main>


</div>

</body>

</html>