<?php
require_once __DIR__ . '/config/auth.php';
$user = requireAuth();
$userId = (int)$user['user_id'];

$fullName = !empty($user['full_name']) ? $user['full_name'] : 'Nadia Rahman';
$firstName = explode(' ', $fullName)[0];
$studentId = !empty($user['student_id']) ? $user['student_id'] : '011231456';
$phone = !empty($user['phone']) ? $user['phone'] : '01711000001';
$department = !empty($user['department']) ? $user['department'] : 'CSE';
$gender = !empty($user['gender']) ? $user['gender'] : 'Female';

$successMsg = '';
$errorMsg = '';
$db = (new Database())->getConnection();

// Upload Directory
$uploadDir = __DIR__ . '/uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 1. Handle Profile Update & Photo Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_profile') {
    $newFullName = trim($_POST['full_name'] ?? '');
    $newStudentId = trim($_POST['student_id'] ?? '');
    $newPhone = trim($_POST['phone'] ?? '');
    $newDepartment = trim($_POST['department'] ?? '');
    $newGender = trim($_POST['gender'] ?? 'Female');
    $avatarPath = $user['profile_picture'] ?? 'profile1.jpg';

    // Handle File Upload
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['avatar_file']['tmp_name'];
        $fileName = $_FILES['avatar_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $newFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $newFileName;
            if (move_uploaded_file($fileTmp, $dest)) {
                $avatarPath = 'uploads/avatars/' . $newFileName;
            }
        }
    }

    if (!empty($newFullName) && !empty($newStudentId) && !empty($newPhone)) {
        try {
            $stmt = $db->prepare("UPDATE `USER` SET full_name = ?, student_id = ?, phone = ?, department = ?, gender = ?, profile_picture = ? WHERE user_id = ?");
            $stmt->execute([$newFullName, $newStudentId, $newPhone, $newDepartment, $newGender, $avatarPath, $userId]);

            // Sync session
            $_SESSION['user']['full_name'] = $newFullName;
            $_SESSION['user']['student_id'] = $newStudentId;
            $_SESSION['user']['phone'] = $newPhone;
            $_SESSION['user']['department'] = $newDepartment;
            $_SESSION['user']['gender'] = $newGender;
            $_SESSION['user']['profile_picture'] = $avatarPath;
            $user = $_SESSION['user'];

            $fullName = $newFullName;
            $firstName = explode(' ', $fullName)[0];
            $studentId = $newStudentId;
            $phone = $newPhone;
            $department = $newDepartment;
            $gender = $newGender;

            $successMsg = "Profile information updated successfully!";
        } catch (Exception $e) {
            $errorMsg = "Error updating profile: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Please fill in all required fields.";
    }
}

// 2. Handle Password Change (SHA2-256)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $curPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $cnfPass = $_POST['confirm_password'] ?? '';

    if (empty($curPass) || empty($newPass) || empty($cnfPass)) {
        $errorMsg = "All password fields are required.";
    } elseif ($newPass !== $cnfPass) {
        $errorMsg = "New passwords do not match.";
    } elseif (strlen($newPass) < 6) {
        $errorMsg = "Password must be at least 6 characters.";
    } else {
        $curHash = hashStudentPassword($curPass);
        $stmt = $db->prepare("SELECT password FROM `USER` WHERE user_id = ?");
        $stmt->execute([$userId]);
        $dbHash = $stmt->fetchColumn();

        if ($dbHash && $dbHash !== $curHash) {
            $errorMsg = "Current password is incorrect.";
        } else {
            $newHash = hashStudentPassword($newPass);
            $up = $db->prepare("UPDATE `USER` SET password = ? WHERE user_id = ?");
            $up->execute([$newHash, $userId]);
            $successMsg = "Password changed successfully!";
        }
    }
}

// Resolve avatar
$avatarUrl = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150';
if (!empty($user['profile_picture'])) {
    if (str_starts_with($user['profile_picture'], 'uploads/') && file_exists(__DIR__ . '/' . $user['profile_picture'])) {
        $avatarUrl = $user['profile_picture'];
    } elseif (str_starts_with($user['profile_picture'], 'http')) {
        $avatarUrl = $user['profile_picture'];
    }
}

