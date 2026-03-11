<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $slot_number = $_POST['slot_number'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $status = 'Available'; // Default status 
    $lister_id = $_SESSION['user_id'];

    // 1. Check if this slot number already exists for this lister
    $check_query = "SELECT parking_id FROM tbl_parking_listing 
                    WHERE slot_number = $1 AND lister_id = $2";
    $check_result = pg_query_params($conn, $check_query, array($slot_number, $lister_id));

    if (pg_num_rows($check_result) > 0) {
        // 2. If it exists, REACTIVATE and UPDATE instead of inserting a duplicate
        $update_query = "UPDATE tbl_parking_listing 
                         SET location = $1, price = $2, status = $3 
                         WHERE slot_number = $4 AND lister_id = $5";
        $result = pg_query_params($conn, $update_query, array($location, $price, $status, $slot_number, $lister_id));
    } else {
        // 3. If it is a completely new slot, INSERT as usual
        $query = "INSERT INTO tbl_parking_listing (location, slot_number, price, status, lister_id) 
                  VALUES ($1, $2, $3, $4, $5)";
        $result = pg_query_params($conn, $query, array($location, $slot_number, $price, $status, $lister_id));
    }

    if ($result) {
        // Handles the navigation back to the dashboard 
        header("Location: lister_dashboard.php");
        exit(); 
    } else {
        echo "Error: " . pg_last_error($conn);
    }
}
?>