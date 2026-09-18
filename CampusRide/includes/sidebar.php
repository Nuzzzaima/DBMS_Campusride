<?php
/**
 * CampusRide - Sidebar Matching Figma Specification
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$userName = $_SESSION['user']['full_name'] ?? 'Nadia Rahman';
$userEmail = $_SESSION['user']['university_email'] ?? 'nadia@uiu.ac.bd';
$userPic = $_SESSION['user']['profile_picture'] ?? '';

$avatarUrl = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150';
if (!empty($userPic)) {
    if (str_starts_with($userPic, 'uploads/') && file_exists(__DIR__ . '/../' . $userPic)) {
        $avatarUrl = $userPic;
    } elseif (str_starts_with($userPic, 'http')) {
        $avatarUrl = $userPic;
    }
}
?>
<aside class="sidebar">
    <!-- Brand Header -->
    <div class="brand">
        <div class="brand-icon">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 14h3a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7a9 9 0 0 1 18 0v7a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3"/></svg>
        </div>
        <div class="brand-text">
            <h2>CampusRide</h2>
            <span>UIU Student Portal</span>
        </div>
    </div>

    <!-- Navigation Menu Items -->
    <ul class="nav-list">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a href="find_ride.php" class="nav-link <?= $currentPage === 'find_ride.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                Find a Ride
            </a>
        </li>
        <li class="nav-item">
            <a href="create_ride.php" class="nav-link <?= $currentPage === 'create_ride.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><path d="M12 8v8"/><path d="M8 12h8"/></svg>
                Create a Ride
            </a>
        </li>
        <li class="nav-item">
            <a href="my_rides.php" class="nav-link <?= $currentPage === 'my_rides.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/></svg>
                My Rides
            </a>
        </li>
        <li class="nav-item">
            <a href="ride_requests.php" class="nav-link <?= $currentPage === 'ride_requests.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/></svg>
                Ride Requests
            </a>
        </li>
        <li class="nav-item">
            <a href="bookings.php" class="nav-link <?= $currentPage === 'bookings.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2Z"/><path d="M13 5v2"/><path d="M13 17v2"/><path d="M13 11v2"/></svg>
                Bookings
            </a>
        </li>
        <li class="nav-item">
            <a href="vehicles.php" class="nav-link <?= $currentPage === 'vehicles.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/></svg>
                Vehicles
            </a>
        </li>
        <li class="nav-item">
            <a href="profile.php" class="nav-link <?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Profile
            </a>
        </li>
        <li class="nav-item">
            <a href="reviews.php" class="nav-link <?= $currentPage === 'reviews.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Ratings & Reviews
            </a>
        </li>
        <li class="nav-item">
            <a href="savings.php" class="nav-link <?= $currentPage === 'savings.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                My Savings
            </a>
        </li>
        <li class="nav-item">
            <a href="notifications.php" class="nav-link <?= $currentPage === 'notifications.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                Notifications
            </a>
        </li>

        <div class="sidebar-divider"></div>

        <li class="nav-item">
            <a href="sos.php" class="nav-link sos-link <?= $currentPage === 'sos.php' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                SOS / Emergency
            </a>
        </li>
    </ul>

    <!-- Bottom User Section -->
    <div class="sidebar-footer">
        <a href="profile.php" class="sidebar-user" title="Open My Profile">
            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" class="sidebar-avatar">
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="sidebar-user-email"><?= htmlspecialchars($userEmail) ?></div>
            </div>
        </a>
        <a href="logout.php" class="sidebar-logout">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            Logout
        </a>
    </div>
</aside>