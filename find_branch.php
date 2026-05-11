<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login_view.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>MySpot - Finding Nearest Branch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; width: 85%; max-width: 400px; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #d4bc44; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="card">
        <img src="logo.png" style="width: 80px;" alt="Logo">
        <h2>Hi, <?php echo $_SESSION['full_name']; ?></h2>
        <div id="loading-zone">
            <div class="loader"></div>
            <p>Locating the nearest The Summit branch...</p>
        </div>
        <div id="manual-zone" style="display:none;">
            <p>GPS failed. Please select branch:</p>
            <a href="available_parking.php?branch=Batu Pahat" style="display:block; padding:10px; background:#d4bc44; color:black; text-decoration:none; margin:5px; border-radius:10px;">The Summit Batu Pahat</a>
            <a href="available_parking.php?branch=Kluang" style="display:block; padding:10px; background:#eee; color:black; text-decoration:none; margin:5px; border-radius:10px;">The Summit Kluang</a>
        </div>
    </div>

    <script>
        const branches = [
            { name: 'Batu Pahat', lat: 1.8540, lon: 102.9320 },
            { name: 'Kluang', lat: 2.0300, lon: 103.3200 }
        ];

        function getDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // km
            const dLat = (lat2-lat1) * Math.PI / 180;
            const dLon = (lon2-lon1) * Math.PI / 180;
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon/2) * Math.sin(dLon/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        }

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                let nearest = branches[0];
                let minDistance = Infinity;
                branches.forEach(b => {
                    let d = getDistance(position.coords.latitude, position.coords.longitude, b.lat, b.lon);
                    if (d < minDistance) { minDistance = d; nearest = b; }
                });
                window.location.href = `available_parking.php?branch=${nearest.name}`;
            }, () => {
                document.getElementById('loading-zone').style.display = 'none';
                document.getElementById('manual-zone').style.display = 'block';
            });
        }
    </script>
</body>
</html>