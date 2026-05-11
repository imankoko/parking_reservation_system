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
<html>
<head>
    <title>Confirm Booking - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f8f9fa; margin: 0; }
        .nav-container { display:flex; justify-content:space-between; padding:15px; background:#fff; border-bottom:2px solid #FFD700; align-items:center; }
        .btn-sq { display:inline-flex; align-items:center; justify-content:center; width:100px; height:40px; text-decoration:none; font-weight:bold; border-radius:0; text-transform:uppercase; font-size:13px; color:white !important; }
        .btn-back-sq { background:#666; }
        .btn-logout-sq { background:#dc3545; }
        .container { padding:20px; max-width:500px; margin:auto; }
        .slot-info { background:#FFD700; padding:20px; border-radius:12px; margin-bottom:20px; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
        .booking-form { background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
        label { font-weight:bold; display:block; margin-bottom:5px; color:#333; }
        input, select { width:100%; padding:12px; margin-bottom:15px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box; font-size:16px; }
        .calc-box { background:#fdfdfd; padding:15px; border-radius:8px; border:1px dashed #FFD700; text-align:center; margin-bottom:20px; }
        .btn-confirm { width:100%; padding:15px; background:#333; color:white; border:none; font-weight:bold; border-radius:8px; font-size:18px; cursor:pointer; }
        .price-text { font-size:1.3em; color:#27ae60; font-weight:bold; }
        /* FIX: Alert shown if user tries to submit without selecting a start time first */
        .warn-msg { background:#fff3cd; color:#856404; border:1px solid #ffc107; padding:10px; border-radius:8px; margin-bottom:15px; font-size:0.9rem; display:none; }
    </style>
</head>
<body>

    <div class="nav-container">
        <a href="available_parking.php" class="btn-sq btn-back-sq">Back</a>
        <a href="logout.php" class="btn-sq btn-logout-sq">Logout</a>
    </div>

    <div class="container">
        <div class="slot-info">
            <h2 style="margin:0;">Slot: <?php echo htmlspecialchars($slot['slot_number']); ?></h2>
            <p style="margin:5px 0;"><?php echo htmlspecialchars($slot['location']); ?></p>
            <p style="margin:0;">Rate: <strong>RM <?php echo number_format($slot['price'],2); ?>/hour</strong></p>
        </div>

        <div class="booking-form">
            <!-- FIX: Changed action from payment_view.php — end_time must be calculated FIRST -->
            <form action="payment_view.php" method="POST" id="bookingForm" onsubmit="return validateForm()">
                <input type="hidden" name="parking_id"  value="<?php echo $slot['parking_id']; ?>">
                <input type="hidden" name="hourly_rate" id="hourlyRate" value="<?php echo $slot['price']; ?>">

                <label>Plate Number:</label>
                <input type="text" name="plate_number" placeholder="ABC 1234" required style="text-transform:uppercase;">

                <label>Phone Number:</label>
                <input type="tel" name="phone_number" placeholder="01XXXXXXXX" required>

                <div style="display:flex; gap:10px;">
                    <div style="flex:1;">
                        <label>Start Time:</label>
                        <input type="time" name="start_time" id="startTime" required onchange="calculateBooking()">
                    </div>
                    <div style="flex:1;">
                        <label>Duration:</label>
                        <select id="duration" name="duration" required onchange="calculateBooking()">
                            <option value="1">1 Hour</option>
                            <option value="2">2 Hours</option>
                            <option value="3">3 Hours</option>
                            <option value="4">4 Hours</option>
                        </select>
                    </div>
                </div>

                <!-- FIX: end_time hidden field — populated by calculateBooking() before form submits -->
                <input type="hidden" name="end_time" id="endTime">

                <div id="warnMsg" class="warn-msg">
                    ⚠️ Please select a <strong>Start Time</strong> first so the end time can be calculated.
                </div>

                <div class="calc-box">
                    <div>Ends at: <strong id="endTimeDisplay">--:--</strong></div>
                    <div style="margin-top:8px;">Total to Pay: <span class="price-text" id="totalPriceDisplay">RM 0.00</span></div>
                </div>

                <button type="submit" class="btn-confirm">Confirm Booking & Pay Now</button>
            </form>
        </div>
    </div>

<script>
    function calculateBooking() {
        const startTimeInput = document.getElementById('startTime').value;
        const duration       = parseInt(document.getElementById('duration').value);
        const rate           = parseFloat(document.getElementById('hourlyRate').value);

        if (startTimeInput) {
            const [hours, minutes] = startTimeInput.split(':');
            let date = new Date();
            date.setHours(parseInt(hours));
            date.setMinutes(parseInt(minutes));
            date.setSeconds(0);
            date.setHours(date.getHours() + duration);

            const endH = date.getHours().toString().padStart(2, '0');
            const endM = date.getMinutes().toString().padStart(2, '0');
            const finalEndTime = endH + ':' + endM;

            // Populate hidden field AND display
            document.getElementById('endTime').value        = finalEndTime;
            document.getElementById('endTimeDisplay').innerText = finalEndTime;
            document.getElementById('totalPriceDisplay').innerText = 'RM ' + (rate * duration).toFixed(2);

            // Hide warning if it was shown
            document.getElementById('warnMsg').style.display = 'none';
        }
    }

    // FIX: Block form submission if end_time was never calculated (user skipped start time)
    function validateForm() {
        const endTime = document.getElementById('endTime').value;
        if (!endTime || endTime.trim() === '') {
            document.getElementById('warnMsg').style.display = 'block';
            document.getElementById('startTime').focus();
            return false; // stops form submission
        }
        return true;
    }
</script>
</body>
</html>