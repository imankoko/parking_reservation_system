<?php
session_start();
include 'db_connect.php'; 

// 1. Security check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

// 2. Handle Zone Selection
// Default to 'All' if no zone is clicked
$selected_zone = isset($_GET['zone']) ? $_GET['zone'] : 'All';

// 3. Query for the grid with Zone Filtering
// We use DISTINCT ON to prevent duplicate rows for the same slot
$query = "SELECT DISTINCT ON (slot_number) * FROM tbl_parking_listing WHERE status != 'Inactive'";

if ($selected_zone !== 'All') {
    $query .= " AND location LIKE $1 ORDER BY slot_number ASC, parking_id DESC";
    $result = pg_query_params($conn, $query, array("%$selected_zone%"));
} else {
    $query .= " ORDER BY slot_number ASC, parking_id DESC";
    $result = pg_query($conn, $query);
}

// 4. Count Available slots for the selected zone
$count_query = "SELECT COUNT(DISTINCT slot_number) FROM tbl_parking_listing WHERE status = 'Available'";
if ($selected_zone !== 'All') {
    $count_query .= " AND location LIKE $1";
    $count_res = pg_query_params($conn, $count_query, array("%$selected_zone%"));
} else {
    $count_res = pg_query($conn, $count_query);
}
$count_available = pg_fetch_result($count_res, 0, 0);

// Alert if absolutely nothing is available in the whole mall
if ($selected_zone == 'All' && $count_available == 0) {
    echo "<script>alert('Sorry, no parking slots are currently available at The Summit.'); window.location='driver_dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Pick Your Spot - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; margin: 0; padding-bottom: 80px; }
        
        /* Navigation */
        .nav-container {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 20px; background: #fff; border-bottom: 2px solid #FFD700;
        }
        .btn-sq { 
            display: inline-flex; align-items: center; justify-content: center;
            width: 100px; height: 40px; text-decoration: none; font-weight: bold; 
            border-radius: 0px; font-size: 12px; text-transform: uppercase;
        }

        /* Zone Tabs */
        .zone-selector {
            display: flex; overflow-x: auto; background: #fff; padding: 12px; gap: 10px; 
            border-bottom: 1px solid #eee; scrollbar-width: none;
        }
        .zone-selector::-webkit-scrollbar { display: none; }
        .zone-btn {
            padding: 8px 20px; background: #f0f0f0; border-radius: 20px;
            text-decoration: none; color: #333; font-size: 13px; font-weight: bold;
            white-space: nowrap; border: 1px solid #ddd; transition: 0.2s;
        }
        .zone-btn.active { background: #FFD700; border-color: #d4bc44; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

        /* Header & Notifications */
        .header-section { text-align: center; padding: 20px; background: white; }
        .status-badge {
            display: inline-block; padding: 5px 15px; border-radius: 50px;
            font-size: 12px; font-weight: bold; margin-top: 8px;
        }
        .status-high { background: #e8f5e9; color: #2e7d32; }
        .status-low { background: #fff3e0; color: #ef6c00; }
        .status-empty { background: #ffebee; color: #c62828; }

        /* Parking Grid Layout */
        
        .parking-lot {
            display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
            padding: 20px; max-width: 500px; margin: auto;
            background: #eee; border-left: 10px dashed #fff; border-right: 10px dashed #fff;
        }

        .slot-box {
            height: 130px; background: white; border: 2px dashed #ccc;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; position: relative; text-decoration: none;
            color: #333; transition: 0.3s; border-radius: 8px;
        }

        .slot-occupied { background: #e0e0e0; border-style: solid; cursor: not-allowed; opacity: 0.7; }
        .car-icon { font-size: 40px; margin-bottom: 5px; }

        .slot-available { border: 2px solid #333; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .slot-available:hover { background: #fff9c4; border-color: #FFD700; transform: translateY(-2px); }
        
        .price-tag { font-size: 13px; font-weight: bold; color: #2ecc71; margin-top: 4px; }
        .slot-name { font-weight: bold; font-size: 20px; }
        .location-label { font-size: 10px; color: #777; margin-top: 2px; text-transform: uppercase; }

        .footer-action {
            position: fixed; bottom: 0; width: 100%;
            background: white; padding: 15px; box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
            text-align: center; z-index: 100;
        }
    </style>
</head>
<body>

    <div class="nav-container">
        <a href="driver_dashboard.php" class="btn-sq" style="background:#666; color:white;">← Back</a>
        <a href="logout.php" class="btn-sq" style="background:#dc3545; color:white;">Logout</a>
    </div>

    <div class="zone-selector">
        <a href="?zone=All" class="zone-btn <?php echo $selected_zone == 'All' ? 'active' : ''; ?>">All Zones</a>
        <a href="?zone=Wing A" class="zone-btn <?php echo $selected_zone == 'Wing A' ? 'active' : ''; ?>">Wing A</a>
        <a href="?zone=Wing B" class="zone-btn <?php echo $selected_zone == 'Wing B' ? 'active' : ''; ?>">Wing B</a>
        <a href="?zone=Basement" class="zone-btn <?php echo $selected_zone == 'Basement' ? 'active' : ''; ?>">Basement</a>
    </div>

    <div class="header-section">
        <h2 style="margin:0;">Pick your spot</h2>
        <div class="status-badge <?php 
            if ($count_available > 5) echo 'status-high';
            elseif ($count_available > 0) echo 'status-low';
            else echo 'status-empty';
        ?>">
            <?php 
                if ($count_available > 5) echo "🟢 Plenty of space in $selected_zone";
                elseif ($count_available > 0) echo "🟠 Limited spots in $selected_zone";
                else echo "🔴 $selected_zone is FULL";
            ?>
        </div>
    </div>

    <div class="parking-lot">
        <?php while ($row = pg_fetch_assoc($result)): ?>
            <?php if ($row['status'] == 'Available'): ?>
                <a href="booking_view.php?id=<?php echo $row['parking_id']; ?>" class="slot-box slot-available">
                    <span class="slot-name"><?php echo htmlspecialchars($row['slot_number']); ?></span>
                    <span class="price-tag">RM <?php echo number_format($row['price'], 2); ?></span>
                    <span class="location-label"><?php echo htmlspecialchars($row['location']); ?></span>
                </a>
            <?php else: ?>
                <div class="slot-box slot-occupied">
                    <span class="car-icon">🚗</span>
                    <small style="font-weight: bold; color: #666;"><?php echo htmlspecialchars($row['slot_number']); ?></small>
                    <small style="font-size: 9px; color: #999;">OCCUPIED</small>
                </div>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>

    <div class="footer-action">
        <p style="font-size: 13px; color: #333; margin: 0; font-weight: 500;">
            <i class="fa-solid fa-circle-info" style="color: #FFD700;"></i> 
            Tap an empty slot to confirm your reservation.
        </p>
    </div>

</body>
</html>