<?php
session_start();

include "db.php";

if (!isset($_SESSION["admin_user_id"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin_name"] ?? "Admin";
$admin_email = $_SESSION["admin_email"] ?? "";


/* GET USER ID */

$user_id = (int)($_GET["id"] ?? 0);

if ($user_id <= 0) {
    header("Location: admin_users.php");
    exit();
}

$edit_mode = isset($_GET["edit"]) && $_GET["edit"] == "1";


/* UPDATE USER */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["save_user"])) {

    $full_name = mysqli_real_escape_string(
        $conn,
        trim($_POST["full_name"] ?? "")
    );

    $student_id = mysqli_real_escape_string(
        $conn,
        trim($_POST["student_id"] ?? "")
    );

    $email = mysqli_real_escape_string(
        $conn,
        trim($_POST["university_email"] ?? "")
    );

    $department = mysqli_real_escape_string(
        $conn,
        trim($_POST["department"] ?? "")
    );

    $phone = mysqli_real_escape_string(
        $conn,
        trim($_POST["phone"] ?? "")
    );

    $status = mysqli_real_escape_string(
        $conn,
        trim($_POST["account_status"] ?? "")
    );


    $allowed_result = mysqli_query(
        $conn,
        "SELECT status_name
         FROM user_account_status
         ORDER BY status_id"
    );

    $allowed_statuses = [];

    while ($status_row = mysqli_fetch_assoc($allowed_result)) {
        $allowed_statuses[] = $status_row["status_name"];
    }


    if (
        $full_name != "" &&
        $student_id != "" &&
        $email != "" &&
        $department != "" &&
        $phone != "" &&
        in_array($status, $allowed_statuses, true)
    ) {

        mysqli_query(
            $conn,
            "UPDATE `USER`
             SET
                full_name = '$full_name',
                student_id = '$student_id',
                university_email = '$email',
                department = '$department',
                phone = '$phone',
                account_status = '$status'
             WHERE user_id = $user_id"
        );
    }

    header(
        "Location: admin_user_details.php?id=" . $user_id
    );

    exit();
}


/* GET USER */

$user_sql = "SELECT
                user_id,
                student_id,
                full_name,
                university_email,
                profile_picture,
                department,
                phone,
                gender,
                created_at,
                account_status
             FROM `USER`
             WHERE user_id = $user_id
             LIMIT 1";

$user_result = mysqli_query($conn, $user_sql);

if (mysqli_num_rows($user_result) == 0) {
    header("Location: admin_users.php");
    exit();
}

$user = mysqli_fetch_assoc($user_result);


/* RIDE COUNT */

$ride_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ride
     WHERE user_id = $user_id"
);

$ride_count = mysqli_fetch_assoc($ride_result)["total"];


$active_ride_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ride
     WHERE user_id = $user_id
     AND ride_status = 'Active'
     AND available_seats > 0"
);

$active_ride_count = mysqli_fetch_assoc($active_ride_result)["total"];


/* REQUEST COUNT HANDLED FOR THIS USER'S RIDES */

$request_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM ride_request
     JOIN ride
       ON ride_request.ride_id = ride.ride_id
     WHERE ride.user_id = $user_id"
);

$request_count = mysqli_fetch_assoc($request_result)["total"];


/* ACCEPTED / COMPLETED BOOKINGS FOR THIS USER'S RIDES */

$booking_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM booking
     JOIN ride_request
       ON booking.request_id = ride_request.request_id
     JOIN ride
       ON ride_request.ride_id = ride.ride_id
     WHERE ride.user_id = $user_id
     AND booking.booking_status IN ('Confirmed', 'Completed')"
);

$booking_count = mysqli_fetch_assoc($booking_result)["total"];


/* RATING */

$rating_result = mysqli_query(
    $conn,
    "SELECT
        AVG(stars) AS average_stars,
        COUNT(*) AS total_reviews
     FROM rating
     WHERE reviewee_id = $user_id"
);

$rating = mysqli_fetch_assoc($rating_result);

if ($rating["average_stars"] !== null) {
    $average_rating = number_format(
        $rating["average_stars"],
        1
    );
} else {
    $average_rating = "0.0";
}

$total_reviews = $rating["total_reviews"];


/* PROFILE PICTURE */

$picture = $user["profile_picture"] ?? "";

$picture_exists = false;

if ($picture != "") {

    $picture_exists = file_exists(
        __DIR__ . "/" . $picture
    );
}


/* LOGOUT */

if (isset($_GET["logout"])) {

    session_unset();
    session_destroy();

    header("Location: admin_login.php");
    exit();
}


/* STATUS CLASS */

$status_class = "active";

if ($user["account_status"] == "Pending Verification") {
    $status_class = "pending";
} elseif ($user["account_status"] == "Suspended") {
    $status_class = "suspended";
}

