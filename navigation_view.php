<?php
session_start();
include 'db_connect.php';

// 1. Security Check: Ensure only logged-in Drivers access this
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}

// 2. Fix the Warning: Check if booking_id exists in the URL
if (!isset($_GET['booking_id'])) {
    header("Location: driver_dashboard.php");
    exit();
}

$booking_id = $_GET['booking_id'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Navigation - The Summit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css">
    <style>
        /* Container for the Google Map to maintain border radius */
        .map-wrapper {
            height: 300px; 
            margin: 10px; 
            border-radius: 10px; 
            border: 2px solid #FFD700;
            overflow: hidden; /* Important for rounded corners */
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .support-card {
            background: #fff5f5;
            border: 1px solid #ffcccc;
            border-radius: 15px;
            padding: 15px;
            margin: 20px 10px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .btn-call {
            background: #27ae60;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 50px;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="nav-container" style="display: flex; justify-content: space-between; padding: 15px; background: #fff; border-bottom: 2px solid #FFD700; align-items: center;">
        <a href="driver_dashboard.php" style="background: #666; color: white; padding: 10px 20px; text-decoration: none; font-weight: bold; font-size: 13px; text-transform: uppercase;">← Home</a>
        <a href="logout.php" style="background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; font-weight: bold; font-size: 13px; text-transform: uppercase;">Logout</a>
    </div>

    <div class="header" style="text-align: center; padding: 20px;">
        <h2 style="margin:0;">Navigating to Slot</h2>
        <p>Follow the map to reach The Summit Batu Pahat</p>
    </div>

    <div class="map-container">
    <iframe 
        width="100%" 
        height="100%" 
        style="border:0;" 
        loading="lazy" 
        allowfullscreen 
        referrerpolicy="no-referrer-when-downgrade" 
        src="https://maps.google.com/maps?q=The%20Summit%20Batu%20Pahat&t=&z=15&ie=UTF8&iwloc=&output=embed">
    </iframe>
</div>

    <div class="support-card">
        <h3 style="margin:0; color: #cc0000; font-size: 1.1rem;">Problem with your spot?</h3>
        <p style="font-size: 0.85rem; color: #555; margin: 5px 0;">If someone unknown is already parked in your spot, contact management immediately.</p>
        <a href="tel:074326222" class="btn-call">
            <i class="fa-solid fa-phone"></i> Call Management
        </a>
        <p style="margin-top: 8px; font-weight: bold; font-size: 0.8rem;">07-432 6222</p>
    </div>

    <div style="padding: 20px; text-align: center;">
        <h3>Arrived at your spot?</h3>
        
        <a href="submit_review.php?booking_id=<?php echo htmlspecialchars($booking_id); ?>" class="btn-review" style="display:block; background:#FFD700; padding:15px; margin-bottom:10px; text-decoration:none; color:black; font-weight:bold; border-radius:5px;">
            ⭐ Submit Review
        </a>
        
        <a href="driver_dashboard.php" style="display:block; background:#333; color:white; padding:15px; text-decoration:none; border-radius:5px; font-weight: bold;">
            Back to Dashboard
        </a>
    </div>

    </body>
</html>