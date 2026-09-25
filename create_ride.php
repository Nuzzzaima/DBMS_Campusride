<?php
session_start();
include "db.php";

date_default_timezone_set('Asia/Dhaka');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];


$success_message = "";
$error_message = "";

if (isset($_SESSION['success'])) {
    $success_message = $_SESSION['success'];
    unset($_SESSION['success']);
}


/* CREATE RIDE */

if (isset($_POST['create'])) {

    $start = mysqli_real_escape_string($conn, $_POST['start']);
    $destination = mysqli_real_escape_string($conn, $_POST['destination']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
$time = mysqli_real_escape_string($conn, $_POST['time']);


/* CHECK RIDE CREATION TIME */

$ride_datetime = strtotime("$date $time");
$current_datetime = time();

$minimum_create_time = $ride_datetime - (20 * 60);

$can_create = true;

if ($current_datetime < $minimum_create_time) {

    $error_message =
        "You can only create this ride within 20 minutes before departure.";

    $can_create = false;
}

if ($current_datetime > $ride_datetime) {

    $error_message =
        "You cannot create a ride after its departure time.";

    $can_create = false;
}

if ($can_create) {
$ride_type = mysqli_real_escape_string($conn, $_POST['ride_type']);
    $seats = intval($_POST['seats']);
    $fare = floatval($_POST['fare']);

    if (!empty($_POST['vehicle_id'])) {
        $vehicle_id = intval($_POST['vehicle_id']);
    } else {
        $vehicle_id = "NULL";
    }

    $women_only = isset($_POST['women_only']) ? 'Yes' : 'No';

    $description = mysqli_real_escape_string(
        $conn,
        $_POST['description']
    );


    $sql = "INSERT INTO ride
            (
                user_id,
                vehicle_id,
                ride_date,
                departure_time,
                start_location,
                destination,
                ride_type,
                available_seats,
                fare,
                women_only,
                description
            )
            VALUES
            (
                '$user_id',
                $vehicle_id,
                '$date',
                '$time',
                '$start',
                '$destination',
                '$ride_type',
                '$seats',
                '$fare',
                '$women_only',
                '$description'
            )";


    if (mysqli_query($conn, $sql)) {

        $_SESSION['success'] = "Ride created successfully!";

        header("Location: create_ride.php");
        exit();

    } else {

        $error_message = "Ride could not be created. Please try again.";

    }
}
}
?>

<!DOCTYPE html>
<html>

<head>

    <title>Create Rides</title>

    <link rel="stylesheet" href="css/create_ride.css">

    <!-- Leaflet Map CSS -->
    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <style>

        body {
            display: grid !important;
            grid-template-columns: 197px 1fr !important;
            grid-template-areas: "sdbr main" !important;
            margin: 0 !important;
        }

        .sidebar {
            grid-area: sdbr !important;
            min-height: 100vh !important;
            background-color: white !important;
            border-right: 1px solid #ded9f3 !important;
        }

        .sidebar .logo {
            padding: 20px 15px !important;
        }

        .sidebar .logo b {
            display: block !important;
            font-size: 16px !important;
            color: #08194f !important;
        }

        .sidebar .logo small {
            display: block !important;
            margin-top: 4px !important;
            font-size: 10px !important;
            color: #8790a5 !important;
        }

        .sidebar ul {
            list-style: none !important;
            margin: 0 !important;
            padding: 12px 10px !important;
        }

        .sidebar li {
            display: block !important;
            margin-bottom: 4px !important;
        }

        .sidebar a {
            display: block !important;
            padding: 11px 12px !important;
            text-decoration: none !important;
            color: #5f6375 !important;
            font-size: 13px !important;
        }

        .sidebar ul li a.active {
            background-color: #8238ee !important;
            color: white !important;
        }

        .main-content {
            grid-area: main !important;
            margin-left: 0 !important;
        }


        /* SUCCESS MESSAGE */

        .success-message {
            width: 590px;
            margin: 20px auto 0;
            padding: 13px 16px;
            background: #eaf8ee;
            border: 1px solid #b8e5c4;
            border-radius: 10px;
            color: #217a39;
            font-size: 13px;
            font-weight: 600;
        }


        /* ERROR MESSAGE */

        .error-message {
            width: 590px;
            margin: 20px auto 0;
            padding: 13px 16px;
            background: #fff0f0;
            border: 1px solid #f0bcbc;
            border-radius: 10px;
            color: #b52b2b;
            font-size: 13px;
            font-weight: 600;
        }


        /* LOCATION INPUT */

        .location-input-area {
            display: flex;
            gap: 7px;
        }

        .location-input-area input {
            flex: 1;
        }

        .map-button {
            height: 36px;
            padding: 0 10px;
            border: 1px solid #bca9f5;
            border-radius: 9px;
            background: #f8f5ff;
            color: #7132d5;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            white-space: nowrap;
        }

        .map-button:hover {
            background: #eee7ff;
        }


        /* MAP POPUP */

        .map-overlay {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.45);
            align-items: center;
            justify-content: center;
        }

        .map-box {
            width: 700px;
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        .map-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .map-header h3 {
            margin: 0;
            font-size: 16px;
            color: #08194f;
        }

        .close-map {
            border: none;
            background: none;
            font-size: 22px;
            color: #777;
            cursor: pointer;
        }

        .map-instruction {
            margin-bottom: 10px;
            font-size: 11px;
            color: #68738b;
        }

        #map {
            width: 100%;
            height: 400px;
            border-radius: 10px;
        }

        .selected-location {
            margin-top: 10px;
            padding: 10px;
            background: #f8f7fc;
            border-radius: 8px;
            font-size: 11px;
            color: #5f6375;
            min-height: 18px;
        }

        .map-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 12px;
        }

        .map-actions button {
            height: 36px;
            padding: 0 16px;
            border-radius: 9px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
        }

        .use-location {
            border: none;
            background: #8238ee;
            color: white;
        }

        .cancel-map {
            border: 1px solid #bca9f5;
            background: white;
            color: #7132d5;
        }

    </style>

