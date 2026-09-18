<?php

session_start();
include "db.php";


// Check if user is logged in
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}


// Get logged-in user's ID
$userId = $_SESSION['user_id'];


// Get user information from database
$sql = "SELECT *
        FROM USER
        WHERE user_id = '$userId'
        LIMIT 1";

$result = mysqli_query($conn, $sql);


// Check database query
if (!$result) {

    die("Database error: " . mysqli_error($conn));

}


// Check if user exists
if (mysqli_num_rows($result) != 1) {

    session_destroy();

    header("Location: login.php");
    exit();

}


// Get user data
$user = mysqli_fetch_assoc($result);


// User information
$fullName = $user['full_name'];
$firstName = explode(' ', $fullName)[0];

$studentId = $user['student_id'];

$department = $user['department'];

$email = $user['university_email'];


// Profile picture
$avatarUrl = "profile1.jpg";

if (!empty($user['profile_picture'])) {

    if (
        file_exists(__DIR__ . "/" . $user['profile_picture'])
    ) {

        $avatarUrl = $user['profile_picture'];

    } elseif (
        str_starts_with($user['profile_picture'], "http")
    ) {

        $avatarUrl = $user['profile_picture'];

    }

}


// Current date
$currentDate = date("l, j F Y");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - CampusRide UIU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        
body {
    background: #f8fafc;
    color: #0f172a;
    display: grid;
    grid-template-columns: 197px 1fr;
    min-height: 100vh;
    margin: 0;
}


        
        /* Main View Container */
   
