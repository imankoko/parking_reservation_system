<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Ensure proper timezone settings
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");

$alert_message = '';
$alert_type = '';

// --- POST INTERACTION CONTROLLER DISPATCHER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action_type'])) {
        $action = $_POST['action_type'];
        $target_user_id = intval($_POST['user_id']);

        if ($action === 'update_details') {
            $name = trim($_POST['full_name']);
            $email = trim($_POST['email']);
            $role = trim($_POST['role']);
            
            $up_query = "UPDATE tbl_user SET full_name = $1, email = $2, role = $3 WHERE user_id = $4";
            $res = pg_query_params($conn, $up_query, array($name, $email, $role, $target_user_id));
            if ($res) {
                $alert_message = "User profile configuration saved successfully.";
                $alert_type = "success";
            } else {
                $alert_message = "Error mapping account update values.";
                $alert_type = "error";
            }
        } elseif ($action === 'change_password') {
            $new_pass = trim($_POST['new_password']);
            if (!empty($new_pass)) {
                $pass_query = "UPDATE tbl_user SET password = $1 WHERE user_id = $2";
                $res = pg_query_params($conn, $pass_query, array($new_pass, $target_user_id));
                if ($res) {
                    $alert_message = "Password updated cleanly for User ID #$target_user_id.";
                    $alert_type = "success";
                }
            }
        } elseif ($action === 'delete_user') {
            // --- FULL MASTER CASCADING RELATION RESET ENGINE ---
            
            // Step 1: Drop reviews linked to bookings made by this user (Driver Context)
            pg_query_params($conn, "DELETE FROM tbl_reviews WHERE booking_id IN (SELECT booking_id FROM tbl_booking WHERE user_id = $1)", array($target_user_id));
            
            // Step 2: Drop reviews linked to bookings on any slots owned by this user (Lister Context)
            pg_query_params($conn, "DELETE FROM tbl_reviews WHERE booking_id IN (SELECT b.booking_id FROM tbl_booking b JOIN tbl_parking_listing pl ON b.parking_id = pl.parking_id WHERE pl.lister_id = $1)", array($target_user_id));

            // Step 3: Clear bookings created by this user
            pg_query_params($conn, "DELETE FROM tbl_booking WHERE user_id = $1", array($target_user_id));
            
            // Step 4: Clear bookings linked to any parking slots owned by this user
            pg_query_params($conn, "DELETE FROM tbl_booking WHERE parking_id IN (SELECT parking_id FROM tbl_parking_listing WHERE lister_id = $1)", array($target_user_id));
            
            // Step 5: Clear physical slots created by this user
            pg_query_params($conn, "DELETE FROM tbl_parking_listing WHERE lister_id = $1", array($target_user_id));
            
            // Step 6: Finally drop the root user account entry
            $del_query = "DELETE FROM tbl_user WHERE user_id = $1";
            $res = pg_query_params($conn, $del_query, array($target_user_id));
            
            if ($res) {
                $alert_message = "Account database entry and all associated records dropped successfully.";
                $alert_type = "success";
            } else {
                $alert_message = "Error dropping user entry from table.";
                $alert_type = "error";
            }
        }
    }
}

