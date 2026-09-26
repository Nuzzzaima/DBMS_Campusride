<?php

session_start();

include __DIR__ . "/db.php";


/* =========================================================
   ADMIN SESSION PROTECTION
========================================================= */

if (!isset($_SESSION["admin_user_id"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin_name"] ?? "Admin Ops";
$admin_email = $_SESSION["admin_email"] ?? "admin@uiu.ac.bd";


/* =========================================================
   VEHICLE STATUS ACTIONS
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["vehicle_id"]) && isset($_POST["action"])) {

        $vehicle_id = (int) $_POST["vehicle_id"];
        $action = $_POST["action"];


        /* APPROVE */

        if ($action === "approve") {

            $sql = "UPDATE vehicle
                    SET vehicle_status = 'Approved & Active'
                    WHERE vehicle_id = $vehicle_id";

            mysqli_query($conn, $sql);
        }


        /* REJECT / SUSPEND */

        elseif ($action === "reject" || $action === "suspend") {

            $sql = "UPDATE vehicle
                    SET vehicle_status = 'Suspended / Expired'
                    WHERE vehicle_id = $vehicle_id";

            mysqli_query($conn, $sql);
        }


        /* MOVE BACK TO PENDING */

        elseif ($action === "pending") {

            $sql = "UPDATE vehicle
                    SET vehicle_status = 'Pending Inspection'
                    WHERE vehicle_id = $vehicle_id";

            mysqli_query($conn, $sql);
        }


        header("Location: admin_vehicle_fleet.php");
        exit();
    }
}


/* =========================================================
   SEARCH / FILTER VALUES
========================================================= */

$search = $_GET["search"] ?? "";
$status = $_GET["status"] ?? "All Status";
$category = $_GET["category"] ?? "All Fleets";
$department = $_GET["department"] ?? "";
$sort = $_GET["sort"] ?? "newest";

$search_safe = mysqli_real_escape_string($conn, $search);
$department_safe = mysqli_real_escape_string($conn, $department);


/* =========================================================
   STATISTICS
========================================================= */

$total_sql = "
    SELECT COUNT(*) AS total
    FROM vehicle
";

$approved_sql = "
    SELECT COUNT(*) AS total
    FROM vehicle
    WHERE vehicle_status = 'Approved & Active'
";

$pending_sql = "
    SELECT COUNT(*) AS total
    FROM vehicle
    WHERE vehicle_status = 'Pending Inspection'
";

$suspended_sql = "
    SELECT COUNT(*) AS total
    FROM vehicle
    WHERE vehicle_status = 'Suspended / Expired'
";


$total_result = mysqli_query($conn, $total_sql);
$approved_result = mysqli_query($conn, $approved_sql);
$pending_result = mysqli_query($conn, $pending_sql);
$suspended_result = mysqli_query($conn, $suspended_sql);


$total = mysqli_fetch_assoc($total_result)["total"];
$approved = mysqli_fetch_assoc($approved_result)["total"];
$pending = mysqli_fetch_assoc($pending_result)["total"];
$suspended = mysqli_fetch_assoc($suspended_result)["total"];


/* =========================================================
   VEHICLE QUERY
========================================================= */

$sql = "
    SELECT
        vehicle.vehicle_id,
        vehicle.user_id,
        vehicle.vehicle_type,
        vehicle.created_at,
        vehicle.seat_capacity,
        vehicle.registration_number,
        vehicle.vehicle_status,

        `USER`.full_name,
        `USER`.student_id,
        `USER`.university_email,
        `USER`.department

    FROM vehicle

    INNER JOIN `USER`
        ON vehicle.user_id = `USER`.user_id

    WHERE 1 = 1
";


/* =========================================================
   SEARCH
========================================================= */

if ($search !== "") {

    $sql .= "
        AND (
            `USER`.full_name LIKE '%$search_safe%'
            OR `USER`.student_id LIKE '%$search_safe%'
            OR vehicle.vehicle_type LIKE '%$search_safe%'
            OR vehicle.registration_number LIKE '%$search_safe%'
            OR `USER`.department LIKE '%$search_safe%'
        )
    ";
}


/* =========================================================
   STATUS FILTER
========================================================= */

if ($status !== "" && $status !== "All Status") {

    $status_safe = mysqli_real_escape_string($conn, $status);

    $sql .= "
        AND vehicle.vehicle_status = '$status_safe'
    ";
}


/* =========================================================
   CATEGORY FILTER
========================================================= */

if ($category !== "" && $category !== "All Fleets") {

    $category_safe = mysqli_real_escape_string($conn, $category);

    $sql .= "
        AND vehicle.vehicle_type = '$category_safe'
    ";
}


/* =========================================================
   DEPARTMENT FILTER
========================================================= */

if ($department !== "") {

    $sql .= "
        AND `USER`.department LIKE '%$department_safe%'
    ";
}


/* =========================================================
   SORTING
========================================================= */

if ($sort === "oldest") {

    $sql .= "
        ORDER BY vehicle.created_at ASC
    ";

} elseif ($sort === "owner") {

    $sql .= "
        ORDER BY `USER`.full_name ASC
    ";

} else {

    $sql .= "
        ORDER BY vehicle.created_at DESC
    ";
}


/* =========================================================
   PAGINATION
========================================================= */

$per_page = 6;

$page = isset($_GET["page"])
    ? (int) $_GET["page"]
    : 1;

if ($page < 1) {
    $page = 1;
}


/* =========================================================
   COUNT FILTERED RECORDS
========================================================= */

$count_sql = "
    SELECT COUNT(*) AS total

    FROM vehicle

    INNER JOIN `USER`
        ON vehicle.user_id = `USER`.user_id

    WHERE 1 = 1
";


/* SEARCH */

if ($search !== "") {

    $count_sql .= "
        AND (
            `USER`.full_name LIKE '%$search_safe%'
            OR `USER`.student_id LIKE '%$search_safe%'
            OR vehicle.vehicle_type LIKE '%$search_safe%'
            OR vehicle.registration_number LIKE '%$search_safe%'
            OR `USER`.department LIKE '%$search_safe%'
        )
    ";
}


/* STATUS */

if ($status !== "" && $status !== "All Status") {

    $status_safe = mysqli_real_escape_string($conn, $status);

    $count_sql .= "
        AND vehicle.vehicle_status = '$status_safe'
    ";
}


/* CATEGORY */

if ($category !== "" && $category !== "All Fleets") {

    $category_safe = mysqli_real_escape_string($conn, $category);

    $count_sql .= "
        AND vehicle.vehicle_type = '$category_safe'
    ";
}


/* DEPARTMENT */

if ($department !== "") {

    $count_sql .= "
        AND `USER`.department LIKE '%$department_safe%'
    ";
}


$count_result = mysqli_query($conn, $count_sql);

$count_row = mysqli_fetch_assoc($count_result);

$filtered_total = (int) $count_row["total"];


$total_pages = max(
    1,
    ceil($filtered_total / $per_page)
);


if ($page > $total_pages) {
    $page = $total_pages;
}


$offset = ($page - 1) * $per_page;


/* Add pagination */

$sql .= "
    LIMIT $offset, $per_page
";


$result = mysqli_query($conn, $sql);


if (!$result) {
    die("Vehicle query failed: " . mysqli_error($conn));
}


/* =========================================================
   RESULT NUMBERS
========================================================= */

$start_number =
    $filtered_total > 0
    ? $offset + 1
    : 0;

$end_number =
    min(
        $offset + $per_page,
        $filtered_total
    );


/* =========================================================
   PAGE LINK HELPER
========================================================= */

function page_link($page_number)
{
    $params = $_GET;

    $params["page"] = $page_number;

    return "admin_vehicle_fleet.php?"
        . http_build_query($params);
}

?>

<!DOCTYPE html>

<html>

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Vehicle Fleet & Verification - CampusRide
    </title>

    <link
        rel="stylesheet"
        href="admin_vehicle_fleet.css"
    >

</head>


<body>


<div class="dashboard">


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar">


        <div class="brand">

            <div class="brand-icon">
                🚗
            </div>

            <div>

                <b>CampusRide</b>

                <small>
                    Operations Console
                </small>

            </div>

        </div>


        <nav class="menu">


            <a href="admin_dashboard.php">
                Dashboard
            </a>


            <a href="admin_users.php">
                Users Management
            </a>


            <a
                href="admin_vehicle_fleet.php"
                class="active"
            >
                Vehicles Fleet
            </a>


            <a href="admin_rides.php">
                All Rides
            </a>


            <a href="admin_requests.php">
                Requests
            </a>


            <a href="admin_bookings.php">
                Bookings
            </a>


            <a href="admin_sos.php">
                SOS Alerts
            </a>


            <a href="#">
                Settings
            </a>


        </nav>


        <div class="sidebar-bottom">


            <div class="admin-profile">


                <div class="admin-avatar">
                    <?php
                    echo strtoupper(
                        substr($admin_name, 0, 1)
                    );
                    ?>
                </div>


                <div>

                    <b>
                        <?php
                        echo htmlspecialchars(
                            $admin_name
                        );
                        ?>
                    </b>


                    <small>
                        <?php
                        echo htmlspecialchars(
                            $admin_email
                        );
                        ?>
                    </small>

                </div>


                <a
                    href="admin_login.php?logout=1"
                    class="logout-icon"
                    title="Logout"
                >
                    ↪
                </a>


            </div>


        </div>


    </aside>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">


        <!-- HEADER -->

        <header class="top-header">


            <div class="desk-title">

                <span class="online-dot"></span>

                <span>
                    CAMPUS TRANSIT OPERATIONS DESK
                </span>

            </div>


            <div class="top-right">


                <div class="db-status">

                    <span></span>

                    Connected to DB

                </div>


                <span class="bell">
                    ♧
                </span>


                <div class="top-user">

                    <div class="top-avatar">
                        <?php
                        echo strtoupper(
                            substr($admin_name, 0, 1)
                        );
                        ?>
                    </div>

                    <?php
                    echo htmlspecialchars(
                        $admin_name
                    );
                    ?>

                </div>


            </div>


        </header>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <section class="content">


            <!-- TITLE -->

            <div class="page-title-row">


                <div>


                    <div class="title-line">

                        <h1>
                            Vehicle Fleet &amp; Verification
                        </h1>


                        <span class="title-badge">
                            ACTIVE OPS
                        </span>

                    </div>


                    <p>
                        Inspect registered vehicles, verify
                        insurance policies, and monitor
                        campus permits
                    </p>


                </div>


                <div class="title-actions">


                    <a
                        href="#inspection"
                        class="inspection-btn"
                    >
                        ◉&nbsp; Submit New Inspection
                    </a>


                    <a
                        href="#filters"
                        class="download-btn"
                        title="Open filters"
                    >
                        ⇩
                    </a>


                </div>


            </div>


            <!-- =================================================
                 STATS
            ================================================== -->

            <div class="stats">


                <div class="stat-card">

                    <div class="stat-icon purple">
                        ▣
                    </div>

                    <div>

                        <strong>
                            <?php echo $total; ?>
                        </strong>

                        <span>
                            Total Fleet Vehicles
                        </span>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon green">
                        ✓
                    </div>

                    <div>

                        <strong>
                            <?php echo $approved; ?>
                        </strong>

                        <span>
                            Approved &amp; Active
                        </span>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon amber">
                        ♧
                    </div>

                    <div>

                        <strong>
                            <?php echo $pending; ?>
                        </strong>

                        <span>
                            Pending Inspection
                        </span>

                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon red">
                        △
                    </div>

                    <div>

                        <strong>
                            <?php echo $suspended; ?>
                        </strong>

                        <span>
                            Suspended / Expired
                        </span>

                    </div>

                </div>


            </div>


            <!-- =================================================
                 FILTER BOX
            ================================================== -->

            <div
                class="filter-box"
                id="filters"
            >


                <form
                    method="GET"
                    action="admin_vehicle_fleet.php"
                >


                    <div class="search-row">


                        <div class="search-field">

                            <span>⌕</span>

                            <input
                                type="text"
                                name="search"
                                placeholder="Search vehicle model, plate number, or owner name"
                                value="<?php
                                echo htmlspecialchars($search);
                                ?>"
                            >

                        </div>


                        <select
                            name="status"
                            class="filter-select"
                        >

                            <option
                                value="All Status"
                                <?php
                                if ($status === "All Status") {
                                    echo "selected";
                                }
                                ?>
                            >
                                All Status
                            </option>


                            <option
                                value="Pending Inspection"
                                <?php
                                if ($status === "Pending Inspection") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Pending
                            </option>


                            <option
                                value="Approved & Active"
                                <?php
                                if ($status === "Approved & Active") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Approved
                            </option>


                            <option
                                value="Suspended / Expired"
                                <?php
                                if ($status === "Suspended / Expired") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Flagged
                            </option>


                        </select>


                        <details class="more-filters">


                            <summary>
                                ☷ &nbsp; More Filters
                            </summary>


                            <div class="more-filter-panel">


                                <label>

                                    Department

                                    <input
                                        type="text"
                                        name="department"
                                        placeholder="CSE, BBA, EEE..."
                                        value="<?php
                                        echo htmlspecialchars(
                                            $department
                                        );
                                        ?>"
                                    >

                                </label>


                                <label>

                                    Sort

                                    <select name="sort">

                                        <option
                                            value="newest"
                                            <?php
                                            if ($sort === "newest") {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Newest
                                        </option>


                                        <option
                                            value="oldest"
                                            <?php
                                            if ($sort === "oldest") {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Oldest
                                        </option>


                                        <option
                                            value="owner"
                                            <?php
                                            if ($sort === "owner") {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            Owner Name
                                        </option>

                                    </select>

                                </label>


                            </div>


                        </details>


                        <select
                            name="sort"
                            class="sort-select"
                        >

                            <option
                                value="newest"
                                <?php
                                if ($sort === "newest") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Sort: Newest
                            </option>


                            <option
                                value="oldest"
                                <?php
                                if ($sort === "oldest") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Sort: Oldest
                            </option>


                            <option
                                value="owner"
                                <?php
                                if ($sort === "owner") {
                                    echo "selected";
                                }
                                ?>
                            >
                                Sort: Owner
                            </option>

                        </select>


                    </div>


                    <div class="category-row">


                        <span>
                            Category:
                        </span>


                        <button
                            type="submit"
                            name="category"
                            value="All Fleets"
                            class="category-pill
                            <?php
                            echo (
                                $category === "All Fleets"
                            )
                                ? "selected"
                                : "";
                            ?>"
                        >
                            All Fleets
                        </button>


                        <button
                            type="submit"
                            name="category"
                            value="Car"
                            class="category-pill
                            <?php
                            echo (
                                $category === "Car"
                            )
                                ? "selected"
                                : "";
                            ?>"
                        >
                            Sedan (Private Car)
                        </button>


                        <button
                            type="submit"
                            name="category"
                            value="Bike"
                            class="category-pill
                            <?php
                            echo (
                                $category === "Bike"
                            )
                                ? "selected"
                                : "";
                            ?>"
                        >
                            Motorbike
                        </button>


                        <span class="category-pill muted">
                            CNG Auto Share
                        </span>


                        <span class="category-pill muted">
                            Rickshaw Fleet
                        </span>


                        <span class="category-pill muted">
                            Campus Shuttle
                        </span>


                    </div>


                </form>


            </div>


            <!-- =================================================
                 RESULT HEADER
            ================================================== -->

            <div class="result-header">


                <span>

                    Showing
                    <?php echo $start_number; ?>–<?php echo $end_number; ?>

                    of

                    <?php echo $filtered_total; ?>

                    registered campus vehicles

                </span>


                <a
                    href="#inspection"
                    class="batch-link"
                >
                    Batch Verification Mode
                </a>


            </div>


            <!-- =================================================
                 VEHICLE GRID
            ================================================== -->

            <div class="vehicle-grid">


                <?php if (mysqli_num_rows($result) > 0) { ?>


                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>


                        <?php

                        $initial = strtoupper(
                            substr(
                                $row["full_name"],
                                0,
                                1
                            )
                        );


                        $status_class = "pending";


                        if (
                            $row["vehicle_status"]
                            === "Approved & Active"
                        ) {

                            $status_class = "approved";

                        } elseif (
                            $row["vehicle_status"]
                            === "Suspended / Expired"
                        ) {

                            $status_class = "expired";
                        }

                        ?>


                        <article class="vehicle-card">


                            <!-- CARD TOP -->

                            <div class="card-top">


                                <div class="owner-avatar">

                                    <?php
                                    echo htmlspecialchars(
                                        $initial
                                    );
                                    ?>

                                </div>


                                <div class="owner-info">


                                    <b>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["full_name"]
                                        );
                                        ?>

                                    </b>


                                    <span>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["student_id"]
                                        );
                                        ?>

                                        •

                                        <?php
                                        echo htmlspecialchars(
                                            $row["department"]
                                        );
                                        ?>

                                    </span>


                                </div>


                                <div class="status-block">


                                    <span
                                        class="status
                                        <?php echo $status_class; ?>"
                                    >


                                        <?php
                                        if (
                                            $status_class
                                            === "approved"
                                        ) {
                                            echo "✓";
                                        } elseif (
                                            $status_class
                                            === "expired"
                                        ) {
                                            echo "!";
                                        } else {
                                            echo "•";
                                        }
                                        ?>


                                        <?php
                                        echo htmlspecialchars(
                                            $row["vehicle_status"]
                                        );
                                        ?>


                                    </span>


                                    <small>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["vehicle_type"]
                                        );
                                        ?>

                                    </small>


                                </div>


                            </div>


                            <!-- VEHICLE SUMMARY -->

                            <div class="vehicle-summary">


                                <div class="vehicle-name">


                                    <span class="vehicle-symbol">

                                        <?php
                                        echo (
                                            $row["vehicle_type"]
                                            === "Bike"
                                        )
                                            ? "♧"
                                            : "▣";
                                        ?>

                                    </span>


                                    <div>


                                        <b>

                                            <?php
                                            echo htmlspecialchars(
                                                $row["vehicle_type"]
                                            );
                                            ?>

                                        </b>


                                        <span>

                                            <?php
                                            echo htmlspecialchars(
                                                $row["registration_number"]
                                            );
                                            ?>

                                        </span>


                                    </div>


                                </div>


                                <div class="seat-count">


                                    <b>

                                        <?php
                                        echo (int)
                                            $row["seat_capacity"];
                                        ?>

                                    </b>


                                    <span>

                                        <?php
                                        echo (
                                            (int)
                                            $row["seat_capacity"]
                                            === 1
                                        )
                                            ? "Helmet"
                                            : "Seats";
                                        ?>

                                    </span>


                                </div>


                            </div>


                            <!-- DETAILS -->

                            <div class="vehicle-details">


                                <div>

                                    <span>
                                        Registered
                                    </span>


                                    <b>

                                        <?php

                                        echo date(
                                            "M j, Y",
                                            strtotime(
                                                $row["created_at"]
                                            )
                                        );

                                        ?>

                                    </b>

                                </div>


                                <div>

                                    <span>
                                        Owner Email
                                    </span>


                                    <b>

                                        <?php
                                        echo htmlspecialchars(
                                            $row["university_email"]
                                        );
                                        ?>

                                    </b>

                                </div>


                            </div>


                            <!-- ACTIONS -->

                            <div class="card-actions">


                                <?php if (
                                    $status_class
                                    === "pending"
                                ) { ?>


                                    <!-- REJECT -->

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="vehicle_id"
                                            value="<?php
                                            echo (int)
                                                $row["vehicle_id"];
                                            ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="reject"
                                            class="light-btn danger-text"
                                        >
                                            Reject
                                        </button>

                                    </form>


                                    <!-- APPROVE -->

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="vehicle_id"
                                            value="<?php
                                            echo (int)
                                                $row["vehicle_id"];
                                            ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="purple-btn"
                                        >
                                            Approve Vehicle
                                        </button>

                                    </form>


                                <?php } elseif (
                                    $status_class
                                    === "approved"
                                ) { ?>


                                    <a
                                        href="#vehicle-<?php
                                        echo (int)
                                            $row["vehicle_id"];
                                        ?>"
                                        class="light-btn full-btn"
                                    >
                                        View Vehicle Details
                                    </a>


                                    <!-- SUSPEND -->

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="vehicle_id"
                                            value="<?php
                                            echo (int)
                                                $row["vehicle_id"];
                                            ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="suspend"
                                            class="text-btn"
                                        >
                                            Suspend
                                        </button>

                                    </form>


                                <?php } else { ?>


                                    <!-- MOVE TO INSPECTION -->

                                    <form
                                        method="POST"
                                        class="wide-form"
                                    >

                                        <input
                                            type="hidden"
                                            name="vehicle_id"
                                            value="<?php
                                            echo (int)
                                                $row["vehicle_id"];
                                            ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="pending"
                                            class="light-btn full-btn"
                                        >
                                            Move to Inspection
                                        </button>

                                    </form>


                                    <!-- APPROVE -->

                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="vehicle_id"
                                            value="<?php
                                            echo (int)
                                                $row["vehicle_id"];
                                            ?>"
                                        >


                                        <button
                                            type="submit"
                                            name="action"
                                            value="approve"
                                            class="purple-btn"
                                        >
                                            Approve
                                        </button>

                                    </form>


                                <?php } ?>


                            </div>


                        </article>


                    <?php } ?>


                <?php } else { ?>


                    <div class="empty-state">

                        <h3>
                            No vehicles found
                        </h3>


                        <p>
                            No registered campus vehicles
                            match the current filters.
                        </p>

                    </div>


                <?php } ?>


            </div>


            <!-- =================================================
                 PAGINATION
            ================================================== -->

            <div class="pagination">


                <span>

                    Page
                    <?php echo $page; ?>
                    of
                    <?php echo $total_pages; ?>

                    • Auto-syncing fleet data

                </span>


                <div class="page-buttons">


                    <?php if ($page > 1) { ?>


                        <a
                            href="<?php
                            echo htmlspecialchars(
                                page_link($page - 1)
                            );
                            ?>"
                        >
                            ‹
                        </a>


                    <?php } else { ?>


                        <span class="disabled">
                            ‹
                        </span>


                    <?php } ?>


                    <?php

                    $visible_pages = min(
                        5,
                        $total_pages
                    );


                    for (
                        $i = 1;
                        $i <= $visible_pages;
                        $i++
                    ) {

                    ?>


                        <a
                            href="<?php
                            echo htmlspecialchars(
                                page_link($i)
                            );
                            ?>"
                            class="<?php
                            echo (
                                $i === $page
                            )
                                ? "current"
                                : "";
                            ?>"
                        >
                            <?php echo $i; ?>
                        </a>


                    <?php } ?>


                    <?php if ($total_pages > 5) { ?>


                        <span>
                            ...
                        </span>


                        <a
                            href="<?php
                            echo htmlspecialchars(
                                page_link(
                                    $total_pages
                                )
                            );
                            ?>"
                        >
                            <?php echo $total_pages; ?>
                        </a>


                    <?php } ?>


                    <?php if ($page < $total_pages) { ?>


                        <a
                            href="<?php
                            echo htmlspecialchars(
                                page_link($page + 1)
                            );
                            ?>"
                        >
                            ›
                        </a>


                    <?php } else { ?>


                        <span class="disabled">
                            ›
                        </span>


                    <?php } ?>


                </div>


            </div>


        </section>


    </main>


</div>


</body>

</html>