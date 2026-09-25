<?php
session_start();
include "db.php";

if (!isset($_SESSION["admin_user_id"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_user_id = (int)$_SESSION["admin_user_id"];

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
$admin = $admin_result ? mysqli_fetch_assoc($admin_result) : [];
$admin_name = $admin["full_name"] ?? "";
$admin_email = $admin["university_email"] ?? "";

$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");
$page = max(1, (int)($_GET["page"] ?? 1));
$view_id = (int)($_GET["view"] ?? 0);
$per_page = 8;

$status_result = mysqli_query(
    $conn,
    "SELECT DISTINCT request_status
     FROM ride_request
     ORDER BY CASE request_status
         WHEN 'Pending' THEN 1
         WHEN 'Accepted' THEN 2
         WHEN 'Rejected' THEN 3
         ELSE 4
     END"
);

$where = " WHERE 1=1 ";

if ($search !== "") {
    $s = mysqli_real_escape_string($conn, $search);
    $where .= " AND (
        CAST(ride_request.request_id AS CHAR) LIKE '%$s%'
        OR passenger.full_name LIKE '%$s%'
        OR driver.full_name LIKE '%$s%'
        OR CAST(ride_request.ride_id AS CHAR) LIKE '%$s%'
        OR ride.start_location LIKE '%$s%'
        OR ride.destination LIKE '%$s%'
    )";
}

if ($status_filter !== "" && $status_filter !== "All Status") {
    $s = mysqli_real_escape_string($conn, $status_filter);
    $where .= " AND ride_request.request_status = '$s'";
}

$count_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ride_request
     JOIN `USER` AS passenger
         ON ride_request.user_id = passenger.user_id
     JOIN ride
         ON ride_request.ride_id = ride.ride_id
     JOIN `USER` AS driver
         ON ride.user_id = driver.user_id
     $where"
);

if (!$count_result) {
    die("Request count query failed: " . mysqli_error($conn));
}

$total_requests = (int)mysqli_fetch_assoc($count_result)["total"];
$total_pages = max(1, (int)ceil($total_requests / $per_page));

if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $per_page;

$request_result = mysqli_query(
    $conn,
    "SELECT
        ride_request.request_id,
        ride_request.request_status,
        ride_request.request_time,
        ride_request.ride_id,
        passenger.full_name AS passenger_name,
        driver.full_name AS driver_name,
        ride.start_location,
        ride.destination,
        ride.ride_date,
        ride.departure_time
     FROM ride_request
     JOIN `USER` AS passenger
         ON ride_request.user_id = passenger.user_id
     JOIN ride
         ON ride_request.ride_id = ride.ride_id
     JOIN `USER` AS driver
         ON ride.user_id = driver.user_id
     $where
     ORDER BY ride_request.request_id DESC
     LIMIT $offset, $per_page"
);

if (!$request_result) {
    die("Request query failed: " . mysqli_error($conn));
}

$request_detail = null;

if ($view_id > 0) {
    $detail_result = mysqli_query(
        $conn,
        "SELECT
            ride_request.request_id,
            ride_request.request_status,
            ride_request.request_time,
            ride_request.ride_id,
            passenger.user_id AS passenger_id,
            passenger.full_name AS passenger_name,
            passenger.student_id AS passenger_student_id,
            passenger.university_email AS passenger_email,
            passenger.phone AS passenger_phone,
            passenger.department AS passenger_department,
            driver.user_id AS driver_id,
            driver.full_name AS driver_name,
            driver.student_id AS driver_student_id,
            driver.university_email AS driver_email,
            ride.ride_date,
            ride.departure_time,
            ride.start_location,
            ride.destination,
            ride.ride_type,
            ride.available_seats,
            ride.fare,
            ride.women_only,
            ride.ride_status
         FROM ride_request
         JOIN `USER` AS passenger
             ON ride_request.user_id = passenger.user_id
         JOIN ride
             ON ride_request.ride_id = ride.ride_id
         JOIN `USER` AS driver
             ON ride.user_id = driver.user_id
         WHERE ride_request.request_id = $view_id
         LIMIT 1"
    );

    if ($detail_result) {
        $request_detail = mysqli_fetch_assoc($detail_result);
    }
}

function request_page_link($n) {
    $p = $_GET;
    $p["page"] = $n;
    unset($p["view"]);
    return "admin_requests.php?" . http_build_query($p);
}

function status_class($status) {
    if ($status === "Accepted") return "accepted";
    if ($status === "Rejected") return "rejected";
    return "pending";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Management - CampusRide</title>
    <link rel="stylesheet" href="admin_requests.css">
</head>
<body>

<div class="dashboard">

    <aside class="sidebar">

        <div class="logo">
            <div class="logo-icon">🚗</div>
            <div>
                <b>CampusRide</b>
                <small>Operations Console</small>
            </div>
        </div>

        <nav class="menu">
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="admin_users.php">Users Management</a>
            <a href="admin_vehicle_fleet.php">Vehicles Fleet</a>
            <a href="admin_rides.php">All Rides</a>
            <a href="admin_requests.php" class="active">Requests</a>
            <a href="admin_bookings.php">Bookings</a>
            <a href="admin_sos.php">SOS Alerts</a>
            <a href="#">Settings</a>
        </nav>

        <div class="sidebar-bottom">
            <div class="admin-profile">
                <div class="admin-pic">
                    <?php echo htmlspecialchars(strtoupper(substr($admin_name, 0, 1))); ?>
                </div>
                <div class="admin-info">
                    <b><?php echo htmlspecialchars($admin_name); ?></b>
                    <small><?php echo htmlspecialchars($admin_email); ?></small>
                </div>
                <a href="admin_requests.php?logout=1" class="logout">↪</a>
            </div>
        </div>

    </aside>

    <main class="main">

        <header class="top-header">
            <div class="desk-name">● &nbsp; CAMPUS TRANSIT OPERATIONS DESK</div>
            <div class="top-right">
                <span class="db-status"><i></i> Connected to DB</span>
                <span class="bell">♧</span>
                <span class="top-user">
                    ● &nbsp; <?php echo htmlspecialchars($admin_name); ?>
                </span>
            </div>
        </header>

        <section class="content">

            <?php if ($view_id > 0 && $request_detail): ?>

                <div class="page-heading">
                    <div>
                        <h1>Request Details</h1>
                        <p>Complete information for Request #<?php echo (int)$request_detail["request_id"]; ?></p>
                    </div>
                    <a href="admin_requests.php" class="back-btn">← Back to Requests</a>
                </div>

                <div class="detail-grid">

                    <div class="detail-card">
                        <div class="card-title">
                            <span>Request Information</span>
                            <span class="status <?php echo status_class($request_detail["request_status"]); ?>">
                                <?php echo htmlspecialchars($request_detail["request_status"]); ?>
                            </span>
                        </div>

                        <div class="detail-row">
                            <span>Request ID</span>
                            <b>#<?php echo (int)$request_detail["request_id"]; ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Request Time</span>
                            <b><?php echo htmlspecialchars($request_detail["request_time"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Related Ride</span>
                            <b>#<?php echo (int)$request_detail["ride_id"]; ?></b>
                        </div>
                    </div>

                    <div class="detail-card">
                        <div class="card-title"><span>Passenger</span></div>

                        <div class="detail-row">
                            <span>Name</span>
                            <b><?php echo htmlspecialchars($request_detail["passenger_name"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Student ID</span>
                            <b><?php echo htmlspecialchars($request_detail["passenger_student_id"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Email</span>
                            <b><?php echo htmlspecialchars($request_detail["passenger_email"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Phone</span>
                            <b><?php echo htmlspecialchars($request_detail["passenger_phone"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Department</span>
                            <b><?php echo htmlspecialchars($request_detail["passenger_department"]); ?></b>
                        </div>
                    </div>

                    <div class="detail-card">
                        <div class="card-title"><span>Related Ride</span></div>

                        <div class="detail-row">
                            <span>Driver</span>
                            <b><?php echo htmlspecialchars($request_detail["driver_name"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Route</span>
                            <b>
                                <?php echo htmlspecialchars($request_detail["start_location"]); ?>
                                →
                                <?php echo htmlspecialchars($request_detail["destination"]); ?>
                            </b>
                        </div>
                        <div class="detail-row">
                            <span>Date</span>
                            <b><?php echo htmlspecialchars($request_detail["ride_date"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Time</span>
                            <b><?php echo htmlspecialchars($request_detail["departure_time"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Available Seats</span>
                            <b><?php echo (int)$request_detail["available_seats"]; ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Women Only</span>
                            <b><?php echo htmlspecialchars($request_detail["women_only"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Ride Status</span>
                            <b><?php echo htmlspecialchars($request_detail["ride_status"]); ?></b>
                        </div>
                    </div>

                    <div class="detail-card">
                        <div class="card-title"><span>Driver</span></div>

                        <div class="detail-row">
                            <span>Name</span>
                            <b><?php echo htmlspecialchars($request_detail["driver_name"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Student ID</span>
                            <b><?php echo htmlspecialchars($request_detail["driver_student_id"]); ?></b>
                        </div>
                        <div class="detail-row">
                            <span>Email</span>
                            <b><?php echo htmlspecialchars($request_detail["driver_email"]); ?></b>
                        </div>
                    </div>

                </div>

            <?php else: ?>

                <div class="page-heading">
                    <div>
                        <h1>Ride Requests</h1>
                        <p>Overview of all ride requests in the system</p>
                    </div>
                </div>

                <form method="get" class="filter-card">
                    <div class="search-box">
                        <label>Search Requests</label>
                        <input
                            type="text"
                            name="search"
                            value="<?php echo htmlspecialchars($search); ?>"
                            placeholder="Request ID, passenger, ride or driver"
                        >
                    </div>

                    <div class="status-box">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>

                            <?php if ($status_result): ?>
                                <?php while ($status = mysqli_fetch_assoc($status_result)): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($status["request_status"]); ?>"
                                        <?php echo ($status_filter === $status["request_status"]) ? "selected" : ""; ?>
                                    >
                                        <?php echo htmlspecialchars($status["request_status"]); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>

                        </select>
                    </div>

                    <button type="submit" class="search-btn">Search</button>
                    <a href="admin_requests.php" class="clear-btn">Clear</a>
                </form>

                <div class="table-card">

                    <div class="table-top">
                        <div>
                            <b>All Ride Requests</b>
                            <span><?php echo $total_requests; ?> request(s)</span>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Request ID</th>
                                    <th>Passenger</th>
                                    <th>Ride</th>
                                    <th>Driver</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if (mysqli_num_rows($request_result) > 0): ?>

                                <?php while ($request = mysqli_fetch_assoc($request_result)): ?>
                                    <tr>
                                        <td><b>#<?php echo (int)$request["request_id"]; ?></b></td>

                                        <td>
                                            <div class="person-name">
                                                <?php echo htmlspecialchars($request["passenger_name"]); ?>
                                            </div>
                                        </td>

                                        <td>
                                            <div class="ride-cell">
                                                <b>#<?php echo (int)$request["ride_id"]; ?></b>
                                                <small>
                                                    <?php echo htmlspecialchars($request["start_location"]); ?>
                                                    →
                                                    <?php echo htmlspecialchars($request["destination"]); ?>
                                                </small>
                                            </div>
                                        </td>

                                        <td><?php echo htmlspecialchars($request["driver_name"]); ?></td>

                                        <td>
                                            <span class="status <?php echo status_class($request["request_status"]); ?>">
                                                <?php echo htmlspecialchars($request["request_status"]); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <a
                                                href="admin_requests.php?view=<?php echo (int)$request["request_id"]; ?>"
                                                class="view-btn"
                                            >View</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">No ride requests found.</td>
                                </tr>
                            <?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <div>Showing page <?php echo $page; ?> of <?php echo $total_pages; ?></div>

                        <div class="page-links">
                            <?php if ($page > 1): ?>
                                <a href="<?php echo htmlspecialchars(request_page_link($page - 1)); ?>">‹</a>
                            <?php else: ?>
                                <span>‹</span>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a
                                    href="<?php echo htmlspecialchars(request_page_link($i)); ?>"
                                    class="<?php echo ($i === $page) ? "current" : ""; ?>"
                                >
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="<?php echo htmlspecialchars(request_page_link($page + 1)); ?>">›</a>
                            <?php else: ?>
                                <span>›</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>

        </section>

    </main>

</div>

</body>
</html>
