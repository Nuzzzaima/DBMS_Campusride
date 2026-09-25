<?php

session_start();
include "db.php";


/* CHECK LOGIN */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}


$userId = $_SESSION['user_id'];


/* GET USER INFORMATION */

$sql = "SELECT *
        FROM `USER`
        WHERE user_id = '$userId'
        LIMIT 1";

$result = mysqli_query($conn, $sql);

if (!$result) {

    die("Database error: " . mysqli_error($conn));

}

if (mysqli_num_rows($result) != 1) {

    session_destroy();

    header("Location: login.php");
    exit();

}


$user = mysqli_fetch_assoc($result);


/* USER INFORMATION */

$fullName = $user['full_name'];
$firstName = explode(' ', $fullName)[0];

$studentId = $user['student_id'];
$department = $user['department'];
$email = $user['university_email'];


/* PROFILE PICTURE */

$avatarUrl = "uploads/avatars/profile.png";

if (!empty($user['profile_picture'])) {

    if (file_exists(__DIR__ . "/" . $user['profile_picture'])) {

        $avatarUrl = $user['profile_picture'];

    } elseif (str_starts_with($user['profile_picture'], "http")) {

        $avatarUrl = $user['profile_picture'];

    }

}


/* CURRENT DATE */

$currentDate = date("l, j F Y");


/* =========================================================
   1. ACTIVE RIDES
   Rides created by the current user which are still active
   and have not passed yet.
   ========================================================= */

$active_sql = "SELECT COUNT(*) AS active_rides
               FROM ride
               WHERE user_id = $userId
               AND ride_status = 'Active'
               AND (
                    ride_date > CURRENT_DATE
                    OR (
                        ride_date = CURRENT_DATE
                        AND departure_time >= CURRENT_TIME
                    )
               )";

$active_result = mysqli_query($conn, $active_sql);
$active_data = mysqli_fetch_assoc($active_result);

$active_rides = $active_data['active_rides'];


/* =========================================================
   2. UPCOMING RIDES
   Rides created by the current user within the next 7 days.
   ========================================================= */

$upcoming_sql = "SELECT COUNT(*) AS upcoming_rides
                 FROM ride
                 WHERE user_id = $userId
                 AND ride_status = 'Active'
                 AND ride_date >= CURRENT_DATE
                 AND ride_date <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY)";

$upcoming_result = mysqli_query($conn, $upcoming_sql);
$upcoming_data = mysqli_fetch_assoc($upcoming_result);

$upcoming_rides = $upcoming_data['upcoming_rides'];


/* =========================================================
   3. COMPLETED RIDES
   ========================================================= */

$completed_sql = "SELECT COUNT(*) AS completed_rides
                  FROM ride
                  WHERE user_id = $userId
                  AND ride_status = 'Completed'";

$completed_result = mysqli_query($conn, $completed_sql);
$completed_data = mysqli_fetch_assoc($completed_result);

$completed_rides = $completed_data['completed_rides'];


/* =========================================================
   4. MONEY SAVED THIS MONTH
   Each completed passenger represents one shared fare.
   ========================================================= */

$saved_sql = "SELECT COALESCE(SUM(r.fare), 0) AS money_saved
              FROM ride r
              JOIN ride_request rr
              ON r.ride_id = rr.ride_id
              JOIN booking b
              ON rr.request_id = b.request_id
              WHERE r.user_id = $userId
              AND r.ride_status = 'Completed'
              AND b.booking_status = 'Completed'
              AND MONTH(r.ride_date) = MONTH(CURRENT_DATE)
              AND YEAR(r.ride_date) = YEAR(CURRENT_DATE)";

$saved_result = mysqli_query($conn, $saved_sql);
$saved_data = mysqli_fetch_assoc($saved_result);

$money_saved = $saved_data['money_saved'];


/* =========================================================
   5. UPCOMING BOOKED RIDE
   Find the next confirmed booking made by this user.
   ========================================================= */

