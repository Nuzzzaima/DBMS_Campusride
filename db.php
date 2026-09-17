<?php
$host="localhost";
$user="root";
$pass="";
$db="campusride";

$conn=mysqli_connect($host,$user,$pass,$db);
if(!$conn)
    {
        die("connection failed");
    }

?>