</head>


<body>


    <!-- SIDEBAR -->

    <div class="sidebar">

        <div class="logo">
            <b>CampusRide</b>
            <small>UIU Student Portal</small>
        </div>

        <div class="menu">

            <ul>

                <li>
                    <a href="dashboard.php">Dashboard</a>
                </li>

                <li>
                    <a href="find_ride.php">Find a Ride</a>
                </li>

                <li>
                    <a href="create_ride.php" class="active">
                        Create a Ride
                    </a>
                </li>

                <li>
                    <a href="my_rides.php">My Rides</a>
                </li>

                <li>
                    <a href="ride_summary.php">Ride Summary</a>
                </li>

                <li>
                    <a href="ride_requests.php">Ride Requests</a>
                </li>

                <li>
                    <a href="bookings.php">Bookings</a>
                </li>

                <li>
                    <a href="vehicles.php">Vehicles</a>
                </li>

                <li>
                    <a href="profile.php">Profile</a>
                </li>

                <li>
                    <a href="ratings.php">Ratings & Reviews</a>
                </li>

                <li>
                    <a href="my_savings.php">My Savings</a>
                </li>

                <li>
                    <a href="sos.php">SOS / Emergency</a>
                </li>

                <li>
                    <a href="logout.php">Logout</a>
                </li>

            </ul>

        </div>

    </div>


    <!-- MAIN CONTENT -->

    <div class="main-content">


        <div class="header">

            <h2>Create a Ride</h2>

            <p>
                Offer seats to fellow students on your route
            </p>

        </div>


        <?php if (!empty($success_message)) { ?>

            <div class="success-message">
                <?php echo $success_message; ?>
            </div>

        <?php } ?>


        <?php if (!empty($error_message)) { ?>

            <div class="error-message">
                <?php echo $error_message; ?>
            </div>

        <?php } ?>


        <div class="container">


            <form method="POST">


                <div class="row">


                    <!-- START LOCATION -->

                    <div>

                        <label>Starting Location</label>

                        <div class="location-input-area">

                            <input
                                type="text"
                                name="start"
                                id="start"
                                placeholder="e.g. Notun Bazar"
                                required
                            >

                            <button
                                type="button"
                                class="map-button"
                                onclick="openMap('start')"
                            >
                                🗺 Map
                            </button>

                        </div>

                    </div>


                    <!-- DESTINATION -->

                    <div>

                        <label>Destination</label>

                        <div class="location-input-area">

                            <input
                                type="text"
                                name="destination"
                                id="destination"
                                placeholder="e.g. UIU Permanent Campus"
                                required
                            >

                            <button
                                type="button"
                                class="map-button"
                                onclick="openMap('destination')"
                            >
                                🗺 Map
                            </button>

                        </div>

                    </div>


                    <!-- DATE -->

                    <div>

                        <label>Ride Date</label>

                        <input
                            type="date"
                            name="date"
                            required
                        >

                    </div>


                    <!-- TIME -->

                    <div>

                        <label>Departure Time</label>

                        <input
                            type="time"
                            name="time"
                            required
                        >

                    </div>


                    <!-- RIDE TYPE -->

                    <div>

                        <label>Ride Type</label>

                        <select name="ride_type" required>

                            <option value="Rickshaw Share">
                                Rickshaw Share
                            </option>

                            <option value="CNG Share">
                                CNG Share
                            </option>

                            <option value="Car">
                                Car
                            </option>

                        </select>

                    </div>


                    <!-- SEATS -->

                    <div>

                        <label>Available Seats</label>

                        <input
                            type="number"
                            name="seats"
                            min="1"
                            placeholder="e.g. 3"
                            required
                        >

                    </div>


                    <!-- FARE -->

                    <div>

                        <label>Fare (Cost Per Passenger)</label>

                        <input
                            type="number"
                            name="fare"
                            min="0"
                            placeholder="e.g. 25"
                            required
                        >

                    </div>


                    <!-- VEHICLE -->

                    <div>

                        <label>Vehicle (optional)</label>

                        <select name="vehicle_id">

                            <option value="">
                                No personal vehicle
                            </option>


                            <?php

                            $user_id = $_SESSION['user_id'];

                            $sql = "SELECT *
                                    FROM vehicle
                                    WHERE user_id = '$user_id'";

                            $result = mysqli_query($conn, $sql);


                            while ($vehicle = mysqli_fetch_assoc($result)) {

                            ?>

                                <option
                                    value="<?php echo $vehicle['vehicle_id']; ?>"
                                >

                                    <?php echo $vehicle['vehicle_type']; ?>

                                    <?php echo $vehicle['registration_number']; ?>

                                </option>

                            <?php

                            }

                            ?>

                        </select>

                    </div>


                </div>


                <!-- WOMEN ONLY -->

                <div class="women">

                    <div>

                        <b>Women-Only Ride</b>

                        <small>
                            Only female students can request this ride
                        </small>

                    </div>

                    <input
                        type="checkbox"
                        name="women_only"
                    >

                </div>


                <!-- DESCRIPTION -->

                <label>Description (optional)</label>

                <textarea
                    name="description"
                    placeholder="e.g. I leave from Khilgaon at 8:30 AM sharp, WhatsApp me to coordinate pickup point."
                ></textarea>


                <!-- BUTTONS -->

                <div class="buttons">

                    <button
                        type="submit"
                        name="create"
                    >
                        Create Ride
                    </button>

                    <button
                        type="reset"
                    >
                        Cancel
                    </button>

                </div>


            </form>


        </div>


    </div>



    <!-- MAP POPUP -->

    <div
        class="map-overlay"
        id="mapOverlay"
    >

        <div class="map-box">


            <div class="map-header">

                <h3 id="mapTitle">
                    Choose Location
                </h3>

                <button
                    type="button"
                    class="close-map"
                    onclick="closeMap()"
                >
                    ×
                </button>

            </div>


            <div class="map-instruction">

                Click anywhere on the map to select a location.

            </div>


            <div id="map"></div>


            <div
                class="selected-location"
                id="selectedLocation"
            >
                No location selected yet.
            </div>


            <div class="map-actions">

                <button
                    type="button"
                    class="cancel-map"
                    onclick="closeMap()"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="use-location"
                    onclick="useSelectedLocation()"
                >
                    Use This Location
                </button>

            </div>


        </div>

    </div>



    <!-- Leaflet Map JS -->

    <script
        src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    ></script>


    <script>

        let map;
        let marker;

        let selectedField = "";
        let selectedAddress = "";

        let selectedLatitude = null;
        let selectedLongitude = null;


        /* OPEN MAP */

        function openMap(field)
        {

            selectedField = field;

            if (field === "start")
            {
                document.getElementById("mapTitle").innerText =
                    "Choose Starting Location";
            }
            else
            {
                document.getElementById("mapTitle").innerText =
                    "Choose Destination";
            }


            document.getElementById("mapOverlay").style.display = "flex";


            /* Create map only once */

            if (!map)
            {

                map = L.map('map').setView(
                    [23.8103, 90.4125],
                    12
                );


                L.tileLayer(
                    'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                    {
                        attribution: '&copy; OpenStreetMap contributors'
                    }
                ).addTo(map);


                map.on('click', function(e)
                {

                    selectedLatitude = e.latlng.lat;
                    selectedLongitude = e.latlng.lng;


                    /* Put marker */

                    if (marker)
                    {
                        marker.setLatLng(e.latlng);
                    }
                    else
                    {
                        marker = L.marker(e.latlng).addTo(map);
                    }


                    document.getElementById(
                        "selectedLocation"
                    ).innerText =
                        "Getting location name...";


                    /* Reverse geocoding */

                    fetch(
                        "https://nominatim.openstreetmap.org/reverse?format=json&lat="
                        + selectedLatitude
                        + "&lon="
                        + selectedLongitude
                        + "&zoom=18&addressdetails=1"
                    )
                    .then(response => response.json())
                    .then(data =>
                    {

                        if (data.display_name)
                        {

                            selectedAddress =
                                data.display_name;

                            document.getElementById(
                                "selectedLocation"
                            ).innerText =
                                selectedAddress;

                        }
                        else
                        {

                            selectedAddress =
                                selectedLatitude.toFixed(6)
                                + ", "
                                + selectedLongitude.toFixed(6);

                            document.getElementById(
                                "selectedLocation"
                            ).innerText =
                                selectedAddress;

                        }

                    })
                    .catch(error =>
                    {

                        selectedAddress =
                            selectedLatitude.toFixed(6)
                            + ", "
                            + selectedLongitude.toFixed(6);

                        document.getElementById(
                            "selectedLocation"
                        ).innerText =
                            selectedAddress;

                    });

                });

            }


            /* Make sure map displays correctly */

            setTimeout(function()
            {
                map.invalidateSize();
            }, 200);

        }


        /* USE SELECTED LOCATION */

        function useSelectedLocation()
        {

            if (selectedAddress === "")
            {
                alert("Please click on the map to select a location.");
                return;
            }


            if (selectedField === "start")
            {

                document.getElementById("start").value =
                    selectedAddress;

            }
            else if (selectedField === "destination")
            {

                document.getElementById("destination").value =
                    selectedAddress;

            }


            closeMap();

        }


        /* CLOSE MAP */

        function closeMap()
        {

            document.getElementById("mapOverlay").style.display =
                "none";

            selectedAddress = "";
            selectedLatitude = null;
            selectedLongitude = null;

            document.getElementById(
                "selectedLocation"
            ).innerText =
                "No location selected yet.";

        }

    </script>


</body>

</html>
