<?php
include 'db_connect.php';

$query = "UPDATE tbl_parking_listing SET status = 'Available' 
          WHERE parking_id IN (
              SELECT parking_id FROM tbl_booking 
              WHERE end_time < CURRENT_TIME AND booking_date = CURRENT_DATE 
              AND booking_status = 'Confirmed'
          )";

$result = pg_query($conn, $query);

// Update booking status to 'Completed'
$query_booking = "UPDATE tbl_booking SET booking_status = 'Completed' 
                  WHERE end_time < CURRENT_TIME AND booking_date = CURRENT_DATE";
pg_query($conn, $query_booking);
?>