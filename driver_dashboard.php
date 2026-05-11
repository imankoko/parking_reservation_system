<?php
session_start();
include 'db_connect.php';
 
// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}
 
$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];
 
// --- CANCELLATION LOGIC ---
if (isset($_POST['cancel_booking'])) {
    $b_id = $_POST['booking_id'];
    $find_p = pg_query_params($conn, "SELECT parking_id FROM tbl_booking WHERE booking_id = $1 AND user_id = $2", array($b_id, $user_id));
    $b_data = pg_fetch_assoc($find_p);
    if ($b_data) {
        $p_id = $b_data['parking_id'];
        pg_query_params($conn, "UPDATE tbl_booking SET booking_status = 'Cancelled' WHERE booking_id = $1 AND user_id = $2", array($b_id, $user_id));
        // FIX: Release the slot back to Available when cancelled
        pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Available', current_plate = NULL WHERE parking_id = $1", array($p_id));
    }
    $_SESSION['terminate_msg'] = "Booking cancelled successfully.";
    header("Location: driver_dashboard.php");
    exit();
}
 
// --- TERMINATION LOGIC (End Session) ---
if (isset($_POST['terminate_booking'])) {
    $b_id = $_POST['booking_id'];
    $find_p = pg_query_params($conn, "SELECT parking_id FROM tbl_booking WHERE booking_id = $1 AND user_id = $2", array($b_id, $user_id));
    $b_data = pg_fetch_assoc($find_p);
    if ($b_data) {
        $p_id = $b_data['parking_id'];
        pg_query_params($conn, "UPDATE tbl_booking SET booking_status = 'Completed', end_time = CURRENT_TIME WHERE booking_id = $1", array($b_id));
        pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Available', current_plate = NULL WHERE parking_id = $1", array($p_id));
        $_SESSION['terminate_msg'] = "Parking slot released successfully!";
        header("Location: driver_dashboard.php");
        exit();
    }
}
 
// 2. Fetch Active Booking (with valid times for the timer)
// FIX: Added IS NOT NULL guards so JS only runs when both times exist in the DB
$timer_query = "SELECT b.booking_id, p.slot_number, b.start_time, b.end_time, p.status as slot_status 
                FROM tbl_booking b
                JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
                WHERE b.user_id = $1 
                AND b.booking_status = 'Confirmed'
                AND b.booking_date = CURRENT_DATE
                AND b.start_time IS NOT NULL
                AND b.end_time IS NOT NULL
                ORDER BY b.booking_id DESC LIMIT 1";
$timer_res = pg_query_params($conn, $timer_query, array($user_id));
$active_booking = pg_fetch_assoc($timer_res);
 
// 3. Fallback: fetch any Confirmed booking even without times (so Cancel button still shows)
// This handles cases where end_time was not saved properly during booking
$fallback_query = "SELECT b.booking_id, p.slot_number, p.status as slot_status
                   FROM tbl_booking b
                   JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
                   WHERE b.user_id = $1
                   AND b.booking_status = 'Confirmed'
                   AND b.booking_date = CURRENT_DATE
                   ORDER BY b.booking_id DESC LIMIT 1";
$fallback_res = pg_query_params($conn, $fallback_query, array($user_id));
$fallback_booking = pg_fetch_assoc($fallback_res);
 
