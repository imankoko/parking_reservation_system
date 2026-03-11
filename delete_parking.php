<?php
session_start();
include 'db_connect.php';

$parking_id = $_GET['id'];
$lister_id = $_SESSION['user_id'];

// Change DELETE to UPDATE. This is the "Soft Delete" fix.
$query = "UPDATE tbl_parking_listing SET status = 'Inactive' 
          WHERE parking_id = $1 AND lister_id = $2";

$result = pg_query_params($conn, $query, array($parking_id, $lister_id));

if ($result) {
    header("Location: lister_listing_view.php?msg=Deleted Successfully");
    exit();
} else {
    echo "Error: " . pg_last_error($conn);
}
?>