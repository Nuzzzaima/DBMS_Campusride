<?php

session_start();

include "db.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


if (isset($_SESSION['success_message'])) {

    echo "<script>
            alert('" . $_SESSION['success_message'] . "');
          </script>";

    unset($_SESSION['success_message']);
}



$booking_id = intval($_GET['booking_id'] ?? 0);

$booking = null;


if ($booking_id > 0) {

    $booking_sql = "SELECT 
                        b.booking_id,
                        b.booking_status,
                        rr.user_id AS reviewer_id,
                        rr.ride_id,
                        r.user_id AS reviewee_id,
                        u.full_name AS driver_name
                    FROM booking b
                    JOIN ride_request rr
                        ON b.request_id = rr.request_id
                    JOIN ride r
                        ON rr.ride_id = r.ride_id
                    JOIN `USER` u
                        ON r.user_id = u.user_id
                    WHERE b.booking_id = $booking_id
                    AND rr.user_id = $user_id
                    AND b.booking_status = 'Completed'";

    $booking_result = mysqli_query($conn, $booking_sql);

    if (!$booking_result) {
        die("Booking Query Error: " . mysqli_error($conn));
    }

    $booking = mysqli_fetch_assoc($booking_result);

    if (!$booking) {
        die("Booking not found or you are not allowed to rate this ride.");
    }


    $reviewer_id = $booking['reviewer_id'];
    $reviewee_id = $booking['reviewee_id'];
    $ride_id = $booking['ride_id'];


    $check_rating_sql = "SELECT rating_id
                         FROM rating
                         WHERE booking_id = $booking_id";

    $check_rating_result = mysqli_query($conn, $check_rating_sql);

    if (!$check_rating_result) {
        die("Rating Check Error: " . mysqli_error($conn));
    }

    $existing_rating = mysqli_fetch_assoc($check_rating_result);

    if ($existing_rating) {
        die("You have already rated this ride.");
    }



    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        $rating_value = intval($_POST['rating']);

        $review_text = mysqli_real_escape_string(
            $conn,
            $_POST['review_text']
        );


        if ($rating_value < 1 || $rating_value > 5) {
            die("Rating must be between 1 and 5.");
        }


        $rating_sql = "INSERT INTO rating
                       (booking_id, ride_id, reviewer_id, reviewee_id, rating, review_text)
                       VALUES
                       ($booking_id, $ride_id, $reviewer_id, $reviewee_id, $rating_value, '$review_text')";

        $rating_result = mysqli_query($conn, $rating_sql);

        if (!$rating_result) {
            die("Rating Insert Error: " . mysqli_error($conn));
        }


        $_SESSION['success_message'] = "Rating saved successfully!";

        header("Location: ratings.php");
        exit();
    }
}


$completed_sql = "SELECT
                    b.booking_id,
                    rr.ride_id,
                    r.ride_date,
                    r.departure_time,
                    r.user_id AS driver_id,
                    u.full_name AS driver_name
                  FROM booking b
                  JOIN ride_request rr
                    ON b.request_id = rr.request_id
                  JOIN ride r
                    ON rr.ride_id = r.ride_id
                  JOIN `USER` u
                    ON r.user_id = u.user_id
                  WHERE rr.user_id = $user_id
                  AND b.booking_status = 'Completed'
                  ORDER BY r.ride_date DESC,
                           r.departure_time DESC";

$completed_result = mysqli_query($conn, $completed_sql);

if (!$completed_result) {
    die("Completed Rides Query Error: " . mysqli_error($conn));
}

?>


<!DOCTYPE html>

<html>

<head>

    <title>Ratings - CampusRide</title>

    <link rel="stylesheet" href="css/ratings.css">

</head>


<body>

<div class="ratings-sidebar">

    <div class="ratings-logo">

        <b>CampusRide</b>

        <small>UIU Student Portal</small>

    </div>


    <div class="ratings-menu">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="find_ride.php">
            Find a Ride
        </a>

        <a href="create_ride.php">
            Create a Ride
        </a>

        <a href="my_rides.php">
            My Rides
        </a>

        <a href="ride_summary.php">
            Ride Summary
        </a>

        <a href="ride_requests.php">
            Ride Requests
        </a>

        <a href="bookings.php">
            Bookings
        </a>

        <a href="vehicles.php">
            Vehicles
        </a>

        <a href="profile.php">
            Profile
        </a>

        <a href="ratings.php" class="ratings-active">
            Ratings
        </a>

        <a href="#">
            My Savings
        </a>

        <a href="sos.php">
            SOS / Emergency
        </a>

        <a href="logout.php">
            Logout
        </a>

    </div>

</div>



<!-- =====================================================
     MAIN CONTENT
     ===================================================== -->

<div class="ratings-main">


    <?php if ($booking_id > 0 && $booking) { ?>


        <!-- RATE SELECTED RIDE -->

        <h2>Rate Your Ride</h2>


        <div class="rating-form">

            <p>

                <strong>Driver:</strong>

                <?php echo $booking['driver_name']; ?>

            </p>


            <form method="POST"
                  action="ratings.php?booking_id=<?php echo $booking_id; ?>">


                <label>Rating:</label>


                <select name="rating" required>

                    <option value="">
                        Select Rating
                    </option>

                    <option value="1">
                        ⭐ 1
                    </option>

                    <option value="2">
                        ⭐⭐ 2
                    </option>

                    <option value="3">
                        ⭐⭐⭐ 3
                    </option>

                    <option value="4">
                        ⭐⭐⭐⭐ 4
                    </option>

                    <option value="5">
                        ⭐⭐⭐⭐⭐ 5
                    </option>

                </select>


                <br><br>


                <label>Review:</label>


                <br>


                <textarea
                    name="review_text"
                    rows="5"
                    placeholder="Write your review..."
                    required></textarea>


                <br><br>


                <button type="submit">
                    Submit Rating
                </button>


            </form>

        </div>


    <?php } ?>



    <!-- COMPLETED RIDES -->

    <h2>Completed Rides</h2>


    <?php

    if (mysqli_num_rows($completed_result) == 0) {

        echo "<div class='no-rides'>
                You have no completed rides yet.
              </div>";
    }


    while ($ride = mysqli_fetch_assoc($completed_result)) {


        /* CHECK IF ALREADY RATED */

        $rating_check_sql = "SELECT rating_id
                             FROM rating
                             WHERE booking_id = "
                             . $ride['booking_id'];

        $rating_check_result = mysqli_query(
            $conn,
            $rating_check_sql
        );

        if (!$rating_check_result) {
            die("Rating Check Error: " . mysqli_error($conn));
        }

        $already_rated = mysqli_fetch_assoc(
            $rating_check_result
        );

        ?>


        <div class="ride-card">


            <h3>
                <?php echo $ride['driver_name']; ?>
            </h3>


            <p>
                Date:
                <?php echo $ride['ride_date']; ?>
            </p>


            <p>
                Time:
                <?php echo $ride['departure_time']; ?>
            </p>


            <?php if ($already_rated) { ?>

                <p class="already-rated">
                    ✓ Already Rated
                </p>


            <?php } else { ?>

                <a href="ratings.php?booking_id=<?php
                    echo $ride['booking_id'];
                ?>">

                    <button type="button">
                        Rate This Ride
                    </button>

                </a>

            <?php } ?>


        </div>


        <?php
    }
    ?>


</div>


</body>

</html>