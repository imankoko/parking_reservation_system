<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $parking_id = $_POST['parking_id'];
    $plate_number = strtoupper($_POST['plate_number']); // Standardize to uppercase
    $phone_number = $_POST['phone_number']; 
    $booking_date = date('Y-m-d');
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $status = 'Confirmed';

    // Start transaction session safely
    pg_query($conn, "BEGIN");

    // 1. Insert into tbl_booking 
    $query_booking = "INSERT INTO tbl_booking (booking_date, start_time, end_time, booking_status, user_id, parking_id, plate_number, phone_number) 
                      VALUES ($1, $2, $3, $4, $5, $6, $7, $8) RETURNING booking_id";
    
    $res1 = pg_query_params($conn, $query_booking, array($booking_date, $start_time, $end_time, $status, $user_id, $parking_id, $plate_number, $phone_number));

    // 2. Update the listing status directly
    $query_update = "UPDATE tbl_parking_listing SET status = 'Booked', current_plate = $1 WHERE parking_id = $2";
    $res2 = pg_query_params($conn, $query_update, array($plate_number, $parking_id));

    if ($res1 && $res2) {
        $booking_row = pg_fetch_assoc($res1);
        $new_booking_id = $booking_row['booking_id'];
        
        // Save for hardware separately so a hardware log error never kills your booking tables
        $query_display = "INSERT INTO tbl_plate_display (plate_number, parking_id, display_status) VALUES ($1, $2, 'ACTIVE')";
        @pg_query_params($conn, $query_display, array($plate_number, $parking_id));

        pg_query($conn, "COMMIT");

        header("Location: navigation_view.php?booking_id=" . $new_booking_id);
        exit(); 
    } else {
        pg_query($conn, "ROLLBACK");
        echo "<script>alert('Booking failed.'); window.location='available_parking.php';</script>";
        exit();
    }
}
?>