$upcoming_ride_sql = "SELECT
                        r.*,
                        u.full_name,
                        u.profile_picture
                      FROM booking b
                      JOIN ride_request rr
                      ON b.request_id = rr.request_id
                      JOIN ride r
                      ON rr.ride_id = r.ride_id
                      JOIN `USER` u
                      ON r.user_id = u.user_id
                      WHERE rr.user_id = $userId
                      AND b.booking_status = 'Confirmed'
                      AND r.ride_status = 'Active'
                      AND (
                          r.ride_date > CURRENT_DATE
                          OR (
                              r.ride_date = CURRENT_DATE
                              AND r.departure_time >= CURRENT_TIME
                          )
                      )
                      ORDER BY r.ride_date ASC, r.departure_time ASC
                      LIMIT 1";

$upcoming_ride_result = mysqli_query($conn, $upcoming_ride_sql);

$upcoming_ride = mysqli_fetch_assoc($upcoming_ride_result);


/* =========================================================
   6. DRIVER PROFILE PICTURE
   ========================================================= */

$driverAvatar = "uploads/avatars/profile.png";

if ($upcoming_ride && !empty($upcoming_ride['profile_picture'])) {

    if (file_exists(__DIR__ . "/" . $upcoming_ride['profile_picture'])) {

        $driverAvatar = $upcoming_ride['profile_picture'];

    } elseif (str_starts_with($upcoming_ride['profile_picture'], "http")) {

        $driverAvatar = $upcoming_ride['profile_picture'];

    }

}


/* =========================================================
   7. UPCOMING RIDE DATE/TIME
   ========================================================= */

$upcomingDateText = "";

if ($upcoming_ride) {

    $rideDateTime = strtotime(
        $upcoming_ride['ride_date'] . " " .
        $upcoming_ride['departure_time']
    );

    $upcomingDateText = date("D, j M g:i A", $rideDateTime);

}


/* =========================================================
   8. RECENT ACTIVITY
   Get the user's most recent completed rides.
   ========================================================= */

$activity_sql = "SELECT
                    r.ride_id,
                    r.destination,
                    r.ride_date,
                    r.departure_time,
                    r.ride_status
                 FROM ride r
                 WHERE r.user_id = $userId
                 AND r.ride_status = 'Completed'
                 ORDER BY r.ride_date DESC, r.departure_time DESC
                 LIMIT 3";

$activity_result = mysqli_query($conn, $activity_sql);


/* =========================================================
   9. RECENT BOOKINGS
   ========================================================= */

$booking_activity_sql = "SELECT
                            b.booking_id,
                            b.booking_status,
                            b.booking_date,
                            r.destination
                         FROM booking b
                         JOIN ride_request rr
                         ON b.request_id = rr.request_id
                         JOIN ride r
                         ON rr.ride_id = r.ride_id
                         WHERE rr.user_id = $userId
                         ORDER BY b.booking_date DESC
                         LIMIT 2";

