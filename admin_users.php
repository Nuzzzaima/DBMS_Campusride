<?php
session_start();

include "db.php";

if (!isset($_SESSION["admin_user_id"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin_name"] ?? "Admin";
$admin_email = $_SESSION["admin_email"] ?? "";


/* UPDATE USER STATUS */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_status"])) {

    $user_id = (int)$_POST["user_id"];
    $status = mysqli_real_escape_string($conn, $_POST["status"] ?? "");

    $allowed_result = mysqli_query(
        $conn,
        "SELECT status_name
         FROM user_account_status
         ORDER BY status_id"
    );

    $allowed_statuses = [];

    while ($status_row = mysqli_fetch_assoc($allowed_result)) {
        $allowed_statuses[] = $status_row["status_name"];
    }

    if (in_array($status, $allowed_statuses, true)) {

        mysqli_query(
            $conn,
            "UPDATE `USER`
             SET account_status = '$status'
             WHERE user_id = $user_id"
        );
    }

    header("Location: admin_users.php");
    exit();
}


/* LOGOUT */

if (isset($_GET["logout"])) {

    session_unset();
    session_destroy();

    header("Location: admin_login.php");
    exit();
}


/* SEARCH AND FILTERS */

$search = trim($_GET["search"] ?? "");
$status_filter = trim($_GET["status"] ?? "");
$department_filter = trim($_GET["department"] ?? "");

$page = (int)($_GET["page"] ?? 1);

if ($page < 1) {
    $page = 1;
}

$per_page = 6;
$offset = ($page - 1) * $per_page;


/* STATUS OPTIONS */

$status_result = mysqli_query(
    $conn,
    "SELECT status_name
     FROM user_account_status
     ORDER BY status_id"
);


/* DEPARTMENT OPTIONS */

$department_result = mysqli_query(
    $conn,
    "SELECT DISTINCT department
     FROM `USER`
     WHERE department <> ''
     ORDER BY department"
);


/* BUILD WHERE */

$where = " WHERE 1=1 ";

if ($search != "") {

    $safe_search = mysqli_real_escape_string($conn, $search);

    $where .= " AND (
                    full_name LIKE '%$safe_search%'
                    OR student_id LIKE '%$safe_search%'
                    OR university_email LIKE '%$safe_search%'
                )";
}

if ($status_filter != "") {

    $safe_status = mysqli_real_escape_string($conn, $status_filter);

    $where .= " AND account_status = '$safe_status'";
}

if ($department_filter != "") {

    $safe_department = mysqli_real_escape_string($conn, $department_filter);

    $where .= " AND department = '$safe_department'";
}


/* USER COUNTS */

$total_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM `USER`
     $where"
);

$total_users = mysqli_fetch_assoc($total_result)["total"];


$status_counts_result = mysqli_query(
    $conn,
    "SELECT
        s.status_name,
        COUNT(u.user_id) AS total
     FROM user_account_status s
     LEFT JOIN `USER` u
        ON u.account_status = s.status_name
     GROUP BY s.status_id, s.status_name
     ORDER BY s.status_id"
);

$status_counts = [];

while ($status_count = mysqli_fetch_assoc($status_counts_result)) {
    $status_counts[$status_count["status_name"]] = $status_count["total"];
}

$active_users = $status_counts["Active"] ?? 0;
$pending_users = $status_counts["Pending Verification"] ?? 0;
$suspended_users = $status_counts["Suspended"] ?? 0;


/* GET USERS */

$user_sql = "SELECT
                user_id,
                student_id,
                full_name,
                university_email,
                profile_picture,
                department,
                account_status,
                created_at
             FROM `USER`
             $where
             ORDER BY created_at DESC
             LIMIT $offset, $per_page";

$user_result = mysqli_query($conn, $user_sql);


/* PAGINATION */

$total_pages = 0;

if ($total_users > 0) {
    $total_pages = ceil($total_users / $per_page);
}


/* CURRENT FILTERS FOR PAGINATION */

$query_string = "";

if ($search != "") {
    $query_string .= "&search=" . urlencode($search);
}

if ($status_filter != "") {
    $query_string .= "&status=" . urlencode($status_filter);
}

if ($department_filter != "") {
    $query_string .= "&department=" . urlencode($department_filter);
}

?>

<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Users Management - CampusRide</title>

    <link rel="stylesheet" href="admin_users.css">

