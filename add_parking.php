<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $slot_number = $_POST['slot_number'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $branch = $_POST['branch']; 
    $x = $_POST['x_coord']; // New coordinate data[cite: 6]
    $y = $_POST['y_coord']; // New coordinate data[cite: 6]
    $lister_id = $_SESSION['user_id'];
    $status = 'Available';

    // Update query to include x_coord and y_coord[cite: 2]
    $query = "INSERT INTO tbl_parking_listing (location, slot_number, price, status, lister_id, branch, current_plate, x_coord, y_coord) 
              VALUES ($1, $2, $3, $4, $5, $6, NULL, $7, $8)";
    
    $result = pg_query_params($conn, $query, array($location, $slot_number, $price, $status, $lister_id, $branch, $x, $y));

    if ($result) {
        // ADDED: store message in session and redirect back to the view page
        $_SESSION['slot_created_msg'] = "Slot <strong>" . htmlspecialchars($slot_number) . "</strong> created successfully in <strong>" . htmlspecialchars($location) . "</strong>!";
        header("Location: add_parking_view.php?branch=" . urlencode($branch) . "&zone=" . urlencode($location));
    } else {
        echo "Error: " . pg_last_error($conn);
    }
}
?>