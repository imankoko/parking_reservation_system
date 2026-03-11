<?php
session_start();
include 'db_connect.php';

// Capture data coming from booking_view.php
$parking_id   = $_POST['parking_id'] ?? null;
$hourly_rate  = $_POST['hourly_rate'] ?? 0;
$duration     = $_POST['duration'] ?? 1; // Default to 1 hour if empty
$plate_number = $_POST['plate_number'] ?? '';
$phone_number = $_POST['phone_number'] ?? '';
$start_time   = $_POST['start_time'] ?? '';
$end_time     = $_POST['end_time'] ?? '';

// FIX: Define the $total_price variable here
$total_price = (float)$hourly_rate * (int)$duration; 
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Gateway - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f8f9fa; margin: 0; padding: 20px; }
        .payment-container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .summary-box { background: #fffdf0; border: 1px solid #FFD700; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .method-option { 
            display: flex; align-items: center; gap: 15px; padding: 15px; 
            border: 2px solid #eee; border-radius: 10px; margin-bottom: 10px; cursor: pointer; 
        }
        .method-option:hover { border-color: #FFD700; }
        .btn-pay { width: 100%; background: #333; color: white; padding: 15px; border: none; border-radius: 10px; font-weight: bold; font-size: 1.1rem; cursor: pointer; }
        .total-large { font-size: 1.8rem; font-weight: bold; color: #27ae60; text-align: center; margin: 10px 0; }
    </style>
</head>
<body>

<div class="payment-container">
    <h3 style="text-align:center;">Payment Gateway</h3>
    
    <div class="summary-box">
        <p style="margin:0; font-size: 0.9rem; color: #666;">Total Amount</p>
        <div class="total-large">RM <?php echo number_format($total_price, 2); ?></div>
        <p style="margin:5px 0; text-align:center;">Slot: <strong><?php echo $parking_id; ?></strong> | Plate: <strong><?php echo htmlspecialchars($plate_number); ?></strong></p>
    </div>

    <label style="font-weight:bold; margin-bottom:10px; display:block;">Select Payment Method:</label>
    
    <div class="method-option">
        <i class="fa-solid fa-wallet" style="color:#d4bc44;"></i>
        <span>MySpot E-Wallet</span>
    </div>
    
    <div class="method-option">
        <i class="fa-solid fa-building-columns" style="color:#003d7b;"></i>
        <span>FPX Online Banking</span>
    </div>

    <form action="payment_success.php" method="POST">
    <input type="hidden" name="parking_id" value="<?php echo $parking_id; ?>">
    <input type="hidden" name="plate_number" value="<?php echo $plate_number; ?>">
    <input type="hidden" name="phone_number" value="<?php echo $phone_number; ?>">
    <input type="hidden" name="start_time" value="<?php echo $start_time; ?>">
    <input type="hidden" name="end_time" value="<?php echo $end_time; ?>">
    <input type="hidden" name="total_price" value="<?php echo $total_price; ?>">

    <button type="submit" class="btn-pay">Pay & Complete Booking</button>
</form>
</div>

</body>
</html>