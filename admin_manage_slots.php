<?php
session_start();
include 'db_connect.php';

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// 2. Simple Manual Penalty Action Engine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['issue_penalty'])) {
    $parking_id = intval($_POST['parking_id']);
    
    // We update the slot status to 'Penalty' and kick out the current plate number
    $query_penalty = "UPDATE tbl_parking_listing SET status = 'Penalty', current_plate = NULL WHERE parking_id = $1";
    pg_query_params($conn, $query_penalty, array($parking_id));
    
    $_SESSION['admin_msg'] = "Slot manually penalized successfully.";
    header("Location: admin_manage_slots.php");
    exit();
}

// 3. Fetch all slots
$query = "SELECT p.*, u.full_name as lister_name 
          FROM tbl_parking_listing p
          JOIN tbl_user u ON p.lister_id = u.user_id
          WHERE p.status != 'Inactive'
          ORDER BY p.slot_number ASC";
$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Parking Slots - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 80px; }
        
        /* Faded Yellow Header matching Dashboard & admin_reviews.php */
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px;
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        .main-content { padding: 20px; }

        /* Table Container matching administrative design constraints */
        .table-container { 
            background: white; 
            padding: 15px; 
            border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            overflow-x: auto; 
        }
        
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; font-size: 13px; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; }
        
        /* Monospace Slot Number Badge Anchor styling */
        .slot-link { 
            color: #d4bc44; 
            text-decoration: none; 
            font-weight: bold; 
            font-size: 1rem;
            background: #333;
            padding: 4px 8px;
            border-radius: 5px;
            font-family: monospace;
            display: inline-block;
            transition: background-color 0.2s ease;
        }
        .slot-link:hover { background: #000; }
        
        /* Polished Status Badges matrix */
        .status-badge { 
            padding: 4px 10px; 
            border-radius: 5px; 
            font-weight: bold; 
            font-size: 0.85em; 
            text-transform: uppercase;
            display: inline-block;
        }
        .status-available { background: #e8f5e9; color: #1b5e20; }
        .status-booked { background: #fff3e0; color: #e65100; }
        .status-occupied { background: #e3f2fd; color: #0d47a1; }
        .status-penalty { background: #ffebee; color: #b71c1c; }
        
        /* Actions Button design layouts */
        .action-container { display: flex; align-items: center; gap: 6px; }
        .action-btn { 
            font-size: 11px; 
            font-weight: bold; 
            text-decoration: none; 
            padding: 5px 10px; 
            border-radius: 5px; 
            display: inline-block; 
            text-transform: uppercase;
            transition: all 0.2s ease;
        }
        .btn-edit { color: #0d47a1; border: 1px solid #0d47a1; background: #e3f2fd; }
        .btn-edit:hover { background: #0d47a1; color: white; }
        
        .btn-delete { color: #b71c1c; border: 1px solid #b71c1c; background: #ffebee; }
        .btn-delete:hover { background: #b71c1c; color: white; }
        
        .btn-penalty { 
            background: #e67e22; 
            color: white; 
            border: none; 
            cursor: pointer; 
            border-radius: 5px; 
            padding: 6px 12px; 
            font-size: 11px; 
            font-weight: bold; 
            text-transform: uppercase;
            transition: background-color 0.2s ease;
        }
        .btn-penalty:hover { background: #d35400; }
        
        .alert-msg { background: #2ecc71; color: white; padding: 12px; margin-bottom: 20px; border-radius: 10px; font-size: 0.85em; font-weight: bold; }

        /* Bottom Navigation Bar matching administrative framework */
        .bottom-nav {
            position: fixed; bottom: 0; width: 100%; background: #fff;
            display: flex; justify-content: space-around; padding: 10px 0;
            border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }
    </style>
</head>
<body>

    <div class="header">
        <img src="logo.png" class="logo-img" alt="Logo">
        <div class="header-text">
            <h2>Slot Management</h2>
        </div>
    </div>

    <div class="main-content">
        <?php if (isset($_SESSION['admin_msg'])): ?>
            <div class="alert-msg"><i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['admin_msg']; unset($_SESSION['admin_msg']); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <h3 style="margin: 0 0 15px 0; font-size: 1rem; color: #333;">
                <i class="fa-solid fa-layer-group" style="color: #d4bc44; margin-right: 5px;"></i> Active Terminal Slot Grid Directory
            </h3>
            
            <table>
                <thead>
                    <tr>
                        <th>Slot No</th>
                        <th>Registered Operator</th>
                        <th>Zone/Branch Location</th>
                        <th>Price Rate</th>
                        <th>Live Status</th>
                        <th>Administrative Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (pg_num_rows($result) > 0): ?>
                        <?php while ($row = pg_fetch_assoc($result)): 
                            $status_str = trim($row['status']);
                            
                            // Map badge variations matching runtime system definitions
                            $badge_class = 'status-booked';
                            if (strcasecmp($status_str, 'Available') === 0) { $badge_class = 'status-available'; }
                            elseif (strcasecmp($status_str, 'Occupied') === 0) { $badge_class = 'status-occupied'; }
                            elseif (strcasecmp($status_str, 'Penalty') === 0) { $badge_class = 'status-penalty'; }
                        ?>
                        <tr>
                            <td>
                                <a href="plate_number.php?id=<?php echo $row['parking_id']; ?>" class="slot-link" title="Tap to scan license plate data">
                                    <?php echo htmlspecialchars($row['slot_number']); ?>
                                </a>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['lister_name']); ?></strong></td>
                            <td><small style="color: #555;"><?php echo htmlspecialchars($row['location'] . ' (' . $row['branch'] . ')'); ?></small></td>
                            <td style="font-weight: 500;">RM <?php echo number_format($row['price'], 2); ?>/hr</td>
                            <td>
                                <span class="status-badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($status_str); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-container">
                                    <a href="edit_parking.php?id=<?php echo $row['parking_id']; ?>" class="action-btn btn-edit"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                                    <a href="delete_parking.php?id=<?php echo $row['parking_id']; ?>" class="action-btn btn-delete" onclick="return confirm('Confirm slot deactivation?')"><i class="fa-solid fa-trash-can"></i> Remove</a>
                                    
                                    <?php if (strcasecmp($status_str, 'Penalty') !== 0): ?>
                                        <form method="POST" style="margin: 0;">
                                            <input type="hidden" name="parking_id" value="<?php echo $row['parking_id']; ?>">
                                            <button type="submit" name="issue_penalty" class="btn-penalty" onclick="return confirm('Force penalize this parking slot space?')"><i class="fa-solid fa-gavel"></i> Penalize</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 40px; color: #888;"><i class="fa-solid fa-folder-open" style="font-size:1.8rem; display:block; margin-bottom:8px; color:#ccc;"></i>No active parking space listings discovered.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="admin_dashboard.php" class="nav-item">
            <i class="fa-solid fa-chart-pie"></i> Report
        </a>
        <a href="admin_manage_slots.php" class="nav-item active">
            <i class="fa-solid fa-list-check"></i> Slots
        </a>
        <a href="admin_reviews.php" class="nav-item">
            <i class="fa-solid fa-star"></i> Reviews
        </a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout from Admin Session?')">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

</body>
</html>