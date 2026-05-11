<?php
session_start();
include 'db_connect.php';

// Handle Saving new coordinate
if(isset($_POST['save_spot'])) {
    $parking_id = $_POST['parking_id'];
    $x = $_POST['x_coord'];
    $y = $_POST['y_coord'];
    
    $u_query = "UPDATE tbl_parking_listing SET x_coord = $1, y_coord = $2 WHERE parking_id = $3";
    pg_query_params($conn, $u_query, array($x, $y, $parking_id));
    header("Location: lister_map.php?success=1");
}

$slots = pg_query($conn, "SELECT * FROM tbl_parking_listing WHERE branch = 'Batu Pahat'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lister Map Setup</title>
    <style>
        .container { display: flex; gap: 20px; padding: 20px; font-family: sans-serif; }
        .map-area { position: relative; border: 2px solid #333; cursor: crosshair; }
        .map-img { display: block; max-width: 1000px; height: auto; }
        .marker { 
            position: absolute; width: 30px; height: 60px; 
            border: 2px solid yellow; background: rgba(0,0,0,0.5); 
            color: white; font-size: 10px; display: flex; align-items: center; justify-content: center;
        }
        .form-panel { width: 300px; padding: 20px; background: #f4f4f4; border-radius: 8px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>

<div class="container">
    <div class="map-area" id="mapContainer">
        <img src="summit_map.png" id="parkingMap" class="map-img">
        
        <?php while($row = pg_fetch_assoc($slots)): ?>
            <?php if($row['x_coord'] > 0): ?>
                <div class="marker" style="left: <?php echo $row['x_coord']; ?>px; top: <?php echo $row['y_coord']; ?>px;">
                    <?php echo $row['slot_number']; ?>
                </div>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>

    <div class="form-panel">
        <h3>Map Setup Tool</h3>
        <p>1. Click a spot on the map<br>2. Select the Slot Number<br>3. Save</p>
        <form method="POST">
            <label>X Coordinate:</label>
            <input type="text" name="x_coord" id="targetX" readonly>
            <label>Y Coordinate:</label>
            <input type="text" name="y_coord" id="targetY" readonly>
            
            <label>Select Slot:</label>
            <select name="parking_id" required>
                <?php 
                pg_result_seek($slots, 0);
                while($s = pg_fetch_assoc($slots)) echo "<option value='{$s['parking_id']}'>{$s['slot_number']}</option>"; 
                ?>
            </select>
            <button type="submit" name="save_spot" style="width:100%; padding:10px; background:green; color:white;">Update Location</button>
        </form>
    </div>
</div>

<script>
    const map = document.getElementById('parkingMap');
    map.addEventListener('click', function(e) {
        const rect = map.getBoundingClientRect();
        const x = Math.round(e.clientX - rect.left);
        const y = Math.round(e.clientY - rect.top);
        
        document.getElementById('targetX').value = x;
        document.getElementById('targetY').value = y;
    });
</script>
</body>
</html>