<?php
include 'db_connect.php';
date_default_timezone_set('Asia/Kuala_Lumpur');

$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// Only fetch the plate if the slot is ACTIVE and the time is CURRENT
$query = "SELECT b.plate_number 
          FROM tbl_booking b
          JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
          WHERE p.slot_number = 'A01' 
          AND p.status != 'Inactive'
          AND b.booking_status = 'Confirmed'
          AND b.booking_date = $1
          AND $2 BETWEEN b.start_time AND b.end_time
          LIMIT 1";

$result = pg_query_params($conn, $query, array($current_date, $current_time));

if ($row = pg_fetch_assoc($result)) {
    echo $row['plate_number'];
} else {
    echo "VACANT"; // This clears your LCD
}
?>