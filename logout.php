<?php

session_start();


// Remove all session variables
$_SESSION = [];


// Destroy the session
session_destroy();


// Go back to login page
header("Location: login.php?msg=" . urlencode("Signed out safely."));

exit();

?>