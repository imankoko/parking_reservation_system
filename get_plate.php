<?php
/**
 * get_plate.php
 * This file provides the current plate number or countdown tracking status 
 * of a specific slot to the ESP32 in clean PLAIN TEXT format.
 */

// 1. START OUTPUT BUFFERING TO PREVENT ob_clean FATAL CRASHES ON LINUX SERVERS
ob_start();

// Include database connection
include 'db_connect.php';

// Prevent any accidental whitespace or HTML from breaking the text reading
ob_clean(); 
header("Content-Type: text/plain");

// Synchronize all application runtime environments cleanly onto Malaysian Time
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
date_default_timezone_set('Asia/Kuala_Lumpur');

// We target Slot A01 specifically for your hardware unit
$slot_number = 'A01';

/**
 * FETCH ENGINE: Extract dates, start/end times, and the absolute dynamic differences
 * directly from PostgreSQL using the exact type casting logic we verified.
 */
$booking_query = "SELECT b.booking_id, b.plate_number, b.booking_status,
                  EXTRACT(EPOCH FROM (b.start_time - CURRENT_TIME::time)) AS seconds_until_start,
                  EXTRACT(EPOCH FROM (b.end_time - CURRENT_TIME::time)) AS seconds_remaining
                  FROM tbl_booking b
                  JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
                  WHERE TRIM(UPPER(p.slot_number)) = TRIM(UPPER($1))
                  AND b.booking_status IN ('Confirmed', 'Occupied')
                  ORDER BY b.booking_id DESC LIMIT 1";

$result = pg_query_params($conn, $booking_query, array($slot_number));

if ($result && $booking = pg_fetch_assoc($result)) {
    $seconds_until_start = intval($booking['seconds_until_start']);
    $seconds_remaining = intval($booking['seconds_remaining']);
    $plate_number = strtoupper(trim($booking['plate_number']));

    if ($seconds_until_start > 0) {
        // STATE A: Future advanced reservation mode (Waiting)
        // Output format: "In MM:SS [PLATE]"
        $m = floor($seconds_until_start / 60);
        $s = $seconds_until_start % 60;
        echo sprintf("In %02d:%02d [%s]", $m, $s, $plate_number);
    } else if ($seconds_remaining > 0) {
        // STATE B: Active reservation window running live
        // Output format: standard license plate number to display
        echo $plate_number;
    } else {
        // STATE C: Natural expiration block fallback
        echo "VACANT";
    }
} else {
    // STATE D: No booking exists
    echo "VACANT";
}

// Flush and send output clean
ob_end_flush();
exit();
?>