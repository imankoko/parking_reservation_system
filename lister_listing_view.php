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
date_default_timezone_set('Asia/Kuala_Lumpur');

// --- UNIFIED SEQUENTIAL FETCH ENGINE ---
// This safely pulls distinct slots, aggregates todays total confirmed timelines, 
// and accurately checks if any vehicle is currently inside its booked timeframe right now.
$query = "SELECT DISTINCT ON (p.slot_number) p.*, 
          (SELECT b.plate_number 
           FROM tbl_booking b 
           JOIN tbl_parking_listing pl ON b.parking_id = pl.parking_id
           WHERE pl.slot_number = p.slot_number 
           AND TRIM(UPPER(b.booking_status)) IN ('CONFIRMED', 'PAID', 'OCCUPIED') 
           AND b.booking_date::date = CURRENT_DATE
           AND CURRENT_TIME::time BETWEEN b.start_time::time AND b.end_time::time
           LIMIT 1) as plate_number,
          (SELECT STRING_AGG(TO_CHAR(b.start_time::time, 'HH:MI AM') || ' - ' || TO_CHAR(b.end_time::time, 'HH:MI AM'), ', ')
           FROM tbl_booking b 
           JOIN tbl_parking_listing pl ON b.parking_id = pl.parking_id
           WHERE pl.slot_number = p.slot_number 
           AND TRIM(UPPER(b.booking_status)) IN ('CONFIRMED', 'PAID', 'OCCUPIED') 
           AND b.booking_date::date = CURRENT_DATE) as todays_timelines
          FROM tbl_parking_listing p
          WHERE TRIM(BOTH FROM UPPER(p.status)) != 'INACTIVE'
          ORDER BY p.slot_number ASC, 
                   CASE WHEN TRIM(UPPER(p.status)) IN ('PENALIZED', 'OCCUPIED') THEN 1 ELSE 2 END ASC, 
                   p.parking_id DESC";

$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Listings - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; margin: 0; padding-bottom: 80px; }
        .header { background: linear-gradient(to bottom, #d4bc44, #ffffff); padding: 30px 20px; display: flex; align-items: center; gap: 15px; }
        .btn-back { color: #000; text-decoration: none; font-size: 1.2rem; }
        .main-content { padding: 15px; }
        .table-container { background: white; padding: 15px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 750px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle; }
        th { background: #f8f9fa; color: #888; text-transform: uppercase; font-size: 11px; font-weight: bold; letter-spacing: 0.5px; }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: uppercase; display: inline-block; }
        .status-available { background: #e8f5e9; color: #2e7d32; }
        .status-booked { background: #fff8e1; color: #f57f17; }
        .status-occupied { background: #e3f2fd; color: #0d47a1; } 
        .status-penalty { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        .timeline-info { font-size: 11px; color: #64748b; margin-top: 4px; display: block; line-height: 1.4; }
        
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; display: flex; justify-content: space-around; padding: 12px 0; border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 1000; box-sizing: border-box; }
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
                        <th>Status / Schedule Today</th>
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
                            <td><small style="color: #475569; font-weight: 500;"><?php echo htmlspecialchars($row['location']); ?></small></td>
                            <td><strong>RM <?php echo number_format($row['price'], 2); ?></strong></td>
                            <td>
                                <?php 
                                    $raw_status = trim(strtoupper($row['status']));
                                    $has_timelines = !empty($row['todays_timelines']);
                                    $is_active_now = !empty($row['plate_number']);

                                    // Dynamic Badge Context Resolution
                                    if ($raw_status === 'PENALIZED' || $raw_status === 'PENALTY') {
                                        $badge_class = 'status-penalty';
                                        $display_status = 'PENALIZED';
                                    } elseif ($is_active_now || $raw_status === 'OCCUPIED') {
                                        $badge_class = 'status-occupied';
                                        $display_status = 'OCCUPIED';
                                    } elseif ($has_timelines) {
                                        $badge_class = 'status-booked';
                                        $display_status = 'BOOKED';
                                    } else {
                                        $badge_class = 'status-available';
                                        $display_status = 'AVAILABLE';
                                    }
                                ?>
                                <span class="status-badge <?php echo $badge_class; ?>">
                                    <?php echo $display_status; ?>
                                </span>
                                
                                <?php if ($has_timelines): ?>
                                    <span class="timeline-info">
                                        <i class="fa-solid fa-clock"></i> Slots: <?php echo htmlspecialchars($row['todays_timelines']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="timeline-info" style="color: #2ecc71;">
                                        <i class="fa-solid fa-circle-check"></i> Vacant All Day
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-size: 1.1rem; font-weight: bold; color: #1e293b;">
                                    <?php echo htmlspecialchars($row['plate_number'] ?: '-'); ?>
                                </span>
                            </td>
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
                        <tr><td colspan="6" style="text-align:center; padding: 30px; color: #64748b; font-weight: 500;">No active listings found.</td></tr>
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