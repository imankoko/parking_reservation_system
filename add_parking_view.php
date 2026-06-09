<?php
session_start();
include 'db_connect.php';

// Security Check: Ensure only Listers can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lister') {
    header("Location: login_view.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$selected_branch = isset($_GET['branch']) ? $_GET['branch'] : 'Batu Pahat';
$selected_zone = isset($_GET['zone']) ? $_GET['zone'] : 'Wing A'; // Default to Wing A

// ADDED: Read and clear the success message from session
$success_msg = isset($_SESSION['slot_created_msg']) ? $_SESSION['slot_created_msg'] : null;
unset($_SESSION['slot_created_msg']);

// Fetch active pinned slots for the selected branch to display on the setup map
$slots_query = "SELECT * FROM tbl_parking_listing WHERE branch = $1 AND status != 'Inactive'";
$slots_res = pg_query_params($conn, $slots_query, array($selected_branch));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Parking - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Shared Dashboard UI Styles */
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; margin: 0; padding-bottom: 80px; }
        
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px; position: relative; 
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        /* Dashboard-style Zone Selector Bar */
        .zone-selector { display: flex; overflow-x: auto; background: #fff; padding: 12px; gap: 10px; border-bottom: 1px solid #eee; }
        .zone-btn { padding: 8px 20px; background: #f0f0f0; border-radius: 20px; text-decoration: none; color: #333; font-size: 13px; font-weight: bold; white-space: nowrap; transition: 0.3s; }
        .zone-btn.active { background: #d4bc44; border: 1px solid #333; }

        /* Map Setup Tool Styling */
        .map-section { text-align: center; background: #fff; padding: 15px; border-bottom: 1px solid #eee; }
        
        /* RESPONSIVE CSS ANCHOR MATRIX WRAPPER */
        .map-area { 
            position: relative; 
            border: 4px solid #d4bc44; 
            cursor: crosshair; 
            display: inline-block; 
            border-radius: 15px; 
            overflow: hidden; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            --base-width: 1000; /* Anchors the standard canvas layout design resolution width */
        }
        .map-img { display: block; max-width: 1000px; width: 100%; height: auto; user-select: none; -webkit-user-drag: none; }
        
        /* PROPORTIONAL MARKER PLACEMENT LOGIC BLOCK */
        .marker { 
            position: absolute; 
            
            /* Computes dimension values scaling proportionally through map size adjustments */
            width: calc(30px * var(--map-scale, 1)); 
            height: calc(60px * var(--map-scale, 1)); 
            
            border: 2px solid yellow; 
            background: rgba(0,0,0,0.65); 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            
            /* Translates database pixels cleanly to viewport-independent percentages */
            left: calc((var(--x-coord) / var(--base-width)) * 100%);
            top: calc((var(--y-coord) / var(--base-height)) * 100%);
            
            transform: translate(-50%, -50%); 
            pointer-events: none; 
            font-weight: bold;
            font-size: calc(11px * var(--map-scale, 1));
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }

        /* Responsive Setup Selector Pin Marker */
        #temp-marker { 
            position: absolute; 
            width: calc(22px * var(--map-scale, 1)); 
            height: calc(22px * var(--map-scale, 1)); 
            background: #e74c3c; 
            border: 3px solid white; 
            border-radius: 50%; 
            display: none; 
            
            /* Translates dynamic client click parameters smoothly to scalable boundaries */
            left: calc((var(--target-x) / var(--base-width)) * 100%);
            top: calc((var(--target-y) / var(--base-height)) * 100%);
            
            transform: translate(-50%, -50%); 
            z-index: 100; 
            box-shadow: 0 0 10px rgba(0,0,0,0.4); 
        }

        /* Form Styling */
        .form-container { padding: 20px; max-width: 500px; margin: auto; background: white; margin-top: 10px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        label { font-weight: bold; display: block; margin-bottom: 8px; color: #444; font-size: 0.9rem; }
        input, select { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; background: #fdfdfd; font-size: 1rem; }
        .submit-btn { background: #333; color: #d4bc44; padding: 15px; border: none; border-radius: 10px; width: 100%; font-weight: bold; cursor: pointer; text-transform: uppercase; letter-spacing: 1px; font-size: 1rem; }

        .bottom-nav {
            position: fixed; bottom: 0; width: 100%; background: #fff; display: flex; 
            justify-content: space-around; padding: 10px 0; border-top: 1px solid #eee; 
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 1000;
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }

        /* Popup styles */
        .popup-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        .popup-overlay.show { display: flex; }
        .popup-box {
            background: #fff;
            border-radius: 20px;
            padding: 35px 30px;
            max-width: 300px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            animation: popIn 0.3s ease;
        }
        @keyframes popIn {
            from { transform: scale(0.8); opacity: 0; }
            to   { transform: scale(1);   opacity: 1; }
        }
        .popup-icon { font-size: 55px; margin-bottom: 12px; }
        .popup-title { font-size: 1.2rem; font-weight: bold; color: #333; margin-bottom: 10px; }
        .popup-msg { font-size: 0.9rem; color: #555; margin-bottom: 22px; line-height: 1.5; }
        .popup-btn { background: #2ecc71; color: white; border: none; border-radius: 50px; padding: 12px 28px; font-weight: bold; font-size: 0.95rem; cursor: pointer; }

        /* DEVICE VIEWPORT SCREEN SIZE CORRECTION MEDIA QUERIES */
        @media (max-width: 768px) {
            .map-area { border-radius: 10px; border-width: 2px; }
            .marker { border-width: 1px; border-radius: 3px; }
            #temp-marker { border-width: 2px; }
        }
    </style>
</head>
<body>

    <?php if ($success_msg): ?>
    <div class="popup-overlay show" id="successPopup">
        <div class="popup-box">
            <div class="popup-icon">✅</div>
            <div class="popup-title">Slot Created!</div>
            <div class="popup-msg"><?php echo $success_msg; ?></div>
            <button class="popup-btn" onclick="document.getElementById('successPopup').classList.remove('show')">
                Add Another
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="header">
        <img src="logo.png" alt="Summit Logo" class="logo-img">
        <div class="header-text">
            <h2>Add Parking</h2>
            <p style="margin:0; font-size: 0.8em; color: #666;">Lister Management Dashboard</p>
        </div>
    </div>

    <div class="zone-selector">
        <a href="?branch=<?php echo $selected_branch; ?>&zone=Wing A" class="zone-btn <?php echo $selected_zone == 'Wing A' ? 'active' : ''; ?>">Wing A</a>
        <a href="?branch=<?php echo $selected_branch; ?>&zone=Wing B" class="zone-btn <?php echo $selected_zone == 'Wing B' ? 'active' : ''; ?>">Wing B</a>
        <a href="?branch=<?php echo $selected_branch; ?>&zone=Basement" class="zone-btn <?php echo $selected_zone == 'Basement' ? 'active' : ''; ?>">Basement</a>
    </div>

    <div class="map-section">
        <p style="margin-top: 0; font-size: 0.85rem; color: #666;"><i class="fa-solid fa-location-dot" style="color: #d4bc44;"></i> Click map to pin <strong><?php echo $selected_zone; ?></strong> slot location</p>
        <div class="map-area" id="mapSetupArea">
            <img src="summit_map.png" id="parkingMap" class="map-img">
            <div id="temp-marker"></div>
            
            <?php 
            while($row = pg_fetch_assoc($slots_res)): 
                if($row['x_coord'] > 0): 
                    $x = intval($row['x_coord']);
                    $y = intval($row['y_coord']);
            ?>
                    <div class="marker" style="--x-coord: <?php echo $x; ?>; --y-coord: <?php echo $y; ?>;">
                        <?php echo htmlspecialchars($row['slot_number']); ?>
                    </div>
            <?php endif; endwhile; ?>
        </div>
    </div>

    <div class="form-container">
        <form action="add_parking.php" method="POST">
            <input type="hidden" name="x_coord" id="targetX" required>
            <input type="hidden" name="y_coord" id="targetY" required>
            
            <input type="hidden" name="location" value="<?php echo $selected_zone; ?>">

            <label>Branch Mall</label>
            <select name="branch" readonly>
                <option value="Batu Pahat" <?php if($selected_branch == 'Batu Pahat') echo 'selected'; ?>>The Summit Batu Pahat</option>
                <option value="Kluang" <?php if($selected_branch == 'Kluang') echo 'selected'; ?>>The Summit Kluang</option>
            </select>

            <label>Slot Number</label>
            <input type="text" name="slot_number" placeholder="e.g. A-12" required>

            <label>Price per Hour (RM)</label>
            <input type="number" step="0.01" name="price" placeholder="5.00" required>

            <button type="submit" class="submit-btn">Create Slot in <?php echo $selected_zone; ?></button>
        </form>
    </div>

    <nav class="bottom-nav">
        <a href="lister_dashboard.php" class="nav-item">
            <i class="fa-solid fa-house"></i> Home
        </a>
        <a href="lister_listing_view.php" class="nav-item">
            <i class="fa-solid fa-list-check"></i> Listings
        </a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout?')">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

    <script>
        const map = document.getElementById('parkingMap');
        const tempMarker = document.getElementById('temp-marker');
        const setupArea = document.getElementById('mapSetupArea');

        // RUNTIME COMPLIANT AUTOMATED SCALING ARRAYS ENGINE
        function computeScalingMatrix() {
            if (setupArea && map) {
                const scalingFactor = map.clientWidth / 1000;
                const naturalHeightVal = map.naturalHeight || 600;
                
                setupArea.style.setProperty('--map-scale', scalingFactor);
                setupArea.style.setProperty('--base-height', naturalHeightVal);
            }
        }

        window.addEventListener('resize', computeScalingMatrix);
        map.addEventListener('load', computeScalingMatrix);
        if (map.complete) computeScalingMatrix();

        // CAPTURES SPATIAL PIN SELECTIONS WITH SCALING CORRECTION FACTOR APPLIED
        map.addEventListener('click', function(e) {
            const rect = map.getBoundingClientRect();
            
            // Calculate current runtime client selection bounds
            const clientX = e.clientX - rect.left;
            const clientY = e.clientY - rect.top;
            
            // Calculate relative mathematical scaling variables reverse-mapped back to baseline 1000px resolution boundaries
            const dynamicScale = map.clientWidth / 1000;
            const absoluteX = Math.round(clientX / dynamicScale);
            const absoluteY = Math.round(clientY / dynamicScale);
            
            // Log absolute baseline pixel values to hidden inputs for safe storage in PostgreSQL database fields
            document.getElementById('targetX').value = absoluteX;
            document.getElementById('targetY').value = absoluteY;

            // Update live workspace custom variables styles to render selection indicator instantly on map wrapper
            setupArea.style.setProperty('--target-x', absoluteX);
            setupArea.style.setProperty('--target-y', absoluteY);
            tempMarker.style.display = 'block';
        });
    </script>
</body>
</html>