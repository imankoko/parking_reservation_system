<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];
$selected_branch = isset($_GET['branch']) ? $_GET['branch'] : 'Batu Pahat';
$selected_zone = isset($_GET['zone']) ? $_GET['zone'] : 'All';

// 1. Fetch the actual list of slots for the grid/map
$query = "SELECT DISTINCT ON (slot_number) * FROM tbl_parking_listing 
          WHERE branch = $1 AND status != 'Inactive'";

if ($selected_zone !== 'All') {
    $query .= " AND location LIKE $2 ORDER BY slot_number ASC, parking_id DESC";
    $result = pg_query_params($conn, $query, array($selected_branch, "%$selected_zone%"));
} else {
    $query .= " ORDER BY slot_number ASC, parking_id DESC";
    $result = pg_query_params($conn, $query, array($selected_branch));
}

// 2. Count Available slots for the header display
$count_query = "SELECT COUNT(DISTINCT slot_number) FROM tbl_parking_listing 
                WHERE branch = $1 AND status = 'Available'";
if ($selected_zone !== 'All') {
    $count_query .= " AND location LIKE $2";
    $count_res = pg_query_params($conn, $count_query, array($selected_branch, "%$selected_zone%"));
} else {
    $count_res = pg_query_params($conn, $count_query, array($selected_branch));
}
$count_available = ($count_res) ? pg_fetch_result($count_res, 0, 0) : 0;
$has_slots = (pg_num_rows($result) > 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Available Parking - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Shared Styles from Driver Dashboard */
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; margin: 0; padding-bottom: 80px; }
        
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px; position: relative; 
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        /* Dashboard Top Alert for Empty Zones */
        .top-alert { background: #fee2e2; border-bottom: 2px solid #ef4444; padding: 15px; text-align: center; color: #b91c1c; font-size: 0.9rem; }

        /* Navigation and Buttons */
        .zone-selector { display: flex; overflow-x: auto; background: #fff; padding: 12px; gap: 10px; border-bottom: 1px solid #eee; }
        .zone-btn { padding: 8px 20px; background: #f0f0f0; border-radius: 20px; text-decoration: none; color: #333; font-size: 13px; font-weight: bold; white-space: nowrap; transition: 0.3s; }
        .zone-btn.active { background: #d4bc44; border: 1px solid #333; }

        .header-section { text-align: center; padding: 20px; background: white; }

        /* Interactive Map Styles */
        .map-wrapper { text-align: center; background: #fff; padding: 10px; }
        .map-container { position: relative; display: inline-block; border: 4px solid #d4bc44; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .map-img { display: block; max-width: 1000px; width: 100%; height: auto; }
        
        .spot { position: absolute; width: 35px; height: 70px; border: 2px solid white; border-radius: 5px; font-weight: bold; color: white; display: flex; align-items: center; justify-content: center; cursor: pointer; transform: translate(-50%, -50%); transition: 0.3s; font-size: 11px; }
        .available { background: rgba(46, 204, 113, 0.9); }
        .occupied { background: rgba(231, 76, 60, 0.9); cursor: not-allowed; }
        .spot:hover:not(.occupied) { transform: translate(-50%, -50%) scale(1.2); z-index: 100; background: #2ecc71; }

        /* Grid Layout */
        .parking-lot { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px; max-width: 500px; margin: auto; }
        .slot-box { height: 130px; background: white; border: 2px dashed #ccc; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: #333; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .slot-available { border: 2px solid #333; }
        
        .bottom-nav {
            position: fixed; bottom: 0; width: 100%; background: #fff; display: flex; 
            justify-content: space-around; padding: 10px 0; border-top: 1px solid #eee; 
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 1000;
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }
    </style>
</head>
<body>

    <!-- Nav-style Header Bar -->
    <div class="header">
        <img src="logo.png" alt="Summit Logo" class="logo-img">
        <div class="header-text">
            <h2>Find Parking</h2>
            <p style="margin:0; font-size: 0.8em; color: #666;"><?php echo htmlspecialchars($selected_branch); ?></p>
        </div>
    </div>

    <!-- Top Alert (Matches Dashboard Penalty/Success message style) -->
    <?php if (!$has_slots): ?>
        <div class="top-alert">
            <i class="fa-solid fa-circle-xmark"></i> <strong>No Slots Found</strong> in <?php echo htmlspecialchars($selected_zone); ?>. 
            <a href="?branch=<?php echo $selected_branch; ?>&zone=All" style="color: #b91c1c; font-weight: bold; text-decoration: underline; margin-left: 10px;">Reset View</a>
        </div>
    <?php endif; ?>

    <!-- Zone Filter -->
    <div class="zone-selector">
        <a href="?branch=<?php echo $selected_branch; ?>&zone=All" class="zone-btn <?php echo $selected_zone == 'All' ? 'active' : ''; ?>">All Zones</a>
        <a href="?branch=<?php echo $selected_branch; ?>&zone=Wing A" class="zone-btn <?php echo $selected_zone == 'Wing A' ? 'active' : ''; ?>">Wing A</a>
        <a href="?branch=<?php echo $selected_branch; ?>&zone=Wing B" class="zone-btn <?php echo $selected_zone == 'Wing B' ? 'active' : ''; ?>">Wing B</a>
        <a href="?branch=<?php echo $selected_branch; ?>&zone=Basement" class="zone-btn <?php echo $selected_zone == 'Basement' ? 'active' : ''; ?>">Basement</a>
    </div>

    <div class="header-section">
        <h2 style="margin:0; font-size: 1.2rem;">Interactive Map</h2>
        <p style="color: #666; font-size: 0.85rem; margin-top: 5px;">Available: <strong><?php echo $count_available; ?> slots</strong></p>
    </div>

    <!-- Interactive Map View -->
    <div class="map-wrapper">
        <div class="map-container">
            <img src="summit_map.png" id="parkingMap" class="map-img">
            <?php 
            pg_result_seek($result, 0);
            while($row = pg_fetch_assoc($result)): 
                if($row['x_coord'] > 0):
                    $is_available = ($row['status'] == 'Available');
                    $class = $is_available ? 'available' : 'occupied';
                    $action = $is_available ? "location.href='booking_view.php?id=".$row['parking_id']."'" : "alert('Slot Taken!')";
            ?>
                <div class="spot <?php echo $class; ?>" 
                     style="left: <?php echo $row['x_coord']; ?>px; top: <?php echo $row['y_coord']; ?>px;"
                     onclick="<?php echo $action; ?>">
                    <?php echo htmlspecialchars($row['slot_number']); ?>
                </div>
            <?php endif; endwhile; ?>
        </div>
    </div>

    <!-- List/Grid View -->
    <div class="parking-lot">
        <?php 
        pg_result_seek($result, 0); 
        while ($row = pg_fetch_assoc($result)): ?>
            <?php if ($row['status'] == 'Available'): ?>
                <a href="booking_view.php?id=<?php echo $row['parking_id']; ?>" class="slot-box slot-available">
                    <span style="font-size: 1.5rem; font-weight: bold;"><?php echo htmlspecialchars($row['slot_number']); ?></span>
                    <span style="color: #2ecc71; font-weight: bold;">RM <?php echo number_format($row['price'], 2); ?></span>
                    <small style="color:#888;"><?php echo htmlspecialchars($row['location']); ?></small>
                </a>
            <?php else: ?>
                <div class="slot-box" style="background: #e0e0e0; border: 2px solid #ccc; opacity: 0.6;">
                    <i class="fa-solid fa-car" style="font-size: 1.8rem; color: #999;"></i>
                    <small style="font-weight:bold;"><?php echo htmlspecialchars($row['slot_number']); ?></small>
                    <small>OCCUPIED</small>
                </div>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>

    <!-- Persistent Bottom Nav -->
    <nav class="bottom-nav">
        <a href="driver_dashboard.php" class="nav-item">
            <i class="fa-solid fa-house"></i> Home
        </a>
        <a href="booking_history.php" class="nav-item">
            <i class="fa-solid fa-rectangle-list"></i> History
        </a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

</body>
</html>