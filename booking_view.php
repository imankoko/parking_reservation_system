<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

if (isset($_GET['id'])) {
    $parking_id = $_GET['id'];
} else {
    header("Location: available_parking.php");
    exit();
}

$query  = "SELECT * FROM tbl_parking_listing WHERE parking_id = $1";
$result = pg_query_params($conn, $query, array($parking_id));
$slot   = pg_fetch_assoc($result);

if (!$slot) die("Parking slot not found.");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Confirm Booking - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Aligned with dashboard design found in add_parking_view.php */
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8f9fa; margin: 0; padding-bottom: 80px; }
        
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; display: flex; align-items: center; gap: 15px;
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        .container { padding: 20px; max-width: 500px; margin: auto; }
        
        .slot-info { 
            background: #fff; padding: 20px; border-radius: 15px; 
            margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #d4bc44;
        }

        .booking-form { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        label { font-weight: bold; display: block; margin-bottom: 8px; color: #444; font-size: 0.9rem; }
        input, select { width: 100%; padding: 12px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 10px; box-sizing: border-box; background: #fdfdfd; }
        
        .calc-box { background: #fffdf0; padding: 15px; border-radius: 10px; border: 1px dashed #d4bc44; text-align: center; margin-bottom: 20px; }
        
        .submit-btn { background: #333; color: #d4bc44; padding: 15px; border: none; border-radius: 10px; width: 100%; font-weight: bold; cursor: pointer; text-transform: uppercase; }
        
        .warn-msg { background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 8px; margin-bottom: 15px; display: none; text-align: center; font-size: 0.85rem; }
    </style>
</head>
<body>

    <div class="header">
        <img src="logo.png" alt="Logo" class="logo-img">
        <div class="header-text">
            <h2>Confirm Booking</h2>
            <p style="margin:0; font-size: 0.8em; color: #666;">Complete reservation details</p>
        </div>
    </div>

    <div class="container">
        <div class="slot-info">
            <h2 style="margin:0;">Slot: <?php echo htmlspecialchars($slot['slot_number']); ?></h2>
            <p style="margin:5px 0; color:#666;"><?php echo htmlspecialchars($slot['location']); ?></p>
            <p style="margin:0;">Rate: <strong>RM <?php echo number_format($slot['price'], 2); ?>/hour</strong></p>
        </div>

        <div class="booking-form">
            <form action="payment_view.php" method="POST" id="bookingForm" onsubmit="return validateForm()">
                <input type="hidden" name="parking_id" value="<?php echo $slot['parking_id']; ?>">
                <input type="hidden" name="hourly_rate" id="hourlyRate" value="<?php echo $slot['price']; ?>">

                <label>Plate Number</label>
                <input type="text" name="plate_number" placeholder="e.g. ABC 1234" required style="text-transform:uppercase;">

                <label>Phone Number</label>
                <input type="tel" name="phone_number" placeholder="01XXXXXXXX" required>

                <div style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <label>Start Time</label>
                        <input type="time" name="start_time" id="startTime" required onchange="calculateBooking()">
                    </div>
                    <div style="flex:1;">
                        <label>Duration</label>
                        <select id="duration" name="duration" required onchange="calculateBooking()">
                            <option value="1">1 Hour</option>
                            <option value="2">2 Hours</option>
                            <option value="3">3 Hours</option>
                            <option value="4">4 Hours</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="end_time" id="endTime">

                <div id="warnMsg" class="warn-msg">
                    ⚠️ Please select a Start Time first.
                </div>

                <div class="calc-box">
                    <div>Ends at: <strong id="endTimeDisplay">--:--</strong></div>
                    <div style="margin-top:8px;">Total to Pay: <strong id="totalPriceDisplay" style="color:#d4bc44;">RM 0.00</strong></div>
                </div>

                <button type="submit" class="submit-btn">Confirm Booking</button>
            </form>
        </div>
    </div>

<script>
    function calculateBooking() {
        const startTimeInput = document.getElementById('startTime').value;
        const duration = parseInt(document.getElementById('duration').value);
        const rate = parseFloat(document.getElementById('hourlyRate').value);

        if (startTimeInput) {
            const [hours, minutes] = startTimeInput.split(':');
            let date = new Date();
            date.setHours(parseInt(hours));
            date.setMinutes(parseInt(minutes));
            date.setHours(date.getHours() + duration);

            const endH = date.getHours().toString().padStart(2, '0');
            const endM = date.getMinutes().toString().padStart(2, '0');
            const finalEndTime = endH + ':' + endM;

            document.getElementById('endTime').value = finalEndTime;
            document.getElementById('endTimeDisplay').innerText = finalEndTime;
            document.getElementById('totalPriceDisplay').innerText = 'RM ' + (rate * duration).toFixed(2);
            document.getElementById('warnMsg').style.display = 'none';
        }
    }

    function validateForm() {
        const endTime = document.getElementById('endTime').value;
        if (!endTime || endTime.trim() === '') {
            document.getElementById('warnMsg').style.display = 'block';
            return false;
        }
        return true;
    }
</script>
</body>
</html>