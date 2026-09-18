<?php

session_start();
include "db.php";


// =========================
// CHECK LOGIN
// =========================

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


// =========================
// GET LOGGED-IN USER
// =========================

$sql_user = "SELECT full_name, university_email
             FROM `USER`
             WHERE user_id = '$user_id'
             LIMIT 1";

$result_user = mysqli_query($conn, $sql_user);

if (!$result_user) {
    die("Database Error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result_user) != 1) {
    session_destroy();
    header("Location: login.php");
    exit();
}

$user = mysqli_fetch_assoc($result_user);

$fullName = $user['full_name'];
$firstName = explode(' ', $fullName)[0];


// =========================
// RATING SUMMARY
// =========================

$summary_sql = "SELECT
                    COUNT(*) AS total_reviews,
                    AVG(stars) AS average_rating,
                    SUM(stars = 5) AS five_star,
                    SUM(stars = 4) AS four_star,
                    SUM(stars = 3) AS three_star,
                    SUM(stars = 2) AS two_star,
                    SUM(stars = 1) AS one_star
                FROM rating
                WHERE reviewee_id = '$user_id'";

$summary_result = mysqli_query($conn, $summary_sql);

if (!$summary_result) {
    die("Database Error: " . mysqli_error($conn));
}

$summary = mysqli_fetch_assoc($summary_result);

$total_reviews = $summary["total_reviews"];
$average_rating = $summary["average_rating"];

if ($average_rating == null) {
    $average_rating = 0;
}


// =========================
// GET REVIEWS
// =========================

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
        WHERE rating.reviewee_id = '$user_id'
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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>CampusRide - Ratings & Reviews</title>

    <link rel="stylesheet" href="ratings.css">

</head>

<body>


<div class="dashboard">


    <!-- SHARED SIDEBAR -->

    <?php include __DIR__ . '/includes/sidebar.php'; ?>


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

                        <?= htmlspecialchars(
                            strtoupper(substr($firstName, 0, 1))
                        ) ?>

                    </div>

                    <span>
                        <?= htmlspecialchars($firstName) ?>
                    </span>

                </div>

            </div>

        </header>


        <!-- CONTENT -->

        <section class="content">


            <!-- RATING SUMMARY -->

            <div class="rating-summary">


                <div class="rating-number">

                    <h1>
                        <?= number_format($average_rating, 1); ?>
                    </h1>


                    <div class="big-stars">
                        ★★★★★
                    </div>


                    <p>
                        <?= $total_reviews; ?>

                        <?= ($total_reviews == 1)
                            ? "review"
                            : "reviews"; ?>
                    </p>

                </div>


                <div class="rating-bars">


                    <!-- 5 STAR -->

                    <div class="bar-row">

                        <span>5</span>

                        <b>★</b>

                        <div class="bar">

                            <div style="width:
                                <?= $total_reviews > 0
                                    ? ($summary["five_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">

                            </div>

                        </div>

                        <span>
                            <?= $summary["five_star"]; ?>
                        </span>

                    </div>


                    <!-- 4 STAR -->

                    <div class="bar-row">

                        <span>4</span>

                        <b>★</b>

                        <div class="bar">

                            <div style="width:
                                <?= $total_reviews > 0
                                    ? ($summary["four_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">

                            </div>

                        </div>

                        <span>
                            <?= $summary["four_star"]; ?>
                        </span>

                    </div>


                    <!-- 3 STAR -->

                    <div class="bar-row">

                        <span>3</span>

                        <b>★</b>

                        <div class="bar">

                            <div style="width:
                                <?= $total_reviews > 0
                                    ? ($summary["three_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">

                            </div>

                        </div>

                        <span>
                            <?= $summary["three_star"]; ?>
                        </span>

                    </div>


                    <!-- 2 STAR -->

                    <div class="bar-row">

                        <span>2</span>

                        <b>★</b>

                        <div class="bar">

                            <div style="width:
                                <?= $total_reviews > 0
                                    ? ($summary["two_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">

                            </div>

                        </div>

                        <span>
                            <?= $summary["two_star"]; ?>
                        </span>

                    </div>


                    <!-- 1 STAR -->

                    <div class="bar-row">

                        <span>1</span>

                        <b>★</b>

                        <div class="bar">

                            <div style="width:
                                <?= $total_reviews > 0
                                    ? ($summary["one_star"] / $total_reviews) * 100
                                    : 0;
                                ?>%;">

                            </div>

                        </div>

                        <span>
                            <?= $summary["one_star"]; ?>
                        </span>

                    </div>

                </div>

            </div>


            <!-- REVIEW TITLE -->

            <div class="review-heading">

                <h3>Reviews Received</h3>

                <button type="button">
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
                                        src="<?= htmlspecialchars($row["profile_picture"]); ?>"
                                        alt="Profile"
                                    >

                                <?php } else { ?>

                                    <div class="review-picture">

                                        <?= htmlspecialchars(
                                            strtoupper(
                                                substr($row["full_name"], 0, 1)
                                            )
                                        ); ?>

                                    </div>

                                <?php } ?>


                                <div>

                                    <h4>
                                        <?= htmlspecialchars($row["full_name"]); ?>
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

                                <?= date(
                                    "M d",
                                    strtotime($row["rating_date"])
                                ); ?>

                            </span>

                        </div>


                        <p class="review-text">

                            <?= htmlspecialchars(
                                $row["review"] ?: "No written review."
                            ); ?>

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