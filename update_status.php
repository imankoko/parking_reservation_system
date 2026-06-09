<?php
/**
 * update_status.php
 * 
 * Resets expired bookings to 'Available' automatically.
 * 
 * HOW TO USE (since XAMPP has no cron job):
 * This file is included at the bottom of db_connect.php so it silently
 * runs on every page load. A session flag ensures it only fires ONCE
 * per minute per user session — not on every single request.
 */

// RATE LIMITER: Only run the cleanup once per minute per session
// This prevents hammering the database on every page load
$now = time();
if (!isset($_SESSION['last_status_update']) || ($now - $_SESSION['last_status_update']) > 60) {

    // 1. Free up slots whose booking end time has passed
    $query_free = "UPDATE tbl_parking_listing SET status = 'Available', current_plate = NULL
                   WHERE parking_id IN (
                       SELECT b.parking_id FROM tbl_booking b
                       WHERE b.booking_date = CURRENT_DATE
                       AND b.booking_status = 'Confirmed'
                       AND b.end_time < CURRENT_TIME
                   )";
    pg_query($conn, $query_free);

    // 2. Mark those bookings as Completed
    $query_complete = "UPDATE tbl_booking SET booking_status = 'Completed'
                       WHERE booking_date = CURRENT_DATE
                       AND booking_status = 'Confirmed'
                       AND end_time < CURRENT_TIME";
    pg_query($conn, $query_complete);

    // 3. Also handle 'Occupied' slots whose time has passed (driver checked in but didn't end manually)
    $query_free_occupied = "UPDATE tbl_parking_listing SET status = 'Available', current_plate = NULL
                            WHERE parking_id IN (
                                SELECT b.parking_id FROM tbl_booking b
                                WHERE b.booking_date = CURRENT_DATE
                                AND b.booking_status IN ('Confirmed', 'Occupied')
                                AND b.end_time < CURRENT_TIME
                            )";
    pg_query($conn, $query_free_occupied);

    // 4. Also complete Occupied bookings
    $query_complete_occupied = "UPDATE tbl_booking SET booking_status = 'Completed'
                                WHERE booking_date = CURRENT_DATE
                                AND booking_status IN ('Confirmed', 'Occupied')
                                AND end_time < CURRENT_TIME";
    pg_query($conn, $query_complete_occupied);

    // Update the session timestamp so this won't re-run for another 60 seconds
    $_SESSION['last_status_update'] = $now;
}
?>