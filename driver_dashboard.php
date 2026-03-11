<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

$full_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];

// --- FETCH ACTIVE BOOKING DATA ---
// We join with tbl_parking_listing to get the Slot Number (e.g., A01)
$timer_query = "SELECT b.booking_id, p.slot_number, b.end_time 
                FROM tbl_booking b
                JOIN tbl_parking_listing p ON b.parking_id = p.parking_id
                WHERE b.user_id = $1 AND b.booking_status = 'Confirmed' 
                AND b.booking_date = CURRENT_DATE
                ORDER BY b.booking_id DESC LIMIT 1";
$timer_res = pg_query_params($conn, $timer_query, array($user_id));
$active_booking = pg_fetch_assoc($timer_res);
$end_time_js = $active_booking ? $active_booking['end_time'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Driver Dashboard - The Summit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #ffffff; margin: 0; padding-bottom: 80px; }
        
        /* Header Section */
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative; 
        }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        /* Notification Timer Bubble (Top Right) */
        .timer-bubble {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #333; /* Dark theme for high visibility */
            padding: 8px 15px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            text-align: center;
            border: 2px solid #2ecc71; /* Green border by default */
            transition: all 0.3s ease;
            z-index: 10;
        }
        .timer-icon-set { display: flex; align-items: center; gap: 8px; font-weight: bold; font-size: 15px; color: #fff; }
        .timer-slot { 
            display: block; 
            font-size: 10px; 
            color: #FFD700; /* Summit Yellow for Slot Number */
            margin-top: 2px; 
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        /* Warning & Danger Animations */
        .timer-warning { border-color: #f39c12; }
        .timer-danger { border-color: #e74c3c; background: #5a1212; animation: pulse 0.8s infinite; }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .search-area { text-align: center; margin: 25px 0; }
        .btn-search {
            background: #d4bc44; color: black; padding: 15px 30px;
            text-decoration: none; font-weight: bold; border-radius: 50px;
            display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .map-container {
            height: 350px; margin: 15px; border-radius: 20px; 
            border: 2px solid #d4bc44; overflow: hidden; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .bottom-nav {
            position: fixed; bottom: 0; width: 100%; background: #fff;
            display: flex; justify-content: space-around; padding: 10px 0;
            border-top: 1px solid #eee; box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
        }
        .nav-item { text-align: center; text-decoration: none; color: #888; font-size: 0.8rem; flex: 1; }
        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }
    </style>
</head>
<body>

    <div class="header">
        <img src="logo.png" alt="Summit Logo" class="logo-img">
        <div class="header-text">
            <h2>Hi, <?php echo $full_name; ?>!</h2>
            <p style="margin:0; font-size: 0.8em; color: #666;">Parking Reservation System</p>
        </div>

        <?php if ($active_booking): ?>
            <div id="timer-status" class="timer-bubble">
                <div class="timer-icon-set">
                    <i class="fa-solid fa-clock"></i>
                    <span id="countdown-text">--:--</span>
                </div>
                <small class="timer-slot">SLOT: <?php echo htmlspecialchars($active_booking['slot_number']); ?></small>
            </div>
        <?php endif; ?>
    </div>

    <div class="search-area">
        <a href="available_parking.php" class="btn-search">
            🔍 Search Available Slots
        </a>
    </div>

    <div class="map-container">
        <iframe 
            width="100%" height="100%" style="border:0;" loading="lazy" 
            allowfullscreen referrerpolicy="no-referrer-when-downgrade" 
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.353342371987!2d102.93043237583625!3d1.8430635981397223!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31d059abd28766a5%3A0x66f6f94747764082!2sThe%20Summit%20Batu%20Pahat!5e0!3m2!1sen!2smy!4v1710145200000!5m2!1sen!2smy">
        </iframe>
    </div>

    <nav class="bottom-nav">
        <a href="driver_dashboard.php" class="nav-item active">
            <i class="fa-solid fa-house"></i> Home
        </a>
        <a href="booking_history.php" class="nav-item">
            <i class="fa-solid fa-rectangle-list"></i> History
        </a>
        <a href="logout.php" class="nav-item" style="color:#dc3545;" onclick="return confirm('Logout?')">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

    <script>
    const endTimeStr = "<?php echo $end_time_js; ?>";
    
    if (endTimeStr) {
        const timerBubble = document.getElementById('timer-status');
        const countdownText = document.getElementById('countdown-text');

        function updateTimer() {
            const now = new Date();
            
            // Set target time based on today's date + end_time from DB
            const [hours, minutes, seconds] = endTimeStr.split(':');
            const endDate = new Date();
            endDate.setHours(parseInt(hours), parseInt(minutes), parseInt(seconds || 0));

            const diff = endDate - now;

            if (diff <= 0) {
                countdownText.innerHTML = "EXIT NOW";
                timerBubble.className = 'timer-bubble timer-danger';
                return;
            }

            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);

            // COLOR LOGIC: 15 mins (Warning), 5 mins (Danger)
            if (diff < 300000) { // < 5 mins
                timerBubble.className = 'timer-bubble timer-danger';
            } else if (diff < 900000) { // < 15 mins
                timerBubble.className = 'timer-bubble timer-warning';
            }

            countdownText.innerHTML = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }

        setInterval(updateTimer, 1000);
        updateTimer();
    }
    </script>
</body>
</html>