?>

<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>User Details - CampusRide</title>

    <link rel="stylesheet" href="admin_user_details.css">

</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->

    <div class="sidebar">

        <div class="logo">

            <div class="logo-icon">
                🚗
            </div>

            <div>
                <b>CampusRide</b>
                <small>Operations Console</small>
            </div>

        </div>


        <div class="menu">

            <a href="admin_dashboard.php">
                Dashboard
            </a>

            <a href="admin_users.php" class="active">
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

                    <b>
                        <?php echo htmlspecialchars($admin_name); ?>
                    </b>

                    <small>
                        <?php echo htmlspecialchars($admin_email); ?>
                    </small>

                </div>

                <a
                    href="admin_user_details.php?id=<?php echo $user_id; ?>&logout=1"
                    class="logout"
                >
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

            <!-- BREADCRUMB -->

            <div class="breadcrumb">

                <a href="admin_users.php">
                    Users
                </a>

                <span>›</span>

                <span>
                    <?php echo htmlspecialchars($user["full_name"]); ?>
                </span>

                <span>›</span>

                <span class="current">
                    User Details
                </span>

            </div>


            <!-- TITLE -->

            <div class="detail-title">

                <div>

                    <a
                        href="admin_users.php"
                        class="back"
                    >
                        ←
                    </a>

                    <h1>User Details</h1>

                </div>


                <div class="detail-actions">

                    <a
                        href="admin_user_details.php?id=<?php echo $user_id; ?>&edit=1"
                        class="edit-btn"
                    >
                        ✎ Edit Record
                    </a>


                    <form
                        method="POST"
                        action="admin_user_details.php?id=<?php echo $user_id; ?>"
                    >

                        <select
                            name="account_status"
                            class="status-select <?php echo $status_class; ?>"
                        >

                            <?php
                            $status_options = mysqli_query(
                                $conn,
                                "SELECT status_name
                                 FROM user_account_status
                                 ORDER BY status_id"
                            );
                            ?>

                            <?php while ($status_option = mysqli_fetch_assoc($status_options)) { ?>

                                <option
                                    value="<?php echo htmlspecialchars($status_option["status_name"]); ?>"
                                    <?php
                                    if (
                                        $status_option["status_name"]
                                        ==
                                        $user["account_status"]
                                    ) {
                                        echo "selected";
                                    }
                                    ?>
                                >
                                    Status: <?php echo htmlspecialchars($status_option["status_name"]); ?>
                                </option>

                            <?php } ?>

                        </select>


                        <input
                            type="hidden"
                            name="full_name"
                            value="<?php echo htmlspecialchars($user["full_name"]); ?>"
                        >

                        <input
                            type="hidden"
                            name="student_id"
                            value="<?php echo htmlspecialchars($user["student_id"]); ?>"
                        >

                        <input
                            type="hidden"
                            name="university_email"
                            value="<?php echo htmlspecialchars($user["university_email"]); ?>"
                        >

                        <input
                            type="hidden"
                            name="department"
                            value="<?php echo htmlspecialchars($user["department"]); ?>"
                        >

                        <input
                            type="hidden"
                            name="phone"
                            value="<?php echo htmlspecialchars($user["phone"]); ?>"
                        >

                        <button
                            type="submit"
                            name="save_user"
                            class="save-btn"
                        >
                            Save Changes
                        </button>

                    </form>

                </div>

            </div>


            <!-- USER CARD -->

            <div class="profile-card">

                <div class="profile-top">

                    <div class="profile-main">

                        <div class="large-pic">

                            <?php if ($picture_exists) { ?>

                                <img
                                    src="<?php echo htmlspecialchars($picture); ?>"
                                    alt=""
                                >

                            <?php } else { ?>

                                <?php
                                echo strtoupper(
                                    substr($user["full_name"], 0, 1)
                                );
                                ?>

                            <?php } ?>

                            <span class="online-dot"></span>

                        </div>


                        <div class="profile-name">

                            <h2>
                                <?php
                                echo htmlspecialchars(
                                    $user["full_name"]
                                );
                                ?>
                            </h2>

                            <span class="verified">
                                ✓ UIU Email
                            </span>

                            <p>
                                Department of
                                <?php
                                echo htmlspecialchars(
                                    $user["department"]
                                );
                                ?>
                                • CampusRide User
                            </p>

                        </div>

                    </div>


                    <div class="rating-box">

                        <strong>
                            ★ <?php echo $average_rating; ?>
                        </strong>

                        <span>
                            <?php
                            echo number_format($total_reviews);
                            ?>
                            Passenger Reviews
                        </span>

                    </div>

                </div>


                <div class="profile-info">

                    <div>
                        <small>FULL NAME</small>
                        <b>
                            <?php
                            echo htmlspecialchars(
                                $user["full_name"]
                            );
                            ?>
                        </b>
                    </div>


                    <div>
                        <small>STUDENT ID</small>
                        <b>
                            <?php
                            echo htmlspecialchars(
                                $user["student_id"]
                            );
                            ?>
                        </b>
                    </div>


                    <div>
                        <small>UNIVERSITY OFFICIAL EMAIL</small>
                        <b class="purple-text">
                            <?php
                            echo htmlspecialchars(
                                $user["university_email"]
                            );
                            ?>
                        </b>
                    </div>


                    <div>
                        <small>DEPARTMENT</small>
                        <b>
                            <?php
                            echo htmlspecialchars(
                                $user["department"]
                            );
                            ?>
                        </b>
                    </div>


                    <div>
                        <small>PHONE</small>
                        <b>
                            <?php
                            echo htmlspecialchars(
                                $user["phone"]
                            );
                            ?>
                        </b>
                    </div>


                    <div>
                        <small>REGISTRATION DATE</small>
                        <b>
                            <?php
                            echo date(
                                "F j, Y",
                                strtotime($user["created_at"])
                            );
                            ?>
                        </b>
                    </div>

                </div>

            </div>


            <!-- STATS -->

            <div class="detail-stats">

                <div class="detail-stat">

                    <div>

                        <small>NUMBER OF RIDES CREATED</small>

                        <strong>
                            <?php echo number_format($ride_count); ?>
                        </strong>

                        <span>
                            rides created
                        </span>

                        <p>
                            ↗
                            <?php
                            echo number_format($active_ride_count);
                            ?>
                            active rides with available seats
                        </p>

                    </div>


                    <div class="stat-icon">
                        ▣
                    </div>

                </div>


                <div class="detail-stat">

                    <div>

                        <small>NUMBER OF BOOKINGS & REQUESTS</small>

                        <strong>
                            <?php echo number_format($request_count); ?>
                        </strong>

                        <span>
                            requests handled
                        </span>

                        <p>
                            ✓
                            <?php
                            echo number_format($booking_count);
                            ?>
                            accepted passenger bookings
                        </p>

                    </div>


                    <div class="stat-icon">
                        ♙
                    </div>

                </div>

            </div>


            <?php if ($edit_mode) { ?>

            <!-- EDIT RECORD -->

            <div class="edit-box">

                <div class="edit-title">
                    Edit Record
                </div>

                <form
                    method="POST"
                    action="admin_user_details.php?id=<?php echo $user_id; ?>"
                >

                    <div class="edit-grid">

                        <div>
                            <label>Full Name</label>
                            <input
                                type="text"
                                name="full_name"
                                value="<?php echo htmlspecialchars($user["full_name"]); ?>"
                                required
                            >
                        </div>


                        <div>
                            <label>Student ID</label>
                            <input
                                type="text"
                                name="student_id"
                                value="<?php echo htmlspecialchars($user["student_id"]); ?>"
                                required
                            >
                        </div>


                        <div>
                            <label>University Email</label>
                            <input
                                type="email"
                                name="university_email"
                                value="<?php echo htmlspecialchars($user["university_email"]); ?>"
                                required
                            >
                        </div>


                        <div>
                            <label>Department</label>
                            <input
                                type="text"
                                name="department"
                                value="<?php echo htmlspecialchars($user["department"]); ?>"
                                required
                            >
                        </div>


                        <div>
                            <label>Phone</label>
                            <input
                                type="text"
                                name="phone"
                                value="<?php echo htmlspecialchars($user["phone"]); ?>"
                                required
                            >
                        </div>


                        <div>
                            <label>Account Status</label>

                            <select name="account_status">

                                <?php
                                $edit_statuses = mysqli_query(
                                    $conn,
                                    "SELECT status_name
                                     FROM user_account_status
                                     ORDER BY status_id"
                                );
                                ?>

                                <?php while ($edit_status = mysqli_fetch_assoc($edit_statuses)) { ?>

                                    <option
                                        value="<?php echo htmlspecialchars($edit_status["status_name"]); ?>"
                                        <?php
                                        if (
                                            $edit_status["status_name"]
                                            ==
                                            $user["account_status"]
                                        ) {
                                            echo "selected";
                                        }
                                        ?>
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $edit_status["status_name"]
                                        );
                                        ?>
                                    </option>

                                <?php } ?>

                            </select>

                        </div>

                    </div>


                    <button
                        type="submit"
                        name="save_user"
                        class="save-edit-btn"
                    >
                        Save Changes
                    </button>

                </form>

            </div>

            <?php } ?>

        </div>

    </div>

</div>

</body>
</html>
