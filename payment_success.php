<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login.php");
    exit();
}

$new_booking_id = null;

// Handle the Data Insertion from booking_view.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $parking_id = $_POST['parking_id'];
    $plate_number = strtoupper($_POST['plate_number']);
    $phone_number = $_POST['phone_number'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $booking_date = date('Y-m-d');

    // 1. Insert Booking and return the new ID
    $query = "INSERT INTO tbl_booking (user_id, parking_id, plate_number, phone_number, start_time, end_time, booking_date, booking_status) 
              VALUES ($1, $2, $3, $4, $5, $6, $7, 'Confirmed') RETURNING booking_id";
    
    $result = pg_query_params($conn, $query, array($user_id, $parking_id, $plate_number, $phone_number, $start_time, $end_time, $booking_date));
    
    if ($result) {
        $row = pg_fetch_assoc($result);
        $new_booking_id = $row['booking_id'];

        // 2. Update the slot status to 'Booked' so no one else can take it
        pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Booked' WHERE parking_id = $1", array($parking_id));
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Payment Successful - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #fff; text-align: center; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .success-card { padding: 30px; max-width: 400px; width: 90%; }
        .check-icon { font-size: 80px; color: #2ecc71; margin-bottom: 20px; }
        .btn-proceed { display: block; width: 100%; padding: 15px; background: #333; color: white; text-decoration: none; font-weight: bold; border-radius: 8px; margin-top: 30px; font-size: 18px; }
        h2 { color: #333; margin-bottom: 10px; }
        p { color: #666; }
    </style>
</head>
<body>

    <div class="success-card">
        <div class="check-icon">✔</div>
        <h2>Payment Successful!</h2>
        <p>Your parking spot has been reserved. You can now proceed to your slot at The Summit Batu Pahat.</p>
        
        <div style="background: #f9f9f9; padding: 15px; border-radius: 10px; margin-top: 20px; text-align: left; border: 1px solid #eee;">
            <small style="color: #999; display: block;">Transaction ID</small>
            <strong style="font-family: monospace;">MYSPOT-<?php echo $new_booking_id ? "B".$new_booking_id : strtoupper(uniqid()); ?></strong>
        </div>

        <?php if ($new_booking_id): ?>
            <a href="navigation_view.php?booking_id=<?php echo $new_booking_id; ?>" class="btn-proceed">Proceed to Navigation</a>
        <?php else: ?>
            <p style="color:red;">Error processing booking. Please contact admin.</p>
            <a href="driver_dashboard.php" class="btn-proceed" style="background:#666;">Back to Dashboard</a>
        <?php endif; ?>
    </div>

</body>
</html>