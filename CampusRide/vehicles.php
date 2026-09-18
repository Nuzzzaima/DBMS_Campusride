<?php
require_once __DIR__ . '/config/auth.php';
$user = requireAuth();
$userId = (int)$user['user_id'];
$fullName = !empty($user['full_name']) ? $user['full_name'] : 'Nadia Rahman';
$firstName = explode(' ', $fullName)[0];

$db = (new Database())->getConnection();
$msg = $_GET['msg'] ?? '';

// ADD VEHICLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $type = $_POST['vehicle_type'] ?? 'Bike';
    $seatCapacity = (int)($_POST['seat_capacity'] ?? 1);
    $regNumber = strtoupper(trim($_POST['registration_number'] ?? ''));

    if (!empty($regNumber) && $seatCapacity > 0) {
        try {
            $stmt = $db->prepare("INSERT INTO vehicle (user_id, vehicle_type, seat_capacity, registration_number) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $type, $seatCapacity, $regNumber]);
            header("Location: vehicles.php?msg=" . urlencode("Vehicle registered successfully!"));
            exit();
        } catch (PDOException $e) {
            $msg = "Error adding vehicle: " . $e->getMessage();
        }
    }
}

// EDIT VEHICLE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $vehicleId = (int)$_POST['vehicle_id'];
    $type = $_POST['vehicle_type'] ?? 'Bike';
    $seatCapacity = (int)$_POST['seat_capacity'];
    $regNumber = strtoupper(trim($_POST['registration_number']));

    if (!empty($regNumber) && $seatCapacity > 0) {
        try {
            $stmt = $db->prepare("UPDATE vehicle SET vehicle_type = ?, seat_capacity = ?, registration_number = ? WHERE vehicle_id = ? AND user_id = ?");
            $stmt->execute([$type, $seatCapacity, $regNumber, $vehicleId, $userId]);
            header("Location: vehicles.php?msg=" . urlencode("Vehicle updated successfully!"));
            exit();
        } catch (PDOException $e) {
            $msg = "Error updating vehicle: " . $e->getMessage();
        }
    }
}

// DELETE VEHICLE
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM vehicle WHERE vehicle_id = ? AND user_id = ?");
        $stmt->execute([$delId, $userId]);
        header("Location: vehicles.php?msg=" . urlencode("Vehicle removed from registry."));
        exit();
    } catch (PDOException $e) {
        $msg = "Error deleting vehicle: " . $e->getMessage();
    }
}

