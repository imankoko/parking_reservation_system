<?php
include 'db_connect.php';
$query = "SELECT * FROM tbl_parking_listing WHERE branch = 'Batu Pahat'";
$result = pg_query($conn, $query);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Your Spot - MySpot</title>
    <style>
        body { text-align: center; font-family: 'Segoe UI', sans-serif; background: #eee; }
        .map-container { 
            position: relative; display: inline-block; 
            margin: 20px auto; border: 10px solid #333; border-radius: 15px; overflow: hidden;
        }
        .spot {
            position: absolute; width: 35px; height: 70px;
            border: 2px solid white; border-radius: 5px;
            font-size: 12px; font-weight: bold; color: white;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: 0.3s;
        }
        .available { background: rgba(46, 204, 113, 0.85); box-shadow: 0 0 10px rgba(46,204,113,0.5); }
        .occupied { background: rgba(231, 76, 60, 0.85); cursor: not-allowed; }
        .spot:hover:not(.occupied) { transform: scale(1.2); z-index: 100; background: #27ae60; }
        
        .legend { margin-top: 20px; display: flex; justify-content: center; gap: 20px; }
        .leg-item { display: flex; align-items: center; gap: 8px; }
        .box { width: 20px; height: 20px; border-radius: 4px; }
    </style>
</head>
<body>

    <h2>The Summit Batu Pahat - Level 1</h2>

    <div class="legend">
        <div class="leg-item"><div class="box" style="background:#2ecc71"></div> Available</div>
        <div class="leg-item"><div class="box" style="background:#e74c3c"></div> Occupied</div>
    </div>

    <div class="map-container">
        <img src="summit_map.png" style="max-width: 1000px;">
        
        <?php while($row = pg_fetch_assoc($result)): ?>
            <?php 
                $class = ($row['status'] == 'Available') ? 'available' : 'occupied';
                $link = ($row['status'] == 'Available') ? "onclick='location.href=\"booking_view.php?id={$row['parking_id']}\"'" : "onclick='alert(\"Spot Taken!\")'";
            ?>
            <div class="spot <?php echo $class; ?>" 
                 style="left: <?php echo $row['x_coord']; ?>px; top: <?php echo $row['y_coord']; ?>px;"
                 <?php echo $link; ?>>
                <?php echo $row['slot_number']; ?>
            </div>
        <?php endwhile; ?>
    </div>

</body>
</html>