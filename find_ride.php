
<?php

session_start();
include "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// =========================
// GET LOGGED-IN USER
// =========================

$sql_user = "SELECT full_name, university_email
             FROM `USER`
             WHERE user_id = '$user_id'
             LIMIT 1";

$result_user = mysqli_query($conn, $sql_user);

if (!$result_user) {
    die("Database error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result_user) != 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result_user);

$fullName = $user['full_name'];
$firstName = explode(' ', $fullName)[0];


// =========================
// SEARCH VALUES
// =========================

$from = "";
$to = "";
$date = "";
$time = "";
$ride_type = "";
$women_only = "";


// Get search values
if (isset($_GET['search'])) {

    $from = trim($_GET['from'] ?? "");
    $to = trim($_GET['to'] ?? "");
    $date = $_GET['date'] ?? "";
    $time = $_GET['time'] ?? "";
    $ride_type = $_GET['ride_type'] ?? "";

    if (isset($_GET['women_only'])) {
        $women_only = "Yes";
    }
}


// =========================
// GET ACTIVE RIDES
// =========================

$sql = "SELECT
            ride.*,
            `USER`.full_name,
            `USER`.profile_picture,
            vehicle.vehicle_type,
            vehicle.registration_number,
            vehicle.seat_capacity
        FROM ride
        JOIN `USER`
            ON ride.user_id = `USER`.user_id
        LEFT JOIN vehicle
            ON ride.vehicle_id = vehicle.vehicle_id
        WHERE ride.ride_status = 'Active'
        AND ride.available_seats > 0";


// =========================
// FROM FILTER
// =========================

if ($from != "") {

    $from_safe = mysqli_real_escape_string($conn, $from);

    $sql .= " AND ride.start_location LIKE '%$from_safe%'";
}


// =========================
// TO FILTER
// =========================

if ($to != "") {

    $to_safe = mysqli_real_escape_string($conn, $to);

    $sql .= " AND ride.destination LIKE '%$to_safe%'";
}


// =========================
// DATE FILTER
// =========================

if ($date != "") {

    $date_safe = mysqli_real_escape_string($conn, $date);

    $sql .= " AND ride.ride_date = '$date_safe'";
}


// =========================
// TIME FILTER
// =========================

if ($time != "") {

    $time_safe = mysqli_real_escape_string($conn, $time);

    $sql .= " AND ride.departure_time >= '$time_safe'";
}


// =========================
// RIDE TYPE FILTER
// =========================

if ($ride_type != "" && $ride_type != "All Types") {

    $type_safe = mysqli_real_escape_string($conn, $ride_type);

    $sql .= " AND ride.ride_type = '$type_safe'";
}


// =========================
// WOMEN ONLY FILTER
// =========================

if ($women_only == "Yes") {

    $sql .= " AND ride.women_only = 'Yes'";
}


// =========================
// SORT RIDES
// =========================

$sql .= " ORDER BY ride.ride_date ASC, ride.departure_time ASC";


// =========================
// RUN QUERY
// =========================

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Database error: " . mysqli_error($conn));
}


// Number of rides found
$rideCount = mysqli_num_rows($result);

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Find a Ride - CampusRide</title>

    <link rel="stylesheet" href="find_ride.css">

</head>

<body>


<div class="dashboard">


    <!-- SHARED SIDEBAR -->

    <?php include __DIR__ . '/includes/sidebar.php'; ?>


    <!-- MAIN -->

    <div class="main">


        <!-- HEADER -->

        <div class="header">

            <div>

                <h2>Finding a Ride</h2>

                <p>Search available rides on your route</p>

            </div>


            <div class="header-right">

                <span class="notification">
                    ♧
                </span>


                <div class="user">

                    <div class="small-pic">
                        <?= htmlspecialchars(strtoupper(substr($firstName, 0, 1))) ?>
                    </div>

                    <?= htmlspecialchars($firstName) ?>

                </div>

            </div>

        </div>


        <!-- CONTENT -->

        <div class="content">


            <!-- SEARCH BOX -->

            <form
                method="GET"
                action="find_ride.php"
                class="search-box"
            >


                <div class="field">

                    <label>From</label>

                    <input
                        type="text"
                        name="from"
                        placeholder="Starting location"
                        value="<?= htmlspecialchars($from) ?>"
                    >

                </div>


                <div class="field">

                    <label>To</label>

                    <input
                        type="text"
                        name="to"
                        placeholder="Destination"
                        value="<?= htmlspecialchars($to) ?>"
                    >

                </div>


                <div class="field">

                    <label>Date</label>

                    <input
                        type="date"
                        name="date"
                        value="<?= htmlspecialchars($date) ?>"
                    >

                </div>


                <div class="field">

                    <label>Time</label>

                    <input
                        type="time"
                        name="time"
                        value="<?= htmlspecialchars($time) ?>"
                    >

                </div>


                <div class="field">

                    <label>Ride Type</label>

                    <select name="ride_type">

                        <option
                            value="All Types"
                            <?= ($ride_type == "" || $ride_type == "All Types") ? "selected" : "" ?>
                        >
                            All Types
                        </option>

                        <option
                            value="Car Share"
                            <?= ($ride_type == "Car Share") ? "selected" : "" ?>
                        >
                            Car Share
                        </option>

                        <option
                            value="Bike Share"
                            <?= ($ride_type == "Bike Share") ? "selected" : "" ?>
                        >
                            Bike Share
                        </option>

                        <option
                            value="Rickshaw Share"
                            <?= ($ride_type == "Rickshaw Share") ? "selected" : "" ?>
                        >
                            Rickshaw Share
                        </option>

                    </select>

                </div>


                <div class="field">

                    <label>Women Only</label>

                    <label class="check-box">

                        <input
                            type="checkbox"
                            name="women_only"
                            <?= ($women_only == "Yes") ? "checked" : "" ?>
                        >

                        Women Only Rides

                    </label>

                </div>


                <div class="search-buttons">

                    <button
                        type="submit"
                        name="search"
                        class="search-btn"
                    >
                        Search Rides
                    </button>


                    <a
                        href="find_ride.php"
                        class="clear-btn"
                    >
                        Clear Filters
                    </a>

                </div>

            </form>


            <!-- RESULT HEADER -->

            <div class="result-header">

                <p>

                    <?= $rideCount ?>

                    <?= ($rideCount == 1) ? "ride" : "rides" ?>

                    found

                </p>


                <div class="sort">

                    Sort: Departure Time

                </div>

            </div>


            <!-- RIDES -->

            <div class="ride-list">


                <?php if ($rideCount > 0): ?>


                    <?php while ($row = mysqli_fetch_assoc($result)): ?>


                        <!-- RIDE CARD -->

                        <div class="ride-card">


                            <!-- DRIVER -->

                            <div class="driver">


                                <div class="driver-pic">

                                    <?= htmlspecialchars(
                                        strtoupper(
                                            substr($row['full_name'], 0, 1)
                                        )
                                    ) ?>

                                </div>


                                <div class="driver-info">

                                    <b>
                                        <?= htmlspecialchars($row['full_name']) ?>
                                    </b>


                                    <div class="stars">
                                        ★★★★★
                                    </div>

                                </div>


                                <div class="ride-tags">


                                    <?php if (!empty($row['vehicle_type'])): ?>

                                        <span class="vehicle">

                                            <?= htmlspecialchars($row['vehicle_type']) ?>

                                        </span>

                                    <?php endif; ?>


                                    <?php if ($row['women_only'] == "Yes"): ?>

                                        <span class="women-tag">

                                            Women Only

                                        </span>

                                    <?php endif; ?>


                                </div>

                            </div>


                            <!-- ROUTE -->

                            <div class="route">


                                <div class="route-line">

                                    <span class="dot"></span>

                                    <div>

                                        <?= htmlspecialchars($row['start_location']) ?>

                                    </div>

                                </div>


                                <div class="route-line">

                                    <span class="circle"></span>

                                    <div>

                                        <?= htmlspecialchars($row['destination']) ?>

                                    </div>

                                </div>


                                <div class="fare">

                                    <b>

                                        ৳<?= number_format($row['fare'], 2) ?>

                                    </b>


                                    <small>
                                        / person
                                    </small>

                                </div>

                            </div>


                            <!-- RIDE INFORMATION -->

                            <div class="ride-details">


                                <span>

                                    Date:

                                    <?= date(
                                        "M j, Y",
                                        strtotime($row['ride_date'])
                                    ) ?>

                                </span>


                                <span>

                                    Time:

                                    <?= date(
                                        "g:i A",
                                        strtotime($row['departure_time'])
                                    ) ?>

                                </span>


                                <span>

                                    <?= htmlspecialchars($row['available_seats']) ?>

                                    seats left

                                </span>

                            </div>


                            <!-- RIDE TYPE -->

                            <div class="ride-type">

                                <?= htmlspecialchars($row['ride_type']) ?>

                            </div>


                            <!-- BUTTONS -->

                            <div class="card-buttons">


                                <a
                                    href="ride_details.php?ride_id=<?= $row['ride_id'] ?>"
                                    class="view-btn"
                                >
                                    View Details
                                </a>


                                <a
                                    href="request_ride.php?id=<?= $row['ride_id'] ?>"
                                    class="request-btn"
                                >
                                    Request Ride
                                </a>


                            </div>

                        </div>


                    <?php endwhile; ?>


                <?php else: ?>


                    <div class="no-rides">

                        <h3>
                            No rides found
                        </h3>

                        <p>
                            No active rides match your search.
                        </p>

                    </div>


                <?php endif; ?>


            </div>

        </div>

    </div>

</div>


</body>

</html>
