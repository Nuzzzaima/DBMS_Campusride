<?php

session_start();
include "db.php";

$error = "";


// If user is already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {

    header("Location: dashboard.php");
    exit();

}


// Get message from URL
$msg = isset($_GET['msg']) ? $_GET['msg'] : "";


// Check if login form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = trim($_POST['student_id']);
    $password = $_POST['password'];


    // Check empty fields
    if (empty($studentId) || empty($password)) {

        $error = "Please enter your Student ID and password.";

    } else {

        // Convert entered password to SHA-256
        $hashedPassword = hash("sha256", $password);


        // Find student using Student ID
        $sql = "SELECT *
                FROM USER
                WHERE student_id = '$studentId'
                LIMIT 1";

        $result = mysqli_query($conn, $sql);


        // Check if query worked
        if (!$result) {

            $error = "Database error: " . mysqli_error($conn);

        } elseif (mysqli_num_rows($result) == 1) {

            // Get user information
            $user = mysqli_fetch_assoc($result);


            // Check password
            if ($user['password'] === $hashedPassword) {

                // Store logged-in user's information
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['full_name'];
                $_SESSION['email'] = $user['university_email'];


                // Login successful
                header("Location: dashboard.php");
                exit();

            } else {

                $error = "Invalid Student ID or Password.";

            }

        } else {

            $error = "Invalid Student ID or Password.";

        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Login - CampusRide UIU</title>


    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }


        body {
            background: #f8fafc;
            color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }


        .card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 36px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.04);
        }


        .brand-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            background: #7c3aed;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25);
        }


        h1 {
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 6px;
        }


        p {
            font-size: 13.5px;
            color: #64748b;
            text-align: center;
            margin-bottom: 24px;
        }


        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
        }


        .form-group {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }


        label {
            font-size: 12.5px;
            font-weight: 700;
            color: #475569;
        }


        input {
            padding: 12px 14px;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 14px;
            outline: none;
            transition: border 0.15s, box-shadow 0.15s;
        }


        input:focus {
            border-color: #7c3aed;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }


        input::placeholder {
            color: #94a3b8;
            font-weight: 400;
            opacity: 0.8;
        }


        .btn {
            background: #7c3aed;
            color: white;
            padding: 12px;
            border-radius: 10px;
            border: none;
            font-size: 14px;
            font-weight: 700;
            width: 100%;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.15s;
        }


        .btn:hover {
            background: #6d28d9;
        }


        .footer-links {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #64748b;
        }


        .footer-links a {
            color: #7c3aed;
            font-weight: 700;
            text-decoration: none;
        }

    </style>

</head>


<body>

    <div class="card">


        <a href="index.php" style="display:block; text-decoration:none;">

            <div class="brand-icon">
                CR
            </div>

        </a>


        <h1>
            Student Login
        </h1>


        <p>
            Sign in to your verified UIU account
        </p>


        <?php if (!empty($error)): ?>

            <div
                class="alert"
                style="
                    background:#fff1f2;
                    color:#9f1239;
                    border:1px solid #fecdd3;
                "
            >

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <?php if (!empty($msg)): ?>

            <div
                class="alert"
                style="
                    background:#ecfdf5;
                    color:#065f46;
                    border:1px solid #a7f3d0;
                "
            >

                <?= htmlspecialchars($msg) ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label>
                    Student ID
                </label>

                <input
                    type="text"
                    name="student_id"
                    placeholder="e.g. 0112310001"
                    required
                    autocomplete="off"
                >

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="At least 6 characters"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn"
            >
                Sign In to Dashboard
            </button>


        </form>


        <div class="footer-links">

            New student?

            <a href="register.php">
                Create an account
            </a>


            <div style="margin-top:8px;">

                <a
                    href="index.php"
                    style="color:#94a3b8; font-weight:600;"
                >
                    ← Back to Home
                </a>

            </div>

        </div>


    </div>

</body>

</html>
