<?php
session_start();
include 'db_connect.php';

// Security: Only Admin can access this management view
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Query to get ALL slots and their current lister's name
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7f6; margin: 0; padding-bottom: 30px; }
        
        .admin-header { 
            background: #333; color: white; padding: 15px 20px; 
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .main-content { padding: 20px; max-width: 1000px; margin: auto; }
        
        .table-container { 
            background: white; padding: 20px; border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow-x: auto; 
        }

        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }

        .slot-link { color: #d4bc44; text-decoration: none; font-weight: bold; font-size: 1.1rem; }
        .slot-link:hover { text-decoration: underline; }

        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .status-available { background: #e8f5e9; color: #2e7d32; }
        .status-booked { background: #fff3e0; color: #ef6c00; }
        
        .action-btn { font-size: 12px; font-weight: bold; text-decoration: none; padding: 5px 10px; border-radius: 4px; }
        .btn-edit { color: #007bff; border: 1px solid #007bff; margin-right: 5px; }
        .btn-edit:hover { background: #007bff; color: white; }
        .btn-delete { color: #dc3545; border: 1px solid #dc3545; }
        .btn-delete:hover { background: #dc3545; color: white; }

        .btn-back-admin { background: #d4bc44; color: black; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 13px; }
    </style>
</head>
<body>

    <header class="admin-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="admin_dashboard.php" class="btn-back-admin">← Dashboard</a>
            <span style="font-weight: bold; font-size: 1.2rem;">Manage All Slots</span>
        </div>
        <a href="logout.php" style="color: #ff6b6b; text-decoration: none; font-weight: bold;">Logout</a>
    </header>

    <div class="main-content">
        <div class="table-container">
            <p style="margin-top: 0; color: #666; font-size: 0.9rem;">
                <i class="fa-solid fa-circle-info"></i> Click on a <strong>Slot Number</strong> to view the current driver's plate and contact info.
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Slot No</th>
                        <th>Lister Name</th>
                        <th>Location</th>
                        <th>Price/Hr</th>
                        <th>Status</th>
                        <th>Admin Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (pg_num_rows($result) > 0): ?>
                        <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <a href="plate_number.php?id=<?php echo $row['parking_id']; ?>" class="slot-link">
                                    <?php echo htmlspecialchars($row['slot_number']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($row['lister_name']); ?></td>
                            <td><small><?php echo htmlspecialchars($row['location']); ?></small></td>
                            <td>RM <?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo ($row['status'] == 'Available') ? 'status-available' : 'status-booked'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit_parking.php?id=<?php echo $row['parking_id']; ?>" class="action-btn btn-edit">EDIT</a>
                                <a href="delete_parking.php?id=<?php echo $row['parking_id']; ?>" 
                                   class="action-btn btn-delete" 
                                   onclick="return confirm('Confirm deactivation of this slot?')">DELETE</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 30px; color: #999;">No parking slots found in the system.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>