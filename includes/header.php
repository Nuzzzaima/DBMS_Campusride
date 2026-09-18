<?php
$userName = $_SESSION['user']['full_name'] ?? 'UIU Student';
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
<header class="topbar">
    <div class="route-pill">UIU Permanent Campus Route Active</div>
    <div class="topbar-right">
        <!-- Profile photo badge in top header -->
        <a href="profile.php" class="header-profile-link" title="Click to view and edit Profile">
            <div class="header-profile-text">
                <div class="p-name"><?= htmlspecialchars($userName) ?></div>
                <div class="p-badge">Verified Student</div>
            </div>
            <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid #7c3aed;">
        </a>
    </div>
</header>