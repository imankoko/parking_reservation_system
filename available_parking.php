<?php
session_start();
include 'db_connect.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];

// --- AUTOMATED GPS BOUNDING DETECTION (PRIORITIZED) ---
$selected_zone = isset($_GET['zone']) ? trim($_GET['zone']) : 'All';
$selected_branch = '';

// 1. Check if GPS tokens are passed via browser location metadata first
if (isset($_GET['lat']) && isset($_GET['lng'])) {
    $lat = floatval($_GET['lat']);
    $lng = floatval($_GET['lng']);
    
    // Bounding Box Lookup
    if ($lat > 1.95 && $lat < 2.10 && $lng > 103.20 && $lng < 103.45) {
        $selected_branch = 'Kluang';
    } else {
        $selected_branch = 'Batu Pahat';
    }
} 

// 2. Fallback: If no GPS data is present in the URL, check if a manual branch parameter was passed
if (empty($selected_branch)) {
    $selected_branch = isset($_GET['branch']) ? trim($_GET['branch']) : 'Batu Pahat';
}

// Force connection to stay localized on Malaysian Time
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
date_default_timezone_set('Asia/Kuala_Lumpur');

// --- 100% MATCHED LOCALHOST QUERY ENGINE ---
$query = "SELECT DISTINCT ON (slot_number) *,
          (SELECT STRING_AGG(TO_CHAR(b.start_time, 'HH:MI AM') || ' - ' || TO_CHAR(b.end_time, 'HH:MI AM'), ', ')
           FROM tbl_booking b 
           WHERE b.parking_id = tbl_parking_listing.parking_id 
           AND b.booking_status = 'Confirmed' 
           AND b.booking_date = CURRENT_DATE) as reserved_timelines
          FROM tbl_parking_listing 
          WHERE TRIM(BOTH FROM UPPER(branch)) LIKE $1 
          AND TRIM(BOTH FROM UPPER(status)) != 'INACTIVE'";

if ($selected_zone !== 'All') {
    $query .= " AND location LIKE $2 ORDER BY slot_number ASC, parking_id DESC";
    $result = pg_query_params($conn, $query, array("%" . strtoupper($selected_branch) . "%", "%$selected_zone%"));
} else {
    $query .= " ORDER BY slot_number ASC, parking_id DESC";
    $result = pg_query_params($conn, $query, array("%" . strtoupper($selected_branch) . "%"));
}

// FALLBACK ENGINE: If the branch filter returned 0 rows, show all active slots
if (!$result || pg_num_rows($result) === 0) {
    $query = "SELECT DISTINCT ON (slot_number) *,
              (SELECT STRING_AGG(TO_CHAR(b.start_time, 'HH:MI AM') || ' - ' || TO_CHAR(b.end_time, 'HH:MI AM'), ', ')
               FROM tbl_booking b 
               WHERE b.parking_id = tbl_parking_listing.parking_id 
               AND b.booking_status = 'Confirmed' 
               AND b.booking_date = CURRENT_DATE) as reserved_timelines
              FROM tbl_parking_listing 
              WHERE TRIM(BOTH FROM UPPER(status)) != 'INACTIVE'";
    if ($selected_zone !== 'All') {
        $query .= " AND location LIKE $1 ORDER BY slot_number ASC, parking_id DESC";
        $result = pg_query_params($conn, $query, array("%$selected_zone%"));
    } else {
        $query .= " ORDER BY slot_number ASC, parking_id DESC";
        $result = pg_query($conn, $query);
    }
}

// --- 2. COUNT TOTAL VACANT SLOTS ---
$count_query = "SELECT COUNT(DISTINCT slot_number) FROM tbl_parking_listing 
                WHERE TRIM(BOTH FROM UPPER(status)) = 'AVAILABLE'";
if ($selected_zone !== 'All') {
    $count_query .= " AND location LIKE $1";
    $count_res = pg_query_params($conn, $count_query, array("%$selected_zone%"));
} else {
    $count_res = pg_query($conn, $count_query);
}

