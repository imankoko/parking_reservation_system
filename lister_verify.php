<?php
session_start();
include 'db_connect.php';

// Extract the incoming token parameter from the scanned QR code link
$scanned_booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

$status_state = "WAITING"; // Options: WAITING, VALID, INVALID, ALREADY_USED
$driver_name = "";
$slot_assigned = "";
$plate_number = "";

if ($scanned_booking_id > 0) {
    // Force connection to stay localized on Malaysian Time
    pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");

    // 1. Query the layout data linked with the incoming token id
    $query = "SELECT b.*, u.full_name as driver_name, p.slot_number, p.status as current_slot_status, p.parking_id
              FROM tbl_booking b
              JOIN tbl_user u ON b.user_id = u.user_id
              JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
              WHERE b.booking_id = $1 LIMIT 1";
    $res = pg_query_params($conn, $query, array($scanned_booking_id));
    $data = pg_fetch_assoc($res);

    if ($data) {
        $driver_name   = $data['driver_name'];
        $slot_assigned = $data['slot_number'];
        $plate_number  = $data['plate_number'];
        $parking_id    = intval($data['parking_id']);

        if ($data['booking_status'] === 'Confirmed' && $data['current_slot_status'] === 'Booked') {
            $status_state = "VALID";

            // Trigger atomic database state check-in mutations
            pg_query($conn, "BEGIN");
            // Turn the spot status into 'Occupied' inside your lot records
            pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Occupied' WHERE parking_id = $1", array($parking_id));
            pg_query($conn, "COMMIT");

        } elseif ($data['current_slot_status'] === 'Occupied') {
            // DUPLICATION PREVENTER: Pass was already presented previously
            $status_state = "ALREADY_USED";
        } else {
            $status_state = "INVALID";
        }
    } else {
        $status_state = "INVALID";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Lister Verification Terminal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; margin: 0; text-align: center; background: #1a1a1a; color: white; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .lister-card { max-width: 450px; width: 90%; background: #262626; padding: 30px; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); border: 2px solid #333; }
        
        .state-bubble { padding: 18px; border-radius: 10px; font-weight: bold; font-size: 1.3rem; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
        .state-WAITING { background: #434343; color: #bbb; border: 2px dashed #555; }
        .state-VALID { background: #1b5e20; color: #fff; border: 2px solid #4caf50; animation: pulse 1s infinite; }
        .state-INVALID { background: #b71c1c; color: #fff; border: 2px solid #f44336; }
        .state-ALREADY_USED { background: #f57f17; color: #000; border: 2px solid #fbc02d; }

        .info-grid { text-align: left; background: #111; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #222; font-size: 14px; }
        .info-row:last-child { border: none; }
        .label { color: #666; }
        .value { font-weight: bold; color: #fff; }
        .btn-reset { display: block; width: 100%; padding: 12px; background: #d4bc44; color: black; font-weight: bold; border: none; border-radius: 6px; text-decoration: none; margin-top: 20px; font-size: 13px; box-sizing: border-box; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.02); } 100% { transform: scale(1); } }
    </style>
</head>
<body>

    <div class="lister-card">
        <h2 style="margin-top: 0;"><i class="fa-solid fa-id-card-clip" style="color: #d4bc44;"></i> Lister Space Control</h2>
        <p style="color: #666; font-size: 12px; margin-top: -8px;">MySpot Space Verification Interface</p>

        <div class="state-bubble state-<?php echo $status_state; ?>">
            <?php if($status_state === 'WAITING'): ?>
                <i class="fa-solid fa-circle-nodes"></i> Awaiting Driver QR Scan Pass
            <?php elseif($status_state === 'VALID'): ?>
                <i class="fa-solid fa-circle-check"></i> ACCESS GRANTED
            <?php elseif($status_state === 'ALREADY_USED'): ?>
                <i class="fa-solid fa-triangle-exclamation"></i> ACCESS DENIED: PASS USED
            <?php else: ?>
                <i class="fa-solid fa-circle-xmark"></i> INVALID RESERVATION PASS
            <?php endif; ?>
        </div>

        <?php if($status_state !== 'WAITING' && $data): ?>
            <div class="info-grid">
                <div class="info-row"><span class="label">Driver Account:</span> <span class="value"><?php echo htmlspecialchars($driver_name); ?></span></div>
                <div class="info-row"><span class="label">Registered Plate:</span> <span class="value" style="background:#333; padding:2px 6px; border-radius:4px; font-family:monospace;"><?php echo htmlspecialchars($plate_number); ?></span></div>
                <div class="info-row"><span class="label">Assigned Bay:</span> <span class="value" style="color:#FFD700;"><?php echo htmlspecialchars($slot_assigned); ?></span></div>
                <div class="info-row"><span class="label">Pass Token:</span> <span class="value">MYSPOT-B<?php echo $scanned_booking_id; ?></span></div>
            </div>
        <?php else: ?>
            <p style="padding: 10px 0; color:#555; font-size:12px;">Scan the dynamic dashboard QR code from the driver's phone screen to verify authorization and trigger checkout access.</p>
        <?php endif; ?>

        <a href="lister_verify.php" class="btn-reset">🔄 RESET TERMINAL SCREEN</a>
    </div>

</body>
</html>