</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->

    <div class="sidebar">

        <div class="logo">

            <div class="logo-icon">
               🚗
            </div>

            <div>
                <b>CampusRide</b>
                <small>Operations Console</small>
            </div>

        </div>


        <div class="menu">

            <a href="admin_dashboard.php">
                Dashboard
            </a>

            <a href="admin_users.php" class="active">
                Users Management
            </a>

            <a href="admin_vehicle_fleet.php">
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
            <a href="admin_sos.php">SOS Alerts</a>

            <a href="#">
                Settings
            </a>

        </div>


        <div class="sidebar-bottom">

            <div class="admin-profile">

                <div class="admin-pic">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>

                <div class="admin-info">

                    <b>
                        <?php echo htmlspecialchars($admin_name); ?>
                    </b>

                    <small>
                        <?php echo htmlspecialchars($admin_email); ?>
                    </small>

                </div>

                <a
                    href="admin_users.php?logout=1"
                    class="logout"
                >
                    ↪
                </a>

            </div>

        </div>

    </div>


    <!-- MAIN -->

    <div class="main">

        <!-- TOP HEADER -->

        <div class="top-header">

            <div class="desk-name">
                ● &nbsp; CAMPUS TRANSIT OPERATIONS DESK
            </div>

            <div class="top-right">

                <span class="db-status">
                    <i></i> Connected to DB
                </span>

                <span class="bell">
                    ♧
                </span>

                <span class="top-user">
                    ● &nbsp; UIU Ops Team
                </span>

            </div>

        </div>


        <div class="content">

            <!-- PAGE HEADER -->

            <div class="page-heading">

                <div>

                    <h1>Users Directory</h1>

                    <p>
                        View, search, filter and manage registered UIU students and staff accounts
                    </p>

                </div>


                <div class="user-stats">

                    <div class="mini-stat purple">

                        <span>♙</span>

                        <div>
                            <small>Total Registered</small>
                            <b><?php echo number_format($total_users); ?></b>
                        </div>

                    </div>


                    <div class="mini-stat green">

                        <span>✓</span>

                        <div>
                            <small>Active</small>
                            <b><?php echo number_format($active_users); ?></b>
                        </div>

                    </div>


                    <div class="mini-stat yellow">

                        <span>◉</span>

                        <div>
                            <small>Pending</small>
                            <b><?php echo number_format($pending_users); ?></b>
                        </div>

                    </div>


                    <div class="mini-stat red">

                        <span>⊘</span>

                        <div>
                            <small>Suspended</small>
                            <b><?php echo number_format($suspended_users); ?></b>
                        </div>

                    </div>

                </div>

            </div>


            <!-- SEARCH -->

            <div class="search-panel">

                <form method="GET" action="admin_users.php">

                    <div class="search-row">

                        <div class="search-input">

                            <span>⌕</span>

                            <input
                                type="text"
                                name="search"
                                placeholder="Search by student name, UIU ID (011...), or institutional email..."
                                value="<?php echo htmlspecialchars($search); ?>"
                            >

                        </div>


                        <select name="status">

                            <option value="">
                                All Account Statuses
                            </option>

                            <?php while ($status = mysqli_fetch_assoc($status_result)) { ?>

                                <option
                                    value="<?php echo htmlspecialchars($status["status_name"]); ?>"
                                    <?php
                                    if ($status_filter == $status["status_name"]) {
                                        echo "selected";
                                    }
                                    ?>
                                >
                                    <?php echo htmlspecialchars($status["status_name"]); ?>
                                </option>

                            <?php } ?>

                        </select>


                        <button type="submit" class="search-btn">
                            Search
                        </button>

                        <a href="admin_users.php" class="clear-btn">
                            Clear
                        </a>

                    </div>


                    <div class="filter-row">

                        <span>Department:</span>

                        <a
                            href="admin_users.php<?php
                            if ($search != "") {
                                echo "?search=" . urlencode($search);
                            }
                            ?>"
                            class="<?php
                            if ($department_filter == "") {
                                echo "selected";
                            }
                            ?>"
                        >
                            All Departments
                        </a>

                        <?php while ($department = mysqli_fetch_assoc($department_result)) { ?>

                            <a
                                href="admin_users.php?department=<?php echo urlencode($department["department"]); ?><?php
                                if ($search != "") {
                                    echo "&search=" . urlencode($search);
                                }
                                if ($status_filter != "") {
                                    echo "&status=" . urlencode($status_filter);
                                }
                                ?>"
                                class="<?php
                                if ($department_filter == $department["department"]) {
                                    echo "selected";
                                }
                                ?>"
                            >
                                <?php echo htmlspecialchars($department["department"]); ?>
                            </a>

                        <?php } ?>

                    </div>

                </form>

            </div>


            <!-- USER TABLE -->

            <div class="user-panel">

                <div class="table-head">

                    <span>STUDENT /<br>MEMBER</span>
                    <span>UIU ID</span>
                    <span>CAMPUS EMAIL</span>
                    <span>DEPT</span>
                    <span>STATUS</span>
                    <span>CHANGE STATUS</span>
                    <span>VIEW INFO</span>

                </div>


                <?php if (mysqli_num_rows($user_result) > 0) { ?>

                    <?php while ($row = mysqli_fetch_assoc($user_result)) { ?>

                        <div class="user-row">

                            <div class="member">

                                <?php
                                $picture = $row["profile_picture"] ?? "";
                                $picture_exists = false;

                                if ($picture != "") {
                                    $picture_exists = file_exists(
                                        __DIR__ . "/" . $picture
                                    );
                                }
                                ?>

                                <div class="member-pic">

                                    <?php if ($picture_exists) { ?>

                                        <img
                                            src="<?php echo htmlspecialchars($picture); ?>"
                                            alt=""
                                        >

                                    <?php } else { ?>

                                        <?php
                                        echo strtoupper(
                                            substr($row["full_name"], 0, 1)
                                        );
                                        ?>

                                    <?php } ?>

                                </div>


                                <b>
                                    <?php
                                    echo htmlspecialchars(
                                        $row["full_name"]
                                    );
                                    ?>
                                </b>

                            </div>


                            <span class="student-id">
                                <?php
                                echo htmlspecialchars(
                                    $row["student_id"]
                                );
                                ?>
                            </span>


                            <span class="email">
                                <?php
                                echo htmlspecialchars(
                                    $row["university_email"]
                                );
                                ?>
                            </span>


                            <span>

                                <small class="department">
                                    <?php
                                    echo htmlspecialchars(
                                        $row["department"]
                                    );
                                    ?>
                                </small>

                            </span>


                            <span>

                                <?php
                                $status_class = "active";

                                if ($row["account_status"] == "Pending Verification") {
                                    $status_class = "pending";
                                } elseif ($row["account_status"] == "Suspended") {
                                    $status_class = "suspended";
                                }
                                ?>

                                <small class="status <?php echo $status_class; ?>">
                                    ●
                                    <?php
                                    echo htmlspecialchars(
                                        $row["account_status"]
                                    );
                                    ?>
                                </small>

                            </span>


                            <form
                                method="POST"
                                action="admin_users.php"
                                class="change-status"
                            >

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?php echo $row["user_id"]; ?>"
                                >

                                <select name="status">

                                    <?php
                                    $status_options = mysqli_query(
                                        $conn,
                                        "SELECT status_name
                                         FROM user_account_status
                                         ORDER BY status_id"
                                    );
                                    ?>

                                    <?php while ($option = mysqli_fetch_assoc($status_options)) { ?>

                                        <option
                                            value="<?php echo htmlspecialchars($option["status_name"]); ?>"
                                            <?php
                                            if ($option["status_name"] == $row["account_status"]) {
                                                echo "selected";
                                            }
                                            ?>
                                        >
                                            <?php
                                            echo htmlspecialchars(
                                                $option["status_name"]
                                            );
                                            ?>
                                        </option>

                                    <?php } ?>

                                </select>

                                <button
                                    type="submit"
                                    name="change_status"
                                    value="1"
                                    title="Save status"
                                >
                                    ✓
                                </button>

                            </form>


                            <a
                                href="admin_user_details.php?id=<?php echo $row["user_id"]; ?>"
                                class="view-btn"
                            >
                                ⓘ
                                <span>View<br>Info</span>
                            </a>

                        </div>

                    <?php } ?>

                <?php } else { ?>

                    <div class="no-users">
                        No users found.
                    </div>

                <?php } ?>


                <!-- PAGINATION -->

                <div class="pagination">

                    <span>
                        <?php

                        if ($total_users > 0) {

                            $first_number = $offset + 1;
                            $last_number = min(
                                $offset + $per_page,
                                $total_users
                            );

                            echo "Showing $first_number to $last_number of "
                                . number_format($total_users)
                                . " registered users";

                        } else {

                            echo "Showing 0 registered users";

                        }

                        ?>
                    </span>


                    <?php if ($total_pages > 0) { ?>

                        <div class="page-links">

                            <?php if ($page > 1) { ?>

                                <a href="admin_users.php?page=<?php echo $page - 1 . $query_string; ?>">
                                    ‹
                                </a>

                            <?php } else { ?>

                                <span>‹</span>

                            <?php } ?>


                            <?php
                            $start_page = max(1, $page - 1);
                            $end_page = min($total_pages, $page + 1);

                            for ($i = $start_page; $i <= $end_page; $i++) {
                            ?>

                                <a
                                    href="admin_users.php?page=<?php echo $i . $query_string; ?>"
                                    class="<?php
                                    if ($i == $page) {
                                        echo "current";
                                    }
                                    ?>"
                                >
                                    <?php echo $i; ?>
                                </a>

                            <?php } ?>


                            <?php if ($page < $total_pages) { ?>

                                <a href="admin_users.php?page=<?php echo $page + 1 . $query_string; ?>">
                                    Next ›
                                </a>

                            <?php } else { ?>

                                <span>Next ›</span>

                            <?php } ?>

                        </div>

                    <?php } ?>

                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>
