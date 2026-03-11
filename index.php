<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySpot - The Summit Batu Pahat</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#FFD700">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    
    <style>
        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #d4bc44; /* Matches the golden-yellow background in your photo */
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .splash-container {
            text-align: center;
            width: 100%;
            max-width: 400px;
            padding: 20px;
            animation: fadeIn 1.5s ease-in-out;
        }

        .logo-box {
            margin-bottom: 20px;
        }

        .logo-img {
            width: 120px;
            height: auto;
        }

        .brand-name {
            color: #b02a37; /* Deep red color from your photo */
            font-size: 3.5rem;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 0;
            text-transform: uppercase;
        }

        .location-name {
            color: #4a5d4e; /* Muted green/gray from your photo */
            font-size: 1.5rem;
            letter-spacing: 4px;
            margin-top: -10px;
            font-weight: 500;
        }

        .system-title {
            margin-top: 60px;
            font-size: 1.8rem;
            color: #000;
            font-weight: 500;
            line-height: 1.2;
        }

        .start-btn {
            margin-top: 50px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 80px;
            height: 80px;
            background-color: #000;
            color: #FFD700;
            border-radius: 50%;
            text-decoration: none;
            font-size: 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            transition: transform 0.2s, background-color 0.2s;
        }

        .start-btn:hover {
            transform: scale(1.1);
            background-color: #222;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Desktop Optimization */
        @media (min-width: 768px) {
            .brand-name { font-size: 5rem; }
            .location-name { font-size: 2rem; }
            .splash-container { max-width: 600px; }
        }
    </style>
</head>
<body>

    <div class="splash-container">
        <div class="logo-box">
            <img src="logo.png" alt="Summit Logo" class="logo-img">
        </div>

        <div class="system-title">
            Parking Reservation<br>System
        </div>

        <a href="login.php" class="start-btn">
            ➔
        </a>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>