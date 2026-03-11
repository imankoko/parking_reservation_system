<?php
session_start();
include 'db_connect.php';

// 1. Security: Only Admin can delete reviews
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// 2. Check if an ID was actually sent
if (isset($_GET['id'])) {
    $review_id = $_GET['id'];

    // 3. Execute the DELETE query
    // Make sure the table name matches (tbl_review or tbl_reviews)
    $query = "DELETE FROM tbl_reviews WHERE review_id = $1";
    $result = pg_query_params($conn, $query, array($review_id));

    if ($result) {
        // Deletion successful: Redirect back with success message
        header("Location: admin_reviews.php?msg=deleted");
    } else {
        // Deletion failed: Show database error
        echo "Error deleting record: " . pg_last_error($conn);
    }
} else {
    // No ID found in URL: Send back to reviews page
    header("Location: admin_reviews.php");
}
exit();
?>