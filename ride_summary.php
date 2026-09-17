<?php

session_start();
include "db.php";

$user_id = 1;

$sql1 = "SELECT
            u.full_name,
            COUNT(r.ride_id) AS total_rides,
            COALESCE(SUM(r.fare), 0) AS total_fare,
            COALESCE(AVG(r.fare), 0) AS average_fare
        FROM user u
        LEFT JOIN ride r
            ON u.user_id = r.user_id
            AND r.ride_date < CURRENT_DATE
        WHERE u.user_id = $user_id
        GROUP BY u.user_id, u.full_name";

$sql2 = "SELECT
            ride_type,
            COUNT(*) AS ride_count
        FROM ride
        WHERE user_id = $user_id
        AND ride_date < CURRENT_DATE
        GROUP BY ride_type
        ORDER BY ride_count DESC
        LIMIT 1";

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
</head>
<body>
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

</body>

</html>