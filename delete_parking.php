<?php
session_start();
include 'db_connect.php';

// Security: Only Admin can delete/deactivate
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $parking_id = $_GET['id'];

    $query = "UPDATE tbl_parking_listing SET status = 'Inactive', current_plate = NULL WHERE parking_id = $1";
    $result = pg_query_params($conn, $query, array($parking_id));

    if ($result) {
        header("Location: admin_manage_slots.php?msg=success");
    } else {
        echo "Error: " . pg_last_error($conn);
    }
}
exit();
?>