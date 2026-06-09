<?php
session_start();
include 'db_connect.php';

// Role and Parameter Validation
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    header("Location: driver_dashboard.php");
    exit();
}

$booking_id = $_GET['booking_id'];
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Driver';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Navigation - Parking Reservation System for The Summit Batu Pahat</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Modern App Design Foundation */
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f8f9fa; 
            margin: 0; 
            padding-bottom: 90px; /* Leaves room for modern bottom navigation view bar */
        }
        
        /* Unified Header Accent */
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px; 
            text-align: left;
        }
        .header h2 { margin: 0; font-size: 1.4rem; color: #000000; }
        .header p { margin: 5px 0 0 0; font-size: 0.85rem; color: #475569; }

        /* Map Canvas Layout Wrapper */
        .map-wrapper {
            padding: 15px;
            text-align: center;
        }
        .map-container { 
            width: 100%;
            max-width: 600px;
            height: 320px; 
            margin: 0 auto;
            border: 4px solid #d4bc44; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        /* Action Card Grid Modules */
        .container { padding: 20px; max-width: 500px; margin: auto; }
        .card { 
            background: #ffffff; 
            border-radius: 16px; 
            padding: 20px; 
            text-align: center; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            border: 1px solid #e2e8f0;
        }
        
        .btn-confirm { 
            background: #2ecc71; 
            color: #ffffff;
            font-weight: bold;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none; 
            display: inline-block; 
            margin-top: 15px;
            box-shadow: 0 4px 10px rgba(46, 204, 113, 0.2);
            transition: 0.2s ease;
        }
        .btn-confirm:active { transform: scale(0.95); }

        .btn-submit {
            background: #333333;
            color: #ffffff;
            font-weight: bold;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: 0.2s ease;
        }
        .btn-submit:active { transform: scale(0.95); }

        /* Modern Polished Bottom Navigation View Bar */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: #ffffff;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 10px 0 calc(10px + env(safe-area-inset-bottom));
            border-top: 1px solid #eef0f2;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.04);
            z-index: 1000;
        }

        .nav-item {
            text-align: center;
            text-decoration: none;
            color: #94a3b8; 
            font-size: 0.75rem;
            font-weight: 500;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px; 
            transition: color 0.2s ease, transform 0.1s ease;
        }

        .nav-item i {
            font-size: 1.35rem; 
            transition: transform 0.2s ease;
        }

        .nav-item.active {
            color: #d4bc44; 
            font-weight: 700;
        }

        .nav-item.active i {
            transform: translateY(-2px); 
        }

        .nav-item:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Navigating to Slot</h2>
        <p>Follow the routing guidelines to reach your destination</p>
    </div>

    <div class="map-wrapper">
        <div class="map-container">
            <iframe 
                width="100%" height="100%" style="border:0;" loading="lazy" 
                src="https://maps.google.com/maps?q=The%20Summit%20Batu%20Pahat&t=&z=15&ie=UTF8&iwloc=&output=embed">
            </iframe>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <h3 style="color: #e74c3c; margin-top: 0;">Problem with your spot?</h3>
            <p style="color: #64748b; font-size: 0.9rem;">If another vehicle is blocking your reserved space, please contact mall operations immediately.</p>
            <a href="tel:074326222" class="btn-confirm">
                <i class="fa-solid fa-phone"></i> Call Management
            </a>
            <p style="margin-top: 12px; font-weight: bold; color: #334155;">07-432 6222</p>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="submit_review.php?booking_id=<?php echo urlencode($booking_id); ?>" class="btn-submit">
                ⭐ Proceed to Submit Review
            </a>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="driver_dashboard.php" class="nav-item">
            <i class="fa-solid fa-house"></i> Home
        </a>
        <a href="booking_history.php" class="nav-item">
            <i class="fa-solid fa-rectangle-list"></i> History
        </a>
        <a href="logout.php" class="nav-item" style="color: #ef4444;">
            <i class="fa-solid fa-right-from-bracket"></i> Logout
        </a>
    </nav>

</body>
</html>