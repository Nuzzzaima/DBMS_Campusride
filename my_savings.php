<?php

session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$completed_rides_sql="SELECT COUNT(*) AS complete FROM ride WHERE user_id=$user_id AND ride_status='Completed'";
$completed_rides_result=mysqli_query($conn,$completed_rides_sql);
$completed_rides=mysqli_fetch_assoc($completed_rides_result);
$numofcompletedrides=$completed_rides['complete'];

$passengers_sql=
"SELECT COUNT(rr.user_id) AS passenger
FROM ride r
JOIN ride_request rr ON r.ride_id=rr.ride_id
JOIN booking b ON rr.request_id=b.request_id
WHERE r.ride_status='Completed'
AND b.booking_status='Completed'
AND r.user_id=$user_id";

$passengers_result=mysqli_query($conn,$passengers_sql);
$passengers_assoc=mysqli_fetch_assoc($passengers_result);
$passengers=$passengers_assoc['passenger'];

$total_saved_sql = "SELECT SUM(r.fare) AS saved
FROM ride r
JOIN ride_request rr ON r.ride_id=rr.ride_id
JOIN booking b ON rr.request_id=b.request_id
WHERE r.ride_status='Completed'
AND b.booking_status='Completed'
AND r.user_id=$user_id";

$total_saved_result = mysqli_query($conn, $total_saved_sql);
$total_saved_data = mysqli_fetch_assoc($total_saved_result);
$total_saved = $total_saved_data['saved'];

$monthly_savings_sql = "SELECT SUM(r.fare) AS monthly_saved
FROM ride r
JOIN ride_request rr ON r.ride_id=rr.ride_id
JOIN booking b ON rr.request_id=b.request_id
WHERE r.ride_status='Completed'
AND b.booking_status='Completed'
AND r.user_id=$user_id
AND MONTH(r.ride_date)=MONTH(CURRENT_DATE)
AND YEAR(r.ride_date)=YEAR(CURRENT_DATE)";

$monthly_savings_result = mysqli_query($conn, $monthly_savings_sql);
$monthly_savings_data = mysqli_fetch_assoc($monthly_savings_result);
$monthly_savings = $monthly_savings_data['monthly_saved'];
$co2_saved = $numofcompletedrides * 1.2;
$fuel_saved = $numofcompletedrides * 0.4;
$solo_cost_sql = "SELECT SUM(r.fare * (
SELECT COUNT(*)
FROM ride_request rr2
 JOIN booking b2 ON rr2.request_id=b2.request_id
WHERE rr2.ride_id=r.ride_id
AND b2.booking_status='Completed'
) + r.fare) AS solo_cost
FROM ride r
WHERE r.ride_status='Completed'
AND r.user_id=$user_id";

$solo_cost_result = mysqli_query($conn, $solo_cost_sql);
$solo_cost_data = mysqli_fetch_assoc($solo_cost_result);
$solo_cost = $solo_cost_data['solo_cost'] ?? 0;
$shared_cost = $total_saved;
$saved_cost = $solo_cost - $shared_cost;
?>

<!DOCTYPE html>
<html>

<head>

    <title>My Savings - CampusRide</title>

    <link rel="stylesheet" href="css/my_savings.css">

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
            <li><a href="ride_summary.php">Ride Summary</a></li>
            <li><a href="ride_requests.php">Ride Requests</a></li>
            <li><a href="bookings.php">Bookings</a></li>
            <li><a href="vehicles.php">Vehicles</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="ratings.php">Ratings & Reviews</a></li>

            <li>
                <a href="my_savings.php" class="active">
                    My Savings
                </a>
            </li>

            <li><a href="sos.php">SOS / Emergency</a></li>
            <li><a href="logout.php">Logout</a></li>

        </ul>

    </div>

</div>


<div class="main-content">

    <div class="header">

        <div>

            <h2>My Savings</h2>

            <p>
                Track your savings and environmental impact
                through shared rides
            </p>

        </div>

    </div>


    <div class="container">


        <!-- SUMMARY CARDS -->

        <div class="summary-cards">


            <div class="saving-card">

                <div class="card-title">
                    Total Money Saved
                </div>

                <div class="card-value">
                    ৳ <span id="total-saved"><?php echo $total_saved; ?></span>
                </div>

                <div class="card-subtitle">
                    From shared rides
                </div>

            </div>


            <div class="saving-card">

                <div class="card-title">
                    Total Passengers
                </div>

                <div class="card-value">
                    <span id="total-passengers"><?php echo $passengers; ?></span>
                </div>

                <div class="card-subtitle">
                    Passengers shared with you
                </div>

            </div>


            <div class="saving-card">

                <div class="card-title">
                    Completed Rides
                </div>

                <div class="card-value">
                    <span id="completed-rides"><?php echo $numofcompletedrides;?></span>
                </div>

                <div class="card-subtitle">
                    Successfully completed
                </div>

            </div>


            <div class="saving-card">

                <div class="card-title">
                    Monthly Savings
                </div>

                <div class="card-value">
                    ৳ <span id="monthly-savings"><?php echo $monthly_savings; ?></span>
                </div>

                <div class="card-subtitle">
                    Saved this month
                </div>

            </div>


        </div>



        <!-- ENVIRONMENTAL IMPACT -->

        <div class="section">

            <div class="section-title">

                <h3>Environmental Impact</h3>

                <p>
                    Your contribution through shared transportation
                </p>

            </div>


            <div class="impact-cards">


                <div class="impact-card">

                    <div class="impact-label">
                        CO₂ Saved
                    </div>

                    <div class="impact-value">
                        <span id="co2-saved"><?php echo $co2_saved; ?></span>
                    </div>

                    <p>
                        Estimated carbon reduction
                    </p>

                </div>


                <div class="impact-card">

                    <div class="impact-label">
                        Fuel Saved
                    </div>

                    <div class="impact-value">
                       <span id="fuel-saved"><?php echo $fuel_saved; ?></span>L
                    </div>

                    <p>
                        Estimated fuel savings
                    </p>

                </div>


                <div class="impact-card">

                    <div class="impact-label">
                        Total Rides Shared
                    </div>

                    <div class="impact-value">
                        <span id="shared-rides"><?php echo $numofcompletedrides; ?></span>
                    </div>

                    <p>
                        Shared rides completed
                    </p>

                </div>


            </div>

        </div>



        <!-- SAVINGS BREAKDOWN -->

        <div class="section">

            <div class="section-title">

                <h3>Savings Breakdown</h3>

                <p>
                    Estimated cost comparison
                </p>

            </div>


            <div class="breakdown">


                <div class="breakdown-row">

                    <span>
                        Solo Ride Cost
                    </span>

                    <strong>
                        ৳ <span id="solo-cost"><?php echo $solo_cost; ?></span>
                    </strong>

                </div>


                <div class="breakdown-row">

                    <span>
                        Shared Ride Cost
                    </span>

                    <strong>
                        ৳ <span id="shared-cost"><?php echo $shared_cost; ?></span>
                    </strong>

                </div>


                <div class="breakdown-row total-row">

                    <span>
                        Total Saved Cost
                    </span>

                    <strong>
                        ৳ <span id="saved-cost"><?php echo $saved_cost; ?></span>
                    </strong>

                </div>


            </div>

        </div>


    </div>

</div>


</body>

</html>