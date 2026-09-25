<?php

session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$sql1 = "SELECT u.full_name,COUNT(r.ride_id) AS total_rides,COALESCE(SUM(r.fare), 0) AS total_fare,
COALESCE(AVG(r.fare), 0) AS average_fare FROM user u LEFT JOIN ride r ON u.user_id = r.user_id
AND r.ride_date < CURRENT_DATE WHERE u.user_id = $user_id GROUP BY u.user_id, u.full_name";

$sql2 = "SELECT ride_type, COUNT(*) AS ride_count FROM ride
WHERE user_id = $user_id AND ride_date < CURRENT_DATE GROUP BY ride_type HAVING COUNT(*) > 0
ORDER BY ride_count DESC LIMIT 1";

$result1 = mysqli_query($conn, $sql1);
$result2 = mysqli_query($conn, $sql2);

$row1 = mysqli_fetch_assoc($result1);
$row2 = mysqli_fetch_assoc($result2);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Summary</title>
    <link rel="stylesheet" href="css/ride_summary.css">
    <style>
body {
    display: grid !important;
    grid-template-columns: 197px 1fr !important;
    grid-template-areas: "sdbr main" !important;
    margin: 0 !important;
}

.sidebar {
    grid-area: sdbr !important;
    min-height: 100vh !important;
    background-color: white !important;
    border-right: 1px solid #ded9f3 !important;
}

.sidebar .logo {
    padding: 20px 15px !important;
}

.sidebar .logo b {
    display: block !important;
    font-size: 16px !important;
    color: #08194f !important;
}

.sidebar .logo small {
    display: block !important;
    margin-top: 4px !important;
    font-size: 10px !important;
    color: #8790a5 !important;
}

.sidebar ul {
    list-style: none !important;
    margin: 0 !important;
    padding: 12px 10px !important;
}

.sidebar li {
    display: block !important;
    margin-bottom: 4px !important;
}

.sidebar a {
    display: block !important;
    padding: 11px 12px !important;
    text-decoration: none !important;
    color: #5f6375 !important;
    font-size: 13px !important;
}

.main-content {
    grid-area: main !important;
    margin-left: 0 !important;
}
</style>
</head>
<body>
    <div class="sidebar">

    <div class="logo">
        <b>CampusRide</b>
        <small>UIU Student Portal</small>
    </div>

    <div class="menu">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="find_ride.php">Find a Ride</a></li>
            <li><a href="create_ride.php">Create a Ride</a></li>
            <li><a href="my_rides.php">My Rides</a></li>
            <li><a class="active">Ride Summary</a></li>
            <li><a href="ride_requests.php">Ride Requests</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="vehicles.php">Vehicles</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="ratings.php">Ratings & Reviews</a></li>
            <li><a href="my_savings.php">My Savings</a></li>
            <li><a href="#">Notifications</a></li>
            <li><a href="sos.php">SOS / Emergency</a></li>
              <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

</div>
<div class="main-content">
    <div class="summary-container">
        <div class="summary-header">
            <h1>Ride Summary</h1>
            <p>Welcome, <?php echo $row1['full_name']; ?>!</p>
        </div>
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Rides</h3>
                <p class="summary-value">
                    <?php echo $row1['total_rides']; ?>
                </p>
                <span>Completed rides</span>
            </div>
            <div class="summary-card">
                <h3>Total Fare</h3>
                <p class="summary-value">
                    <?php echo number_format($row1['total_fare'], 2); ?> Taka
                </p>
                <span>Total amount spent</span>
            </div>
            <div class="summary-card">
                <h3>Average Fare</h3>
                <p class="summary-value">
                    <?php echo number_format($row1['average_fare'], 2); ?> Taka
                </p>
                <span>Average fare per ride</span>
            </div>
            <div class="summary-card">
                <h3>Most Used Ride</h3>
                <?php if ($row2) { ?>
                    <p class="summary-value ride-type">
                        <?php echo $row2['ride_type']; ?>
                    </p>
                    <span>
                        Used <?php echo $row2['ride_count']; ?> time(s)
                    </span>

                <?php } else { ?>
                    <p class="summary-value ride-type">
                        No rides yet
                    </p>
                    <span>No completed rides found</span>
                <?php } ?>
            </div>
        </div>
        <a href="my_rides.php" class="back-button">Back to My Rides</a>

    </div>
</div>

</body>

</html>