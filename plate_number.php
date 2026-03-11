<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lister') {
    header("Location: login_view.php");
    exit();
}

$parking_id = $_GET['id'];

$query = "SELECT p.slot_number, b.plate_number, b.phone_number 
          FROM tbl_parking_listing p
          JOIN tbl_booking b ON p.parking_id = b.parking_id 
          WHERE p.parking_id = $1 AND b.booking_status = 'Confirmed'";

$result = pg_query_params($conn, $query, array($parking_id));
$data = pg_fetch_assoc($result);

if (!$data) {
    echo "<script>alert('No active booking.'); window.location='lister_dashboard.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Plate & Contact</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<div class="nav-container">
    <a href="lister_dashboard.php" class="btn-back">← Dashboard</a>
    <a href="logout.php" class="btn-logout">Logout</a>
</div>
<body style="text-align: center; font-family: sans-serif; background: #f4f4f4;">
    <script>
        var check = confirm("View plate and contact for Slot <?php echo $data['slot_number']; ?>?");
        if (!check) { window.location.href = "lister_dashboard.php"; }
    </script>

    <div style="margin-top: 50px; padding: 20px;">
        <h2>Parking Slot: <?php echo $data['slot_number']; ?></h2>
        
        <div style="background: #333; color: #fff; padding: 40px; border-radius: 15px; display: inline-block; border: 4px solid #FFD700;">
            <h1 style="font-size: 3.5em; margin: 0;"><?php echo $data['plate_number']; ?></h1>
            <hr style="border: 1px solid #FFD700; margin: 20px 0;">
            <p style="font-size: 1.2em; margin: 0;">DRIVER CONTACT:</p>
            <a href="tel:<?php echo $data['phone_number']; ?>" style="font-size: 2em; color: #FFD700; text-decoration: none; font-weight: bold;">
                <?php echo $data['phone_number']; ?>
            </a>
            <p style="font-size: 0.8em; margin-top: 10px; color: #aaa;">(Tap number to call)</p>
        </div>

        <br><br>
    </div>
</body>
</html>