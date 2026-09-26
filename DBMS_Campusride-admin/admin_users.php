<?php
session_start();

include "db.php";

if (!isset($_SESSION["admin_user_id"])) {
    header("Location: admin_login.php");
    exit();
}

$admin_name = $_SESSION["admin_name"] ?? "Admin";
$admin_email = $_SESSION["admin_email"] ?? "";


/* ALLOWED ACCOUNT STATUSES */

$status_options = [
    "Active",
    "Pending Verification",
    "Suspended"
];


/* UPDATE USER STATUS */

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_status"])) {

    $user_id = (int)$_POST["user_id"];
    $status = mysqli_real_escape_string($conn, $_POST["status"] ?? "");

    if (in_array($status, $status_options, true)) {

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


/* SEARCH */

$search = trim($_GET["search"] ?? "");


/* FILTERS */

$status_filter = $_GET["status"] ?? "";
$department_filter = $_GET["department"] ?? "";


/* PAGINATION */

$per_page = 10;

$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;

if ($page < 1) {
    $page = 1;
}


/* BUILD WHERE CONDITION */

$where = "WHERE 1=1";


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


/* TOTAL USERS */

$total_result = mysqli_query(
    $conn,
    "SELECT COUNT(*) AS total
     FROM `USER`
     $where"
);

$total_row = mysqli_fetch_assoc($total_result);

$total_users = $total_row["total"];

$total_pages = ceil($total_users / $per_page);

if ($total_pages < 1) {
    $total_pages = 1;
}

if ($page > $total_pages) {
    $page = $total_pages;
}

$offset = ($page - 1) * $per_page;


/* DEPARTMENT OPTIONS */

$department_result = mysqli_query(
    $conn,
    "SELECT DISTINCT department
     FROM `USER`
     WHERE department <> ''
     ORDER BY department"
);


/* USER COUNTS */

$status_counts = [];

foreach ($status_options as $status) {
    $status_counts[$status] = 0;
}

$status_counts_result = mysqli_query(
    $conn,
    "SELECT account_status, COUNT(*) AS total
     FROM `USER`
     GROUP BY account_status"
);

while ($status_count = mysqli_fetch_assoc($status_counts_result)) {

    $account_status = $status_count["account_status"];

    if (isset($status_counts[$account_status])) {
        $status_counts[$account_status] = $status_count["total"];
    }
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

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Users Management - CampusRide Admin</title>

    <link rel="stylesheet" href="admin_users.css">

</head>

<body>

<div class="admin-container">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <h2>CampusRide</h2>

            <p>Admin Panel</p>

        </div>


        <nav class="sidebar-nav">

            <a href="admin_dashboard.php">
                Dashboard
            </a>

            <a href="admin_users.php" class="active">
                Users Management
            </a>

            <a href="admin_vehicle_fleet.php">
                Vehicle Fleet
            </a>

            <a href="admin_rides.php">
                Ride Management
            </a>

            <a href="admin_requests.php">
                Ride Requests
            </a>

            <a href="admin_bookings.php">
                Bookings
            </a>

            <a href="admin_sos.php">
                SOS Alerts
            </a>

        </nav>


        <div class="sidebar-bottom">

            <a href="admin_dashboard.php?logout=1">
                Logout
            </a>

        </div>

    </aside>


    <!-- MAIN CONTENT -->

    <main class="main-content">


        <!-- TOP BAR -->

        <div class="top-bar">

            <div>

                <h1>Users Management</h1>

                <p>Manage CampusRide users and their account status.</p>

            </div>


            <div class="admin-info">

                <strong>
                    <?php echo htmlspecialchars($admin_name); ?>
                </strong>

                <span>
                    <?php echo htmlspecialchars($admin_email); ?>
                </span>

            </div>

        </div>


        <!-- USER STATUS SUMMARY -->

        <div class="status-summary">


            <div class="summary-card">

                <h3>Active Users</h3>

                <p>
                    <?php echo $active_users; ?>
                </p>

            </div>


            <div class="summary-card">

                <h3>Pending Verification</h3>

                <p>
                    <?php echo $pending_users; ?>
                </p>

            </div>


            <div class="summary-card">

                <h3>Suspended Users</h3>

                <p>
                    <?php echo $suspended_users; ?>
                </p>

            </div>


            <div class="summary-card">

                <h3>Total Users</h3>

                <p>
                    <?php echo $total_users; ?>
                </p>

            </div>


        </div>


        <!-- SEARCH AND FILTER -->

        <div class="filter-section">

            <form method="GET" action="admin_users.php">


                <div class="filter-group">

                    <label for="search">
                        Search
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Name, student ID or email"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>


                <div class="filter-group">

                    <label for="status">
                        Status
                    </label>

                    <select name="status" id="status">

                        <option value="">
                            All Statuses
                        </option>

                        <?php foreach ($status_options as $status) { ?>

                            <option
                                value="<?php echo htmlspecialchars($status); ?>"
                                <?php if ($status_filter == $status) echo "selected"; ?>
                            >
                                <?php echo htmlspecialchars($status); ?>
                            </option>

                        <?php } ?>

                    </select>

                </div>


                <div class="filter-group">

                    <label for="department">
                        Department
                    </label>

                    <select name="department" id="department">

                        <option value="">
                            All Departments
                        </option>

                        <?php while ($department = mysqli_fetch_assoc($department_result)) { ?>

                            <option
                                value="<?php echo htmlspecialchars($department["department"]); ?>"
                                <?php
                                if ($department_filter == $department["department"]) {
                                    echo "selected";
                                }
                                ?>
                            >
                                <?php echo htmlspecialchars($department["department"]); ?>
                            </option>

                        <?php } ?>

                    </select>

                </div>


                <div class="filter-buttons">

                    <button type="submit">
                        Apply Filters
                    </button>

                    <a href="admin_users.php">
                        Clear
                    </a>

                </div>


            </form>

        </div>


        <!-- USERS TABLE -->

        <div class="users-section">

            <div class="section-header">

                <h2>All Users</h2>

                <span>
                    <?php echo $total_users; ?> users found
                </span>

            </div>


            <div class="table-container">

                <table>

                    <thead>

                        <tr>

                            <th>User</th>

                            <th>Student ID</th>

                            <th>Email</th>

                            <th>Department</th>

                            <th>Status</th>

                            <th>Joined</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (mysqli_num_rows($user_result) > 0) { ?>


                        <?php while ($user = mysqli_fetch_assoc($user_result)) { ?>

                            <tr>


                                <!-- USER -->

                                <td>

                                    <div class="user-info">


                                        <?php if (!empty($user["profile_picture"])) { ?>

                                            <img
                                                src="<?php echo htmlspecialchars($user["profile_picture"]); ?>"
                                                alt="Profile"
                                                class="user-avatar"
                                            >

                                        <?php } else { ?>

                                            <div class="user-avatar-placeholder">

                                                <?php
                                                echo strtoupper(
                                                    substr($user["full_name"], 0, 1)
                                                );
                                                ?>

                                            </div>

                                        <?php } ?>


                                        <div>

                                            <strong>
                                                <?php
                                                echo htmlspecialchars(
                                                    $user["full_name"]
                                                );
                                                ?>
                                            </strong>

                                            <small>
                                                User ID:
                                                <?php echo $user["user_id"]; ?>
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- STUDENT ID -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["student_id"]
                                    );
                                    ?>

                                </td>


                                <!-- EMAIL -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["university_email"]
                                    );
                                    ?>

                                </td>


                                <!-- DEPARTMENT -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["department"]
                                    );
                                    ?>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <span class="status-badge">

                                        <?php
                                        echo htmlspecialchars(
                                            $user["account_status"]
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- JOINED -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $user["created_at"]
                                    );
                                    ?>

                                </td>


                                <!-- ACTION -->

                                <td>

                                    <div class="action-buttons">


                                        <a
                                            href="admin_user_details.php?id=<?php echo $user["user_id"]; ?>"
                                        >
                                            View
                                        </a>


                                        <form
                                            method="POST"
                                            action="admin_users.php"
                                        >

                                            <input
                                                type="hidden"
                                                name="user_id"
                                                value="<?php echo $user["user_id"]; ?>"
                                            >


                                            <select name="status">

                                                <?php foreach ($status_options as $status) { ?>

                                                    <option
                                                        value="<?php echo htmlspecialchars($status); ?>"
                                                        <?php
                                                        if ($user["account_status"] == $status) {
                                                            echo "selected";
                                                        }
                                                        ?>
                                                    >
                                                        <?php echo htmlspecialchars($status); ?>
                                                    </option>

                                                <?php } ?>

                                            </select>


                                            <button
                                                type="submit"
                                                name="change_status"
                                                value="1"
                                            >
                                                Update
                                            </button>

                                        </form>


                                    </div>

                                </td>


                            </tr>

                        <?php } ?>


                    <?php } else { ?>

                        <tr>

                            <td colspan="7" class="no-users">

                                No users found.

                            </td>

                        </tr>

                    <?php } ?>

                    </tbody>

                </table>

            </div>


            <!-- PAGINATION -->

            <?php if ($total_pages > 1) { ?>

                <div class="pagination">


                    <?php if ($page > 1) { ?>

                        <a
                            href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo urlencode($department_filter); ?>"
                        >
                            Previous
                        </a>

                    <?php } ?>


                    <?php for ($i = 1; $i <= $total_pages; $i++) { ?>

                        <a
                            href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo urlencode($department_filter); ?>"
                            class="<?php if ($i == $page) echo 'active'; ?>"
                        >
                            <?php echo $i; ?>
                        </a>

                    <?php } ?>


                    <?php if ($page < $total_pages) { ?>

                        <a
                            href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>&department=<?php echo urlencode($department_filter); ?>"
                        >
                            Next
                        </a>

                    <?php } ?>


                </div>

            <?php } ?>


        </div>


    </main>

</div>

</body>

</html>