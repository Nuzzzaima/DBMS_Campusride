<?php
session_start();
 include "db.php";
 date_default_timezone_set('Asia/Dhaka');
 if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ride_id=intval($_GET['ride_id'] ?? 0);

if($ride_id<=0)
    {
        die("Invalid ride id");
    }

   $ride_status_sql="SELECT ride_status, ride_date, departure_time
                  FROM ride
                  WHERE ride_id=$ride_id
                  AND user_id=$user_id";
   $ride_status_result=mysqli_query($conn,$ride_status_sql);
   $status=mysqli_fetch_assoc($ride_status_result);
   if(!$status)
    {
        die ("Ride Status Error");
    }
    if($status['ride_status']!='Active')
        {
            die ("This ride is not active");
        }
        $ride_datetime = strtotime(
    $status['ride_date'] . ' ' . $status['departure_time']
);

$current_datetime = time();

if ($current_datetime < $ride_datetime)
{
    die("This ride cannot be completed before its departure time.");
}

        mysqli_begin_transaction($conn);
$ride_update_sql="UPDATE ride
SET ride_status = 'Completed'
WHERE ride_status = 'Active'
AND user_id = $user_id
AND ride_id = $ride_id";

$ride_update_result=mysqli_query($conn,$ride_update_sql);
   if(!$ride_update_result)
    {
         mysqli_rollback($conn);
        die("Ride cannot be updated");
    }

    $booking_update_sql="UPDATE booking
SET booking_status = 'Completed'
WHERE booking_status = 'Confirmed'
AND request_id IN (
    SELECT request_id
    FROM ride_request
    WHERE ride_id = $ride_id
)";

$booking_update_result=mysqli_query($conn,$booking_update_sql);
   if(!$booking_update_result)
    {
         mysqli_rollback($conn);
        die("Booking cannot be updated");
    }

     mysqli_commit($conn);
     $_SESSION['success'] = "Ride marked as completed successfully.";
     header("Location: my_rides.php");
exit();



?>