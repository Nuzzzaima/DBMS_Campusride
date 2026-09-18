
<?php

session_start();
include "db.php";

$error = "";


// Check if registration form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $studentId = trim($_POST['student_id']);
    $fullName = trim($_POST['full_name']);
    $email = strtolower(trim($_POST['university_email']));
    $phone = trim($_POST['phone']);
    $dept = trim($_POST['department']);
    $gender = trim($_POST['gender']);
    $password = $_POST['password'];


    // Check empty fields
    if (
        empty($studentId) ||
        empty($fullName) ||
        empty($email) ||
        empty($phone) ||
        empty($password)
    ) {

        $error = "Please fill in all required fields.";

    }


    // Check UIU email
    elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        !str_ends_with($email, '@uiu.ac.bd')
    ) {

        $error = "Official UIU email must end with @uiu.ac.bd";

    }


    // Check Student ID format
    elseif (!preg_match('/^[0-9]{9}$/', $studentId)) {

        $error = "Student ID must contain exactly 9 digits.";

    }


    else {

        // Check whether Student ID or email already exists
        $sql = "SELECT user_id
                FROM USER
                WHERE student_id = '$studentId'
                OR university_email = '$email'";

        $result = mysqli_query($conn, $sql);


        if (!$result) {

            $error = "Database error: " . mysqli_error($conn);

        }

        elseif (mysqli_num_rows($result) > 0) {

            $error = "Account with this Student ID or Email already exists.";

        }

        else {

            // Convert password to SHA-256
            $hash = hash("sha256", $password);


            // Default profile picture
            $profilePicture = "profile1.jpg";


            // Insert new student
            $sql = "INSERT INTO USER
                    (
                        student_id,
                        full_name,
                        university_email,
                        password,
                        gender,
                        phone,
                        department,
                        profile_picture
                    )
                    VALUES
                    (
                        '$studentId',
                        '$fullName',
                        '$email',
                        '$hash',
                        '$gender',
                        '$phone',
                        '$dept',
                        '$profilePicture'
                    )";


            if (mysqli_query($conn, $sql)) {

                // Registration successful
                header(
                    "Location: login.php?msg=" .
                    urlencode("Account created! Please sign in.")
                );

                exit();

            } else {

                $error = "Registration failed: " . mysqli_error($conn);

            }
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Student Registration - CampusRide UIU</title>


    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap"
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
            max-width: 480px;
        }


        h1 {
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            margin-bottom: 6px;
        }


        p {
            font-size: 13px;
            color: #64748b;
            text-align: center;
            margin-bottom: 20px;
        }


        .form-group {
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }


        label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
        }


        input,
        select {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font-size: 13px;
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
            margin-top: 14px;
        }


        .btn:hover {
            background: #6d28d9;
        }

    </style>

</head>


<body>


    <div class="card">


        <h1>
            Register Student Account
        </h1>


        <p>
            Join the verified UIU CampusRide network
        </p>


        <?php if (!empty($error)): ?>

            <div
                style="
                    background:#fff1f2;
                    color:#9f1239;
                    border:1px solid #fecdd3;
                    padding:10px 14px;
                    border-radius:8px;
                    font-size:12.5px;
                    margin-bottom:14px;
                "
            >

                <?= htmlspecialchars($error) ?>

            </div>

        <?php endif; ?>


        <form method="POST">


            <div class="form-group">

                <label>
                    UIU Student ID
                </label>

                <input
                    type="text"
                    name="student_id"
                    placeholder="e.g. 011241022"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Full Name
                </label>

                <input
                    type="text"
                    name="full_name"
                    placeholder="Jubayer Nurshi"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    University Email (@uiu.ac.bd)
                </label>

                <input
                    type="email"
                    name="university_email"
                    placeholder="name@uiu.ac.bd"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Phone Number
                </label>

                <input
                    type="text"
                    name="phone"
                    placeholder="01711000000"
                    required
                >

            </div>


            <div class="form-group">

                <label>
                    Academic Department
                </label>

                <select name="department">

                    <option value="CSE">
                        CSE
                    </option>

                    <option value="EEE">
                        EEE
                    </option>

                    <option value="BBA">
                        BBA
                    </option>

                    <option value="Civil">
                        Civil
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Gender
                </label>

                <select name="gender">

                    <option value="Male">
                        Male
                    </option>

                    <option value="Female">
                        Female
                    </option>

                </select>

            </div>


            <div class="form-group">

                <label>
                    Password
                </label>

                <input
                    type="password"
                    name="password"
                    placeholder="Create secure password"
                    required
                >

            </div>


            <button
                type="submit"
                class="btn"
            >
                Register Account
            </button>


        </form>


        <div
            style="
                text-align:center;
                margin-top:16px;
                font-size:13px;
                color:#64748b;
            "
        >

            Already have an account?

            <a
                href="login.php"
                style="
                    color:#7c3aed;
                    font-weight:700;
                    text-decoration:none;
                "
            >
                Sign In
            </a>

        </div>


    </div>


</body>

</html>