$booking_activity_result = mysqli_query($conn, $booking_activity_sql);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard - CampusRide UIU</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: #f8fafc;
            color: #0f172a;
            display: grid;
            grid-template-columns: 197px 1fr;
            min-height: 100vh;
            margin: 0;
        }

        .main-wrapper {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 100vh;
            overflow-y: auto;
        }

        .topbar {
            padding: 22px 36px 14px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            background: #f8fafc;
        }

        .page-title h1 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .page-title span {
            font-size: 12.5px;
            color: #64748b;
            font-weight: 500;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notif-pill {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            text-decoration: none;
            position: relative;
        }

        .notif-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #7c3aed;
            position: absolute;
            top: 9px;
            right: 9px;
        }

        .header-profile {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            padding: 4px 12px 4px 4px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
            text-decoration: none;
        }

        .header-profile img {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            object-fit: cover;
        }

        .header-profile span {
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .content-area {
            padding: 10px 36px 36px;
            max-width: 1240px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .welcome-card {
            background: #7c3aed;
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            border-radius: 20px;
            padding: 28px 32px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.25);
        }

        .welcome-text .greeting {
            font-size: 12.5px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 4px;
        }

        .welcome-text h2 {
            font-size: 24px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
        }

        .welcome-text .student-meta {
            font-size: 12px;
            font-weight: 500;
            opacity: 0.85;
        }

        .welcome-avatar-wrapper {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.9);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }

        .welcome-avatar-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 18px 20px;
            border: 1px solid #edf2f7;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .icon-pin {
            background: #f3f4f6;
            color: #64748b;
        }

        .icon-clock {
            background: #ecfeff;
            color: #0891b2;
        }

        .icon-check {
            background: #f0fdf4;
            color: #16a34a;
        }

        .icon-wallet {
            background: #fffbeb;
            color: #d97706;
        }

        .stat-info {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 11.5px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 4px;
        }

        .stat-num {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
            margin-bottom: 4px;
        }

        .stat-sub {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .action-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 20px 24px;
            border: 1px solid #edf2f7;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.04);
        }

        .action-icon {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: #f5f3ff;
            color: #7c3aed;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .action-info h3 {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .action-info p {
            font-size: 12.5px;
            color: #64748b;
            font-weight: 500;
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1.6fr 1fr;
            gap: 20px;
            align-items: start;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }

        .section-header h3 {
            font-size: 15.5px;
            font-weight: 800;
            color: #0f172a;
        }

        .view-all-link {
            font-size: 12px;
            font-weight: 700;
            color: #7c3aed;
            text-decoration: none;
        }

        .upcoming-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 22px;
            border: 1px solid #edf2f7;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .driver-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .driver-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .driver-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            object-fit: cover;
        }

        .driver-name {
            font-size: 14px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
        }

        .driver-stars {
            color: #f59e0b;
            font-size: 12px;
        }

        .time-badge {
            background: #f5f3ff;
            color: #7c3aed;
            font-size: 11.5px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 8px;
        }

        .route-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 22px;
            padding-left: 6px;
        }

        .route-points {
            position: relative;
            padding-left: 20px;
        }

        .route-points::before {
            content: "";
            position: absolute;
            left: 4px;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: #e2e8f0;
        }

        .point-dot-from {
            position: absolute;
            left: 1px;
            top: 4px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #7c3aed;
        }

        .point-dot-to {
            position: absolute;
            left: 1px;
            bottom: 4px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #7c3aed;
        }

        .point-label {
            font-size: 10px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .point-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
        }

        .point-gap {
            height: 18px;
        }

        .route-fare-box {
            text-align: right;
        }

        .fare-amount {
            font-size: 26px;
            font-weight: 800;
            color: #7c3aed;
            line-height: 1;
        }

        .fare-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
            font-weight: 500;
        }

        .tags-row {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            padding-top: 14px;
            border-top: 1px solid #f8fafc;
        }

        .tag-badge-pink {
            background: #fff1f2;
            color: #e11d48;
            font-size: 10.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 6px;
        }

        .activity-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 8px 18px;
            border: 1px solid #edf2f7;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 0;
            border-bottom: 1px solid #f8fafc;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
            font-weight: 800;
        }

        .act-green {
            background: #ecfdf5;
            color: #10b981;
        }

        .act-gold {
            background: #fffbeb;
            color: #f59e0b;
        }

        .act-purple {
            background: #f5f3ff;
            color: #7c3aed;
        }

        .act-blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .activity-details h4 {
            font-size: 12.5px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 2px;
        }

        .activity-details span {
            font-size: 10.5px;
            color: #94a3b8;
            font-weight: 500;
        }

    </style>

</head>

<body>


<!-- SIDEBAR -->

<?php include __DIR__ . '/includes/sidebar.php'; ?>


<!-- MAIN WORKSPACE -->

