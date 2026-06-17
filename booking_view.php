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

// Force connection to stay localized on Malaysian Time
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
date_default_timezone_set('Asia/Kuala_Lumpur');

$user_id = $_SESSION['user_id'];

// --- ENFORCE 1 ACTIVE BOOKING LIMIT PER DRIVER USER ---
$limit_query = "SELECT *, TO_CHAR(end_time, 'HH:MI AM') as friendly_end 
                FROM tbl_booking 
                WHERE user_id = $1 
                AND booking_status IN ('Confirmed', 'Paid') 
                AND booking_date = CURRENT_DATE 
                AND end_time::time > CURRENT_TIME::time
                LIMIT 1";
$limit_res = pg_query_params($conn, $limit_query, array($user_id));
$active_booking = pg_fetch_assoc($limit_res);

// Fetch parking slot metadata details
$query  = "SELECT * FROM tbl_parking_listing WHERE parking_id = $1";
$result = pg_query_params($conn, $query, array($parking_id));
$slot   = pg_fetch_assoc($result);

if (!$slot) die("Parking slot not found.");

// --- EXTRACT ACTIVE CONFIRMED BOOKINGS BY SLOT NUMBER FOR FORMS TIMELINE ---
$booking_check_query = "SELECT TO_CHAR(b.start_time, 'HH24:MI') as start_hhmm, 
                               TO_CHAR(b.end_time, 'HH24:MI') as end_hhmm 
                        FROM tbl_booking b
                        JOIN tbl_parking_listing pl ON b.parking_id = pl.parking_id
                        WHERE pl.slot_number = $1 
                        AND b.booking_status = 'Confirmed' 
                        AND b.booking_date = CURRENT_DATE";
$booking_res = pg_query_params($conn, $booking_check_query, array($slot['slot_number']));

$existing_bookings = [];
while ($b_row = pg_fetch_assoc($booking_res)) {
    $existing_bookings[] = [
        'start' => $b_row['start_hhmm'],
        'end' => $b_row['end_hhmm']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Confirm Booking - MySpot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        
        .warn-msg { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 15px; display: none; text-align: left; font-size: 0.85rem; border-left: 4px solid #ef4444; }
        
        .timeline-list { background: #f1f5f9; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; color: #334155; }
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
            
            <?php if ($active_booking): ?>
                <div style="background: #fff1f2; border: 2px solid #f43f5e; padding: 20px; border-radius: 12px; text-align: center; color: #9f1239;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 2.5rem; margin-bottom: 10px; color: #e11d48;"></i>
                    <h3 style="margin: 0 0 8px 0;">Booking Limit Reached</h3>
                    <p style="margin: 0; font-size: 0.9rem; line-height: 1.5;">
                        You currently have an active reservation today. To maintain fair access, you are limited to <strong>1 active booking at a time</strong>.
                    </p>
                    <div style="background: #ffe4e6; padding: 10px; border-radius: 8px; margin-top: 15px; font-size: 0.85rem; font-weight: bold;">
                        Active Session Ends At: <?php echo htmlspecialchars($active_booking['friendly_end']); ?>
                    </div>
                    <button type="button" class="submit-btn" style="margin-top: 20px; background: #94a3b8; color: #fff; cursor: not-allowed;" onclick="location.href='available_parking.php'">Back to Dashboard</button>
                </div>

            <?php else: ?>
                <?php if (!empty($existing_bookings)): ?>
                    <label><i class="fa-solid fa-calendar-day"></i> Reserved Slots Today:</label>
                    <div class="timeline-list">
                        <?php foreach ($existing_bookings as $b): ?>
                            • <strong><?php echo date("g:i A", strtotime($b['start'])); ?></strong> until <strong><?php echo date("g:i A", strtotime($b['end'])); ?></strong><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div id="warnMsg" class="warn-msg">
                    ⚠️ Please select a Start Time first.
                </div>

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

                    <div class="calc-box">
                        <div>Ends at: <strong id="endTimeDisplay">--:--</strong></div>
                        <div style="margin-top:8px;">Total to Pay: <strong id="totalPriceDisplay" style="color:#d4bc44;">RM 0.00</strong></div>
                    </div>

                    <button type="submit" class="submit-btn">Confirm Booking</button>
                </form>
            <?php endif; ?>
            
        </div>
    </div>

<script>
    const existingBookings = <?php echo json_encode($existing_bookings); ?>;

    function calculateBooking() {
        const startTimeInput = document.getElementById('startTime').value;
        const duration = parseInt(document.getElementById('duration').value);
        const rate = parseFloat(document.getElementById('hourlyRate').value);
        const warnMsg = document.getElementById('warnMsg');

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
            warnMsg.style.display = 'none';
            
            checkOverlap(startTimeInput, finalEndTime);
        }
    }

    function checkOverlap(startTime, endTime) {
        const warnMsg = document.getElementById('warnMsg');
        const now = new Date();
        const currentHHMM = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
        
        if (startTime < currentHHMM) {
            warnMsg.innerHTML = `❌ <strong>Time Error:</strong> You cannot reserve a slot before the current time (<strong>${currentHHMM}</strong>).`;
            warnMsg.style.display = 'block';
            return false;
        }

        for (let booking of existingBookings) {
            if (startTime < booking.end && endTime > booking.start) {
                warnMsg.innerHTML = `❌ <strong>Collision Detected:</strong> This slot is already reserved from <strong>${booking.start}</strong> to <strong>${booking.end}</strong>. Please select a start time after ${booking.end}.`;
                warnMsg.style.display = 'block';
                return false;
            }
        }
        warnMsg.style.display = 'none';
        return true;
    }

    function validateForm() {
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        const warnMsg = document.getElementById('warnMsg');

        if (!endTime || endTime.trim() === '') {
            warnMsg.innerText = '⚠️ Please select a Start Time first.';
            warnMsg.style.display = 'block';
            return false;
        }
        return checkOverlap(startTime, endTime);
    }
</script>
</body>
</html>