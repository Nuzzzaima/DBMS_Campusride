<?php
session_start();
include "db.php";

$user_id = 1;


/* GET RATING SUMMARY */

$summary_sql = "SELECT
                    COUNT(*) AS total_reviews,
                    AVG(stars) AS average_rating,
                    SUM(stars = 5) AS five_star,
                    SUM(stars = 4) AS four_star,
                    SUM(stars = 3) AS three_star,
                    SUM(stars = 2) AS two_star,
                    SUM(stars = 1) AS one_star
                FROM rating
                WHERE reviewee_id = $user_id";

$summary_result = mysqli_query($conn, $summary_sql);
$summary = mysqli_fetch_assoc($summary_result);

$total_reviews = $summary["total_reviews"];
$average_rating = $summary["average_rating"];

if ($average_rating == null) {
    $average_rating = 0;
}


/* GET REVIEWS */

$sql = "SELECT
            rating.rating_id,
            rating.stars,
            rating.review,
            rating.rating_date,
            `USER`.full_name,
            `USER`.profile_picture,
            `USER`.student_id
        FROM rating
        JOIN `USER`
            ON rating.reviewer_id = `USER`.user_id
        WHERE rating.reviewee_id = $user_id
        ORDER BY rating.rating_date DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>CampusRide - Ratings & Reviews</title>

    <link rel="stylesheet" href="ratings.css">
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">

            <div class="logo-icon">🚗</div>

            <div>
                <b>CampusRide</b>
                <span>UIU Student Portal</span>
            </div>

        </div>


        <div class="menu">

            <a href="#">
                <span>Dashboard</span>
            </a>

            <a href="find_ride.php">
                <span>Find a Ride</span>
            </a>

            <a href="#">
                <span>Create a Ride</span>
            </a>

            <a href="#">
                <span>My Rides</span>
            </a>

            <a href="#">
                <span>Ride Summary</span>
            </a>

            <a href="ride_requests.php">
                <span>Ride Requests</span>
            </a>

            <a href="bookings.php">
                <span>Bookings</span>
            </a>

            <a href="#">
                <span>Vehicles</span>
            </a>

            <a href="#">
                <span>Profile</span>
            </a>

            <a href="ratings.php" class="active">
                <span>Ratings & Reviews</span>
            </a>

            <a href="#">
                <span>My Savings</span>
            </a>

            <a href="#">
                <span>Notifications</span>
            </a>

            <a href="sos.php">
                <span>SOS / Emergency</span>
            </a>

        </div>


        <!-- BOTTOM USER -->

        <div class="sidebar-bottom">

            <div class="user">

                <div class="user-picture">N</div>

                <div>
                    <b>Nadia Rahman</b>
                    <span>nadia@uiu.ac.bd</span>
                </div>

            </div>

            <div class="logout">
                ⇥ &nbsp; Logout
            </div>

        </div>

    </aside>


    <!-- MAIN -->

    <main class="main">

        <!-- HEADER -->

        <header class="header">

            <div>

                <h2>Ratings & Reviews</h2>

                <p>Your reputation on CampusRide</p>

            </div>


            <div class="header-right">

                <div class="notification">
                    ♧
                </div>

                <div class="profile">

                    <div class="small-picture">
                        N
                    </div>

                    <span>Nadia</span>

                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="content">


            <!-- RATING SUMMARY -->

            <div class="rating-summary">

                <div class="rating-number">

                    <h1>
                        <?php echo number_format($average_rating, 1); ?>
                    </h1>

                    <div class="big-stars">
                        ★★★★★
                    </div>

                    <p>
                        <?php echo $total_reviews; ?> reviews
                    </p>

                </div>


                <div class="rating-bars">

                    <!-- 5 STAR -->

                    <div class="bar-row">

                        <span>5</span>

                        <b>★</b>

                        <div class="bar">
                            <div style="width:
                                <?php
                                echo $total_reviews > 0
                                    ? ($summary["five_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">
                            </div>
                        </div>

                        <span>
                            <?php echo $summary["five_star"]; ?>
                        </span>

                    </div>


                    <!-- 4 STAR -->

                    <div class="bar-row">

                        <span>4</span>

                        <b>★</b>

                        <div class="bar">
                            <div style="width:
                                <?php
                                echo $total_reviews > 0
                                    ? ($summary["four_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">
                            </div>
                        </div>

                        <span>
                            <?php echo $summary["four_star"]; ?>
                        </span>

                    </div>


                    <!-- 3 STAR -->

                    <div class="bar-row">

                        <span>3</span>

                        <b>★</b>

                        <div class="bar">
                            <div style="width:
                                <?php
                                echo $total_reviews > 0
                                    ? ($summary["three_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">
                            </div>
                        </div>

                        <span>
                            <?php echo $summary["three_star"]; ?>
                        </span>

                    </div>


                    <!-- 2 STAR -->

                    <div class="bar-row">

                        <span>2</span>

                        <b>★</b>

                        <div class="bar">
                            <div style="width:
                                <?php
                                echo $total_reviews > 0
                                    ? ($summary["two_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">
                            </div>
                        </div>

                        <span>
                            <?php echo $summary["two_star"]; ?>
                        </span>

                    </div>


                    <!-- 1 STAR -->

                    <div class="bar-row">

                        <span>1</span>

                        <b>★</b>

                        <div class="bar">
                            <div style="width:
                                <?php
                                echo $total_reviews > 0
                                    ? ($summary["one_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">
                            </div>
                        </div>

                        <span>
                            <?php echo $summary["one_star"]; ?>
                        </span>

                    </div>

                </div>

            </div>


            <!-- REVIEW TITLE -->

            <div class="review-heading">

                <h3>Reviews Received</h3>

                <button>
                    ☆ &nbsp; Give a Rating
                </button>

            </div>


            <!-- REVIEWS -->

            <?php if (mysqli_num_rows($result) > 0) { ?>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>

                    <div class="review-card">

                        <div class="review-top">

                            <div class="review-user">

                                <?php if (!empty($row["profile_picture"])) { ?>

                                    <img
                                        src="<?php echo htmlspecialchars($row["profile_picture"]); ?>"
                                        alt="Profile"
                                    >

                                <?php } else { ?>

                                    <div class="review-picture">

                                        <?php
                                        echo strtoupper(
                                            substr($row["full_name"], 0, 1)
                                        );
                                        ?>

                                    </div>

                                <?php } ?>


                                <div>

                                    <h4>
                                        <?php echo htmlspecialchars($row["full_name"]); ?>
                                    </h4>

                                    <div class="stars">

                                        <?php

                                        for ($i = 1; $i <= 5; $i++) {

                                            if ($i <= $row["stars"]) {
                                                echo "★";
                                            } else {
                                                echo "☆";
                                            }

                                        }

                                        ?>

                                    </div>

                                </div>

                            </div>


                            <span class="review-date">

                                <?php

                                echo date(
                                    "M d",
                                    strtotime($row["rating_date"])
                                );

                                ?>

                            </span>

                        </div>


                        <p class="review-text">

                            <?php

                            echo htmlspecialchars(
                                $row["review"] ?: "No written review."
                            );

                            ?>

                        </p>

                    </div>

                <?php } ?>

            <?php } else { ?>

                <div class="empty">
                    No reviews received yet.
                </div>

            <?php } ?>

        </section>

    </main>

</div>

</body>
</html>