// Safely extract time strings for JavaScript
$end_time_js   = ($active_booking && !empty($active_booking['end_time']))   ? trim($active_booking['end_time'])   : "";
$start_time_js = ($active_booking && !empty($active_booking['start_time'])) ? trim($active_booking['start_time']) : "";
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Driver Dashboard - The Summit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 80px; }
        .header { background: linear-gradient(to bottom, #d4bc44, #ffffff); padding: 30px 20px; display: flex; align-items: center; gap: 15px; position: relative; }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }
 
        .timer-bubble {
            position: absolute; top: 15px; right: 15px; background: #333; padding: 10px 15px;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); text-align: center;
            border: 2px solid #2ecc71; z-index: 10; min-width: 115px;
        }
        .timer-icon-set { display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: bold; font-size: 15px; color: #fff; }
        .timer-slot { display: block; font-size: 10px; color: #FFD700; margin-top: 2px; font-weight: bold; letter-spacing: 1px; }
        .spent-time { display: block; font-size: 9px; color: #999; margin-top: 3px; }
 
        .timer-warning { border-color: #f39c12; }
        .timer-danger { border-color: #e74c3c; background: #5a1212; animation: pulse 0.8s infinite; }
        @keyframes pulse { 0%{transform:scale(1);}50%{transform:scale(1.05);}100%{transform:scale(1);} }
 
        /* Pending bubble when booking exists but times missing */
        .pending-bubble {
            position: absolute; top: 15px; right: 15px; background: #333; padding: 10px 15px;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); text-align: center;
            border: 2px solid #7f8c8d; z-index: 10; min-width: 115px;
        }
        .pending-label { display: block; font-size: 10px; color: #aaa; margin-bottom: 4px; letter-spacing: 1px; font-weight: bold; }
        .pending-icon  { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: #ccc; margin-bottom: 4px; }
 
        .alert-msg { background: #2ecc71; color: white; text-align: center; padding: 10px; margin: 15px; border-radius: 12px; font-size: 0.9em; }
        .search-area { text-align: center; margin: 25px 0; }
        .btn-search { background: #d4bc44; color: black; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 50px; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .map-container { height: 350px; margin: 15px; border-radius: 20px; border: 2px solid #d4bc44; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .bottom-nav { position: fixed; bottom: 0; width: 100%; background: #fff; display: flex; justify-content: space-around; padding: 10px 0; border-top: 1px solid #eee; z-index: 1000; }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }
        .btn-timer { margin-top: 8px; color: white; border: none; border-radius: 5px; width: 100%; cursor: pointer; font-size: 9px; padding: 6px; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>
 
    <div class="header">
        <img src="logo.png" alt="Summit Logo" class="logo-img">
        <div class="header-text">
            <h2>Hi, <?php echo htmlspecialchars($full_name); ?>!</h2>
            <p style="margin:0; font-size: 0.8em; color: #666;">Parking Reservation System</p>
        </div>
 
        <?php if ($active_booking): ?>
            <!-- Full timer bubble: both start_time and end_time exist in DB -->
            <div id="timer-status" class="timer-bubble">
                <div class="timer-icon-set">
                    <i class="fa-solid fa-clock"></i>
                    <span id="countdown-text">--:--</span>
                </div>
                <small class="timer-slot">SLOT: <?php echo htmlspecialchars($active_booking['slot_number']); ?></small>
                <small id="spent-time-text" class="spent-time">Spent: 0m</small>
                <form method="POST">
                    <input type="hidden" name="booking_id" value="<?php echo $active_booking['booking_id']; ?>">
                    <?php
                    // FIX: payment_success.php sets status = 'Reserved' (not 'Occupied')
                    // So the Cancel button must show for 'Reserved' AND 'Booked' states.
                    // Only show 'End Session' when slot is physically 'Occupied'.
                    if ($active_booking['slot_status'] === 'Occupied'): ?>
                        <button type="submit" name="terminate_booking" class="btn-timer" style="background:#e74c3c;" onclick="return confirm('End session and release slot?')">End Session</button>
                    <?php else: ?>
                        <button type="submit" name="cancel_booking" class="btn-timer" style="background:#7f8c8d;" onclick="return confirm('Cancel this booking?')">Cancel</button>
                    <?php endif; ?>
                </form>
            </div>
 
        <?php elseif ($fallback_booking): ?>
            <!-- Pending bubble: booking confirmed but no times saved in DB yet -->
            <div class="pending-bubble">
                <small class="pending-label">SLOT: <?php echo htmlspecialchars($fallback_booking['slot_number']); ?></small>
                <div class="pending-icon">
                    <i class="fa-solid fa-hourglass-start"></i>
                    <span>Pending</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="booking_id" value="<?php echo $fallback_booking['booking_id']; ?>">
                    <?php if ($fallback_booking['slot_status'] === 'Occupied'): ?>
                        <button type="submit" name="terminate_booking" class="btn-timer" style="background:#e74c3c;" onclick="return confirm('End session and release slot?')">End Session</button>
                    <?php else: ?>
                        <button type="submit" name="cancel_booking" class="btn-timer" style="background:#7f8c8d;" onclick="return confirm('Cancel this booking?')">Cancel</button>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>
    </div>
 
    <?php if (isset($_SESSION['terminate_msg'])): ?>
        <div class="alert-msg"><i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['terminate_msg']; unset($_SESSION['terminate_msg']); ?></div>
    <?php endif; ?>
 
    <div class="search-area">
        <a href="find_branch.php" class="btn-search">🔍 Search Available Slots</a>
    </div>
 
    <div class="map-container">
        <iframe width="100%" height="100%" style="border:0;" loading="lazy" allowfullscreen
            src="https://maps.google.com/maps?q=The%20Summit%20Batu%20Pahat&t=&z=15&ie=UTF8&iwloc=&output=embed">
        </iframe>
    </div>
 
    <nav class="bottom-nav">
        <a href="driver_dashboard.php" class="nav-item active"><i class="fa-solid fa-house"></i> Home</a>
        <a href="booking_history.php" class="nav-item"><i class="fa-solid fa-rectangle-list"></i> History</a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>
 
<script>
    const endTimeStr   = "<?php echo $end_time_js; ?>";
    const startTimeStr = "<?php echo $start_time_js; ?>";
 
    if (endTimeStr !== '' && startTimeStr !== '') {
        const countdownText    = document.getElementById('countdown-text');
        const spentTextDisplay = document.getElementById('spent-time-text');
 
        function parseTime(timeStr) {
            if (!timeStr || timeStr.trim() === '') return null;
            const now   = new Date();
            const parts = timeStr.split(':');
            if (parts.length < 2) return null;
            return new Date(
                now.getFullYear(), now.getMonth(), now.getDate(),
                parseInt(parts[0], 10),
                parseInt(parts[1], 10),
                parseInt(parts[2] || 0, 10)
            );
        }
 
        function runLiveTimer() {
            const now       = new Date();
            const startDate = parseTime(startTimeStr);
            const endDate   = parseTime(endTimeStr);
 
            if (!startDate || !endDate || isNaN(startDate) || isNaN(endDate)) {
                if (countdownText) countdownText.innerHTML = '--:--';
                return;
            }
 
            // Spent time (counts up)
            const elapsedMin = Math.max(0, Math.floor((now - startDate) / 60000));
            if (spentTextDisplay) spentTextDisplay.innerHTML = "Spent: " + elapsedMin + "m";
 
            // Remaining time (counts down)
            const remainingMs = endDate - now;
            const timerBubble = document.getElementById('timer-status');
 
            if (remainingMs <= 0) {
                if (countdownText) countdownText.innerHTML = "EXPIRED";
                if (timerBubble)   timerBubble.className  = 'timer-bubble timer-danger';
            } else {
                const mins = Math.floor(remainingMs / 60000);
                const secs = Math.floor((remainingMs % 60000) / 1000);
                if (countdownText) {
                    countdownText.innerHTML = mins.toString().padStart(2,'0') + ':' + secs.toString().padStart(2,'0');
                }
                if (timerBubble) {
                    if (remainingMs < 300000)       timerBubble.className = 'timer-bubble timer-danger';   // < 5 min
                    else if (remainingMs < 900000)  timerBubble.className = 'timer-bubble timer-warning';  // < 15 min
                    else                            timerBubble.className = 'timer-bubble';                // normal
                }
            }
        }
 
        setInterval(runLiveTimer, 1000);
        runLiveTimer();
    }
</script>
</body>
</html>
 