<div class="main-wrapper">


    <!-- TOPBAR -->

    <header class="topbar">

        <div class="page-title">

            <h1>Dashboard</h1>

            <span><?= htmlspecialchars($currentDate) ?></span>

        </div>


        <div class="topbar-right">

            <a href="notifications.php" class="notif-pill" title="Notifications">

                <svg width="18"
                     height="18"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2">

                    <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                    <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>

                </svg>

                <span class="notif-dot"></span>

            </a>


            <a href="profile.php" class="header-profile" title="View Profile">

                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">

                <span><?= htmlspecialchars($firstName) ?></span>

            </a>

        </div>

    </header>


    <!-- DASHBOARD CONTENT -->

    <div class="content-area">


        <!-- WELCOME CARD -->

        <div class="welcome-card">

            <div class="welcome-text">

                <div class="greeting">
                    Good morning,
                </div>

                <h2>
                    <?= htmlspecialchars($fullName) ?> 👋
                </h2>

                <div class="student-meta">

                    <?= htmlspecialchars($department) ?>
                    · Student ID:
                    <?= htmlspecialchars($studentId) ?>

                </div>

            </div>


            <div class="welcome-avatar-wrapper">

                <img src="<?= htmlspecialchars($avatarUrl) ?>"
                     alt="<?= htmlspecialchars($fullName) ?>">

            </div>

        </div>


        <!-- FOUR STAT CARDS -->

        <div class="stats-grid">


            <!-- ACTIVE RIDES -->

            <div class="stat-card">

                <div class="stat-icon icon-pin">

                    <svg width="20"
                         height="20"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                        <circle cx="12" cy="10" r="3"/>

                    </svg>

                </div>

                <div class="stat-info">

                    <span class="stat-label">
                        Active Rides
                    </span>

                    <span class="stat-num">
                        <?= $active_rides ?>
                    </span>

                    <span class="stat-sub">
                        Right now
                    </span>

                </div>

            </div>


            <!-- UPCOMING RIDES -->

            <div class="stat-card">

                <div class="stat-icon icon-clock">

                    <svg width="20"
                         height="20"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>

                    </svg>

                </div>

                <div class="stat-info">

                    <span class="stat-label">
                        Upcoming Rides
                    </span>

                    <span class="stat-num">
                        <?= $upcoming_rides ?>
                    </span>

                    <span class="stat-sub">
                        This week
                    </span>

                </div>

            </div>


            <!-- COMPLETED RIDES -->

            <div class="stat-card">

                <div class="stat-icon icon-check">

                    <svg width="20"
                         height="20"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <path d="M20 6 9 17l-5-5"/>

                    </svg>

                </div>

                <div class="stat-info">

                    <span class="stat-label">
                        Completed Rides
                    </span>

                    <span class="stat-num">
                        <?= $completed_rides ?>
                    </span>

                    <span class="stat-sub">
                        All time
                    </span>

                </div>

            </div>


            <!-- MONEY SAVED -->

            <div class="stat-card">

                <div class="stat-icon icon-wallet">

                    <svg width="20"
                         height="20"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/>

                        <path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>

                    </svg>

                </div>

                <div class="stat-info">

                    <span class="stat-label">
                        Money Saved
                    </span>

                    <span class="stat-num">
                        ৳<?= number_format($money_saved, 0) ?>
                    </span>

                    <span class="stat-sub">
                        This month
                    </span>

                </div>

            </div>

        </div>


        <!-- QUICK ACTIONS -->

        <div class="actions-grid">

            <a href="find_ride.php" class="action-card">

                <div class="action-icon">

                    <svg width="22"
                         height="22"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <circle cx="11" cy="11" r="8"/>
                        <path d="m21 21-4.3-4.3"/>

                    </svg>

                </div>

                <div class="action-info">

                    <h3>Find a Ride</h3>

                    <p>
                        Browse available rides on your route
                    </p>

                </div>

            </a>


            <a href="create_ride.php" class="action-card">

                <div class="action-icon">

                    <svg width="22"
                         height="22"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2">

                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v8"/>
                        <path d="M8 12h8"/>

                    </svg>

                </div>

                <div class="action-info">

                    <h3>Create a Ride</h3>

                    <p>
                        Offer seats to fellow students
                    </p>

                </div>

            </a>

        </div>


        <!-- BOTTOM SECTION -->

        <div class="bottom-grid">


            <!-- UPCOMING RIDE -->

            <div>

                <div class="section-header">

                    <h3>
                        Upcoming Ride
                    </h3>

                    <a href="bookings.php" class="view-all-link">
                        View All
                    </a>

                </div>


                <div class="upcoming-card">


                    <?php if ($upcoming_ride) { ?>


                        <div class="driver-header">

                            <div class="driver-info">

                                <img src="<?= htmlspecialchars($driverAvatar) ?>"
                                     alt="Driver"
                                     class="driver-avatar">

                                <div>

                                    <div class="driver-name">

                                        <?= htmlspecialchars($upcoming_ride['full_name']) ?>

                                    </div>

                                    <div class="driver-stars">
                                        ★★★★★
                                    </div>

                                </div>

                            </div>


                            <span class="time-badge">

                                <?= htmlspecialchars($upcomingDateText) ?>

                            </span>

                        </div>


                        <!-- ROUTE -->

                        <div class="route-timeline">

                            <div class="route-points">


                                <span class="point-dot-from"></span>

                                <div class="point-label">
                                    From
                                </div>

                                <div class="point-name">

                                    <?= htmlspecialchars($upcoming_ride['start_location']) ?>

                                </div>


                                <div class="point-gap"></div>


                                <span class="point-dot-to"></span>

                                <div class="point-label">
                                    To
                                </div>

                                <div class="point-name">

                                    <?= htmlspecialchars($upcoming_ride['destination']) ?>

                                </div>


                            </div>


                            <div class="route-fare-box">

                                <div class="fare-amount">

                                    ৳<?= number_format($upcoming_ride['fare'], 0) ?>

                                </div>

                                <div class="fare-sub">
                                    per person
                                </div>

                            </div>

                        </div>


                        <!-- RIDE TAGS -->

                        <div class="tags-row">

                            <span>
                                🚘 <?= htmlspecialchars($upcoming_ride['ride_type']) ?>
                            </span>

                            <span>
                                👥 <?= htmlspecialchars($upcoming_ride['available_seats']) ?> seats left
                            </span>


                            <?php if ($upcoming_ride['women_only'] == 'Yes') { ?>

                                <span class="tag-badge-pink">
                                    Women Only
                                </span>

                            <?php } ?>


                        </div>


                    <?php } else { ?>


                        <div style="padding: 30px; text-align: center; color: #94a3b8;">

                            <strong style="display:block; color:#64748b; margin-bottom:5px;">
                                No upcoming ride
                            </strong>

                            You do not have a confirmed upcoming booking.

                        </div>


                    <?php } ?>


                </div>

            </div>


            <!-- RECENT ACTIVITY -->

            <div>

                <div class="section-header">

                    <h3>
                        Recent Activity
                    </h3>

                </div>


                <div class="activity-card">


                    <?php

                    $hasActivity = false;


                    /* COMPLETED RIDES */

                    while ($activity = mysqli_fetch_assoc($activity_result)) {

                        $hasActivity = true;

                        $activityDate = date(
                            "d M Y",
                            strtotime($activity['ride_date'])
                        );

                    ?>

                        <div class="activity-item">

                            <div class="activity-icon act-green">
                                ✓
                            </div>

                            <div class="activity-details">

                                <h4>
                                    Ride completed to
                                    <?= htmlspecialchars($activity['destination']) ?>
                                </h4>

                                <span>
                                    <?= $activityDate ?>
                                </span>

                            </div>

                        </div>

                    <?php
                    }


                    /* BOOKINGS */

                    while ($booking = mysqli_fetch_assoc($booking_activity_result)) {

                        $hasActivity = true;

                        $bookingDate = date(
                            "d M Y",
                            strtotime($booking['booking_date'])
                        );

                    ?>

                        <div class="activity-item">

                            <div class="activity-icon act-purple">
                                ★
                            </div>

                            <div class="activity-details">

                                <h4>
                                    Booking #<?= htmlspecialchars($booking['booking_id']) ?>
                                    <?= strtolower($booking['booking_status']) ?>
                                </h4>

                                <span>
                                    <?= $bookingDate ?>
                                </span>

                            </div>

                        </div>

                    <?php
                    }


                    if (!$hasActivity) {

                    ?>

                        <div class="activity-item">

                            <div class="activity-icon act-blue">
                                -
                            </div>

                            <div class="activity-details">

                                <h4>
                                    No recent activity
                                </h4>

                                <span>
                                    Your recent ride activity will appear here.
                                </span>

                            </div>

                        </div>

                    <?php } ?>


                </div>

            </div>


        </div>


    </div>

</div>

</body>
</html>
