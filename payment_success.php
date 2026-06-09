<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login.php");
    exit();
}

$new_booking_id = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id      = $_SESSION['user_id'];
    $parking_id   = $_POST['parking_id'];
    $plate_number = strtoupper($_POST['plate_number']);
    $phone_number = $_POST['phone_number'];
    $start_time   = $_POST['start_time'];
    $end_time     = $_POST['end_time'];
    $booking_date = date('Y-m-d');

    // Force PostgreSQL time zone configuration alignment for this tracking transaction
    pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");

    // Insert booking safely matching dashboard check requirements
    $query = "INSERT INTO tbl_booking (user_id, parking_id, plate_number, phone_number, start_time, end_time, booking_date, booking_status) 
              VALUES ($1, $2, $3, $4, $5, $6, $7, 'Confirmed') RETURNING booking_id";
    $params = array($user_id, $parking_id, $plate_number, $phone_number, $start_time, $end_time, $booking_date);
    $result = pg_query_params($conn, $query, $params);

    if ($result) {
        $row = pg_fetch_assoc($result);
        $new_booking_id = $row['booking_id'];

        // Synchronize state markers perfectly for active dashboard listeners
        pg_query_params($conn,
            "UPDATE tbl_parking_listing SET status = 'Booked', current_plate = $1 WHERE parking_id = $2",
            array($plate_number, $parking_id)
        );
    } else {
        die("Database Sync Error Trace: " . pg_last_error($conn));
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Success - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #fff; text-align: center; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .success-card { padding: 30px; max-width: 400px; width: 90%; }
        .btn-proceed   { display: block; width: 100%; padding: 15px; background: #d4bc44; color: black; text-decoration: none; font-weight: bold; border-radius: 8px; margin-top: 15px; box-sizing: border-box; }
        .btn-secondary { display: block; width: 100%; padding: 15px; background: #333; color: white; text-decoration: none; font-weight: bold; border-radius: 8px; margin-top: 10px; box-sizing: border-box; }
    </style>
</head>
<body>
    <div class="success-card">
        <i class="fa-solid fa-circle-check" style="font-size:80px; color:#2ecc71;"></i>
        <h2>Payment Successful!</h2>
        <p>Booking ID: <strong>MYSPOT-B<?php echo htmlspecialchars($new_booking_id); ?></strong></p>
        <a href="navigation_view.php?booking_id=<?php echo $new_booking_id; ?>" class="btn-proceed">Proceed to Navigation</a>
        <a href="driver_dashboard.php" class="btn-secondary">Go to Dashboard</a>
    </div>
</body>
</html>