.main-wrapper {
    display: flex;
    flex-direction: column;
    min-width: 0;
    min-height: 100vh;
    overflow-y: auto;
}

        
        /* Top Navigation Bar */
        .topbar { padding: 22px 36px 14px; display: flex; align-items: flex-start; justify-content: space-between; background: #f8fafc; }
        .page-title h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
        .page-title span { font-size: 12.5px; color: #64748b; font-weight: 500; }
        
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .notif-pill { width: 38px; height: 38px; border-radius: 50%; background: #ffffff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; text-decoration: none; position: relative; }
        .notif-dot { width: 7px; height: 7px; border-radius: 50%; background: #7c3aed; position: absolute; top: 9px; right: 9px; }
        .header-profile { display: flex; align-items: center; gap: 8px; background: #ffffff; padding: 4px 12px 4px 4px; border-radius: 999px; border: 1px solid #e2e8f0; text-decoration: none; }
        .header-profile img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
        .header-profile span { font-size: 12.5px; font-weight: 700; color: #0f172a; }

        /* Dashboard Content Workspace */
        .content-area { padding: 10px 36px 36px; max-width: 1240px; width: 100%; display: flex; flex-direction: column; gap: 20px; }

        /* 1. Purple Welcome Banner */
        .welcome-card { background: #7c3aed; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); border-radius: 20px; padding: 28px 32px; color: white; display: flex; align-items: center; justify-content: space-between; position: relative; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(124, 58, 237, 0.25); }
        .welcome-text .greeting { font-size: 12.5px; font-weight: 500; opacity: 0.9; margin-bottom: 4px; }
        .welcome-text h2 { font-size: 24px; font-weight: 800; display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .welcome-text .student-meta { font-size: 12px; font-weight: 500; opacity: 0.85; }
        .welcome-avatar-wrapper { width: 68px; height: 68px; border-radius: 50%; border: 3px solid rgba(255, 255, 255, 0.9); overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.15); flex-shrink: 0; }
        .welcome-avatar-wrapper img { width: 100%; height: 100%; object-fit: cover; }

        /* 2. Four Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .stat-card { background: #ffffff; border-radius: 16px; padding: 18px 20px; border: 1px solid #edf2f7; display: flex; align-items: flex-start; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .stat-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .icon-pin { background: #f3f4f6; color: #64748b; }
        .icon-clock { background: #ecfeff; color: #0891b2; }
        .icon-check { background: #f0fdf4; color: #16a34a; }
        .icon-wallet { background: #fffbeb; color: #d97706; }
        .stat-info { display: flex; flex-direction: column; }
        .stat-label { font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 4px; }
        .stat-num { font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.1; margin-bottom: 4px; }
        .stat-sub { font-size: 11px; color: #94a3b8; font-weight: 500; }

        /* 3. Quick Action Cards (Find a Ride / Create a Ride) */
        .actions-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .action-card { background: #ffffff; border-radius: 16px; padding: 20px 24px; border: 1px solid #edf2f7; display: flex; align-items: center; gap: 16px; text-decoration: none; transition: transform 0.15s, box-shadow 0.15s; }
        .action-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.04); }
        .action-icon { width: 46px; height: 46px; border-radius: 14px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .action-info h3 { font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
        .action-info p { font-size: 12.5px; color: #64748b; font-weight: 500; }

        /* 4. Two Column Layout: Upcoming Ride & Recent Activity */
        .bottom-grid { display: grid; grid-template-columns: 1.6fr 1fr; gap: 20px; align-items: start; }
        
        .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
        .section-header h3 { font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .view-all-link { font-size: 12px; font-weight: 700; color: #7c3aed; text-decoration: none; }

        /* Left Column Card: Upcoming Ride */
        .upcoming-card { background: #ffffff; border-radius: 18px; padding: 22px; border: 1px solid #edf2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .driver-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .driver-info { display: flex; align-items: center; gap: 12px; }
        .driver-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; }
        .driver-name { font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 3px; }
        .driver-stars { color: #f59e0b; font-size: 12px; }
        .time-badge { background: #f5f3ff; color: #7c3aed; font-size: 11.5px; font-weight: 700; padding: 5px 12px; border-radius: 8px; }

        /* Timeline Route */
        .route-timeline { display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; padding-left: 6px; }
        .route-points { position: relative; padding-left: 20px; }
        .route-points::before { content: ""; position: absolute; left: 4px; top: 8px; bottom: 8px; width: 2px; background: #e2e8f0; }
        .point-dot-from { position: absolute; left: 1px; top: 4px; width: 8px; height: 8px; border-radius: 50%; background: #7c3aed; }
        .point-dot-to { position: absolute; left: 1px; bottom: 4px; width: 8px; height: 8px; border-radius: 50%; background: #7c3aed; }
        .point-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 1px; }
        .point-name { font-size: 13.5px; font-weight: 700; color: #0f172a; }
        .point-gap { height: 18px; }

        .route-fare-box { text-align: right; }
        .fare-amount { font-size: 26px; font-weight: 800; color: #7c3aed; line-height: 1; }
        .fare-sub { font-size: 11px; color: #94a3b8; margin-top: 4px; font-weight: 500; }

        .tags-row { display: flex; align-items: center; gap: 12px; font-size: 12px; color: #64748b; font-weight: 600; padding-top: 14px; border-top: 1px solid #f8fafc; }
        .tag-badge-pink { background: #fff1f2; color: #e11d48; font-size: 10.5px; font-weight: 700; padding: 3px 10px; border-radius: 6px; }

        /* Right Column Card: Recent Activity */
        .activity-card { background: #ffffff; border-radius: 18px; padding: 8px 18px; border: 1px solid #edf2f7; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .activity-item { display: flex; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid #f8fafc; }
        .activity-item:last-child { border-bottom: none; }
        .activity-icon { width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; font-weight: 800; }
        .act-green { background: #ecfdf5; color: #10b981; }
        .act-gold { background: #fffbeb; color: #f59e0b; }
        .act-purple { background: #f5f3ff; color: #7c3aed; }
        .act-blue { background: #eff6ff; color: #3b82f6; }
        .activity-details h4 { font-size: 12.5px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
        .activity-details span { font-size: 10.5px; color: #94a3b8; font-weight: 500; }
    </style>
</head>
<body>

    <!-- PERSISTENT SIDEBAR -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- MAIN WORKSPACE -->
    <div class="main-wrapper">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="page-title">
                <h1>Dashboard</h1>
                <span><?= htmlspecialchars($currentDate) ?></span>
            </div>
            <div class="topbar-right">
                <a href="notifications.php" class="notif-pill" title="Notifications">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    <span class="notif-dot"></span>
                </a>
                <a href="profile.php" class="header-profile" title="View Profile">
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
                    <span><?= htmlspecialchars($firstName) ?></span>
                </a>
            </div>
        </header>

        <!-- DASHBOARD CONTENT AREA -->
        <div class="content-area">
            
            <!-- 1. PURPLE WELCOME BANNER -->
            <div class="welcome-card">
                <div class="welcome-text">
                    <div class="greeting">Good morning,</div>
                    <h2><?= htmlspecialchars($fullName) ?> 👋</h2>
                    <div class="student-meta"><?= htmlspecialchars($department) ?>, Semester 8 · Student ID: <?= htmlspecialchars($studentId) ?></div>
                </div>
                <div class="welcome-avatar-wrapper">
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($fullName) ?>">
                </div>
            </div>

            <!-- 2. FOUR METRIC STATS ROW -->
            <div class="stats-grid">
                <!-- Stat 1: Active Rides -->
                <div class="stat-card">
                    <div class="stat-icon icon-pin">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Active Rides</span>
                        <span class="stat-num">2</span>
                        <span class="stat-sub">Right now</span>
                    </div>
                </div>

                <!-- Stat 2: Upcoming Rides -->
                <div class="stat-card">
                    <div class="stat-icon icon-clock">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Upcoming Rides</span>
                        <span class="stat-num">4</span>
                        <span class="stat-sub">This week</span>
                    </div>
                </div>

                <!-- Stat 3: Completed Rides -->
                <div class="stat-card">
                    <div class="stat-icon icon-check">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Completed Rides</span>
                        <span class="stat-num">38</span>
                        <span class="stat-sub">All time</span>
                    </div>
                </div>

                <!-- Stat 4: Money Saved -->
                <div class="stat-card">
                    <div class="stat-icon icon-wallet">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Money Saved</span>
                        <span class="stat-num">৳4,280</span>
                        <span class="stat-sub">This month</span>
                    </div>
                </div>
            </div>

            <!-- 3. TWO QUICK ACTION CARDS -->
            <div class="actions-grid">
                <a href="find_ride.php" class="action-card">
                    <div class="action-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                    </div>
                    <div class="action-info">
                        <h3>Find a Ride</h3>
                        <p>Browse available rides on your route</p>
                    </div>
                </a>

                <a href="create_ride.php" class="action-card">
                    <div class="action-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>
                    </div>
                    <div class="action-info">
                        <h3>Create a Ride</h3>
                        <p>Offer seats to fellow students</p>
                    </div>
                </a>
            </div>

            <!-- 4. BOTTOM TWO-COLUMN SECTION -->
            <div class="bottom-grid">
                
                <!-- LEFT: UPCOMING RIDE CARD -->
                <div>
                    <div class="section-header">
                        <h3>Upcoming Ride</h3>
                        <a href="bookings.php" class="view-all-link">View All</a>
                    </div>
                    <div class="upcoming-card">
                        <div class="driver-header">
                            <div class="driver-info">
                                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=100" alt="Rafiq Hossain" class="driver-avatar">
                                <div>
                                    <div class="driver-name">Rafiq Hossain</div>
                                    <div class="driver-stars">★★★★★</div>
                                </div>
                            </div>
                            <span class="time-badge">Tomorrow 8:30 AM</span>
                        </div>

                        <!-- Route Details & Fare -->
                        <div class="route-timeline">
                            <div class="route-points">
                                <span class="point-dot-from"></span>
                                <div class="point-label">From</div>
                                <div class="point-name">Khilgaon Railgate</div>
                                
                                <div class="point-gap"></div>

                                <span class="point-dot-to"></span>
                                <div class="point-label">To</div>
                                <div class="point-name">UIU Main Campus, Madani Ave</div>
                            </div>

                            <div class="route-fare-box">
                                <div class="fare-amount">৳45</div>
                                <div class="fare-sub">per person</div>
                            </div>
                        </div>

                        <!-- Card Footer Tags -->
                        <div class="tags-row">
                            <span>🚘 CNG Auto</span>
                            <span>👥 3 seats left</span>
                            <span class="tag-badge-pink">Women Only</span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: RECENT ACTIVITY CARD -->
                <div>
                    <div class="section-header">
                        <h3>Recent Activity</h3>
                    </div>
                    <div class="activity-card">
                        <!-- Activity 1 -->
                        <div class="activity-item">
                            <div class="activity-icon act-green">✓</div>
                            <div class="activity-details">
                                <h4>Ride completed to UIU</h4>
                                <span>Today, 9:15 AM</span>
                            </div>
                        </div>

                        <!-- Activity 2 -->
                        <div class="activity-item">
                            <div class="activity-icon act-gold">★</div>
                            <div class="activity-details">
                                <h4>You rated Rafiq 5 stars</h4>
                                <span>Today, 9:20 AM</span>
                            </div>
                        </div>

                        <!-- Activity 3 -->
                        <div class="activity-item">
                            <div class="activity-icon act-purple">🏷</div>
                            <div class="activity-details">
                                <h4>Booking #1042 confirmed</h4>
                                <span>Yesterday</span>
                            </div>
                        </div>

                        <!-- Activity 4 -->
                        <div class="activity-item">
                            <div class="activity-icon act-blue">✉</div>
                            <div class="activity-details">
                                <h4>Ride request received</h4>
                                <span>Yesterday</span>
                            </div>
                        </div>

                        <!-- Activity 5 -->
                        <div class="activity-item">
                            <div class="activity-icon act-green">৳</div>
                            <div class="activity-details">
                                <h4>Saved ৳90 this week</h4>
                                <span>2 days ago</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

</body>
</html>