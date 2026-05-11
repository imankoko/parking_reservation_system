<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Get the data from your form names
    $parking_id = $_POST['parking_id'];
    $slot_number = $_POST['slot_number']; // Changed from $price
    $branch = $_POST['branch'];           // Changed from $location
    $status = $_POST['status'];
    
    // Optional: Get current_plate if you added it to your form
    $current_plate = !empty($_POST['current_plate']) ? strtoupper($_POST['current_plate']) : NULL;

    // 2. Update the Query with your actual table column names
    // Columns: slot_number, branch, status, current_plate
    $query = "UPDATE tbl_parking_listing 
              SET slot_number = $1, 
                  branch = $2, 
                  status = $3, 
                  current_plate = $4 
              WHERE parking_id = $5";
    
    // 3. Execute the query
    $result = pg_query_params($conn, $query, array($slot_number, $branch, $status, $current_plate, $parking_id));

    if ($result) {
        echo "<script>alert('Parking updated successfully!'); window.location='admin_dashboard.php';</script>";
    } else {
        echo "Error updating record: " . pg_last_error($conn);
    }
}
?>