$count_available = ($count_res) ? pg_fetch_result($count_res, 0, 0) : 0;
$has_slots = ($result && pg_num_rows($result) > 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Available Parking - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; margin: 0; padding-bottom: 80px; }
        
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px; position: relative; 
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        .top-alert { background: #fee2e2; border-bottom: 2px solid #ef4444; padding: 15px; text-align: center; color: #b91c1c; font-size: 0.9rem; }

        .zone-selector { display: flex; overflow-x: auto; background: #fff; padding: 12px; gap: 10px; border-bottom: 1px solid #eee; }
        .zone-btn { padding: 8px 20px; background: #f0f0f0; border-radius: 20px; text-decoration: none; color: #333; font-size: 13px; font-weight: bold; white-space: nowrap; transition: 0.3s; }
        .zone-btn.active { background: #d4bc44; border: 1px solid #333; }

        .header-section { text-align: center; padding: 20px; background: white; }

        .map-wrapper { text-align: center; background: #fff; padding: 10px; }
        .map-container { 
            position: relative; 
            display: inline-block; 
            border: 4px solid #d4bc44; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            --base-width: 1000; 
        }
        .map-img { display: block; max-width: 1000px; width: 100%; height: auto; }
        
        .spot { 
            position: absolute; 
            width: calc(35px * var(--map-scale, 1)); 
            height: calc(70px * var(--map-scale, 1)); 
            border: 2px solid white; 
            border-radius: 5px; 
            font-weight: bold; 
            color: white; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            left: calc((var(--x-coord) / var(--base-width)) * 100%);
            top: calc((var(--y-coord) / var(--base-height)) * 100%);
            transform: translate(-50%, -50%); 
            transition: transform 0.2s ease, background-color 0.2s ease; 
            font-size: calc(11px * var(--map-scale, 1)); 
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .available { background: rgba(46, 204, 113, 0.95); }
        .occupied { background: rgba(231, 76, 60, 0.9); cursor: not-allowed; }
        .spot:hover:not(.occupied) { transform: translate(-50%, -50%) scale(1.15); z-index: 100; background: #2ecc71; }

        .parking-lot { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 20px; max-width: 500px; margin: auto; }
        .slot-box { height: 130px; background: white; border: 2px dashed #ccc; display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: #333; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        
        .slot-available { border: 2px solid #2ecc71; background: #ffffff; cursor: pointer; }
        .slot-available:active { background: #f4fbf7; }

        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%; background: #fff; display: flex; 
            justify-content: space-around; padding: 10px 0; border-top: 1px solid #eee; 
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05); z-index: 1000; box-sizing: border-box;
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 9999; padding: 20px; box-sizing: border-box; }
        .modal-card { background: #fff; width: 100%; max-width: 400px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); overflow: hidden; animation: modalFadeIn 0.3s ease; }
        .modal-header { background: linear-gradient(to bottom, #d4bc44, #ffffff); padding: 20px; text-align: center; font-weight: bold; font-size: 1.2rem; color: #000; border-bottom: 1px solid #ddd; }
        .modal-body { padding: 20px; color: #333; font-size: 0.95rem; }
        .timeline-container { background: #f1f5f9; padding: 12px; border-radius: 8px; margin: 12px 0; font-size: 0.9rem; color: #475569; border-left: 4px solid #ef4444; line-height: 1.5; }
        .modal-footer { padding: 15px 20px 20px 20px; display: flex; gap: 12px; }
        .modal-btn { flex: 1; padding: 12px; border: none; border-radius: 10px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 0.85rem; transition: 0.2s; }
        .btn-cancel { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
        .btn-confirm { background: #333; color: #d4bc44; }
        
        @keyframes modalFadeIn {
            from { transform: scale(0.9); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

    <div class="header">
        <img src="logo.png" alt="Summit Logo" class="logo-img">
        <div class="header-text">
            <h2>Find Parking</h2>
            <p style="margin:0; font-size: 0.8em; color: #666;"><?php echo htmlspecialchars($selected_branch); ?></p>
        </div>
    </div>

    <?php if (!$has_slots): ?>
        <div class="top-alert">
            <i class="fa-solid fa-circle-xmark"></i> <strong>No Slots Configured</strong>. 
            <a href="?branch=<?php echo urlencode($selected_branch); ?>&zone=All" style="color: #b91c1c; font-weight: bold; text-decoration: underline; margin-left: 10px;">Reset View</a>
        </div>
    <?php endif; ?>

    <div class="zone-selector">
        <a href="?branch=<?php echo urlencode($selected_branch); ?>&zone=All" class="zone-btn <?php echo $selected_zone == 'All' ? 'active' : ''; ?>">All Zones</a>
        <a href="?branch=<?php echo urlencode($selected_branch); ?>&zone=Wing A" class="zone-btn <?php echo $selected_zone == 'Wing A' ? 'active' : ''; ?>">Wing A</a>
        <a href="?branch=<?php echo urlencode($selected_branch); ?>&zone=Wing B" class="zone-btn <?php echo $selected_zone == 'Wing B' ? 'active' : ''; ?>">Wing B</a>
        <a href="?branch=<?php echo urlencode($selected_branch); ?>&zone=Basement" class="zone-btn <?php echo $selected_zone == 'Basement' ? 'active' : ''; ?>">Basement</a>
    </div>

    <div class="header-section">
        <h2 style="margin:0; font-size: 1.2rem;">Parking Layout</h2>
        <p style="color: #666; font-size: 0.85rem; margin-top: 5px;">Available: <strong><?php echo $count_available; ?> slots</strong></p>
    </div>

    <div class="map-wrapper">
        <div class="map-container" id="mapScaleContainer">
            <img src="summit_map.png" id="parkingMap" class="map-img">
            <?php 
            if ($has_slots):
                pg_result_seek($result, 0);
                while($row = pg_fetch_assoc($result)): 
                    if(intval($row['x_coord']) > 0):
                        $clean_status = trim(strtoupper($row['status']));
                        $timeline_data = !empty($row['reserved_timelines']) ? htmlspecialchars($row['reserved_timelines']) : '';
                        
                        if ($clean_status === 'AVAILABLE' || (!empty($timeline_data) && $clean_status !== 'PENALIZED')) {
                            $class = 'available';
                            $action = "openThemedModal('".$row['slot_number']."', '".$timeline_data."', '".intval($row['parking_id'])."')";
                        } else {
                            $class = 'occupied';
                            $action = "alert('Slot Taken or Locked!')";
                        }
                        
                        $x = intval($row['x_coord']);
                        $y = intval($row['y_coord']);
            ?>
                        <div class="spot <?php echo $class; ?>" 
                             style="--x-coord: <?php echo $x; ?>; --y-coord: <?php echo $y; ?>;"
                             onclick="<?php echo $action; ?>">
                            <?php echo htmlspecialchars($row['slot_number']); ?>
                        </div>
            <?php 
                    endif; 
                endwhile; 
            endif;
            ?>
        </div>
    </div>

    <div class="parking-lot">
        <?php 
        if ($has_slots):
            pg_result_seek($result, 0); 
            while ($row = pg_fetch_assoc($result)): 
                $clean_status = trim(strtoupper($row['status']));
                $timeline_data = !empty($row['reserved_timelines']) ? htmlspecialchars($row['reserved_timelines']) : '';
                
                if ($clean_status === 'AVAILABLE' || (!empty($timeline_data) && $clean_status !== 'PENALIZED')): 
        ?>
                    <div class="slot-box slot-available" onclick="openThemedModal('<?php echo $row['slot_number']; ?>', '<?php echo $timeline_data; ?>', '<?php echo intval($row['parking_id']); ?>')">
                        <span style="font-size: 1.5rem; font-weight: bold; color: #333;"><?php echo htmlspecialchars($row['slot_number']); ?></span>
                        <span style="color: #2ecc71; font-weight: bold;">RM <?php echo number_format($row['price'], 2); ?></span>
                        <small style="color:#888;"><?php echo htmlspecialchars($row['location']); ?></small>
                    </div>
                <?php else: ?>
                    <div class="slot-box" style="background: #e0e0e0; border: 2px solid #ccc; opacity: 0.6;">
                        <i class="fa-solid fa-car" style="font-size: 1.8rem; color: #999;"></i>
                        <small style="font-weight:bold;"><?php echo htmlspecialchars($row['slot_number']); ?></small>
                        <small style="color: #ef4444; font-weight: bold;"><?php echo htmlspecialchars($clean_status); ?></small>
                    </div>
        <?php 
                endif; 
            endwhile; 
        endif;
        ?>
    </div>

    <div class="modal-overlay" id="customParkingModal">
        <div class="modal-card">
            <div class="modal-header">
                <i class="fa-solid fa-circle-info"></i> Slot Information
            </div>
            <div class="modal-body">
                Selected Parking Box: <strong id="modalSlotNo" style="font-size: 1.1rem; color: #333;">--</strong>
                <div style="margin-top: 15px; font-weight: bold; font-size: 0.85rem; text-transform: uppercase; color: #64748b;">
                    <i class="fa-solid fa-clock"></i> Booked Timelines Today:
                </div>
                <div class="timeline-container" id="modalTimelines">
                    --
                </div>
                <p style="margin: 15px 0 0 0; font-size: 0.9rem; color: #475569;">Would you like to reserve this parking slot?</p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn btn-cancel" onclick="closeThemedModal()">Cancel</button>
                <button class="modal-btn btn-confirm" id="modalConfirmBtn">Proceed</button>
            </div>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="driver_dashboard.php" class="nav-item"><i class="fa-solid fa-house"></i> Home</a>
        <a href="booking_history.php" class="nav-item"><i class="fa-solid fa-rectangle-list"></i> History</a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>

<script>
function openThemedModal(slotNumber, reservedTimelines, parkingId) {
    const modal = document.getElementById('customParkingModal');
    document.getElementById('modalSlotNo').innerText = slotNumber;
    
    const timelineContainer = document.getElementById('modalTimelines');
    if (!reservedTimelines || reservedTimelines.trim() === '') {
        timelineContainer.innerHTML = `<span style="color:#2ecc71; font-weight:bold;"><i class="fa-solid fa-circle-check"></i> Available: 6:00 AM - 10:00 PM</span>`;
    } else {
        const formattedTimelines = reservedTimelines.split(', ').join('<br>• ');
        timelineContainer.innerHTML = '• ' + formattedTimelines;
    }
    
    document.getElementById('modalConfirmBtn').setAttribute('onclick', `window.location.href='booking_view.php?id=${parkingId}'`);
    modal.style.display = 'flex';
}

function closeThemedModal() {
    document.getElementById('customParkingModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('customParkingModal');
    if (event.target === modal) { modal.style.display = 'none'; }
}

function recalculateMapAspectRatios() {
    const wrapper = document.getElementById("mapScaleContainer");
    const baseImage = document.getElementById("parkingMap");
    if (wrapper && baseImage && baseImage.clientWidth > 0) {
        const scaleFactor = baseImage.clientWidth / 1000;
        const nativeHeight = baseImage.naturalHeight || 600; 
        wrapper.style.setProperty('--map-scale', scaleFactor);
        wrapper.style.setProperty('--base-height', nativeHeight);
    }
}
window.addEventListener('resize', recalculateMapAspectRatios);
const mapImgEl = document.getElementById("parkingMap");
if (mapImgEl) { mapImgEl.addEventListener('load', recalculateMapAspectRatios); }
document.addEventListener("DOMContentLoaded", recalculateMapAspectRatios);
</script>
</body>
</html>