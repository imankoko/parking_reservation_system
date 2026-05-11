<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lister') {
    header("Location: login_view.php");
    exit();
}

$lister_id = $_SESSION['user_id'];

$query = "SELECT p.*, 
          (SELECT b.plate_number 
           FROM tbl_booking b 
           WHERE b.parking_id = p.parking_id 
           AND b.booking_status = 'Confirmed' 
           AND b.booking_date = CURRENT_DATE
           AND CURRENT_TIME BETWEEN b.start_time AND b.end_time
           LIMIT 1) as plate_number
          FROM tbl_parking_listing p
          WHERE p.status != 'Inactive'
          AND p.lister_id = $1
          ORDER BY p.slot_number ASC";

$result = pg_query_params($conn, $query, array($lister_id));
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Listings - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 80px; }
        .header { background: linear-gradient(to bottom, #d4bc44, #ffffff); padding: 30px 20px; display: flex; align-items: center; gap: 15px; }
        .btn-back { color: #000; text-decoration: none; font-size: 1.2rem; }
        .main-content { padding: 15px; }
        .table-container { background: white; padding: 10px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-available { background: #e8f5e9; color: #2e7d32; }
        .status-booked { background: #ffebee; color: #c62828; }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: #fff; display: flex; justify-content: space-around; padding: 12px 0; border-top: 1px solid #eee; }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.7rem; flex: 1; }
        .nav-item i { font-size: 1.4rem; display: block; margin-bottom: 3px; }
        .nav-item.active { color: #000; }
    </style>
</head>
<body>
    <div class="header">
        <a href="lister_dashboard.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i></a>
        <h2 style="margin:0; font-size: 1.2rem;">My Parking Listings</h2>
    </div>

    <div class="main-content">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Slot No</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Current Plate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (pg_num_rows($result) > 0): ?>
                        <?php while ($row = pg_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <a href="plate_number.php?id=<?php echo $row['parking_id']; ?>" style="text-decoration: none; color: #d4bc44; font-weight: bold;">
                                    <?php echo htmlspecialchars($row['slot_number']); ?>
                                </a>
                            </td>
                            <td><small><?php echo htmlspecialchars($row['location']); ?></small></td>
                            <td>RM <?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <span class="status-badge <?php echo ($row['status'] == 'Available') ? 'status-available' : 'status-booked'; ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><small><?php echo htmlspecialchars($row['current_plate'] ?: '-'); ?></small></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 20px; color: #888;">No active listings found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="lister_dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i> Home</a>
        <a href="lister_listing_view.php" class="nav-item active"><i class="fa-solid fa-list-check"></i> Listings</a>
        <a href="add_parking_view.php" class="nav-item"><i class="fa-solid fa-plus-square"></i> Add</a>
    </nav>
</body>
</html>