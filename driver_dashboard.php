<?php
session_start();
include 'db_connect.php';

// 1. Security Access Control Layer
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$user_id   = $_SESSION['user_id'];

// Synchronize all application runtime environments cleanly onto Malaysian Time
pg_query($conn, "SET TIME ZONE 'Asia/Kuala_Lumpur'");
date_default_timezone_set('Asia/Kuala_Lumpur');

// --- CANCELLATION & EXPIRATION LOGIC ---
if (isset($_POST['cancel_booking']) && !empty($_POST['booking_id'])) {
    $b_id = intval($_POST['booking_id']);

    $find_p = pg_query_params($conn, "SELECT parking_id FROM tbl_booking WHERE booking_id = $1 AND user_id = $2", array($b_id, $user_id));
    $b_data = pg_fetch_assoc($find_p);

    if ($b_data && !empty($b_data['parking_id'])) {
        $p_id = intval($b_data['parking_id']);

        pg_query_params($conn, "UPDATE tbl_booking SET booking_status = 'Cancelled' WHERE booking_id = $1", array($b_id));
        pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Available', current_plate = NULL WHERE parking_id = $1", array($p_id));
    }

    if (isset($_POST['is_expired_trigger'])) {
        $_SESSION['terminate_msg'] = "Your previous reservation timeframe officially expired and has been reset.";
    } else {
        $_SESSION['terminate_msg'] = "Booking cancelled successfully.";
    }

    header("Location: driver_dashboard.php");
    exit();
}

// --- TERMINATION LOGIC ---
if (isset($_POST['terminate_booking']) && !empty($_POST['booking_id'])) {
    $b_id = intval($_POST['booking_id']);

    $find_p = pg_query_params($conn, "SELECT parking_id FROM tbl_booking WHERE booking_id = $1 AND user_id = $2", array($b_id, $user_id));
    $b_data = pg_fetch_assoc($find_p);

    if ($b_data && !empty($b_data['parking_id'])) {
        $p_id = intval($b_data['parking_id']);

        pg_query_params($conn, "UPDATE tbl_booking SET booking_status = 'Completed', end_time = CURRENT_TIME WHERE booking_id = $1", array($b_id));
        pg_query_params($conn, "UPDATE tbl_parking_listing SET status = 'Available', current_plate = NULL WHERE parking_id = $1", array($p_id));
    }

    $_SESSION['terminate_msg'] = "Parking slot released successfully!";
    header("Location: driver_dashboard.php");
    exit();
}

// 2. FETCH ENGINE: Uses localized relative intervals to sync state dynamically
$booking_query = "SELECT b.booking_id, p.parking_id, p.slot_number, b.start_time, b.end_time, b.booking_date, p.status as slot_status, b.booking_status,
                  TO_CHAR(b.booking_date, 'YYYY-MM-DD') AS clean_date,
                  TO_CHAR(b.start_time, 'HH24:MI:SS') AS clean_start_time,
                  TO_CHAR(b.end_time, 'HH24:MI:SS') AS clean_end_time,
                  
                  -- Computes absolute remaining seconds until checkout deadline
                  EXTRACT(EPOCH FROM (b.end_time::time - LOCALTIME)) AS dynamic_remaining_seconds,
                  
                  -- Tracks early buffer delay pool
                  EXTRACT(EPOCH FROM (b.start_time::time - LOCALTIME)) AS seconds_until_start
                  
                  FROM tbl_booking b
                  JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
                  WHERE b.user_id = $1
                  AND b.booking_status IN ('Confirmed', 'Occupied')
                  ORDER BY b.booking_id DESC LIMIT 1";
$booking_res    = pg_query_params($conn, $booking_query, array($user_id));
$latest_booking = pg_fetch_assoc($booking_res);

$has_active_booking = $latest_booking ? 'true' : 'false';

$time_until_start = 0;
$total_booking_duration = 0;

