<?php
session_start();
include 'db_connect.php';

// Enable error reporting to find any hidden database errors
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
ini_set('display_errors', 0); // Disables raw text warnings from interrupting user view interfaces

// 1. UPDATED SECURITY: Allow both Lister and Admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'Lister' && $_SESSION['role'] !== 'Admin')) {
    header("Location: login_view.php");
    exit();
}

// 2. CHECK FOR ID: Ensure the parking_id is in the URL
if (!isset($_GET['id'])) {
    header("Location: available_parking.php");
    exit();
}

$parking_id = $_GET['id'];

// Force connection to stay localized on Malaysian Time
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
date_default_timezone_set('Asia/Kuala_Lumpur');

// 3. UPDATED CORE ENGINE: Real-time active booking validation
// Finds the slot metadata and joins tbl_booking based on the group slot string 
// to grab the exact plate and phone number active at THIS SPECIFIC MINUTE.
$query = "SELECT p.slot_number, 
                 b.plate_number, 
                 b.phone_number
          FROM tbl_parking_listing p
          LEFT JOIN tbl_parking_listing sub_p ON sub_p.slot_number = p.slot_number
          LEFT JOIN tbl_booking b ON b.parking_id = sub_p.parking_id 
               AND TRIM(UPPER(b.booking_status)) IN ('CONFIRMED', 'PAID', 'OCCUPIED')
               AND b.booking_date::date = CURRENT_DATE
               AND CURRENT_TIME::time BETWEEN b.start_time::time AND b.end_time::time
          WHERE p.parking_id = $1 
          LIMIT 1";

$result = pg_query_params($conn, $query, array($parking_id));
$data = pg_fetch_assoc($result);

// 4. VACANT CHECK AND REDIRECT
// If the slot layout element does not exist or has no active session running right now
if (!$data || empty($data['plate_number'])) {
    echo "<script>alert('This slot is currently vacant (no active booking at this time).'); window.history.back();</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>View Plate & Contact - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body style="text-align: center; font-family: 'Segoe UI', sans-serif; background: #f4f4f4; margin: 0;">

    <div class="nav-container" style="background: #fff; padding: 15px; border-bottom: 2px solid #FFD700; display: flex; justify-content: space-between; align-items: center;">
        <a href="javascript:history.back()" style="text-decoration: none; color: #333; font-weight: bold; font-size: 1.1rem;">← Back</a>
        <a href="logout.php" style="text-decoration: none; color: #dc3545; font-weight: bold;">Logout</a>
    </div>

    <div style="margin-top: 50px; padding: 20px;">
        <h2 style="color: #333;">Parking Slot: <span style="color: #d4bc44;"><?php echo htmlspecialchars($data['slot_number']); ?></span></h2>
        
        <div style="background: #333; color: #fff; padding: 40px; border-radius: 15px; display: inline-block; border: 4px solid #FFD700; box-shadow: 0 10px 20px rgba(0,0,0,0.2); max-width: 90%; box-sizing: border-box;">
            <p style="font-size: 0.9em; color: #FFD700; letter-spacing: 2px; margin-bottom: 10px; font-weight: bold;">CURRENT OCCUPANT</p>
            
            <h1 style="font-size: 3.5em; margin: 0; letter-spacing: 5px; font-family: monospace; text-transform: uppercase;">
                <?php echo htmlspecialchars($data['plate_number']); ?>
            </h1>
            
            <hr style="border: 0; border-top: 1px solid #555; margin: 25px 0;">
            
            <p style="font-size: 1em; margin: 0; color: #aaa;">DRIVER CONTACT:</p>
            
            <?php if (!empty($data['phone_number'])): ?>
                <a href="tel:<?php echo htmlspecialchars($data['phone_number']); ?>" style="font-size: 2rem; color: #FFD700; text-decoration: none; font-weight: bold; display: block; margin-top: 10px;">
                    <i class="fa-solid fa-phone-flip" style="font-size: 0.7em;"></i> <?php echo htmlspecialchars($data['phone_number']); ?>
                </a>
                <p style="font-size: 0.8em; margin-top: 10px; color: #888;">(Tap number to call driver)</p>
            <?php else: ?>
                <span style="font-size: 1.8rem; color: #888; font-weight: bold; display: block; margin-top: 10px; font-style: italic;">
                    No Active Driver Linked (-)
                </span>
                <p style="font-size: 0.8em; margin-top: 10px; color: #555;">(Slot is currently vacant or clear of active occupants)</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>