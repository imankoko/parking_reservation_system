<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lister') {
    header("Location: login_view.php");
    exit();
}

$lister_id = $_SESSION['user_id'];

// SYNC TIMEZONE CONFIG
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");

// FIXED: Removed 'AND p.lister_id = $1' so all listers see all slots.
// Kept everything else exactly like your original database structure.
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
          ORDER BY p.slot_number ASC";

// Running a standard query with no extra parameters now
$result = pg_query($conn, $query);
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
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-available { background: #e8f5e9; color: #2e7d32; }
        .status-booked { background: #ffebee; color: #c62828; }
        .status-occupied { background: #e3f2fd; color: #0d47a1; } 
        .status-penalty { background: #e74c3c; color: white; }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: #fff; display: flex; justify-content: space-around; padding: 12px 0; border-top: 1px solid #eee; }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.7rem; flex: 1; }
        .nav-item i { font-size: 1.4rem; display: block; margin-bottom: 3px; }
        .nav-item.active { color: #000; }
        .qr-thumb { width: 45px; height: 45px; border: 1px solid #ddd; padding: 2px; border-radius: 4px; cursor: pointer; transition: transform 0.2s; }
        .qr-thumb:hover { transform: scale(2); position: relative; z-index: 10; background: white; }
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
                        <th>Scan QR Check-In</th> 
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && pg_num_rows($result) > 0): ?>
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
                                <?php 
                                    $badge_class = 'status-booked'; 
                                    if ($row['status'] === 'Available') { 
                                        $badge_class = 'status-available'; 
                                    } elseif ($row['status'] === 'Occupied') { 
                                        $badge_class = 'status-occupied'; 
                                    } elseif ($row['status'] === 'Penalty') { 
                                        $badge_class = 'status-penalty'; 
                                    }
                                ?>
                                <span class="status-badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                            </td>
                            <td><small><?php echo htmlspecialchars($row['current_plate'] ?: '-'); ?></small></td>
                            
                            <td>
                                <?php 
                                    $slot_param = urlencode($row['slot_number']);
                                    $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode("http://localhost/parking_system/verify_qr.php?slot=" . $slot_param);
                                ?>
                                <a href="verify_qr.php?slot=<?php echo $slot_param; ?>" target="_blank" title="Click to simulate scanning this physical slot barcode sticker">
                                    <img src="<?php echo $qr_api_url; ?>" alt="QR Code" class="qr-thumb">
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 20px; color: #888;">No active listings found.</td></tr>
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