$currentDate = date('l, j F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - CampusRide UIU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* UNIFIED SIDEBAR (Matching Dashboard) */
        .sidebar { width: 250px; background: #ffffff; border-right: 1px solid #edf2f7; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; flex-shrink: 0; z-index: 20; }
        .brand { padding: 22px 20px; display: flex; align-items: center; gap: 12px; }
        .brand-icon { width: 36px; height: 36px; border-radius: 10px; background: #7c3aed; color: white; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .brand-text h2 { font-size: 16px; font-weight: 800; color: #0f172a; line-height: 1.1; }
        .brand-text span { font-size: 10.5px; font-weight: 600; color: #94a3b8; }
        
        .nav-list { list-style: none; padding: 0 14px; overflow-y: auto; flex: 1; }
        .nav-item { margin-bottom: 2px; }
        .nav-link { display: flex; align-items: center; padding: 9px 14px; border-radius: 12px; color: #64748b; text-decoration: none; font-size: 13px; font-weight: 600; transition: all 0.15s ease; }
        .nav-link:hover { background: #f8fafc; color: #7c3aed; }
        .nav-link.active { background: #7c3aed; color: #ffffff; font-weight: 700; box-shadow: 0 4px 12px rgba(124, 58, 237, 0.25); }
        .nav-link svg { width: 17px; height: 17px; stroke-width: 2; margin-right: 11px; flex-shrink: 0; }
        .nav-link.active svg { stroke: #ffffff; }

        .sidebar-divider { height: 1px; background: #f1f5f9; margin: 8px 14px; }
        .nav-link.sos-link { color: #64748b; }
        .nav-link.sos-link:hover { color: #e11d48; background: #fff1f2; }

        /* Sidebar Footer User Card */
        .sidebar-footer { padding: 14px 16px; border-top: 1px solid #f1f5f9; background: #ffffff; }
        .sidebar-user { display: flex; align-items: center; gap: 10px; text-decoration: none; padding: 6px 4px; border-radius: 10px; }
        .sidebar-user:hover { background: #f8fafc; }
        .sidebar-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; border: 1.5px solid #e2e8f0; flex-shrink: 0; }
        .sidebar-user-info { flex: 1; min-width: 0; }
        .sidebar-user-name { font-size: 12.5px; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-user-email { font-size: 10.5px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sidebar-logout { display: flex; align-items: center; gap: 8px; color: #e11d48; text-decoration: none; font-size: 12px; font-weight: 700; margin-top: 10px; padding: 6px 4px; border-radius: 8px; }
        .sidebar-logout:hover { background: #fff1f2; }

        /* MAIN WORKSPACE */
        .main-wrapper { flex: 1; display: flex; flex-direction: column; height: 100vh; overflow-y: auto; }
        .topbar { padding: 22px 36px 14px; display: flex; align-items: flex-start; justify-content: space-between; background: #f8fafc; }
        .page-title h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin-bottom: 2px; }
        .page-title span { font-size: 12.5px; color: #64748b; font-weight: 500; }
        
        .topbar-right { display: flex; align-items: center; gap: 12px; }
        .notif-pill { width: 38px; height: 38px; border-radius: 50%; background: #ffffff; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #64748b; text-decoration: none; }
        .header-profile { display: flex; align-items: center; gap: 8px; background: #ffffff; padding: 4px 12px 4px 4px; border-radius: 999px; border: 1px solid #e2e8f0; text-decoration: none; }
        .header-profile img { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
        .header-profile span { font-size: 12.5px; font-weight: 700; color: #0f172a; }

        .content-area { padding: 10px 36px 36px; max-width: 900px; width: 100%; display: flex; flex-direction: column; gap: 20px; }

        .alert { padding: 12px 18px; border-radius: 12px; font-size: 13px; font-weight: 600; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }

        /* Profile Card */
        .card { background: #ffffff; border: 1px solid #edf2f7; border-radius: 20px; padding: 30px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .card h2 { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 20px; }

        .tabs-nav { display: flex; gap: 10px; border-bottom: 1px solid #f1f5f9; margin-bottom: 24px; }
        .tab-btn { padding: 10px 18px; background: none; border: none; font-size: 13.5px; font-weight: 700; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .tab-btn.active { color: #7c3aed; border-bottom-color: #7c3aed; }

        /* Form Grid */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: span 2; }
        label { font-size: 12.5px; font-weight: 700; color: #475569; }
        input, select { padding: 11px 14px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 13.5px; outline: none; transition: border 0.15s; background: white; }
        input:focus, select:focus { border-color: #7c3aed; }

        .upload-box { background: #faf5ff; border: 1px dashed #c084fc; border-radius: 14px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
        .current-preview { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; border: 2px solid #7c3aed; flex-shrink: 0; }
        .upload-info h4 { font-size: 13px; font-weight: 700; color: #7c3aed; margin-bottom: 4px; }
        .upload-info p { font-size: 11.5px; color: #64748b; margin-bottom: 8px; }

        .btn-save { background: #7c3aed; color: white; border: none; padding: 12px 26px; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; transition: background 0.15s; }
        .btn-save:hover { background: #6d28d9; }
    </style>
</head>
<body>

    <!-- PERSISTENT SIDEBAR -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- MAIN WORKSPACE -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="page-title">
                <h1>Profile Settings</h1>
                <span><?= htmlspecialchars($currentDate) ?></span>
            </div>
            <div class="topbar-right">
                <a href="notifications.php" class="notif-pill" title="Notifications">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                </a>
                <a href="profile.php" class="header-profile">
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
                    <span><?= htmlspecialchars($firstName) ?></span>
                </a>
            </div>
        </header>

        <div class="content-area">
            <?php if (!empty($successMsg)): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="tabs-nav">
                    <button class="tab-btn active" onclick="switchTab('edit')">1. Edit Student Profile</button>
                    <button class="tab-btn" onclick="switchTab('password')">2. Change Password</button>
                </div>

                <!-- 1. EDIT PROFILE -->
                <div id="tab-edit">
                    <h2>Edit Student Profile Information</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="edit_profile">

                        <!-- Photo Upload -->
                        <div class="form-group full" style="margin-bottom: 16px;">
                            <div class="upload-box">
                                <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Current Avatar" class="current-preview">
                                <div class="upload-info">
                                    <h4>Upload Profile Picture</h4>
                                    <p>Supports PNG, JPG, WebP up to 5MB. Appears on your profile, header, and dashboard.</p>
                                    <input type="file" name="avatar_file" accept="image/*" style="padding:6px; font-size:12px;">
                                </div>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" value="<?= htmlspecialchars($fullName) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>UIU Student ID</label>
                                <input type="text" name="student_id" value="<?= htmlspecialchars($studentId) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Academic Department</label>
                                <input type="text" name="department" value="<?= htmlspecialchars($department) ?>" required>
                            </div>
                            <div class="form-group full">
                                <label>Gender</label>
                                <select name="gender">
                                    <option value="Female" <?= $gender === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Male" <?= $gender === 'Male' ? 'selected' : '' ?>>Male</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">Save Profile Changes</button>
                    </form>
                </div>

                <!-- 2. CHANGE PASSWORD -->
                <div id="tab-password" style="display:none;">
                    <h2>Change Account Password</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-grid" style="grid-template-columns: 1fr;">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required placeholder="Enter current password">
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" required placeholder="Enter new password (min 6 chars)">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required placeholder="Re-type new password">
                            </div>
                        </div>
                        <button type="submit" class="btn-save">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            if (tab === 'edit') {
                document.getElementById('tab-edit').style.display = 'block';
                document.getElementById('tab-password').style.display = 'none';
                event.target.classList.add('active');
            } else {
                document.getElementById('tab-edit').style.display = 'none';
                document.getElementById('tab-password').style.display = 'block';
                event.target.classList.add('active');
            }
        }
    </script>
</body>
</html>