// Fetch vehicles
$vehicles = [];
if ($db) {
    $stmt = $db->prepare("SELECT * FROM vehicle WHERE user_id = ? ORDER BY vehicle_id DESC");
    $stmt->execute([$userId]);
    $vehicles = $stmt->fetchAll();
}

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
    <title>Vehicles - CampusRide UIU</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f8fafc; color: #0f172a; display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* UNIFIED SIDEBAR */
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

        .content-area { padding: 10px 36px 36px; max-width: 1100px; width: 100%; display: flex; flex-direction: column; gap: 20px; }
        .action-row { display: flex; align-items: center; justify-content: space-between; }
        .btn-add { background: #7c3aed; color: white; padding: 10px 20px; border-radius: 10px; font-weight: 700; font-size: 13.5px; border: none; cursor: pointer; }
        .btn-add:hover { background: #6d28d9; }

        .alert { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; padding: 12px 18px; border-radius: 12px; font-size: 13.5px; font-weight: 600; }
        
        .vehicle-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 16px; }
        .card { background: white; border: 1px solid #edf2f7; border-radius: 18px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; }
        .v-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; }
        .v-type { font-size: 11px; font-weight: 800; text-transform: uppercase; background: #f5f3ff; color: #7c3aed; padding: 4px 10px; border-radius: 6px; }
        .v-plate { font-family: monospace; font-size: 14px; font-weight: 700; color: #334155; background: #f8fafc; padding: 6px 10px; border-radius: 6px; display: inline-block; margin-bottom: 10px; }

        .v-actions { display: flex; gap: 10px; border-top: 1px solid #f8fafc; padding-top: 14px; margin-top: 10px; }
        .btn-edit { flex: 1; padding: 8px; text-align: center; border-radius: 8px; font-size: 12.5px; font-weight: 700; color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe; cursor: pointer; }
        .btn-delete { flex: 1; padding: 8px; text-align: center; border-radius: 8px; font-size: 12.5px; font-weight: 700; color: #e11d48; background: #fff1f2; border: 1px solid #fecdd3; text-decoration: none; }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 50; }
        .modal-content { background: white; padding: 26px; border-radius: 20px; width: 100%; max-width: 440px; }
        .form-group { margin-bottom: 12px; display: flex; flex-direction: column; gap: 4px; }
        label { font-size: 12px; font-weight: 700; color: #475569; }
        input, select { padding: 10px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; }
    </style>
</head>
<body>

    <!-- PERSISTENT SIDEBAR -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- MAIN WORKSPACE -->
    <div class="main-wrapper">
        <header class="topbar">
            <div class="page-title">
                <h1>Registered Vehicles</h1>
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
            <div class="action-row">
                <div>
                    <h3 style="font-size:16px; font-weight:800;">My Vehicles</h3>
                    <span style="font-size:12px; color:#64748b;">Registered for UIU student carpooling</span>
                </div>
                <button class="btn-add" onclick="openAddModal()">+ Add Vehicle</button>
            </div>

            <?php if (!empty($msg)): ?>
                <div class="alert"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <div class="vehicle-grid">
                <?php if (empty($vehicles)): ?>
                    <div style="grid-column: 1 / -1; padding: 40px; background:white; border-radius:16px; text-align:center; color:#64748b; border:1px dashed #cbd5e1;">
                        No vehicles registered yet. Click "+ Add Vehicle" to register your Car or Bike.
                    </div>
                <?php else: ?>
                    <?php foreach ($vehicles as $v): ?>
                        <div class="card">
                            <div>
                                <div class="v-top">
                                    <span class="v-type"><?= htmlspecialchars($v['vehicle_type']) ?></span>
                                    <span style="font-size:11.5px; color:#059669; font-weight:700;">● Active</span>
                                </div>
                                <div class="v-plate"><?= htmlspecialchars($v['registration_number']) ?></div>
                                <div style="font-size:12.5px; color:#64748b;">
                                    Seat Capacity: <strong><?= $v['seat_capacity'] ?> passenger(s)</strong>
                                </div>
                            </div>
                            <div class="v-actions">
                                <button class="btn-edit" onclick='openEditModal(<?= json_encode($v) ?>)'>Edit</button>
                                <a href="vehicles.php?delete=<?= $v['vehicle_id'] ?>" class="btn-delete" onclick="return confirm('Delete this vehicle?')">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ADD VEHICLE MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3 style="font-size:17px; font-weight:800; margin-bottom:14px;">Add New Vehicle</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <select name="vehicle_type">
                        <option value="Bike">Bike</option>
                        <option value="Car">Car</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" name="registration_number" placeholder="DHAKA-METRO-LA-1101" required>
                </div>
                <div class="form-group">
                    <label>Seat Capacity</label>
                    <input type="number" name="seat_capacity" min="1" max="8" value="1" required>
                </div>
                <div style="display:flex; gap:10px; margin-top:16px;">
                    <button type="button" onclick="closeModal('addModal')" style="flex:1; padding:9px; border-radius:8px; border:1px solid #cbd5e1; background:white; font-weight:700; cursor:pointer;">Cancel</button>
                    <button type="submit" class="btn-add" style="flex:1;">Save Vehicle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT VEHICLE MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="font-size:17px; font-weight:800; margin-bottom:14px;">Edit Vehicle</h3>
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="vehicle_id" id="edit_id">
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <select name="vehicle_type" id="edit_type">
                        <option value="Bike">Bike</option>
                        <option value="Car">Car</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Registration Number</label>
                    <input type="text" name="registration_number" id="edit_reg" required>
                </div>
                <div class="form-group">
                    <label>Seat Capacity</label>
                    <input type="number" name="seat_capacity" id="edit_capacity" min="1" max="8" required>
                </div>
                <div style="display:flex; gap:10px; margin-top:16px;">
                    <button type="button" onclick="closeModal('editModal')" style="flex:1; padding:9px; border-radius:8px; border:1px solid #cbd5e1; background:white; font-weight:700; cursor:pointer;">Cancel</button>
                    <button type="submit" class="btn-add" style="flex:1;">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() { document.getElementById('addModal').style.display = 'flex'; }
        function openEditModal(v) {
            document.getElementById('edit_id').value = v.vehicle_id;
            document.getElementById('edit_type').value = v.vehicle_type;
            document.getElementById('edit_reg').value = v.registration_number;
            document.getElementById('edit_capacity').value = v.seat_capacity;
            document.getElementById('editModal').style.display = 'flex';
        }
        function closeModal(id) { document.getElementById(id).style.display = 'none'; }
    </script>
</body>
</html>