<?php
session_start();
include 'db_connect.php';

// If user isn't logged in as a driver, send them away
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    die("Please log into Parking Reservation System app to scan QR codes.");
}

$scanned_slot = $_GET['slot'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($scanned_slot)) {
    die("Invalid QR code scanned.");
}

// Check if this logged-in driver actually holds a live booking for this exact slot
$check_query = "SELECT b.booking_id FROM tbl_booking b
                JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
                WHERE b.user_id = $1 
                AND p.slot_number = $2
                AND b.booking_status = 'Confirmed'
                LIMIT 1";
$res = pg_query_params($conn, $check_query, array($user_id, $scanned_slot));

if (pg_num_rows($res) > 0) {
    // MATCH FOUND: Change the slot status to 'Occupied' because they have officially checked in!
    pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Occupied' WHERE slot_number = $1", array($scanned_slot));
    
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h1 style='color:#2ecc71;'>✅ QR Check-in Successful!</h1>
            <p>Welcome to slot <strong>$scanned_slot</strong>. Your session has officially started.</p>
            <a href='driver_dashboard.php' style='padding:10px 20px; background:#333; color:white; text-decoration:none; border-radius:5px;'>Go to Dashboard</a>
          </div>";
} else {
    // FAIL: They are trying to park in a space they didn't reserve
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'>
            <h1 style='color:#e74c3c;'>❌ Access Denied!</h1>
            <p>You do not have an active reservation for slot <strong>$scanned_slot</strong>.</p>
            <a href='driver_dashboard.php' style='padding:10px 20px; background:#333; color:white; text-decoration:none; border-radius:5px;'>Go back</a>
          </div>";
}
?>