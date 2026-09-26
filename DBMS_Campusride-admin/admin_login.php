<?php
session_start();

include "db.php";

$error = "";

if (isset($_SESSION['admin_user_id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login == "" || $password == "") {

        $error = "Please enter your email/username and password.";

    } else {

        $login_safe = mysqli_real_escape_string($conn, $login);
        $password_hash = hash("sha256", $password);

        $sql = "SELECT user_id, student_id, full_name, university_email
        FROM `USER`
        WHERE (university_email = '$login_safe'
            OR student_id = '$login_safe')
        AND password = '$password_hash'
        AND role = 'Admin'
        LIMIT 1";

        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {

            $user = mysqli_fetch_assoc($result);

            $_SESSION["admin_user_id"] = $user["user_id"];
            $_SESSION["admin_name"] = $user["full_name"];
            $_SESSION["admin_email"] = $user["university_email"];

            header("Location: admin_dashboard.php");
            exit();

        } else {

            $error = "Invalid email/username or password. Please check your credentials.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - CampusRide</title>
    <link rel="stylesheet" href="admin_login.css">
</head>

<body>

<div class="top-bar">

    <div class="brand">

        <div class="brand-icon">
            ▣
        </div>

        <div>
            <b>CampusRide</b>
            <small>UIU Operations</small>
        </div>

    </div>

    <div class="access">
        ♙ Authorized Access Only
    </div>

</div>


<div class="login-area">

    <?php if ($error != "") { ?>

        <div class="error-box">

            <div class="error-icon">!</div>

            <div>
                <b>Authentication Failed</b>
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>

            <span class="close">×</span>

        </div>

    <?php } ?>


    <div class="login-card">

        <div class="login-icon">
            ♧
        </div>

        <h1>Admin Login</h1>

        <p class="subtitle">
            Sign in to access the admin panel
        </p>


        <form method="POST" action="admin_login.php">

            <div class="field">

                <label>ADMIN EMAIL OR USERNAME</label>

                <input
                    type="text"
                    name="login"
                    placeholder="Enter admin email or username"
                    value="<?php echo htmlspecialchars($_POST["login"] ?? ""); ?>"
                >

            </div>


            <div class="field password-field">

                <div class="label-row">

                    <label>PASSWORD</label>

                    <span>Forgot password?</span>

                </div>

                <input
                    type="password"
                    name="password"
                    placeholder="Enter your password"
                >

            </div>


            <label class="remember">

                <input
                    type="checkbox"
                    name="remember"
                >

                Keep me logged in

            </label>


            <button type="submit" class="login-button">
                ↪ &nbsp; Log In
            </button>

        </form>


        <div class="session-link">
            ↪ &nbsp; Currently logged in elsewhere? Log out of all sessions
        </div>

    </div>

</div>


<div class="footer">
    © <?php echo date("Y"); ?> United International University • UIU CampusRide System
</div>

</body>
</html>
