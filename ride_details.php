<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$ride_id = $_GET['ride_id'] ?? 0;


$sql = "SELECT *
        FROM ride_details_view
        WHERE ride_id = ?
        AND ride_status = 'Active'";

$stmt = mysqli_prepare($conn, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $ride_id
);

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$row = mysqli_fetch_assoc($result);


if (!$row) {
    die("Ride not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ride Details</title>
    <link rel="stylesheet" href="css/ride_details.css">
</head>

<body>

    <nav class="navbar">
        <div class="logo">
            CampusRide
        </div>

        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="find_ride.php">Find Ride</a>
            <a href="my_rides.php">My Rides</a>
            <a href="profile.php">Profile</a>
        </div>

    </nav>


    <main class="container">

        <div class="page-title">

            <h1>Ride Details</h1>

            <p>
                Complete information about this ride
            </p>

        </div>


        <div class="route-card">

            <div class="route">

                <div class="location">

                    <h3>
                        <?php echo htmlspecialchars($row['start_location']); ?>
                    </h3>

                    <p>Starting Point</p>

                </div>


                <div class="arrow">
                    -
                </div>


                <div class="location">

                    <h3>
                        <?php echo htmlspecialchars($row['destination']); ?>
                    </h3>

                    <p>Destination</p>

                </div>

            </div>


            <div style="text-align: center;">

                <span class="status">
                    <?php echo htmlspecialchars($row['ride_status']); ?>
                </span>

            </div>

        </div>

        <div class="section">

            <h2>Ride Information</h2>

            <div class="details-grid">

                <div class="detail">

                    <label>Ride ID</label>

                    <p>
                        <?php echo htmlspecialchars($row['ride_id']); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Ride Type</label>

                    <p>
                        <?php echo htmlspecialchars($row['ride_type']); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Ride Date</label>

                    <p>
                        <?php echo htmlspecialchars($row['ride_date']); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Departure Time</label>

                    <p>
                        <?php echo htmlspecialchars($row['departure_time']); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Available Seats</label>

                    <p>
                        <?php echo htmlspecialchars($row['available_seats']); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Fare</label>

                    <p>
                        Taka <?php echo htmlspecialchars($row['fare']); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Women Only</label>

                    <p>
                        <?php echo htmlspecialchars($row['women_only']); ?>
                    </p>

                </div>

            </div>


            <div class="description">

                <label>Description</label>

                <p>
                    <?php echo htmlspecialchars($row['description']); ?>
                </p>

            </div>

        </div>

        <div class="section">

            <h2>Driver Information</h2>

            <div class="details-grid">

                <div class="detail">

                    <label>Name</label>

                    <p>
                        <?php echo htmlspecialchars($row['full_name']); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Student ID</label>

                    <p>
                        <?php echo htmlspecialchars($row['student_id']); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Phone</label>

                    <p>
                        <?php echo htmlspecialchars($row['phone']); ?>
                    </p>

                </div>

            </div>

        </div>


        <div class="section">

            <h2>Vehicle Information</h2>

            <div class="details-grid">

                <div class="detail">

                    <label>Vehicle Type</label>

                    <p>
                        <?php echo htmlspecialchars($row['vehicle_type'] ?? 'Not applicable'); ?>
                    </p>

                </div>


                <div class="detail">

                    <label>Registration Number</label>

                    <p>
                        <?php echo htmlspecialchars($row['registration_number'] ?? 'Not applicable'); ?>
                    </p>

                </div>

            </div>

        </div>


        <a href="my_rides.php" class="back-button">
            Back to My Rides
        </a>


    </main>

</body>

</html>