// Search filtration engine block (Includes Email field lookups seamlessly)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $fetch_query = "SELECT * FROM tbl_user WHERE full_name ILIKE $1 OR email ILIKE $1 OR role ILIKE $1 ORDER BY user_id DESC";
    $users_res = pg_query_params($conn, $fetch_query, array("%$search%"));
} else {
    $users_res = pg_query($conn, "SELECT * FROM tbl_user ORDER BY user_id DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Users - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; margin: 0; padding-bottom: 100px; }
        .header { background: linear-gradient(to bottom, #d4bc44, #ffffff); padding: 30px 20px; display: flex; align-items: center; gap: 15px; }
        .btn-back { color: #000; text-decoration: none; font-size: 1.2rem; }
        .main-content { padding: 20px; }
        
        .alert-box { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; font-weight: bold; font-size: 13px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }

        .search-container { margin-bottom: 20px; display: flex; gap: 10px; }
        .search-input { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; background: white; }
        .btn-search { background: #333; color: #d4bc44; border: none; padding: 0 20px; border-radius: 10px; font-weight: bold; cursor: pointer; }

        .user-card { background: white; border-radius: 15px; padding: 20px; margin-bottom: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-left: 5px solid #d4bc44; }
        .user-info h4 { margin: 0; font-size: 1.1rem; color: #333; }
        .user-info p { margin: 5px 0 0 0; font-size: 0.85rem; color: #555; display: flex; align-items: center; gap: 8px; }
        .user-info p i { color: #888; width: 14px; }
        
        .badge { padding: 3px 8px; border-radius: 6px; font-size: 10px; font-weight: bold; text-transform: uppercase; display: inline-block; margin-top: 8px; }
        .badge-admin { background: #fee2e2; color: #dc3545; }
        .badge-lister { background: #e3f2fd; color: #0d47a1; }
        .badge-driver { background: #e8f5e9; color: #2e7d32; }

        .card-actions { margin-top: 15px; display: flex; gap: 10px; flex-wrap: wrap; }
        .action-btn { padding: 8px 15px; border: none; border-radius: 6px; font-size: 12px; font-weight: bold; cursor: pointer; transition: 0.2s; text-decoration: none; }
        .btn-edit { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
        .btn-pass { background: #fffdf0; color: #b45309; border: 1px solid #f59e0b; }
        .btn-delete { background: #fee2e2; color: #dc3545; border: 1px solid #fca5a5; }
        
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 2000; padding: 25px; box-sizing: border-box; }
        .modal-content { background: white; padding: 25px; border-radius: 15px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); animation: zoomIn 0.25s ease; }
        .modal-content h3 { margin: 0 0 15px 0; font-size: 1.1rem; }
        .form-control { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .modal-buttons { display: flex; gap: 10px; justify-content: flex-end; }
        
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; display: flex; justify-content: space-around; padding: 10px 0; border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 1000; }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }

        @keyframes zoomIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    </style>
</head>
<body>

    <div class="header">
        <a href="admin_dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 style="margin:0; font-size: 1.2rem;">Account Management</h2>
    </div>

    <div class="main-content">
        <?php if (!empty($alert_message)): ?>
            <div class="alert-box alert-<?php echo $alert_type; ?>"><?php echo $alert_message; ?></div>
        <?php endif; ?>

        <div class="search-container">
            <form method="GET" action="admin_manage_users.php" style="display:flex; width:100%; gap:10px;">
                <input type="text" name="search" class="search-input" placeholder="Search by name, email, or role..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>

        <?php if ($users_res && pg_num_rows($users_res) > 0): ?>
            <?php while ($user = pg_fetch_assoc($users_res)): ?>
                <div class="user-card">
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                        <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($user['email'] ?: 'No email linked'); ?></p>
                        <p><i class="fa-solid fa-id-badge"></i> User ID Reference: #<?php echo $user['user_id']; ?></p>
                        <?php 
                            $role = trim(strtoupper($user['role']));
                            $badge_class = 'badge-driver';
                            if ($role === 'ADMIN') $badge_class = 'badge-admin';
                            elseif ($role === 'LISTER') $badge_class = 'badge-lister';
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($user['role']); ?></span>
                    </div>
                    <div class="card-actions">
                        <button class="action-btn btn-edit" onclick="triggerEditModal('<?php echo $user['user_id']; ?>', '<?php echo addslashes($user['full_name']); ?>', '<?php echo addslashes($user['email']); ?>', '<?php echo trim($user['role']); ?>')"><i class="fa-solid fa-user-pen"></i> Edit</button>
                        <button class="action-btn btn-pass" onclick="triggerPasswordModal('<?php echo $user['user_id']; ?>')"><i class="fa-solid fa-key"></i> Key</button>
                        <button class="action-btn btn-delete" onclick="triggerDeleteAction('<?php echo $user['user_id']; ?>')"><i class="fa-solid fa-trash-can"></i> Drop</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; color:#666; margin-top:30px;">No accounts cataloged matching query parameters.</p>
        <?php endif; ?>
    </div>

    <div class="modal" id="editModal">
        <div class="modal-content">
            <h3>Update Profile Information</h3>
            <form method="POST" action="admin_manage_users.php">
                <input type="hidden" name="action_type" value="update_details">
                <input type="hidden" name="user_id" id="editUserId">
                
                <label style="font-size:12px; font-weight:bold;">Full Name</label>
                <input type="text" name="full_name" id="editFullName" class="form-control" required>
                
                <label style="font-size:12px; font-weight:bold;">Email Address</label>
                <input type="email" name="email" id="editEmail" class="form-control" required>
                
                <label style="font-size:12px; font-weight:bold;">System Role</label>
                <select name="role" id="editRole" class="form-control" required>
                    <option value="Driver">Driver</option>
                    <option value="Lister">Lister</option>
                    <option value="Admin">Admin</option>
                </select>
                <div class="modal-buttons">
                    <button type="button" class="action-btn btn-edit" onclick="closeModal('editModal')">Close</button>
                    <button type="submit" class="action-btn btn-confirm">Save Change</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="passwordModal">
        <div class="modal-content">
            <h3>Reset Account Password</h3>
            <form method="POST" action="admin_manage_users.php">
                <input type="hidden" name="action_type" value="change_password">
                <input type="hidden" name="user_id" id="passwordUserId">
                <input type="text" name="new_password" class="form-control" placeholder="Enter new custom string text passphrase..." required minlength="4">
                <div class="modal-buttons">
                    <button type="button" class="action-btn btn-edit" onclick="closeModal('passwordModal')">Close</button>
                    <button type="submit" class="action-btn btn-confirm">Reset</button>
                </div>
            </form>
        </div>
    </div>

    <form id="globalDeleteForm" method="POST" action="admin_manage_users.php" style="display:none;">
        <input type="hidden" name="action_type" value="delete_user">
        <input type="hidden" name="user_id" id="deleteUserId">
    </form>

    <nav class="bottom-nav">
        <a href="admin_dashboard.php" class="nav-item"><i class="fa-solid fa-chart-pie"></i> Report</a>
        <a href="admin_reviews.php" class="nav-item"><i class="fa-solid fa-star"></i> Reviews</a>
        <a href="admin_manage_users.php" class="nav-item active"><i class="fa-solid fa-users-gear"></i> Users</a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>

    <script>
    function triggerEditModal(id, name, email, role) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editFullName').value = name;
        document.getElementById('editEmail').value = email;
        document.getElementById('editRole').value = role;
        document.getElementById('editModal').style.display = 'flex';
    }
    function triggerPasswordModal(id) {
        document.getElementById('passwordUserId').value = id;
        document.getElementById('passwordModal').style.display = 'flex';
    }
    function triggerDeleteAction(id) {
        if(confirm(`Are you absolutely sure you want to completely erase user account reference item #${id}? This action drops dependent logs.`)){
            document.getElementById('deleteUserId').value = id;
            document.getElementById('globalDeleteForm').submit();
        }
    }
    function closeModal(targetId) {
        document.getElementById(targetId).style.display = 'none';
    }
    </script>
</body>
</html>