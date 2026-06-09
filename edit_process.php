<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get the data from your form names
    $parking_id = $_POST['parking_id'];
    $slot_number = $_POST['slot_number']; 
    $branch = $_POST['branch'];           
    $status = $_POST['status'];
    
    // Optional: Get current_plate if you added it to your form
    $current_plate = !empty($_POST['current_plate']) ? strtoupper($_POST['current_plate']) : NULL;

    // Enforce strict Malaysian Time parameters across the current database thread session
    pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");

    // 2. Update the Query with your actual table column names
    $query = "UPDATE tbl_parking_listing 
              SET slot_number = $1, 
                  branch = $2, 
                  status = $3, 
                  current_plate = $4 
              WHERE parking_id = $5";
    
    // 3. Execute the slot table update
    $result = pg_query_params($conn, $query, array($slot_number, $branch, $status, $current_plate, $parking_id));

    if ($result) {
        // =========================================================================
        // CRITICAL DATA SYNC PATCH: Automatically terminate matching driver logs
        // =========================================================================
        if ($status === 'Available') {
            // Force terminate any active reservation logs linked directly to this slot
            $sync_booking_query = "UPDATE tbl_booking 
                                   SET booking_status = 'Completed', end_time = CURRENT_TIME 
                                   WHERE parking_id = $1 AND booking_status IN ('Confirmed', 'Occupied')";
            pg_query_params($conn, $sync_booking_query, array($parking_id));
        }
        // =========================================================================

        echo "<script>alert('Parking updated successfully!'); window.location='admin_manage_slots.php';</script>";
    } else {
        echo "Error updating record: " . pg_last_error($conn);
    }
}
?>