if ($latest_booking) {
    $seconds_diff = floatval($latest_booking['seconds_until_start']);
    if ($seconds_diff > 0) {
        $time_until_start = intval($seconds_diff);
    }
    
    // Locks timer limits directly onto remaining operational window attributes
    $total_booking_duration = intval($latest_booking['dynamic_remaining_seconds']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Driver Dashboard - Parking Reservation System for The Summit Batu Pahat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 80px; }
        .header { background: linear-gradient(to bottom, #d4bc44, #ffffff); padding: 30px 20px; display: flex; align-items: center; gap: 15px; position: relative; }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        /* FLOATING DRAGGABLE TIMER BUBBLE STYLE */
        .timer-bubble {
            position: fixed !important;
            top: 15px; right: 15px; background: #333; padding: 10px 15px;
            border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.3); text-align: center;
            border: 2px solid #2ecc71; z-index: 9999; min-width: 150px;
            cursor: move; touch-action: none; user-select: none; transition: border-color 0.3s ease;
        }
        .timer-icon-set { display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: bold; font-size: 18px; color: #fff; pointer-events: none; margin-top: 4px; }
        .timer-slot { display: block; font-size: 11px; color: #FFD700; margin-top: 5px; font-weight: bold; letter-spacing: 1px; pointer-events: none; }
        .spent-time { display: block; font-size: 11px; color: #fff; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.85; pointer-events: none; margin-bottom: 2px; }
        
        /* State Indicators */
        .timer-waiting { border-color: #f1c40f !important; } 
        .timer-active { border-color: #2ecc71 !important; }  
        .timer-danger { border-color: #e74c3c !important; background: #5a1212 !important; animation: pulse 0.8s infinite; }
        @keyframes pulse { 0%{transform:scale(1);}50%{transform:scale(1.05);}100%{transform:scale(1);} }

        .alert-msg { background: #e67e22; color: white; text-align: center; padding: 12px; margin: 15px; border-radius: 12px; font-size: 0.9em; font-weight: bold; }
        .search-area { text-align: center; margin: 25px 0; }
        .btn-search { background: #d4bc44; color: black; padding: 15px 30px; text-decoration: none; font-weight: bold; border-radius: 50px; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }

        .map-container { position: relative !important; height: 350px; margin: 15px; border-radius: 20px; border: 2px solid #d4bc44; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .map-container iframe { width: 100%; height: 100%; border: 0; display: block; }

        /* Unified Bottom Navigation View Layout */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%; background: #ffffff; display: flex; 
            justify-content: space-around; padding: 10px 0; border-top: 1px solid #eee; z-index: 1000;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.04);
        }
        .nav-item { text-align: center; text-decoration: none; color: #94a3b8; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #d4bc44; font-weight: bold; }
        .btn-timer { margin-top: 6px; color: white; border: none; border-radius: 5px; width: 100%; cursor: pointer; font-size: 10px; padding: 6px; font-weight: bold; text-transform: uppercase; }

        .driver-qr-container { background: #fff; padding: 5px; border-radius: 6px; margin: 6px 0; display: inline-block; cursor: pointer; transition: transform 0.2s ease; }
        .driver-qr-container:hover { transform: scale(1.05); }
        .driver-qr-img { width: 65px; height: 65px; display: block; margin: 0 auto; }

        /* MODAL OVERLAY */
        .qr-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 999999; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.25s ease; }
        .qr-modal-overlay.show { display: flex; opacity: 1; }
        .qr-modal-card { background: white; padding: 30px 25px 25px 25px; border-radius: 20px; text-align: center; max-width: 320px; width: 85%; box-shadow: 0 15px 40px rgba(0,0,0,0.4); transform: scale(0.8); transition: transform 0.25s ease; position: relative; }
        .qr-modal-overlay.show .qr-modal-card { transform: scale(1); }
        .modal-close-btn { position: absolute; top: 15px; right: 18px; background: none; border: none; color: #aaa; font-size: 1.3rem; cursor: pointer; transition: color 0.2s ease; padding: 0; line-height: 1; }
        .modal-close-btn:hover { color: #e74c3c; }
        .big-qr-img { width: 220px; height: 220px; display: block; margin: 0 auto 15px auto; }

        /* MODERN TOAST POPUP NOTIFICATION STYLES */
        .login-toast-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center;
            z-index: 100000; opacity: 1; transition: opacity 0.4s ease;
        }
        .login-toast-card {
            background: #ffffff; padding: 30px 25px; border-radius: 16px;
            text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 2px solid #d4bc44; max-width: 320px; width: 85%;
            transform: scale(1); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .toast-icon-circle {
            width: 60px; height: 60px; background: #2ecc71; color: white;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin: 0 auto 15px auto; animation: popIcon 0.5s ease forwards;
        }
        .login-toast-card h4 { margin: 0 0 8px 0; color: #333; font-size: 1.3rem; }
        .login-toast-card p { margin: 0; color: #666; font-size: 0.95rem; line-height: 1.4; }
        @keyframes popIcon { 0% { transform: scale(0); } 100% { transform: scale(1); } }

        @media (max-width: 768px) {
            .map-container { border-radius: 12px; }
            .timer-bubble { padding: 10px 12px; min-width: 115px; }
            .timer-icon-set { font-size: 15px; }
            .timer-slot { font-size: 10px; margin-top: 3px; }
            .spent-time { font-size: 9px; margin-top: 2px; }
            .driver-qr-img { width: 55px; height: 55px; }
            .btn-timer { font-size: 9px; padding: 5px; }
            .header { padding: 25px 15px; gap: 8px; }
            .header-text h2 { font-size: 1.1rem; max-width: calc(100% - 135px); line-height: 1.3; }
        }
    </style>
</head>
<body>

    <?php if (isset($_SESSION['login_success'])): ?>
        <div id="loginToast" class="login-toast-overlay">
            <div class="login-toast-card">
                <div class="toast-icon-circle">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h4>Login Successful!</h4>
                <p><?php echo $_SESSION['login_success']; ?></p>
            </div>
        </div>
        <?php unset($_SESSION['login_success']); ?>
    <?php endif; ?>

    <div class="header">
        <img src="logo.png" alt="Summit Logo" class="logo-img">
        <div class="header-text">
            <h2>Hi, <?php echo htmlspecialchars($full_name); ?>!</h2>
            <p style="margin:0; font-size: 0.8em; color: #666;">Parking Reservation System for The Summit Batu Pahat</p>
        </div>

        <div id="draggableTimer" class="timer-bubble">
            <small id="spent-time-text" class="spent-time">Syncing...</small>
            
            <div class="timer-icon-set">
                <i class="fa-solid fa-clock"></i>
                <span id="countdown-text">--:--</span>
            </div>
            <small class="timer-slot" id="slot-display">SLOT: <?php echo $latest_booking ? htmlspecialchars($latest_booking['slot_number']) : '-'; ?></small>

            <?php if ($latest_booking): ?>
                <div class="driver-qr-container" onclick="openQRModal()" title="Tap to expand pass view">
                    <?php
                        $booking_token = intval($latest_booking['booking_id']);
                        $gate_qr_url   = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode("http://localhost/parking_reservation_system/guard_verify.php?booking_id=" . $booking_token);
                    ?>
                    <img src="<?php echo $gate_qr_url; ?>" class="driver-qr-img" alt="Pass Thumb">
                    <span style="font-size: 7px; color: #666; display: block; margin-top: 3px; font-weight: bold;"><i class="fa-solid fa-expand"></i> TAP QR</span>
                </div>
            <?php endif; ?>

            <?php if ($latest_booking): ?>
                <?php if ($latest_booking['slot_status'] !== 'Occupied'): ?>
                    <a href="report_blocked.php?booking_id=<?php echo $latest_booking['booking_id']; ?>"
                       class="btn-timer"
                       style="background: #e67e22; text-decoration: none; display: block; margin-bottom: 5px; padding: 6px; box-sizing: border-box;"
                       onclick="return confirm('Report this slot as blocked? The system will try to give you a new slot.')">
                        ⚠️ Blocked Slot
                    </a>
                <?php endif; ?>

                <form id="autoExpirationForm" method="POST">
                    <input type="hidden" name="booking_id" value="<?php echo intval($latest_booking['booking_id']); ?>">
                    <input type="hidden" name="cancel_booking" value="1">
                    <input type="hidden" name="is_expired_trigger" value="1">

                    <?php if ($latest_booking['slot_status'] === 'Occupied'): ?>
                        <button type="submit" name="terminate_booking" class="btn-timer" style="background:#e74c3c;" onclick="return confirm('End session and release slot?')">End</button>
                    <?php else: ?>
                        <button type="submit" id="manualCancelBtn" name="cancel_booking" class="btn-timer" style="background:#7f8c8d;" onclick="return confirm('Cancel this booking?')">Cancel</button>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <div style="font-size: 8px; color: #aaa; margin-top: 5px; font-weight: bold;">VACANT</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($latest_booking): ?>
    <div class="qr-modal-overlay" id="qrOverlay" onclick="closeQRModal()">
        <div class="qr-modal-card" onclick="event.stopPropagation()">
            <button type="button" class="modal-close-btn" onclick="closeQRModal()" title="Close Pass View">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 style="margin: 0 0 5px 0; color: #333;">Gate Entry Token Pass</h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #666;">Slot Allocation Assignment: <strong><?php echo htmlspecialchars($latest_booking['slot_number']); ?></strong></p>
            <img src="<?php echo $gate_qr_url; ?>" class="big-qr-img" alt="Large Scan Pass">
            <?php if ($latest_booking['slot_status'] === 'Occupied'): ?>
                <span style="font-size: 13px; color: #2ecc71; font-weight: bold; display: block;"><i class="fa-solid fa-circle-check"></i> SCAN VERIFIED & LOCKED</span>
            <?php else: ?>
                <span style="font-size: 13px; color: #e67e22; font-weight: bold; display: block;"><i class="fa-solid fa-qrcode"></i> PRESENT TO SECURITY SCANNER</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['terminate_msg'])): ?>
        <div class="alert-msg"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $_SESSION['terminate_msg']; unset($_SESSION['terminate_msg']); ?></div>
    <?php endif; ?>

    <div class="search-area">
        <a href="available_parking.php?branch=Batu+Pahat&zone=All" class="btn-search">🔍 Search Available Slots</a>
    </div>

    <div class="map-container">
        <iframe loading="lazy" allowfullscreen src="https://maps.google.com/maps?q=The%20Summit%20Batu%20Pahat&t=&z=15&ie=UTF8&iwloc=&output=embed"></iframe>
    </div>

    <nav class="bottom-nav">
        <a href="driver_dashboard.php" class="nav-item active"><i class="fa-solid fa-house"></i> Home</a>
        <a href="booking_history.php" class="nav-item"><i class="fa-solid fa-rectangle-list"></i> History</a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </nav>

<script>
function openQRModal() { const overlay = document.getElementById('qrOverlay'); if (overlay) { overlay.classList.add('show'); } }
document.addEventListener("DOMContentLoaded", function() { const closeBtn = document.querySelector(".modal-close-btn"); if (closeBtn) { closeBtn.addEventListener("click", function(e) { e.stopPropagation(); closeQRModal(); }); } });
function closeQRModal() { const overlay = document.getElementById('qrOverlay'); if (overlay) { overlay.classList.remove('show'); } }

(function() {
    const el = document.getElementById("draggableTimer"); if (!el) return;
    let isDragging = false; let startX, startY, initialLeft, initialTop;
    el.addEventListener("mousedown", dragStart); document.addEventListener("mousemove", dragMove); document.addEventListener("mouseup", dragEnd);
    el.addEventListener("touchstart", dragStart); document.addEventListener("touchmove", dragMove, { passive: false }); document.addEventListener("touchend", dragEnd);
    function dragStart(e) { if (e.target.tagName === 'BUTTON' || e.target.tagName === 'A' || e.target.closest('.driver-qr-container')) return; isDragging = true; const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX; const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY; startX = clientX; startY = clientY; const rect = el.getBoundingClientRect(); initialLeft = rect.left; initialTop = rect.top; }
    function dragMove(e) { if (!isDragging) return; if (e.cancelable) e.preventDefault(); const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX; const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY; let newLeft = initialLeft + (clientX - startX); let newTop = initialTop + (clientY - startY); const maxLeft = window.innerWidth - el.offsetWidth; const maxTop = window.innerHeight - el.offsetHeight; if (newLeft < 0) newLeft = 0; if (newLeft > maxLeft) newLeft = maxLeft; if (newTop < 0) newTop = 0; if (newTop > maxTop) newTop = maxTop; el.style.left = newLeft + "px"; el.style.top = newTop + "px"; el.style.right = "auto"; }
    function dragEnd() { isDragging = false; }
})();

window.addEventListener('pageshow', function(e) { if (e.persisted) { window.location.reload(); } });

// Auto dismiss login success overlay toast loop
document.addEventListener("DOMContentLoaded", function() {
    const toastOverlay = document.getElementById("loginToast");
    if (toastOverlay) {
        setTimeout(function() {
            toastOverlay.style.opacity = "0";
            setTimeout(function() { toastOverlay.remove(); }, 400);
        }, 3000);
    }
});

document.addEventListener("DOMContentLoaded", function () {
    const countdownDisplay = document.getElementById("countdown-text");
    const statusDisplay    = document.getElementById("spent-time-text");
    const bubbleElement    = document.getElementById("draggableTimer");
    const hasActiveBooking = <?php echo $has_active_booking; ?>;

    if (!hasActiveBooking) {
        if (countdownDisplay) countdownDisplay.innerText = "--:--";
        if (statusDisplay)    statusDisplay.innerText     = "VACANT";
        if (bubbleElement)    bubbleElement.style.borderColor = "#7f8c8d";
        return;
    }

    sessionStorage.clear();

    let timeUntilStart   = <?php echo (int)$time_until_start; ?>;
    let initialDuration  = <?php echo (int)$total_booking_duration; ?>;
    let durationSeconds  = (timeUntilStart > 0) ? (initialDuration - timeUntilStart) : initialDuration;
    let trackingSeconds  = 0; 

    function formatTime(totalSeconds) {
        const totalMinutes = Math.floor(totalSeconds / 60);
        const hours   = Math.floor(totalMinutes / 60);
        const minutes = totalMinutes % 60;
        const seconds = totalSeconds % 60;

        if (hours > 0) {
            return String(hours).padStart(2, '0') + ":" + String(minutes).padStart(2, '0') + ":" + String(seconds).padStart(2, '0');
        }
        return String(minutes).padStart(2, '0') + ":" + String(seconds).padStart(2, '0');
    }

    function evaluateSystemState() {
        if (timeUntilStart > 0) {
            bubbleElement.className = "timer-bubble timer-waiting";
            if (statusDisplay) statusDisplay.innerText = "Starts In";
            if (countdownDisplay) countdownDisplay.innerText = formatTime(timeUntilStart);
        } else {
            bubbleElement.className = "timer-bubble timer-active";
            if (statusDisplay) statusDisplay.innerText = "Time Remaining";
            
            let currentRemaining = durationSeconds - trackingSeconds;
            
            if (currentRemaining > 0) {
                if (countdownDisplay) countdownDisplay.innerText = formatTime(currentRemaining);
            } else {
                if (countdownDisplay) countdownDisplay.innerText = "EXPIRED";
                bubbleElement.className = "timer-bubble timer-danger";
                clearInterval(DashboardEngine);
                
                const autoForm = document.getElementById("autoExpirationForm");
                if (autoForm) autoForm.submit();
            }
        }
    }

    evaluateSystemState();

    const DashboardEngine = setInterval(function () {
        if (timeUntilStart > 0) {
            timeUntilStart--;
        } else {
            trackingSeconds++;
        }
        evaluateSystemState();
    }, 1000);
});
</script>
</body>
</html>