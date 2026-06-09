<?php
session_start();
include 'db_connect.php';

// 1. Security Check: Only logged-in Drivers
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    header("Location: driver_dashboard.php");
    exit();
}

$booking_id = intval($_GET['booking_id']);
$user_id    = $_SESSION['user_id']; // SECURITY FIX: capture session user

pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");

// SECURITY FIX: Added AND b.user_id = $2 so a driver can only report THEIR OWN booking.
// Without this, any driver could pass any booking_id in the URL and hijack someone else's slot.
$b_query = "SELECT parking_id FROM tbl_booking WHERE booking_id = $1 AND user_id = $2";
$b_res   = pg_query_params($conn, $b_query, array($booking_id, $user_id));
$b_data  = pg_fetch_assoc($b_res);

if ($b_data) {
    $old_parking_id = intval($b_data['parking_id']);

    // Find a fresh backup slot that is currently completely 'Available'
    $backup_query = "SELECT parking_id, slot_number FROM tbl_parking_listing WHERE status = 'Available' LIMIT 1";
    $backup_res   = pg_query($conn, $backup_query);
    $backup_data  = pg_fetch_assoc($backup_res);

    if ($backup_data) {
        $new_parking_id  = intval($backup_data['parking_id']);
        $new_slot_number = $backup_data['slot_number'];

        pg_query($conn, "BEGIN");

        // A. Swap booking to the new slot
        $update_booking  = pg_query_params($conn, "UPDATE tbl_booking SET parking_id = $1 WHERE booking_id = $2", array($new_parking_id, $booking_id));

        // B. Mark new slot as Booked
        $update_new_slot = pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Booked' WHERE parking_id = $1", array($new_parking_id));

        // C. Mark old blocked slot as Penalty and clear its plate
        $update_old_slot = pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Penalty', current_plate = NULL WHERE parking_id = $1", array($old_parking_id));

        if ($update_booking && $update_new_slot && $update_old_slot) {
            pg_query($conn, "COMMIT");
            $_SESSION['terminate_msg'] = "Slot reported! The system has reassigned you to Slot " . $new_slot_number . ".";
        } else {
            pg_query($conn, "ROLLBACK");
            $_SESSION['terminate_msg'] = "Database error during rerouting. Please try again.";
        }
    } else {
        $_SESSION['terminate_msg'] = "Error: No other available spaces found. Please contact mall management.";
    }
} else {
    // Either booking doesn't exist OR it belongs to a different user — both treated the same
    $_SESSION['terminate_msg'] = "Error: Booking not found or access denied.";
}

header("Location: driver_dashboard.php");
exit();