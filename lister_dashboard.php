<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Lister') {
    header("Location: login_view.php");
    exit();
}

$lister_id = $_SESSION['user_id'];
$full_name = $_SESSION['full_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Lister Dashboard - The Summit</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#FFD700">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #ffffff; 
            margin: 0; 
            padding-bottom: 80px; /* Space for bottom nav */
        }

        /* Gradient Header matching the reference */
        .header {
            background: linear-gradient(to bottom, #d4bc44, #ffffff);
            padding: 30px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header-content { display: flex; align-items: center; gap: 15px; }
        .logo-img { width: 60px; height: auto; }
        .header-text h2 { margin: 0; font-size: 1.4rem; color: #000; }

        .main-content { padding: 20px; }
        .welcome-text { margin-bottom: 30px; }
        .welcome-text h1 { font-size: 1.5rem; margin: 0; }
        .welcome-text p { color: #666; margin: 5px 0 0 0; }

        /* Large Selection Cards */
        .menu-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .menu-card {
            background: #d4bc44;
            border-radius: 25px;
            padding: 30px 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #000;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }

        .menu-card:active { transform: scale(0.98); }

        .icon-box {
            background: transparent;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-right: 20px;
        }

        .card-text { font-size: 1.3rem; font-weight: bold; }

        /* Bottom Navigation Bar */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: #fff;
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            border-top: 1px solid #eee;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .nav-item {
            text-align: center;
            text-decoration: none;
            color: #888;
            font-size: 0.8rem;
            flex: 1;
        }

        .nav-item i { font-size: 1.5rem; display: block; margin-bottom: 2px; }
        .nav-item.active { color: #000; }
        .logout-btn { color: #dc3545; }

    </style>
</head>
<body>

    <div class="header">
        <div class="header-content">
            <img src="logo.png" alt="Summit Logo" class="logo-img">
            <div class="header-text">
                <h2>Parking Listing<br>Management</h2>
            </div>
        </div>
    </div>

    <div class="main-content">
        <div class="welcome-text">
            <h1>Hi, <?php echo $full_name; ?></h1>
            <p>Manage your parking assets below</p>
        </div>

        <div class="menu-grid">
            <a href="lister_listing_view.php" class="menu-card">
                <div class="icon-box"><i class="fa-solid fa-car"></i></div>
                <div class="card-text">My Parking Listing</div>
            </a>

            <a href="add_parking_view.php" class="menu-card">
                <div class="icon-box"><i class="fa-solid fa-circle-plus"></i></div>
                <div class="card-text">Add Parking Slot</div>
            </a>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="lister_dashboard.php" class="nav-item active">
            <i class="fa-solid fa-house"></i>
            Home
        </a>
        <a href="lister_listing_view.php" class="nav-item">
            <i class="fa-solid fa-list-check"></i>
            Listings
        </a>
        <a href="add_parking_view.php" class="nav-item">
            <i class="fa-solid fa-plus-square"></i>
            Add
        </a>
        <a href="logout.php" class="nav-item logout-btn" onclick="return confirm('Logout?')">
            <i class="fa-solid fa-right-from-bracket"></i>
            Logout
        </a>
    </nav>

</body>
</html>