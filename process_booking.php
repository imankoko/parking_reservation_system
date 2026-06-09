<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $parking_id   = $_POST['parking_id'];
    $driver_id    = $_SESSION['user_id'];
    $plate_number = $_POST['plate_number'];
    $start_time   = $_POST['start_time'];
    $end_time     = $_POST['end_time'];

    // Enforce strict Malaysian runtime synchronization flags at the database session tier
    pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");

    // 1. Mark the parking slot as 'Booked'
    $update_query = "UPDATE tbl_parking_listing SET status = 'Booked' WHERE parking_id = $1";
    pg_query_params($conn, $update_query, array($parking_id));

    // 2. Patched reservation query ensuring exact Malaysian date tracking parameters across midnight rollovers
    $booking_query = "INSERT INTO tbl_booking (parking_id, user_id, plate_number, booking_status, booking_date, start_time, end_time) 
                      VALUES ($1, $2, $3, 'Confirmed', (CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Kuala_Lumpur')::date, $4, $5) 
                      RETURNING booking_id";
                      
    $result = pg_query_params($conn, $booking_query, array($parking_id, $driver_id, $plate_number, $start_time, $end_time));

    if ($result) {
        // Safe extraction layer pulling the returned token directly from the object row array safely
        $row = pg_fetch_assoc($result);
        $booking_id = $row['booking_id']; 

        // Send the ID to the success page cleanly
        header("Location: payment_success.php?booking_id=" . $booking_id);
        exit();
    } else {
        echo "Query Error: " . pg_last_error($conn);
    }
}
?>