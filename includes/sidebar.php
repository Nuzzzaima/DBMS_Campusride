<?php

// Start session only if it is not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Current page
$currentPage = basename($_SERVER['PHP_SELF']);

?>

<style>

/* ==============================
   CAMPUSRIDE SHARED SIDEBAR
   ============================== */

.sidebar {
    width: 197px !important;
    min-height: 100vh !important;
    background-color: white !important;
    border-right: 1px solid #ded9f3 !important;

    left: 0 !important;
    top: 0 !important;

    z-index: 1000 !important;
}

/* LOGO */

.sidebar .logo {
    padding: 20px 15px !important;
}

.sidebar .logo b {
    display: block !important;
    font-family: Arial, sans-serif !important;
    font-size: 16px !important;
    color: #08194f !important;
}

.sidebar .logo small {
    display: block !important;
    margin-top: 4px !important;
    font-family: Arial, sans-serif !important;
    font-size: 10px !important;
    color: #8790a5 !important;
}

/* MENU */

.sidebar .menu ul {
    list-style: none !important;
    margin: 0 !important;
    padding: 12px 10px !important;
}

.sidebar .menu li {
    display: block !important;
    margin-bottom: 4px !important;
}

.sidebar .menu a {
    display: block !important;
    width: 100% !important;
    padding: 11px 12px !important;

    box-sizing: border-box !important;

    text-decoration: none !important;

    font-family: Arial, sans-serif !important;
    font-size: 13px !important;

    color: #5f6375 !important;

    border-radius: 7px !important;
}

/* HOVER */

.sidebar .menu a:hover {
    background-color: #f1eaff !important;
    color: #8238ee !important;
}

/* ACTIVE PAGE */

.sidebar .menu a.active {
    background-color: #8238ee !important;
    color: white !important;
}

</style>

<div class="sidebar">

    <div class="logo">
        <b>CampusRide</b>
        <small>UIU Student Portal</small>
    </div>

    <div class="menu">
        <ul>

            <li>
                <a href="dashboard.php"
                   class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    Dashboard
                </a>
            </li>

            <li>
                <a href="find_ride.php"
                   class="<?= $currentPage === 'find_ride.php' ? 'active' : '' ?>">
                    Find a Ride
                </a>
            </li>

            <li>
                <a href="create_ride.php"
                   class="<?= $currentPage === 'create_ride.php' ? 'active' : '' ?>">
                    Create a Ride
                </a>
            </li>

            <li>
                <a href="my_rides.php"
                   class="<?= $currentPage === 'my_rides.php' ? 'active' : '' ?>">
                    My Rides
                </a>
            </li>

            <li>
                <a href="ride_summary.php"
                   class="<?= $currentPage === 'ride_summary.php' ? 'active' : '' ?>">
                    Ride Summary
                </a>
            </li>

            <li>
                <a href="ride_requests.php"
                   class="<?= $currentPage === 'ride_requests.php' ? 'active' : '' ?>">
                    Ride Requests
                </a>
            </li>
            <li>
                <a href="bookings.php"
                   class="<?= $currentPage === 'bookings.php' ? 'active' : '' ?>">
                    Bookings
                </a>
            </li>
            <li>
                <a href="vehicles.php"
                   class="<?= $currentPage === 'vehicles.php' ? 'active' : '' ?>">
                    Vehicles
                </a>
            </li>

            <li>
                <a href="profile.php"
                   class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                    Profile
                </a>
            </li>

            <li>
                <a href="ratings.php"
                   class="<?= $currentPage === 'ratings.php' ? 'active' : '' ?>">
                    Ratings
                </a>
            </li>
           <li>
                <a href="my_savings.php"
                   class="<?= $currentPage === 'my_savings.php' ? 'active' : '' ?>">
                    Savings
                </a>
            </li>
            <li>
                <a href="sos.php"
                   class="<?= $currentPage === 'sos.php' ? 'active' : '' ?>">
                    SOS / Emergency
                </a>
            </li>
            
<li>
    <a href="logout.php">
        Logout
    </a>
</li>



        </ul>
    </div>

</div>
