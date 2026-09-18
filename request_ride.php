<?php

session_start();
include "db.php";


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$ride_id = intval($_GET['id'] ?? 0);

if ($ride_id <= 0) {
    die("Invalid ride.");
}


$ride_sql = "SELECT
                ride_id,
                user_id,
                ride_status,
                available_seats
             FROM ride
             WHERE ride_id = $ride_id";

$ride_result = mysqli_query($conn, $ride_sql);

if (!$ride_result) {
    die("Ride Query Error: " . mysqli_error($conn));
}

$ride = mysqli_fetch_assoc($ride_result);


if (!$ride) {
    die("Ride not found.");
}


if ($ride['ride_status'] != 'Active') {
    die("This ride is no longer active.");
}



if ($ride['available_seats'] <= 0) {
    die("No seats are available for this ride.");
}


if ($ride['user_id'] == $user_id) {
    die("You cannot request your own ride.");
}


$check_sql = "SELECT request_id
              FROM ride_request
              WHERE user_id = $user_id
              AND ride_id = $ride_id
              AND request_status IN ('Pending', 'Accepted')";

$check_result = mysqli_query($conn, $check_sql);

if (!$check_result) {
    die("Request Check Error: " . mysqli_error($conn));
}


if (mysqli_num_rows($check_result) > 0) {
    die("You have already requested this ride.");
}


$insert_sql = "INSERT INTO ride_request
               (user_id, ride_id, request_time, request_status)
               VALUES
               ($user_id, $ride_id, NOW(), 'Pending')";

if (!mysqli_query($conn, $insert_sql)) {
    die("Request Error: " . mysqli_error($conn));
}


header("Location: find_ride.php?msg=request_sent");
exit();

?>

