<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $parking_id = $_POST['parking_id'];
    $location = $_POST['location'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $query = "UPDATE tbl_parking_listing SET location = $1, price = $2, status = $3 WHERE parking_id = $4";
    $result = pg_query_params($conn, $query, array($location, $price, $status, $parking_id));

    if ($result) {
        echo "<script>alert('Parking updated successfully!'); window.location='lister_dashboard.php';</script>";
    } else {
        echo "Error updating record: " . pg_last_error($conn);
    }
}
?>